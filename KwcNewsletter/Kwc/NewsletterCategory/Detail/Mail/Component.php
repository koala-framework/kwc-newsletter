<?php
class KwcNewsletter_Kwc_NewsletterCategory_Detail_Mail_Component extends KwcNewsletter_Kwc_Newsletter_Detail_Mail_Component
{
    public static function getSettings($param = null)
    {
        $ret = parent::getSettings($param);
        $ret['recipientSources']['n']['model'] = 'KwcNewsletter\Bundle\Model\Subscribers';
        return $ret;
    }
}
