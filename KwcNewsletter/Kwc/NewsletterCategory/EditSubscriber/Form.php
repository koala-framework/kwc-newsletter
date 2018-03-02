<?php
class KwcNewsletter_Kwc_NewsletterCategory_EditSubscriber_Form extends KwcNewsletter_Kwc_Newsletter_Subscribe_FrontendForm
{
    protected $_modelName = 'KwcNewsletter\Bundle\Model\Subscribers';

    protected function _initFields()
    {
        parent::_initFields();

        $model = Kwf_Component_Model::getInstance('KwcNewsletter\Bundle\Model\Categories');
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
}
