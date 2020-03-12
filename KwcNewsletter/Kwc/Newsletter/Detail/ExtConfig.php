<?php
use \KwcNewsletter\Bundle\Model\Subscribers;

class KwcNewsletter_Kwc_Newsletter_Detail_ExtConfig extends Kwf_Component_Abstract_ExtConfig_Form
{
    protected function _getConfig()
    {
        $ret = parent::_getConfig();
        $mailClass = Kwc_Abstract::getChildComponentClass($this->_class, 'mail');
        $mailContentClass = Kwc_Abstract::getChildComponentClass($mailClass, 'content');
        $cfg = Kwc_Admin::getInstance($mailContentClass)->getExtConfig();
        $configs = array();
        $editComponents = array();
        $mainType = null;
        foreach ($cfg as $key => $c) {
            $configs[$mailContentClass . '-' . $key] = $c;
            $editComponents[] = array(
                'componentClass' => $mailContentClass,
                'type' => $key
            );
            if (!$mainType) $mainType = $key;
        }
        $ret['form'] = array_merge($ret['form'], array(
            'xtype' => 'kwc.newsletter.detail.tabpanel',
            'tabs' => array(
                'settings' => array(
                    'xtype'                 => 'kwf.autoform',
                    'controllerUrl'         => $this->getControllerUrl(),
                    'title'                 => trlKwf('Settings')
                ),
                'mail' => array(
                    'xtype'                 => 'kwf.component',
                    'componentEditUrl'      => '/admin/component/edit',
                    'mainComponentClass'    => $mailContentClass,
                    'componentIdSuffix'     => '_mail-content',
                    'componentConfigs'      => $configs,
                    'mainEditComponents'    => $editComponents,
                    'mainType'              => $mainType,
                    'title'                 => trlKwf('Mail')
                ),
                'preview' => array(
                    'xtype'                 => 'kwc.newsletter.detail.preview',
                    'controllerUrl'         => $this->getControllerUrl('Preview'),
                    'recipientsControllerUrl' => $this->getControllerUrl('Recipients'),
                    'authedUserEmail'       => Kwf_Registry::get('userModel')->getAuthedUser() ? Kwf_Registry::get('userModel')->getAuthedUser()->email : '',
                    'title'                 => trlKwf('Preview'),
                    'recipientSources'      => $this->_getRecipientSources(),
                    'newsletterSources'     => $this->_getNewsletterSources(),
                ),
                'mailing' => array(
                    'xtype'                 => 'kwc.newsletter.recipients',
                    'title'                 => trlKwf('Mailing'),
                    'recipientsPanel' => array(
                        'title' => trlKwf('Add/Remove Subscriber to Queue'),
                        'region' => 'center',
                        'xtype' => 'kwf.tabpanel',
                        'activeTab' => 0,
                        'tabs' => $this->_getNewsletterSources()
                    ),
                    'recipientsQueuePanel' => array(
                        'title' => trlKwf('Queue'),
                        'controllerUrl' => $this->getControllerUrl('Mailing'),
                        'region' => 'east',
                        'width' => 500,
                        'xtype' => 'kwc.newsletter.recipients.queue'
                    ),
                    'mailingPanel' => array(
                        'title' => trlKwf('Mailing'),
                        'region' => 'south',
                        'controllerUrl' => $this->getControllerUrl('Mailing'),
                        'formControllerUrl' => $this->getControllerUrl('MailingForm'),
                        'xtype' => 'kwc.newsletter.startNewsletter'
                    )
                ),
                'queueLogs' => array(
                    'xtype'                 => 'kwc.newsletter.detail.queueLogs',
                    'controllerUrl'         => $this->getControllerUrl('QueueLogs'),
                    'title'                 => trlKwf('Recipients')
                ),
                'statistics' => array(
                    'xtype'                 => 'kwf.autogrid',
                    'controllerUrl'         => $this->getControllerUrl('Statistics'),
                    'title'                 => trlKwf('Statistics')
                )
            )
        ));

        $this->_addRunnerTab($ret);

        return $ret;
    }

    protected function _addRunnerTab(array &$config)
    {
        if (Kwf_Registry::get('userModel')->getAuthedUserRole() === 'admin') {
            $config['form']['tabs']['runner'] = array(
                'xtype' => 'kwc.newsletter.runs',
                'controllerUrl' => $this->getControllerUrl('Runs'),
                'formControllerUrl' => $this->getControllerUrl('Run'),
                'title' => trlKwf('Runner')
            );
        }
    }

    protected function _getRecipientSources()
    {
        $mailClass = (Kwc_Abstract::getChildComponentClass($this->_class, 'mail'));
        $recipientSources = Kwc_Abstract::getSetting($mailClass, 'recipientSources');
        foreach ($recipientSources as &$recipientSource) {
            if (isset($recipientSource['title'])) {
                $recipientSource['title'] = Kwf_Trl::getInstance()->trlStaticExecute($recipientSource['title']);
            }
        }
        return $recipientSources;
    }


    protected function _getNewsletterSources()
    {
        $newsletterComponent = Kwf_Component_Data_Root::getInstance()
            ->getComponentByDbId($_REQUEST['componentId'], array('ignoreVisible' => true));

        $ret = array();
        foreach (Subscribers::getSources($newsletterComponent) as $newsletterSourceId => $newsletterSourceName) {
            $ret[$newsletterSourceId] = array(
                'title' => $newsletterSourceName,
                'controllerUrl' => $this->getControllerUrl('Recipients'),
                'xtype' => 'kwc.newsletter.recipients.grid',
                'baseParams' => array(
                    'newsletterSource' => $newsletterSourceId
                )
            );
        }
        return $ret;
    }
}
