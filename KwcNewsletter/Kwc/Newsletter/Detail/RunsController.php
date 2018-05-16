<?php
class KwcNewsletter_Kwc_Newsletter_Detail_RunsController extends Kwf_Controller_Action_Auto_Grid
{
    protected $_buttons = array();
    protected $_modelName = 'KwcNewsletter\Bundle\Model\NewsletterRuns';
    protected $_paging = 25;
    protected $_defaultOrder = array('field' => 'start', 'direction' => 'DESC');

    protected function _initColumns()
    {
        parent::_initColumns();

        $this->_columns->add(new Kwf_Grid_Column_Datetime('start', 'Start'));
        $this->_columns->add(new Kwf_Grid_Column('status', 'Status', 80));
        $this->_columns->add(new Kwf_Grid_Column('runtime', 'Runtime', 50));
        $this->_columns->add(new Kwf_Grid_Column('log', 'Log', 400));
    }

    protected function _getSelect()
    {
        $ret = parent::_getSelect();
        $ret->whereEquals('newsletter_id', $this->_getParam('newsletterId'));
        return $ret;
    }

    protected function _isAllowed($user)
    {
        return $user && $user->role === 'admin';
    }
}

