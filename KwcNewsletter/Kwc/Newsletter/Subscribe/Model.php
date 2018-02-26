<?php
class KwcNewsletter_Kwc_Newsletter_Subscribe_Model extends Kwf_Model_Db
{
    protected $_table = 'kwc_newsletter_subscribers';
    protected $_rowClass = 'KwcNewsletter_Kwc_Newsletter_Subscribe_Row';

    protected $_columnMappings = array(
        'Kwc_Mail_Recipient_Mapping' => array(
            'firstname' => 'firstname',
            'lastname' => 'lastname',
            'email' => 'email',
            'format' => null,
            'gender' => 'gender',
            'title' => 'title',
        ),
        'Kwc_Mail_Recipient_UnsubscribableMapping' => array(
            'unsubscribed' => 'unsubscribed',
        ),
    );

    protected function _init()
    {
        $this->_dependentModels['Logs'] = 'KwcNewsletter_Kwc_Newsletter_Subscribe_LogsModel';
        parent::_init();

        $abstractSelect = new \Kwf_Model_Select();
        $abstractSelect->order('date', 'DESC');

        $select = clone $abstractSelect;
        $select->whereEquals('state', 'subscribed');
        $this->_exprs['last_subscribe_date'] = new Kwf_Model_Select_Expr_Child_First('Logs', 'date', $select);

        $select = clone $abstractSelect;
        $select->whereEquals('state', 'activated');
        $this->_exprs['last_activated_date'] = new Kwf_Model_Select_Expr_Child_First('Logs', 'date', $select);

        $select = clone $abstractSelect;
        $select->whereEquals('state', 'unsubscribed');
        $this->_exprs['last_unsubscribe_date'] = new Kwf_Model_Select_Expr_Child_First('Logs', 'date', $select);
    }
}
