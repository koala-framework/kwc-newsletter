<?php
class KwcNewsletter_Kwc_NewsletterCategory_Subscribe_Model extends KwcNewsletter_Kwc_Newsletter_Subscribe_Model
{
    protected $_dependentModels = array(
        'ToCategory' => 'KwcNewsletter_Kwc_NewsletterCategory_Subscribe_SubscriberToCategory'
    );
}
