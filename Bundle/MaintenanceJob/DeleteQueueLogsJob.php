<?php
namespace KwcNewsletter\Bundle\MaintenanceJob;

use KwcNewsletter\Bundle\Model\NewsletterQueueLogs;
use KwfBundle\MaintenanceJobs\AbstractJob;
use Psr\Log\LoggerInterface;

class DeleteQueueLogsJob extends AbstractJob
{
    /**
     * @var NewsletterQueueLogs
     */
    private $queueLogsModel;

    public function __construct(NewsletterQueueLogs $model)
    {
        $this->queueLogsModel = $model;
    }

    public function getFrequency()
    {
        return self::FREQUENCY_DAILY;
    }

    public function execute(LoggerInterface $logger)
    {
        $select = new \Kwf_Model_Select();
        $select->where(new \Kwf_Model_Select_Expr_LowerEqual(
            new \Kwf_Model_Select_Expr_Field('send_date'),
            new \Kwf_Date(strtotime("-1 year"))
        ));

        $count = $this->queueLogsModel->countRows($select);
        if ($count > 0) {
            $this->queueLogsModel->deleteRows($select);
        }

        $logger->debug("Deleted $count queue logs");
    }
}
