<?php
namespace KwcNewsletter\Bundle\MaintenanceJob;

use KwfBundle\MaintenanceJobs\AbstractJob;
use Symfony\Component\Process\Process;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Psr\Log\LoggerInterface;

class StartJob extends AbstractJob
{
    private $procs = array();

    public function getFrequency()
    {
        return self::FREQUENCY_SECONDS;
    }

    public function hasWorkload()
    {
        $model = \Kwf_Model_Abstract::getInstance('Kwc_Newsletter_Model');

        $select = $model->select()
            ->where(new \Kwf_Model_Select_Expr_Or(array(
                new \Kwf_Model_Select_Expr_Equal('status', 'start'),
                new \Kwf_Model_Select_Expr_And(array(
                    new \Kwf_Model_Select_Expr_Equal('status', 'startLater'),
                    new \Kwf_Model_Select_Expr_LowerEqual('start_date', new \Kwf_DateTime(time())),
                )),
                new \Kwf_Model_Select_Expr_Equal('status', 'sending')
            )));

        return $model->countRows($select) > 0;
    }

    public function getMaxTime()
    {
        return 60*60*12;
    }

    public function execute(LoggerInterface $logger)
    {
        $debug = false;
        foreach ($logger->getHandlers() as $handler) {
            if ($handler instanceof StreamHandler) {
                $debug = $handler->getLevel() <= Logger::DEBUG;
                break;
            }
        }

        $model = \Kwf_Model_Abstract::getInstance('Kwc_Newsletter_Model');

        $select = $model->select()
            ->where(new \Kwf_Model_Select_Expr_Or(array(
                new \Kwf_Model_Select_Expr_Equal('status', 'start'),
                new \Kwf_Model_Select_Expr_And(array(
                    new \Kwf_Model_Select_Expr_Equal('status', 'startLater'),
                    new \Kwf_Model_Select_Expr_LowerEqual('start_date', new \Kwf_DateTime(time())),
                )),
                new \Kwf_Model_Select_Expr_Equal('status', 'sending')
            )));
        $rows = $model->getRows($select);
        foreach ($rows as $newsletterRow) {

            if ($newsletterRow->status != 'sending') {
                $newsletterRow->resume_date = date('Y-m-d H:i:s');
                $newsletterRow->status = 'sending';
                if (is_null($newsletterRow->count_sent)) $newsletterRow->count_sent = 0;
                $newsletterRow->save();
            }

            if (!isset($this->procs[$newsletterRow->id])) {
                $this->procs[$newsletterRow->id] = array();
            }

            //remove stopped processes (might stop because of memory limit or simply crash for some reason)
            foreach ($this->procs[$newsletterRow->id] as $k=>$p) {
                if (!$p->isRunning()) {
                    $logger->debug("process {$p->getPid()} stopped...");
                    unset($this->procs[$newsletterRow->id][$k]);
                }
            }

            $s = new \Kwf_Model_Select();
            $s->whereEquals('newsletter_id', $newsletterRow->id);
            $s->whereNull('send_process_pid');
            if (!$newsletterRow->getModel()->getDependentModel('Queue')->countRows($s)) {
                $newsletterRow->status = 'finished';
                $newsletterRow->save();
                $logger->debug("Newsletter finished.");

                //give send processes time to finish
                sleep(5);

                //delete "hanging" queue entries
                $s = new \Kwf_Model_Select();
                $s->whereEquals('newsletter_id', $newsletterRow->id);
                foreach ($newsletterRow->getModel()->getDependentModel('Queue')->getRows($s) as $queueRow) {
                    $newsletterRow->getModel()->getDependentModel('QueueLog')->createRow(array(
                        'newsletter_id' => $queueRow->newsletter_id,
                        'recipient_model' => $queueRow->recipient_model,
                        'recipient_id' => $queueRow->recipient_id,
                        'status' => 'failed',
                        'send_date' => date('Y-m-d H:i:s')
                    ))->save();
                    $msg = "Newsletter finished but queue entry with pid $queueRow->send_process_pid still exists: $queueRow->recipient_id $queueRow->searchtext";
                    $e = new \Kwf_Exception($msg);
                    $e->logOrThrow();
                    $logger->debug($msg);
                    $queueRow->delete();
                }
                continue;
            }

            $logger->info(count($this->procs[$newsletterRow->id])." running processes");

            $numOfProcesses = 1;
            if ($newsletterRow->mails_per_minute == 'unlimited') {
                $numOfProcesses = 3;
            }
            while (count($this->procs[$newsletterRow->id]) < $numOfProcesses) {
                $cmd = "php bootstrap.php symfony kwc_newsletter:send --newsletterId=$newsletterRow->id";
                if ($debug) $cmd .= " -v";
                //if ($this->_getParam('benchmark')) $cmd .= " --benchmark";
                //if ($this->_getParam('verbose')) $cmd .= " --verbose";

                $process = new Process($cmd);
                $this->procs[$newsletterRow->id][] = $process;
                $process->start(function ($type, $buffer) use ($logger) {
                    if (Process::ERR === $type) {
                        $logger->error($buffer);
                    } else {
                        $logger->info($buffer);
                    }
                });

                $logger->debug("started new process with PID ".$process->getPid().' on '.gethostname());
                $logger->debug($cmd);

                sleep(3); //don't start all processes at the same time
            }

            $logger->info("Newletter $newsletterRow->id: currently sending with " . round($newsletterRow->getCurrentSpeed()) . " mails/min");
        }
    }
}
