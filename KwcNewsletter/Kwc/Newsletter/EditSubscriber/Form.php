<?php
class KwcNewsletter_Kwc_Newsletter_EditSubscriber_Form extends KwcNewsletter_Kwc_Newsletter_EditSubscriber_FrontendForm
{
    protected $_newsletterComponentId;
    protected $_newsletterSource;

    public function __construct($name = null, $newsletterComponentId, $newsletterSource)
    {
        $this->_newsletterComponentId = $newsletterComponentId;
        $this->_newsletterSource = $newsletterSource;
        parent::__construct($name);
    }

    protected function _initFields()
    {
        parent::_initFields();

        $select = new Kwf_Model_Select();
        $select->whereEquals('newsletter_component_id', $this->_newsletterComponentId);
        $select->whereEquals('newsletter_source', $this->_newsletterSource);
        $select->order('pos');

        $this->add(new Kwf_Form_Field_MultiCheckbox('ToCategories', 'Category', trlKwf('Categories'), 'categories'))
            ->setValuesSelect($select)
            ->setWidth(255)
            ->setAllowBlank(false);
    }
}
