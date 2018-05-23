<?php
namespace KwcNewsletter\Bundle\MaintenanceJob;

use KwcNewsletter\Bundle\Model\NewsletterQueues;
use KwfBundle\MaintenanceJobs\AbstractJob;
use Psr\Log\LoggerInterface;

class DeleteQueuesJob extends AbstractJob
{
    /**
     * @var NewsletterQueues
     */
    private $queuesModel;

    public function __construct(NewsletterQueues $model)
    {
        $this->queuesModel = $model;
    }

    public function getFrequency()
    {
        return self::FREQUENCY_DAILY;
    }

    public function execute(LoggerInterface $logger)
    {
        $select = new \Kwf_Model_Select();
        $select->whereNull('status');
        $ids = array_map(
            function($row) { return $row['id']; },
            $this->queuesModel->getReferencedModel('Newsletter')->export(
                \Kwf_Model_Abstract::FORMAT_ARRAY, $select, array('columns' => array('id'))
            )
        );

        $select = new \Kwf_Model_Select();
        $select->whereEquals('newsletter_id', $ids);
        $select->where(new \Kwf_Model_Select_Expr_LowerEqual(
            new \Kwf_Model_Select_Expr_Field('create_date'),
            new \Kwf_Date(strtotime("-1 week"))
        ));

        $count = $this->queuesModel->countRows($select);
        if ($count > 0) {
            $this->queuesModel->deleteRows($select);
        }

        $logger->debug("Deleted $count queue entries");
    }
}
