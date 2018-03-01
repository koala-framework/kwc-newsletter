<?php
namespace KwcNewsletter\Bundle\Model;

class SubscribeCategories extends \Kwf_Model_Db
{
    protected $_table = 'kwc_newsletter_subscribecategories';

    protected function _init()
    {
        $this->_referenceMap['Category'] = array(
            'column' => 'category_id',
            'refModelClass' => 'KwcNewsletter\Bundle\Model\Categories'
        );

        parent::_init();
    }

    protected function _setupFilters()
    {
        $filter = new \Kwf_Filter_Row_Numberize();
        $filter->setGroupBy('component_id');
        $this->_filters = array('pos' => $filter);
    }
}
