<?php
class KwcNewsletter_Kwc_Newsletter_Detail_QueueLogsController extends Kwf_Controller_Action_Auto_Grid
{
    protected $_buttons = array();
    protected $_sortable = false;
    protected $_defaultOrder = 'id';
    protected $_paging = 20;
    protected $_model = 'KwcNewsletter\Bundle\Model\NewsletterQueueLogs';
    private $_newsletterRow;

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

        $this->_filters['text'] = array(
            'type' => 'TextField',
            'width' => 200,
            'label' => trlKwf('Filter') . ':',
            'skipWhere' => true,
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

    protected function _getSelect()
    {
        $select = parent::_getSelect();
        $select->whereEquals('newsletter_id', $this->_getNewsletterRow()->id);

        if ($query = $this->getParam('query')) {
            $querySelect = new Kwf_Model_Select();
            $querySelect->where(new Kwf_Model_Select_Expr_Or(array(
                new Kwf_Model_Select_Expr_Contains('email', $query),
                new Kwf_Model_Select_Expr_Contains('firstname', $query),
                new Kwf_Model_Select_Expr_Contains('lastname', $query),
            )));
            $select->where(new Kwf_Model_Select_Expr_Parent_Contains('Subscriber', $querySelect));
        }

        return $select;
    }

    private function _getNewsletterRow()
    {
        if (!$this->_newsletterRow) {
            $component = Kwf_Component_Data_Root::getInstance()->getComponentByDbId(
                $this->_getParam('componentId')
            );
            $this->_newsletterRow = $component->row;
        }
        return $this->_newsletterRow;
    }
}
