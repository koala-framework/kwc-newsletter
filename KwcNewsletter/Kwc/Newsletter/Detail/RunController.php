<?php
class KwcNewsletter_Kwc_Newsletter_Detail_RunController extends Kwf_Controller_Action_Auto_Form
{
    protected $_buttons = array();
    protected $_modelName = 'KwcNewsletter\Bundle\Model\NewsletterRuns';

    protected function _initFields()
    {
        parent::_initFields();
        $this->_form->add(new Kwf_Form_Field_ShowField('status', 'Status'));
        $this->_form->add(new Kwf_Form_Field_ShowField('runtime', 'Runtime'));
        $this->_form->add(new Kwf_Form_Field_ShowField('pid', 'PID'));
        $this->_form->add(new Kwf_Form_Field_ShowField('log', 'Log'))
            ->setTpl('<pre>{value:nl2Br}</pre>');

    }

    protected function _isAllowed($user)
    {
        return $user && $user->role === 'admin';
    }
}
