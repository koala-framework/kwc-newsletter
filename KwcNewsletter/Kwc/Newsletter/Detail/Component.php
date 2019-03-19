<?php
class KwcNewsletter_Kwc_Newsletter_Detail_Component extends Kwc_Directories_Item_Detail_Component
{
    private $_toImport = array();

    /**
     * Cache for email addresses that should be checked against the rtr-ecg list
     * Key   = the same key as in $this->_toImport
     * Value = the email address that should be checked
     */
    private $_rtrCheck = array();

    public static function getSettings($param = null)
    {
        $ret = parent::getSettings($param);
        $ret['generators']['mail'] = array(
            'class' => 'Kwf_Component_Generator_Page_Static',
            'component' => 'KwcNewsletter_Kwc_Newsletter_Detail_Mail_Component'
        );
        $ret['assetsAdmin']['files'][] = 'kwcNewsletter/KwcNewsletter/Kwc/Newsletter/Detail/TabPanel.js';
        $ret['assetsAdmin']['files'][] = 'kwcNewsletter/KwcNewsletter/Kwc/Newsletter/Detail/PreviewPanel.js';
        $ret['assetsAdmin']['files'][] = 'kwcNewsletter/KwcNewsletter/Kwc/Newsletter/Detail/RecipientsPanel.js';
        $ret['assetsAdmin']['files'][] = 'kwcNewsletter/KwcNewsletter/Kwc/Newsletter/Detail/RecipientsGridPanel.js';
        $ret['assetsAdmin']['files'][] = 'kwcNewsletter/KwcNewsletter/Kwc/Newsletter/Detail/RecipientsQueuePanel.js';
        $ret['assetsAdmin']['files'][] = 'kwcNewsletter/KwcNewsletter/Kwc/Newsletter/Detail/RecipientsAction.js';
        $ret['assetsAdmin']['files'][] = 'kwcNewsletter/KwcNewsletter/Kwc/Newsletter/Detail/Recipients.css';
        $ret['assetsAdmin']['files'][] = 'kwcNewsletter/KwcNewsletter/Kwc/Newsletter/Detail/StartNewsletterPanel.js';
        $ret['assetsAdmin']['files'][] = 'kwcNewsletter/KwcNewsletter/Kwc/Newsletter/Detail/StartNewsletterPanel.scss';
        $ret['assetsAdmin']['files'][] = 'kwcNewsletter/KwcNewsletter/Kwc/Newsletter/Detail/QueueLogsPanel.js';
        $ret['assetsAdmin']['files'][] = 'kwcNewsletter/KwcNewsletter/Kwc/Newsletter/Detail/RunsPanel.js';
        $ret['assetsAdmin']['files'][] = 'ext2/src/widgets/StatusBar.js';
        $ret['assetsAdmin']['dep'][] = 'KwfFormDateTimeField';
        $ret['assetsAdmin']['dep'][] = 'KwfFormCards';
        $ret['componentName'] = 'Newsletter';
        $ret['checkRtrList'] = !!Kwf_Config::getValue('service.rtrlist.url');
        $ret['flags']['skipFulltext'] = true;
        $ret['flags']['noIndex'] = true;
        $ret['flags']['skipPagesMeta'] = true;

        $ret['extConfig'] = 'KwcNewsletter_Kwc_Newsletter_Detail_ExtConfig';

        $ret['contentSender'] = 'KwcNewsletter_Kwc_Newsletter_Detail_ContentSender';
        return $ret;
    }

    public function countQueue()
    {
        $model = $this->getData()->parent->getComponent()->getChildModel()->getDependentModel('Queues');
        $select = $model->select()->whereEquals('newsletter_id', $this->getData()->row->id);
        return $model->countRows($select);
    }

    public function removeFromQueue($model = '', $ids = array())
    {
        $ret = array();

        $newsletter = $this->getData()->row;
        $queueModel = $this->getData()->parent->getComponent()->getChildModel()->getDependentModel('Queues');
        $select = $queueModel->select()
            ->whereEquals('recipient_model_shortcut', $this->_getRecipientModelShortcutFromModel($this->getData()->getChildComponent('_mail')->getComponent(), $model))
            ->whereEquals('recipient_id', $ids)
            ->whereEquals('newsletter_id', $newsletter->id);
        $queueModel->deleteRows($select);
    }

    public function importToQueue(Kwf_Model_Abstract $model, Kwf_Model_Select $select)
    {
        $newsletter = $this->getData()->row;
        if (in_array($newsletter->status, array('start', 'stop', 'finished', 'sending'))) {
            throw new Kwf_ClientException(trlKwf('Can only add users to a paused newsletter'));
        }

        $ret = array('rtrExcluded' => array());

        if ($this->getData()->getChildComponent('_mail')->getChildComponent('_redirect')) {
            // check if the necessary modelShortcut is set in 'mail' childComponent
            // this function checks if everything neccessary is set
            $this->getData()->getChildComponent('_mail')->getChildComponent('_redirect')
                ->getComponent()->getRecipientModelShortcut(get_class($model));
        }

        if (!$model->hasColumnMappings('Kwc_Mail_Recipient_Mapping')) {
            throw new Kwf_Exception('Model "' . get_class($model) . '" has to implement column mapping "Kwc_Mail_Recipient_Mapping"');
        }

        $mail = $this->getData()->getChildComponent('_mail')->getComponent();
        $select = $mail->getValidRecipientSelect($model, $select);

        $createDate = date('Y-m-d H:i:s');
        $mapping = $model->getColumnMappings('Kwc_Mail_Recipient_Mapping');
        $import = array();
        $emails = array();
        foreach ($model->export(Kwf_Model_Abstract::FORMAT_ARRAY, $select) as $e) {
            $searchArray = array();
            foreach ($e as $k => $field) {
                if ($k == 'firstname' || $k == 'lastname' || $k == 'email') {
                    $searchArray[] = $field;
                }
            }
            $searchText = implode(' ', $searchArray);
            $import[] = array(
                'newsletter_id' => $newsletter->id,
                'recipient_model_shortcut' => $this->_getRecipientModelShortcutFromModel($mail, get_class($model)),
                'recipient_id' => $e['id'],
                'searchtext' => $searchText,
                'create_date' => $createDate
            );
            $emails[] = $e[$mapping['email']];
        }

        // check against rtr-ecg list
        if (count($emails) && $this->_getSetting('checkRtrList')) {
            $badKeys = Kwf_Util_RtrList::getBadKeys($emails);

            // remove the bad rtr entries from the list
            if ($badKeys) {
                foreach ($badKeys as $badKey) {
                    $ret['rtrExcluded'][] = $emails[$badKey];
                    unset($import[$badKey]);
                }
            }
        }

        // add to model
        $queueModel = $this->getData()->parent->getComponent()->getChildModel()->getDependentModel('Queues');
        $queueModel->import(Kwf_Model_Db::FORMAT_ARRAY, $import, array('ignore' => true));
        return $ret;
    }

    /**
     * @param string $modelName
     * @return null|string
     */
    private function _getRecipientModelShortcutFromModel(Kwc_Mail_Abstract_Component $mail, $modelName)
    {
        $ret = null;
        foreach ($mail->getRecipientSources() as $shortcut => $recipientSource) {
            if ($recipientSource['model'] === $modelName) {
                $ret = $shortcut;
                break;
            }
        }
        return $ret;
    }
}
