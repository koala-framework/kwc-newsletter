<?php
namespace KwcNewsletter\Bundle\MaintenanceJob;

use KwfBundle\MaintenanceJobs\AbstractJob;
use Psr\Log\LoggerInterface;

class DeleteUnsubscribedJob extends AbstractJob
{
    /**
     * @var \Kwf_Model_Abstract
     */
    private $subscribersModel;
    /*
     * @var integer
     */
    private $deleteAfterDays;

    public function __construct(\Kwf_Model_Abstract $model, $deleteAfterDays)
    {
        $this->subscribersModel = $model;
        $this->deleteAfterDays = $deleteAfterDays;
    }

    public function getFrequency()
    {
        return self::FREQUENCY_DAILY;
    }

    public function execute(LoggerInterface $logger)
    {
        $select = new \Kwf_Model_Select();
        $select->whereEquals('unsubscribed', true);
        $select->where(new \Kwf_Model_Select_Expr_LowerEqual(
            new \Kwf_Model_Select_Expr_Field('last_unsubscribe_date'),
            new \Kwf_Date(strtotime("-{$this->deleteAfterDays} days"))
        ));

        $ids = array_map(
            function($subscriber) { return $subscriber['id']; },
            $this->subscribersModel->export(\Kwf_Model_Abstract::FORMAT_ARRAY, $select, array('columns' => array('id')))
        );
        $count = count($ids);
        if ($count > 0) {
            $select = new \Kwf_Model_Select();
            $select->whereEquals('id', $ids);
            $this->subscribersModel->deleteRows($select);

            $select = new \Kwf_Model_Select();
            $select->whereEquals('subscriber_id', $ids);
            $this->subscribersModel->getDependentModel('Logs')->deleteRows($select);

            if ($this->subscribersModel instanceof \KwcNewsletter_Kwc_NewsletterCategory_Subscribe_Model) {
                $this->subscribersModel->getDependentModel('ToCategory')->deleteRows($select);
            }
        }

        $logger->debug("Deleted $count subscribers");
    }
}
