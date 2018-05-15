<?php
/**
 * Wird auch zum bearbeiten verwendet.
 * @see KwcNewsletter_Kwc_Newsletter_Subscribe_Edit_Component
 */
class KwcNewsletter_Kwc_Newsletter_Subscribe_Component extends Kwc_Form_Component
{
    protected $_allowWriteLog = true;

    public static function getSettings($param = null)
    {
        $ret = parent::getSettings($param);
        $ret['componentName'] = trlKwfStatic('Newsletter subscribing');
        $ret['placeholder']['submitButton'] = trlKwfStatic('Subscribe the newsletter');

        $ret['generators']['mail'] = array(
            'class' => 'Kwf_Component_Generator_Page_Static',
            'component' => 'KwcNewsletter_Kwc_Newsletter_Subscribe_Mail_Component'
        );
        $ret['generators']['doubleOptIn'] = array(
            'class' => 'Kwf_Component_Generator_Page_Static',
            'component' => 'KwcNewsletter_Kwc_Newsletter_Subscribe_DoubleOptIn_Component',
            'name' => trlKwfStatic('Opt In')
        );

        $ret['from'] = ''; // would be good if overwritten

        $ret['menuConfig'] = 'KwcNewsletter_Kwc_Newsletter_Subscribe_MenuConfig';
        $ret['extConfig'] = 'KwcNewsletter_Kwc_Newsletter_Subscribe_ExtConfig';

        $ret['assetsAdmin']['dep'][] = 'KwfAutoGrid';
        $ret['assetsAdmin']['dep'][] = 'KwfProxyPanel';
        $ret['assetsAdmin']['files'][] = 'kwcNewsletter/KwcNewsletter/Kwc/Newsletter/Subscribe/RecipientsPanel.js';

        $ret['subscribeToNewsletterClass'] = 'KwcNewsletter_Kwc_Newsletter_Component';
        $ret['subscribersModel'] = 'KwcNewsletter\Bundle\Model\Subscribers';
        return $ret;
    }

    public function getSubscribeToNewsletterComponent()
    {
        $nlData = Kwf_Component_Data_Root::getInstance()
            ->getComponentByClass($this->_getSetting('subscribeToNewsletterClass'), array('subroot'=>$this->getData()));
        if (!$nlData) {
            throw new Kwf_Exception('Cannot find newsletter component');
        }
        return $nlData;
    }

    protected function _beforeInsert(Kwf_Model_Row_Interface $fnfRow)
    {
        parent::_beforeInsert($fnfRow);

        $select = new Kwf_Model_Select();
        $select->whereEquals('newsletter_component_id', $this->getSubscribeToNewsletterComponent()->dbId);
        $select->whereEquals('email', $fnfRow->email);
        $select->whereEquals('activated', true);
        $select->whereEquals('unsubscribed', false);
        if (Kwf_Model_Abstract::getInstance($this->_getSetting('subscribersModel'))->countRows($select)) {
            throw new Kwf_Exception_Client('You are already subscribed to this newsletter.');
        }
    }

    protected function _afterInsert(Kwf_Model_Row_Interface $fnfRow)
    {
        parent::_afterInsert($fnfRow);

        $model = Kwf_Model_Abstract::getInstance($this->_getSetting('subscribersModel'));

        $select = new Kwf_Model_Select();
        $select->whereEquals('newsletter_component_id', $this->getSubscribeToNewsletterComponent()->dbId);
        $select->whereEquals('email', $fnfRow->email);
        $row = $model->getRow($select);
        if (!$row) $row = $model->createRow(array(
            'newsletter_component_id' => $this->getSubscribeToNewsletterComponent()->dbId,
            'email' => $fnfRow->email
        ));

        $row->gender = $fnfRow->gender;
        $row->title = $fnfRow->title;
        $row->firstname = $fnfRow->firstname;
        $row->lastname = $fnfRow->lastname;

        $sendActivationMail = false;
        if (!$row->activated || $row->unsubscribed) {
            if ($this->_allowWriteLog) {
                $row->setLogSource($this->getData()->getAbsoluteUrl());
                $this->_writeLog($row);
            }

            $row->unsubscribed = false;
            $row->activated = false;

            $sendActivationMail = true;
        }

        foreach ($this->_getCategories() as $id => $name) {
            $s = new \Kwf_Model_Select();
            $s->whereEquals('category_id', $id);
            if (!$row->countChildRows('ToCategories', $s)) {
                $row->createChildRow('ToCategories', array(
                    'category_id' => $id
                ));
            }
        }

        if ($row->isDirty()) $row->save();

        $sendOneActivationMailForEmailPerHourCacheId = 'send-one-activation-mail-for-email-per-hour-' . md5($row->email);
        $sendOneActivationMailForEmailPerHour = \Kwf_Cache_Simple::fetch($sendOneActivationMailForEmailPerHourCacheId);
        if (!$sendOneActivationMailForEmailPerHour && $sendActivationMail) {
            $this->_sendActivationMail($row);

            \Kwf_Cache_Simple::add($sendOneActivationMailForEmailPerHourCacheId, true, 3600);
        }
    }

    protected function _getCategories()
    {
        return array();
    }

    protected function _sendActivationMail(\Kwc_Mail_Recipient_Interface $row)
    {
        $this->getData()->getChildComponent('_mail')->getComponent()->send($row, array(
            'formRow' => $row,
            'host' => $this->getSubscribeToNewsletterComponent()->getDomain(),
            'unsubscribeComponent' => null,
            'editComponent' => $this->getSubscribeToNewsletterComponent()->getChildComponent('_editSubscriber'),
            'doubleOptInComponent' => $this->getData()->getChildComponent('_doubleOptIn')
        ));
    }

    protected function _writeLog(Kwf_Model_Row_Interface $row)
    {
        $row->writeLog($this->getData()->trlKwf('Subscribed'), 'subscribed');
    }

    /**
     * @deprecated
     */
    public final function insertSubscription(Kwf_Model_Row_Abstract $row)
    {
    }

    /**
     * @deprecated
     */
    protected final function _subscriptionExists(Kwf_Model_Row_Abstract $row)
    {
    }
}
