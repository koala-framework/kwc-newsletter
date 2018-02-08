<?php
class KwcNewsletter_Kwc_NewsletterCategory_Detail_Component extends KwcNewsletter_Kwc_Newsletter_Detail_Component
{
    public static function getSettings($param = null)
    {
        $ret = parent::getSettings($param);
        $ret['generators']['mail']['component'] = 'KwcNewsletter_Kwc_NewsletterCategory_Detail_Mail_Component';
        return $ret;
    }
}
