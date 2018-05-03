<?php
class KwcNewsletter_Kwc_Newsletter_Detail_QueueLogsController extends Kwf_Controller_Action_Auto_Grid
{
    protected $_buttons = array('xls');
    protected $_sortable = false;
    protected $_defaultOrder = 'id';
    protected $_paging = 20;
    protected $_model = 'KwcNewsletter\Bundle\Model\NewsletterQueueLogs';

    protected function _initColumns()
    {
        parent::_initColumns();

        $this->_filters['status'] = array(
            'type' => 'ComboBox',
            'label' => trlKwf('Status') . ':',
            'width' => 200,
            'defaultText' => trlKwf('all'),
            'data' => array(
                'sent' => trlKwf('Sent'),
                'failed' => trlKwf('Sending failed'),
                'usernotfound' => trlKwf('Subscriber no longer available')
            )
        );

        $this->_columns->add(new Kwf_Grid_Column('email', trlKwf('E-Mail'), 200))
            ->setData(new KwcNewsletter_Kwc_Newsletter_Detail_UserData('email'));
        $this->_columns->add(new Kwf_Grid_Column('firstname', trlKwf('Firstname'), 140))
            ->setData(new KwcNewsletter_Kwc_Newsletter_Detail_UserData('firstname'));
        $this->_columns->add(new Kwf_Grid_Column('lastname', trlKwf('Lastname'), 140))
            ->setData(new KwcNewsletter_Kwc_Newsletter_Detail_UserData('lastname'));
        $this->_columns->add(new Kwf_Grid_Column('status', trlKwf('Status'), 140))
            ->setData(new KwcNewsletter_Kwc_Newsletter_Detail_QueueLogStatus());
    }
}
