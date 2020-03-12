<?php
namespace KwcNewsletter\Bundle\Model;

class NewsletterQueueLogs extends \Kwf_Model_Db_Proxy
{
    protected $_table = 'kwc_newsletter_queue_logs';
    protected $_rowClass = 'KwcNewsletter\Bundle\Model\Row\NewsletterQueueLogs';

    protected function _init()
    {
        $this->_referenceMap['Newsletter'] = array(
            'column' => 'newsletter_id',
            'refModelClass' => 'KwcNewsletter\Bundle\Model\Newsletters'
        );

        $this->_referenceMap['Subscriber'] = array(
            'column' => 'recipient_id',
            'refModelClass' => 'KwcNewsletter\Bundle\Model\Subscribers'
        );
        parent::_init();
    }
}
