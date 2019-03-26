<?php

class KwcNewsletter_Controller_ApiKeyController extends Kwf_Controller_Action_Auto_Form
{
    protected $_model = 'KwcNewsletter\Bundle\Model\NewsletterApiKeys';
    protected $_permissions = array('add', 'save');

    protected function _initFields()
    {
        parent::_initFields();

        $this->_form->add(new Kwf_Form_Field_TextField('name', trlKwf('Name')))
            ->setAllowBlank(false)
            ->addValidator(new Kwf_Validate_Row_Unique());

        $newsletters = Kwf_Component_Data_Root::getInstance()->getComponentsByClass('KwcNewsletter_Kwc_Newsletter_Component');
        if (count($newsletters) > 1) {
            $this->_form->add(new Kwf_Form_Field_Select('newsletter_component_id', trlKwf('Country')))
                ->setAllowBlank(false)
                ->setValues(array_map(function ($data) {
                    return array($data->dbId, $data->getSubroot()->name);
                }, $newsletters));
        } else {
            $this->_form->add(new Kwf_Form_Field_Hidden('newsletter_component_id'))
                ->setDefaultValue($newsletters[0]->dbId);
        }

        $this->_form->add(new Kwf_Form_Field_ShowField('key', trlKwf('Key')))
            ->setAllowBlank(false);
    }
}
