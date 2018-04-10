<?php
class KwcNewsletter_Kwc_Newsletter_Unsubscribe_Form_Component extends Kwc_Form_NonAjax_Component
{
    public $_recipient; //set by KwcNewsletter_Kwc_Newsletter_Unsubscribe_Component

    public static function getSettings($param = null)
    {
        $ret = parent::getSettings($param);
        $ret['generators']['child']['component']['success'] =
            'KwcNewsletter_Kwc_Newsletter_Unsubscribe_Form_Success_Component';
        $ret['placeholder']['submitButton'] = trlKwfStatic('Unsubscribe newsletter');
        $ret['viewCache'] = false;
        $ret['flags']['processInput'] = true;
        return $ret;
    }

    protected function _initForm()
    {
        parent::_initForm();
        if ($this->_recipient) {
            $this->_form->setModel($this->_recipient->getModel());
            $this->_form->setId($this->_recipient->id);
        }
    }

    protected function _beforeSave(Kwf_Model_Row_Interface $row)
    {
        parent::_beforeSave($row);

        $row->setLogSource($this->getData()->getAbsoluteUrl());
        $row->writeLog($this->getData()->trlKwf('Unsubscribed'), 'unsubscribed');
    }

    protected function _afterSave(Kwf_Model_Row_Interface $row)
    {
        parent::_afterSave($row);

        $row->mailUnsubscribe();
    }

    //moved to FrontendForm
    protected final function getParentField()
    {}

    public function processInput(array $postData)
    {
        if (!$this->_recipient && isset($postData['recipient'])) {
            $recipient = Kwc_Mail_Redirect_Component::parseRecipientParam($postData['recipient']);
            if ($recipient) $this->_recipient = $recipient;
        }

        parent::processInput($postData);
    }

    private function _getHash(array $hashData)
    {
        $hashData = implode('', $hashData);
        return substr(Kwf_Util_Hash::hash($hashData), 0, 6);
    }
}
