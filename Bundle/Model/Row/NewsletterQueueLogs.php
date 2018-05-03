<?php
namespace KwcNewsletter\Bundle\Model\Row;

class NewsletterQueueLogs extends \Kwf_Model_Proxy_Row
{
    public function getRecipient()
    {
        $modelname = $this->recipient_model;
        if (is_instance_of($modelname, 'Kwf_Model_Abstract')) {
            $row = \Kwf_Model_Abstract::getInstance($modelname)->getRow($this->recipient_id);
        } else {
            throw new \Kwf_Exception("Recipient-Model for id {$this->id} has to be a model.");
        }
        if ($row && !$row instanceof \Kwc_Mail_Recipient_Interface) {
            throw new \Kwf_Exception("Recipient-Row has to implement Kwc_Mail_Recipient_Interface");
        }
        return $row;
    }
}
