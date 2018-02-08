<?php
class KwcNewsletter_Kwc_Newsletter_Model extends Kwf_Model_Db_Proxy
{
    protected $_table = 'kwc_newsletter';
    protected $_rowClass = 'KwcNewsletter_Kwc_Newsletter_Row';
    protected $_dependentModels = array(
        'Queue' => 'KwcNewsletter_Kwc_Newsletter_QueueModel',
        'QueueLog' => 'KwcNewsletter_Kwc_Newsletter_QueueLogModel',
        'Log' => 'KwcNewsletter_Kwc_Newsletter_LogModel',
        'Mail' => 'Kwc_Mail_Model'
    );

    /**
     * @deprecated
     * If you need to override send to enforce a specific start time that should be implemented using sendLater
     */
    public final function send($timeLimit = 60, $debugOutput = false)
    {}
}
