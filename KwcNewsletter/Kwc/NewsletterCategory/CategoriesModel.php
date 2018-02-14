<?php
class KwcNewsletter_Kwc_NewsletterCategory_CategoriesModel extends Kwf_Model_Db
{
    protected $_table = 'kwc_newsletter_categories';
    protected $_toStringField = 'category';

    protected function _setupFilters()
    {
        $filter = new Kwf_Filter_Row_Numberize();
        $filter->setGroupBy('newsletter_component_id');
        $this->_filters = array('pos' => $filter);
    }
}
