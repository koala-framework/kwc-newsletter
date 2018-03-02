<?php
namespace KwcNewsletter\Bundle\Model;

class SubscriberLogs extends \Kwf_Model_Db
{
    protected $_table = 'kwc_newsletter_subscriber_logs';

    protected function _init()
    {
        $this->_referenceMap['Subscriber'] = array(
            'column' => 'subscriber_id',
            'refModelClass' => 'KwcNewsletter\Bundle\Model\Subscribers'
        );

        parent::_init();
    }
}

