<?php
class KwcNewsletter_Kwc_Newsletter_Detail_RecipientsController extends KwcNewsletter_Kwc_Newsletter_Subscribe_RecipientsController
{
    protected $_buttons = array('saveRecipients', 'removeRecipients');

    protected function _initColumns()
    {
        parent::_initColumns();

        $this->_filters['last_activated_date'] = array(
            'type' => 'Date',
            'label' => trlKwf('Activation date before') . ':',
            'skipWhere' => true
        );

        $this->_filters['other_newsletter_id'] = array(
            'type' => 'ComboBox',
            'label' => trlKwf('Other Newsletter').':',
            'width' => 150,
            'listWidth' => 250,
            'skipWhere' => true,
            'data' => $this->_getOtherNewsletters(),
            'defaultText' => trlKwf('all')
        );

        unset($this->_columns['edit']);
    }

    protected function _getOtherNewsletters()
    {
        $ret = array();

        $mailComponentIds = array();
        $select = new Kwf_Model_Select();
        $select->whereEquals('status', 'finished');
        $select->where(new Kwf_Model_Select_Expr_Higher(
            new Kwf_Model_Select_Expr_Field('create_date'),
            new Kwf_Date(strtotime("-1 year"))
        ));
        foreach (Kwf_Model_Abstract::getInstance('KwcNewsletter\Bundle\Model\Newsletters')->export(
            Kwf_Model_Abstract::FORMAT_ARRAY, $select, array('columns' => array('id', 'component_id'))
        ) as $row) {
            $mailComponentIds[] = "{$row['component_id']}_{$row['id']}_mail";
        }

        $select = new Kwf_Model_Select();
        $select->whereEquals('component_id', $mailComponentIds);
        $select->order(new Zend_Db_Expr('LENGTH(component_id)'), 'DESC');
        $select->order('component_id', 'DESC');
        foreach (Kwf_Model_Abstract::getInstance('Kwc_Mail_Model')->export(
            Kwf_Model_Abstract::FORMAT_ARRAY, $select, array('columns' => array('component_id', 'subject'))
        ) as $row) {
            preg_match('#\_([0-9]+)\_mail$#i', $row['component_id'], $match);
            $ret[] = array($match[1], $row['subject']);
        }

        return $ret;
    }

    protected function _isAllowedComponent()
    {
        return Kwf_Controller_Action::_isAllowedComponent();
    }

    protected function _getSelect()
    {
        $ret = parent::_getSelect();
        $mailComponent = $this->_getMailComponent();
        $rs = $mailComponent->getComponent()->getRecipientSources();
        foreach(array_keys($rs) as $key) {
            if ($key == $this->getParam('subscribeModelKey')) {
                $this->_model = Kwf_Model_Abstract::getInstance($rs[$key]['model']);
            }

            if (isset($rs[$key]['select']) && ($rs[$key]['model'] == get_class($this->_getModel()))) {
                $ret->merge($rs[$key]['select']);
            }
        }

        if ($lastActivatedDate = $this->_getParam('query_last_activated_date')) {
            $ret->where(new Kwf_Model_Select_Expr_LowerEqual('last_activated_date', new Kwf_Date($lastActivatedDate)));
        }

        if (($otherNewsletterId = $this->_getParam('query_other_newsletter_id')) && $otherNewsletterId !== 'all') {
            $recipientModelShortcut = null;
            foreach (array_keys($rs) as $key) {
                if ($rs[$key]['model'] == get_class($this->_getModel())) {
                    $recipientModelShortcut = $key;
                    break;
                }
            }
            if (!$recipientModelShortcut) throw new Kwf_Exception_Client(trlKwf('Recipients source of this newsletter doesn\'t exist anymore'));

            $select = new Kwf_Model_Select();
            $select->whereEquals('newsletter_id', $otherNewsletterId);
            $select->whereEquals('recipient_model_shortcut', $recipientModelShortcut);

            $ret->whereEquals('id', array_map(
                function ($row) { return $row['recipient_id']; },
                Kwf_Model_Abstract::getInstance('KwcNewsletter\Bundle\Model\NewsletterQueueLogs')->export(
                    Kwf_Model_Abstract::FORMAT_ARRAY, $select, array('columns' => array('recipient_id'))
                )
            ));
        }

        return $ret;
    }

    protected function _addPluginSelect($select)
    {
        foreach (Kwf_Component_Data_Root::getInstance()->getPlugins('KwcNewsletter_Kwc_Newsletter_PluginInterface') as $plugin) {
            $plugin->modifyRecipientsSelect($select, KwcNewsletter_Kwc_Newsletter_PluginInterface::RECIPIENTS_GRID_TYPE_ADD_TO_QUEUE);
        }
        return $select;
    }

    protected function _getMailComponent()
    {
        $mailComponent = Kwf_Component_Data_Root::getInstance()->getComponentByDbId(
            $this->_getParam('componentId') . '_mail',
            array('ignoreVisible' => true)
        );
        return $mailComponent;
    }
}
