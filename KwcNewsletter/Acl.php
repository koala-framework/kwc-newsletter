<?php

class KwcNewsletter_Acl
{
    public static function initialise($acl)
    {
        $acl->add(new Kwf_Acl_Resource_MenuUrl(
            'kwc-newsletter_controller_api-keys',
            array('text' => trl('Api keys'), 'icon' => 'key.png'),
            '/admin/kwc-newsletter/api-keys'
        ));
        $acl->add(new Zend_Acl_Resource('kwc-newsletter_controller_api-key'), 'kwc-newsletter_controller_api-keys');
    }
}
