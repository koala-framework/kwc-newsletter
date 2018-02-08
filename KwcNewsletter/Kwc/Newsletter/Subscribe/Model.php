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
    }
}
