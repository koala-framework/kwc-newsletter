<?php
namespace KwcNewsletter\Bundle\Model;

class Categories extends \Kwf_Model_Db
{
    protected $_table = 'kwc_newsletter_categories';
    protected $_toStringField = 'category';

    protected function _init()
    {
        $this->_dependentModels['ToSubscribers'] = 'KwcNewsletter\Bundle\Model\SubscribersToCategories';

        parent::_init();

        $select = new \Kwf_Model_Select();
        $select->whereEquals('subscriber_activated', false);
        $select->whereEquals('subscriber_unsubscribed', false);
        $this->_exprs['count_not_activated'] = new \Kwf_Model_Select_Expr_Child_Count('ToSubscribers', $select);

        $select = new \Kwf_Model_Select();
        $select->whereEquals('subscriber_activated', true);
        $select->whereEquals('subscriber_unsubscribed', false);
        $this->_exprs['count_activated'] = new \Kwf_Model_Select_Expr_Child_Count('ToSubscribers', $select);

        $select = new \Kwf_Model_Select();
        $select->whereEquals('subscriber_unsubscribed', true);
        $this->_exprs['count_unsubscribed'] = new \Kwf_Model_Select_Expr_Child_Count('ToSubscribers', $select);
    }

    protected function _setupFilters()
    {
        $filter = new \Kwf_Filter_Row_Numberize();
        $filter->setGroupBy('newsletter_component_id');
        $this->_filters = array('pos' => $filter);
    }
}
