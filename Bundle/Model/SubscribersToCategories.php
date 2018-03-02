<?php
namespace KwcNewsletter\Bundle\Model;

class SubscribersToCategories extends \Kwf_Model_Db
{
    protected $_table = 'kwc_newsletter_subscribers_to_categories';
    protected $_rowClass = 'KwcNewsletter\Bundle\Model\Row\SubscribersToCategories';

    protected function _init()
    {
        $this->_referenceMap['Category'] = array(
            'column' => 'category_id',
            'refModelClass' => 'KwcNewsletter\Bundle\Model\Categories'
        );
        $this->_referenceMap['Subscriber'] = array(
            'column' => 'subscriber_id',
            'refModelClass' => 'KwcNewsletter\Bundle\Model\Subscribers'
        );

        parent::_init();
    }
}
