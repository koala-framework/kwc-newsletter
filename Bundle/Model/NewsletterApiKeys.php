<?php

namespace KwcNewsletter\Bundle\Model;

class NewsletterApiKeys extends \Kwf_Model_Db_Proxy
{
    protected $_table = 'kwc_newsletter_api_keys';
    protected $_filters = array(
        'key' => 'Kwf_Filter_Row_GenerateUuid',
    );
}
