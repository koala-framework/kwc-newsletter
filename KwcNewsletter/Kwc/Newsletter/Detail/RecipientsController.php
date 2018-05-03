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

        unset($this->_columns['edit']);
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
            if (isset($rs[$key]['select']) && ($rs[$key]['model'] == get_class($this->_getModel()))) {
                $ret->merge($rs[$key]['select']);
            }
        }

        if ($lastActivatedDate = $this->_getParam('query_last_activated_date')) {
            $ret->where(new Kwf_Model_Select_Expr_LowerEqual('last_activated_date', new Kwf_Date($lastActivatedDate)));
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
