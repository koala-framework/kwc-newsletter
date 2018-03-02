<?php
namespace KwcNewsletter\Bundle\Model\Row;

class NewsletterQueues extends \Kwf_Model_Proxy_Row
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

    protected function _beforeDelete()
    {
        $newsletter = $this->getParentRow('Newsletter');
        if (in_array($newsletter->status, array('start', 'stop', 'finished'))) {
            throw new \Kwf_ClientException(trlKwf('Can only add users to a paused newsletter'));
        }

        parent::_beforeDelete();
    }
}
