<?php
namespace KwcNewsletter\Bundle\Model;

class Newsletters extends \Kwf_Model_Db_Proxy
{
    protected $_table = 'kwc_newsletters';
    protected $_rowClass = 'KwcNewsletter\Bundle\Model\Row\Newsletters';

    protected function _init()
    {
        $this->_dependentModels['Queues'] = 'KwcNewsletter\Bundle\Model\NewsletterQueues';
        $this->_dependentModels['QueueLogs'] = 'KwcNewsletter\Bundle\Model\NewsletterQueueLogs';
        $this->_dependentModels['Logs'] = 'KwcNewsletter\Bundle\Model\NewsletterLogs';
        $this->_dependentModels['Mails'] = 'Kwc_Mail_Model';

        parent::_init();
    }

    /**
     * @deprecated
     * If you need to override send to enforce a specific start time that should be implemented using sendLater
     */
    public final function send($timeLimit = 60, $debugOutput = false)
    {}
}
