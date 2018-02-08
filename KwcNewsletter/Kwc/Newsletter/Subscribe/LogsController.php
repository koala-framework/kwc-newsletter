<?php
class KwcNewsletter_Kwc_Newsletter_Subscribe_LogsController extends Kwf_Controller_Action_Auto_Grid
{
    protected $_model = 'KwcNewsletter_Kwc_Newsletter_Subscribe_LogsModel';
    protected $_buttons = array();
    protected $_paging = 10;
    protected $_defaultOrder = array(
        'field' => 'date',
        'direction' => 'DESC'
    );

    protected function _initColumns()
    {
        parent::_initColumns();

        $columns = $this->_columns;
        $columns->add(new Kwf_Grid_Column_Datetime('date', trlKwf('Date')));
        $columns->add(new Kwf_Grid_Column('message', trlKwf('Message'), 300))
            ->setRenderer('nl2br');
        $columns->add(new Kwf_Grid_Column('source', trlKwf('Source'), 300));
    }

    protected function _getSelect()
    {
        $ret = parent::_getSelect();
        $ret->whereEquals('subscriber_id', $this->_getParam('subscriberId'));
        return $ret;
    }
}

