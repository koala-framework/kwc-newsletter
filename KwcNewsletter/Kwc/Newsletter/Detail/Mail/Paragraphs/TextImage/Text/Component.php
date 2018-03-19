<?php
class KwcNewsletter_Kwc_Newsletter_Detail_Mail_Paragraphs_TextImage_Text_Component extends Kwc_Basic_Text_Component
{
    public static function getSettings($param = null)
    {
        $ret = parent::getSettings($param);
        $ret['rootElementClass'] = '';
        $ret['generators']['child']['component']['link'] = 'KwcNewsletter_Kwc_Newsletter_Detail_Mail_Paragraphs_LinkTag_Component';
        return $ret;
    }
}
