<?php
namespace KwcNewsletter\Bundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Newsletter extends Command
{
    /**
     * @var \Kwf_Model_Abstract
     */
    private $newslettersModel;

    public function __construct(\Kwf_Model_Abstract $model)
    {
        $this->newslettersModel = $model;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('kwc_newsletter:send')
            ->setDescription('Start sending every newsletter with status "sending"')
            ->addOption(
                'newsletterId',
                '-nId',
                InputOption::VALUE_REQUIRED,
                'ID of newsletter you want to send'
            )
            ->addOption(
                'benchmark',
                '-b',
                InputOption::VALUE_NONE,
                'Display benchmark messages'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        \Kwf_Util_MemoryLimit::set(512);
        \Kwf_Events_ModelObserver::getInstance()->disable();

        $newsletterId = $input->getOption('newsletterId');
        $nlRow = $this->newslettersModel->getRow($newsletterId);

        $mailsPerMinute = $nlRow->getCountOfMailsPerMinute();

        // Send in loop
        $queueModel = $nlRow->getModel()->getDependentModel('Queues');
        $queueLogModel = $nlRow->getModel()->getDependentModel('QueueLogs');
        $count = 0; $countErrors = 0; $countNoUser = 0;
        $start = microtime(true);
        do {
            if ($nlRow->getStatus() != 'sending') {
                $output->writeln(
                    "break sending because newsletter status changed to '{$nlRow->getStatus()}'",
                    OutputInterface::VERBOSITY_NORMAL
                );
                break;
            }

            \Kwf_Benchmark::enable();
            \Kwf_Benchmark::reset();
            \Kwf_Benchmark::checkpoint('start');
            $userStart = microtime(true);

            // Get rows from queue, when empty, newsletter finished
            $rows = $nlRow->getNextQueueRows(getmypid());
            \Kwf_Benchmark::checkpoint('get next recipients');
            foreach ($rows as $row) {
                if ($nlRow->getStatus() != 'sending') {
                    $queueModel->getTable()->update(
                        array('send_process_pid' => null),
                        "send_process_pid=" . getmypid()
                    );
                    break;
                }

                // Sleep while calculation time
                if ($nlRow->mails_per_minute != 'unlimited') {
                    $sleep = $start + 60/$mailsPerMinute * $count - microtime(true);
                    if ($sleep > 0) usleep($sleep * 1000000);
                    $output->writeln(
                        "sleeping {$sleep}s",
                        OutputInterface::VERBOSITY_VERBOSE
                    );
                }

                $recipient = $row->getRecipient();
                $mc = $nlRow->getMailComponent();
                if (!$mc->isValidRecipient($recipient)) {
                    $countNoUser++;
                    $status = 'usernotfound';
                } else {
                    try {

                        if ($mc instanceof \Kwc_Mail_Abstract_Component) {
                            $t = microtime(true);
                            $mail = $mc->createMail($recipient);
                            $createTime = microtime(true)-$t;

                            $t = microtime(true);
                            $mail->send();
                            $sendTime = microtime(true)-$t;
                            \Kwf_Benchmark::checkpoint('send mail');
                        } else {
                            $t = microtime(true);
                            $mc->send($recipient);
                            $sendTime = microtime(true)-$t;
                            \Kwf_Benchmark::checkpoint('send mail');
                        }

                        $count++;
                        $status = 'sent';
                    } catch (\Exception $e) {
                        echo 'Exception in Sending Newsletter with id ' . $nlRow->id . ' with recipient ' . $recipient->getMailEmail();
                        echo $e->__toString();
                        $countErrors++;
                        $status = 'failed';
                    }
                    $nlRow->getModel()->getTable()->update(array(
                        'count_sent' => new \Zend_Db_Expr('count_sent + 1'),
                        'last_sent_date' => date('Y-m-d H:i:s')
                    ), 'id = '.$nlRow->id);
                }

                $queueLogModel->createRow(array(
                    'newsletter_id' => $row->newsletter_id,
                    'recipient_model_shortcut' => $row->recipient_model_shortcut,
                    'recipient_id' => $row->recipient_id,
                    'status' => $status,
                    'send_date' => date('Y-m-d H:i:s')
                ))->save();

                $row->delete();

                \Kwf_Benchmark::checkpoint('update queue');

                if (\Kwf_Benchmark::isEnabled() && $input->getOption('benchmark')) {
                    $output->writeln(
                        \Kwf_Benchmark::getCheckpointOutput(),
                        OutputInterface::VERBOSITY_VERBOSE
                    );
                }

                if ($status === 'sent') {
                    $output->write(
                        array(
                            "[".getmypid()."] $status in ".round((microtime(true)-$userStart)*1000)."ms (",
                            "create ".round($createTime*1000)."ms, ",
                            "send ".round($sendTime*1000)."ms",
                            ") [".round(memory_get_usage()/(1024*1024))."MB] [".round($count/(microtime(true)-$start), 1)." mails/s]\n"
                        ),
                        false,
                        OutputInterface::VERBOSITY_VERBOSE
                    );
                }

                if ($status == 'failed' && $output->isVerbose()) {
                    $output->writeln(
                        "stopping because sending failed in debug mode",
                        OutputInterface::VERBOSITY_VERBOSE
                    );
                    break 2;
                }
            }


            if (memory_get_usage() > 100*1024*1024) {
                $output->writeln(
                    "stopping because of >100MB memory usage",
                    OutputInterface::VERBOSITY_NORMAL
                );

                break;
            }
        } while (!empty($rows));
        $stop = microtime(true);

        // Write log
        $logModel = $nlRow->getModel()->getDependentModel('Logs');
        $row = $logModel->createRow(array(
            'newsletter_id' => $nlRow->id,
            'start' => date('Y-m-d H:i:s', floor($start)),
            'stop' => date('Y-m-d H:i:s', floor($stop)),
            'count' => $count,
            'countErrors' => $countErrors
        ));
        $row->save();

        // Show debug messages
        $average = round($count/($stop-$start)*60);
        $info = $nlRow->getInfo();
        $output->writeln(
            array(
                "\n",
                "$count Newsletters sent ($average/minute), $countErrors errors, $countNoUser user not found.",
                $info['shortText']
            ),
            OutputInterface::VERBOSITY_NORMAL
        );

        \Kwf_Events_ModelObserver::getInstance()->enable();
    }
}
