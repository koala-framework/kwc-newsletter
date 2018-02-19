<?php
class KwcNewsletter_Kwc_Newsletter_Detail_Mail_Paragraphs_Component extends Kwc_Paragraphs_Component
{
    public static function getSettings($param = null)
    {
        $ret = parent::getSettings($param);
        $ret['componentName'] = trlKwfStatic('Mail');
        $ret['generators']['paragraphs']['component'] = array();
        $ret['generators']['paragraphs']['component']['textImage'] =
            'KwcNewsletter_Kwc_Newsletter_Detail_Mail_Paragraphs_TextImage_Component';

        $cc = Kwf_Registry::get('config')->kwc->childComponents;
        if (isset($cc->KwcNewsletter_Kwc_Newsletter_Detail_Mail_Paragraphs_Component)) {
            $ret['generators']['paragraphs']['component'] = array_merge(
                $ret['generators']['paragraphs']['component'],
                $cc->KwcNewsletter_Kwc_Newsletter_Detail_Mail_Paragraphs_Component->toArray()
            );
        }

        $ret['assetsAdmin']['files'][] = 'kwcNewsletter/KwcNewsletter/Kwc/Newsletter/Detail/Mail/Paragraphs/Panel.js';
        return $ret;
    }
}
