<?php
class KwcNewsletter_Kwc_NewsletterCategory_Component extends KwcNewsletter_Kwc_Newsletter_Component
{
    public static function getSettings($param = null)
    {
        $ret = parent::getSettings($param);
        $ret['generators']['detail']['component'] =
            'KwcNewsletter_Kwc_NewsletterCategory_Detail_Component';

        // wird von der Mail_Redirect gerendered
        $ret['generators']['editSubscriber']['component'] =
            'KwcNewsletter_Kwc_NewsletterCategory_EditSubscriber_Component';

        $ret['menuConfig'] = 'KwcNewsletter_Kwc_NewsletterCategory_MenuConfig';
        return $ret;
    }
}
