<?php
namespace KwcNewsletter\Bundle\MaintenanceJob;

use KwfBundle\MaintenanceJobs\AbstractJob;
use Psr\Log\LoggerInterface;

class DeleteNotActivatedJob extends AbstractJob
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
        $select->whereEquals('activated', false);
        $select->where(new \Kwf_Model_Select_Expr_LowerEqual(
            new \Kwf_Model_Select_Expr_Field('last_subscribe_date'),
            new \Kwf_Date(strtotime("-{$this->deleteAfterDays} days"))
        ));

        $count = $this->subscribersModel->countRows($select);
        foreach ($this->subscribersModel->getRows($select) as $row) {
            $row->delete();
        }

        $logger->debug("Deleted $count subscribers");
    }
}
