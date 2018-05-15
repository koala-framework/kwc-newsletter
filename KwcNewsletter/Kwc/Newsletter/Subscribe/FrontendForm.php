<?php
class KwcNewsletter_Kwc_Newsletter_Subscribe_FrontendForm extends Kwf_Form
{
    protected $_model = 'Kwf_Model_FnF';

    protected function _initFields()
    {
        parent::_initFields();

        $this->add(new Kwf_Form_Field_Radio('gender', trlKwfStatic('Gender')))
            ->setAllowBlank(false)
            ->setValues(array(
                'female' => trlKwfStatic('Female'),
                'male'   => trlKwfStatic('Male')
            ))
            ->setCls('kwf-radio-group-transparent');
        $this->add(new Kwf_Form_Field_TextField('title', trlKwfStatic('Title')));
        $this->add(new Kwf_Form_Field_TextField('firstname', trlKwfStatic('Firstname')))
            ->setAllowBlank(false);
        $this->add(new Kwf_Form_Field_TextField('lastname', trlKwfStatic('Lastname')))
            ->setAllowBlank(false);
        $this->add(new Kwf_Form_Field_TextField('email', trlKwfStatic('E-Mail')))
            ->setVtype('email')
            ->setAllowBlank(false);
    }

    /**
     * @deprecated
     */
    public final function getCategories()
    {
    }

    /**
     * @deprecated
     */
    protected final function _getCategories()
    {
        return array();
    }
}
