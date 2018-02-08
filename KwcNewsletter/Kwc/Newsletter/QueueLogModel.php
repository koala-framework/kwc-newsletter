<?php
class KwcNewsletter_Kwc_Newsletter_QueueLogModel extends Kwf_Model_Db_Proxy
{
    protected $_table = 'kwc_newsletter_queue_log';
    protected $_referenceMap = array(
        'Newsletter' => array(
            'column' => 'newsletter_id',
            'refModelClass' => 'KwcNewsletter_Kwc_Newsletter_Model'
        )
    );
}
