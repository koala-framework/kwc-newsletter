<?php

namespace KwcNewsletter\Bundle\Model;

use \Kwf_Component_Data;

class Subscribers extends \Kwf_Model_Db
{
    const DEFAULT_NEWSLETTER_SOURCE = 'newsletter';
    protected $_table = 'kwc_newsletter_subscribers';
    protected $_rowClass = 'KwcNewsletter\Bundle\Model\Row\Subscribers';

    protected $_columnMappings = array(
        'Kwc_Mail_Recipient_Mapping' => array(
            'firstname' => 'firstname',
            'lastname' => 'lastname',
            'email' => 'email',
            'format' => null,
            'gender' => 'gender',
            'title' => 'title'
        ),
        'Kwc_Mail_Recipient_UnsubscribableMapping' => array(
            'unsubscribed' => 'unsubscribed'
        )
    );
    protected $_serialization = array(
        'gender' => 'openApi',
        'title' => 'openApi',
        'firstname' => 'openApi',
        'lastname' => 'openApi',
        'email' => 'openApi',
        'last_subscribe_date' => 'openApi',
        'last_activated_date' => 'openApi',
        'last_unsubscribe_date' => 'openApi',
        'category_ids' => 'openApi',
    );

    protected function _init()
    {
        $this->_dependentModels['Logs'] = 'KwcNewsletter\Bundle\Model\SubscriberLogs';
        $this->_dependentModels['ToCategories'] = 'KwcNewsletter\Bundle\Model\SubscribersToCategories';

        parent::_init();

        $abstractSelect = new \Kwf_Model_Select();
        $abstractSelect->order('date', 'DESC');

        $select = clone $abstractSelect;
        $select->whereEquals('state', 'subscribed');
        $this->_exprs['last_subscribe_date'] = new \Kwf_Model_Select_Expr_Child_First('Logs', 'date', $select);

        $select = clone $abstractSelect;
        $select->whereEquals('state', 'activated');
        $this->_exprs['last_activated_date'] = new \Kwf_Model_Select_Expr_Child_First('Logs', 'date', $select);

        $select = clone $abstractSelect;
        $select->whereEquals('state', 'unsubscribed');
        $this->_exprs['last_unsubscribe_date'] = new \Kwf_Model_Select_Expr_Child_First('Logs', 'date', $select);

        $this->_exprs['category_ids'] = new \Kwf_Model_Select_Expr_Child_GroupConcat('ToCategories', 'category_id');
    }

    public static function getSources(Kwf_Component_Data $newsletterComponent)
    {
        $ret = $newsletterComponent->getBaseProperty('newsletterSources');
        if (!$ret) {
            $ret = array(
                self::DEFAULT_NEWSLETTER_SOURCE => $newsletterComponent->trlKwf('Newsletter')
            );
        }
        return $ret;
    }
}
