<?php
class KwcNewsletter_Kwc_Newsletter_Subscribe_DoubleOptIn_Component extends Kwc_Form_Success_Component
{
    protected $_recipient;
    public static function getSettings($param = null)
    {
        $ret = parent::getSettings($param);
        $ret['placeholder']['success'] = trlKwfStatic('Your E-Mail address has been verified. You will receive our newsletters in future.');
        $ret['viewCache'] = false;
        $ret['flags']['processInput'] = true;
        $ret['flags']['passMailRecipient'] = true;
        return $ret;
    }

    public function processInput(array $postData)
    {
        if (!isset($postData['recipient'])) {
            throw new Kwf_Exception_NotFound();
        }

        try {
            $this->_recipient = Kwc_Mail_Redirect_Component::parseRecipientParam($postData['recipient']);
        } catch (Kwf_Exception_NotFound $e) {}

        if ($this->_recipient && !$this->_recipient->activated) {
            $this->_recipient->unsubscribed = 0;
            $this->_recipient->activated = 1;

            $this->_recipient->setLogSource($this->getData()->getAbsoluteUrl());
            $this->_recipient->writeLog($this->getData()->trlKwf('Activated'), 'activated');
            $this->_recipient->save();

            $this->_deleteEmailHash(sha1($this->_recipient->email));
        }
    }

    public function getTemplateVars(Kwf_Component_Renderer_Abstract $renderer)
    {
        $ret = parent::getTemplateVars($renderer);
        $ret['recipientNotFound'] = !$this->_recipient;
        return $ret;
    }

    protected function _deleteEmailHash($hash)
    {
        $select = new Kwf_Model_Select();
        $select->whereId($hash);
        Kwf_Model_Abstract::getInstance('KwcNewsletter\Bundle\Model\DeletedSubscriberHashes')->deleteRows($select);
    }
}
