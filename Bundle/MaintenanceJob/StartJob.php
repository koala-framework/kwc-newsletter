<?php
namespace KwcNewsletter\Bundle\MaintenanceJob;

use KwfBundle\MaintenanceJobs\AbstractJob;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Psr\Log\LoggerInterface;

class StartJob extends AbstractJob
{
    /**
     * @var \Kwf_Model_Abstract
     */
    private $newslettersModel;

    public function __construct(\Kwf_Model_Abstract $model)
    {
        $this->newslettersModel = $model;
    }

    public function getFrequency()
    {
        return self::FREQUENCY_SECONDS;
    }

    public function hasWorkload()
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

        return $this->newslettersModel->countRows($select) > 0;
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

        $procs = array();
        foreach (\Kwf_Util_Process::getRunningWebProcesses() as $process) {
            if ($process['cmd'] !== 'symfony kwc_newsletter:send') continue;

            if (preg_match("#--newsletterId=([0-9]+)#", $process['args'], $matches)) {
                $newsletterId = $matches[1];
                if (!array_key_exists($newsletterId, $procs)) $procs[$newsletterId] = array();

                $procs[$newsletterId][] = $process['pid'];
            }
        }

        $select = $this->newslettersModel->select()
            ->where(new \Kwf_Model_Select_Expr_Or(array(
                new \Kwf_Model_Select_Expr_Equal('status', 'start'),
                new \Kwf_Model_Select_Expr_And(array(
                    new \Kwf_Model_Select_Expr_Equal('status', 'startLater'),
                    new \Kwf_Model_Select_Expr_LowerEqual('start_date', new \Kwf_DateTime(time())),
                )),
                new \Kwf_Model_Select_Expr_Equal('status', 'sending')
            )));
        $rows = $this->newslettersModel->getRows($select);
        foreach ($rows as $newsletterRow) {

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
            if (!$newsletterRow->getModel()->getDependentModel('Queues')->countRows($s)) {
                $newsletterRow->status = 'finished';
                $newsletterRow->save();
                $logger->debug("Newsletter finished.");

                //give send processes time to finish
                sleep(5);

                //delete "hanging" queue entries
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
                    $logger->debug($msg);
                    $queueRow->delete();
                }
                continue;
            }

            $logger->info(count($procs[$newsletterRow->id])." running processes");

            $numOfProcesses = 1;
            if ($newsletterRow->mails_per_minute == 'unlimited') {
                $numOfProcesses = 3;
            }
            while (count($procs[$newsletterRow->id]) < $numOfProcesses) {
                $cmd = "php bootstrap.php symfony kwc_newsletter:send --newsletterId=$newsletterRow->id";
                if ($debug) $cmd .= " -v";
                //if ($this->_getParam('benchmark')) $cmd .= " --benchmark";
                //if ($this->_getParam('verbose')) $cmd .= " --verbose";

                $descriptorspec = array(
                    1 => STDOUT,
                    2 => STDERR,
                );
                $p = new \Kwf_Util_Proc($cmd, $descriptorspec);
                $procs[$newsletterRow->id][] = $p->getPid();

                $logger->debug("started new process with PID ".$p->getPid().' on '.gethostname());
                $logger->debug($cmd);

                sleep(3); //don't start all processes at the same time
            }

            $logger->info("Newletter $newsletterRow->id: currently sending with " . round($newsletterRow->getCurrentSpeed()) . " mails/min");
        }
    }
}
