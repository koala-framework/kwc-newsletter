<?php
class KwcNewsletter_Kwc_Newsletter_EditSubscriber_Form extends KwcNewsletter_Kwc_Newsletter_Subscribe_FrontendForm
{
    protected function _init()
    {
        parent::_init();

        $model = $this->_model->getDependentModel('ToCategories')->getReferencedModel('Category');
        $s = $model->select()
           ->whereEquals('newsletter_component_id', $this->_newsletterComponentId)
           ->order('pos');
       $categories = array();
       foreach ($model->getRows($s) as $row) {
           $categories[$row->id] = $row->category;
       }

       $this->add(new Kwf_Form_Field_MultiCheckbox('ToCategories', 'Category', trlKwf('Categories')))
           ->setValues($categories)
           ->setWidth(255)
           ->setAllowBlank(false);
    }

    protected function _afterSave(Kwf_Model_Row_Interface $row)
    {
        Kwf_Form::_afterSave($row);
    }
}
