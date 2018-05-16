<?php
namespace KwcNewsletter\Bundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use KwcNewsletter\Bundle\Model\Newsletters;
use KwcNewsletter\Bundle\Model\Row\Newsletters as NewsletterRow;
use KwcNewsletter\Bundle\Model\NewsletterRuns;

class StartRunner extends Command
{
    /**
     * @var Newsletters
     */
    private $newslettersModel;
    /**
     * @var NewsletterRuns
     */
    private $newsletterRunsModel;

    public function __construct(Newsletters $newsletterModel, NewsletterRuns $newsletterRunsModel)
    {
        $this->newslettersModel = $newsletterModel;
        $this->newsletterRunsModel = $newsletterRunsModel;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('kwc_newsletter:start_runner')
            ->setDescription('execute newsletter commands, should be run by process-control');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $procs = array();

        while (true) {
            $this->updateStoppedProcesses($procs, $output);

            foreach ($this->getStartedNewsletterRows() as $newsletterRow) {
                if ($newsletterRow->status != 'sending') {
                    $newsletterRow->resume_date = date('Y-m-d H:i:s');
                    $newsletterRow->status = 'sending';
                    if (is_null($newsletterRow->count_sent)) $newsletterRow->count_sent = 0;
                    $newsletterRow->save();
                }

                if (!isset($procs[$newsletterRow->id])) {
                    $procs[$newsletterRow->id] = array();
                }

                $s = new \Kwf_Model_Select();
                $s->whereEquals('newsletter_id', $newsletterRow->id);
                $s->whereNull('send_process_pid');
                if (!$newsletterRow->getModel()->getDependentModel('Queues')->countRows($s) && empty($procs[$newsletterRow->id])) {
                    $newsletterRow->status = 'finished';
                    $newsletterRow->save();
                    $output->writeln("Newsletter finished.", OutputInterface::VERBOSITY_VERBOSE);

                    //give send processes time to finish
                    sleep(5);

                    $this->deleteHangingQueueRows($newsletterRow, $output);
                    continue;
                }

                $output->writeln(count($procs[$newsletterRow->id])." running processes", OutputInterface::VERBOSITY_VERBOSE);

                $this->runNewsletterJobs($procs, $newsletterRow, $output);

                $output->writeln(
                    "Newletter $newsletterRow->id: currently sending with " . round($newsletterRow->getCurrentSpeed()) . " mails/min",
                    OutputInterface::VERBOSITY_VERBOSE
                );
            }

            sleep(10);
        }
    }

    private function updateStoppedProcesses(array &$procs, OutputInterface $output)
    {
        foreach (array_keys($procs) as $newsletterId) {
            foreach ($procs[$newsletterId] as $pId => $process) {
                if ($process->isRunning()) continue;

                $select = new \Kwf_Model_Select();
                $select->whereEquals('newsletter_id', $newsletterId);
                $select->whereEquals('pid', $pId);
                $runRow = $this->newsletterRunsModel->getRow($select);
                if ($process->getExitCode()) {
                    $runRow->status = 'failed';
                    $e = new \Kwf_Exception("Newsletter sending run failed with exit code {$process->getExitCode()} and ErrorMessage: {$process->getErrorOutput()}");
                    $e->log();
                } else {
                    $runRow->status = 'success';
                }
                $runRow->save();

                $output->writeln("process {$process->getPid()} stopped...", OutputInterface::VERBOSITY_VERBOSE);
                unset($procs[$newsletterId][$pId]);
            }
        }
    }

    private function runNewsletterJobs(array &$procs, NewsletterRow $newsletterRow, OutputInterface $output)
    {
        $numOfProcesses = 1;
        if ($newsletterRow->mails_per_minute == 'unlimited') {
            $numOfProcesses = 3;
        }
        while (count($procs[$newsletterRow->id]) < $numOfProcesses) {
            $cmd = "php bootstrap.php symfony kwc_newsletter:send --newsletterId=$newsletterRow->id";
            if ($output->isVeryVerbose()) {
                $cmd .= " -vv";
            } else if ($output->isVerbose()) {
                $cmd .= " -v";
            }

            $runRow = $this->newsletterRunsModel->createRow();
            $runRow->newsletter_id = $newsletterRow->id;
            $runRow->start = date('Y-m-d H:i:s');
            $runRow->status = 'starting';
            $runRow->save();

            $process = new Process($cmd);
            $process->start(function($type, $buffer) use ($output, $runRow) {
                $output->writeln($buffer, Process::ERR === $type ? OutputInterface::OUTPUT_NORMAL : OutputInterface::VERBOSITY_VERBOSE);

                \Kwf_Registry::get('db')->query("UPDATE {$this->newsletterRunsModel->getTableName()} SET log=CONCAT(log, ?) WHERE id=?", array($buffer, $runRow->id));
            });
            $procs[$newsletterRow->id][$process->getPid()] = $process;

            $runRow->pid = $process->getPid();
            $runRow->status = 'running';

            $runRow->save();

            $output->writeln(array(
                "started new process with PID {$process->getPid()} on " . gethostname(),
                $cmd
            ), OutputInterface::VERBOSITY_VERBOSE);

            sleep(3); //don't start all processes at the same time
        }
    }

    private function getStartedNewsletterRows()
    {
        $select = $this->newslettersModel->select()
            ->where(new \Kwf_Model_Select_Expr_Or(array(
                new \Kwf_Model_Select_Expr_Equal('status', 'start'),
                new \Kwf_Model_Select_Expr_And(array(
                    new \Kwf_Model_Select_Expr_Equal('status', 'startLater'),
                    new \Kwf_Model_Select_Expr_LowerEqual('start_date', new \Kwf_DateTime(time())),
                )),
                new \Kwf_Model_Select_Expr_Equal('status', 'sending')
            )));
        return $this->newslettersModel->getRows($select);
    }

    private function deleteHangingQueueRows(NewsletterRow $newsletterRow, OutputInterface $output)
    {
        $s = new \Kwf_Model_Select();
        $s->whereEquals('newsletter_id', $newsletterRow->id);
        foreach ($newsletterRow->getModel()->getDependentModel('Queues')->getRows($s) as $queueRow) {
            $newsletterRow->getModel()->getDependentModel('QueueLogs')->createRow(array(
                'newsletter_id' => $queueRow->newsletter_id,
                'recipient_model' => $queueRow->recipient_model,
                'recipient_id' => $queueRow->recipient_id,
                'status' => 'failed',
                'send_date' => date('Y-m-d H:i:s')
            ))->save();
            $msg = "Newsletter finished but queue entry with pid $queueRow->send_process_pid still exists: $queueRow->recipient_id $queueRow->searchtext";
            $e = new \Kwf_Exception($msg);
            $e->logOrThrow();
            $output->writeln($msg, OutputInterface::VERBOSITY_VERBOSE);
            $queueRow->delete();
        }
    }
}
