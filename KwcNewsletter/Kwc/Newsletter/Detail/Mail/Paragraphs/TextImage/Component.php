<?php
class KwcNewsletter_Kwc_Newsletter_Detail_Mail_Paragraphs_TextImage_Component extends Kwc_TextImage_Component
{
    public static function getSettings($param = null)
    {
        $ret = parent::getSettings($param);
        $ret['generators']['child']['component']['text'] =
            'KwcNewsletter_Kwc_Newsletter_Detail_Mail_Paragraphs_TextImage_Text_Component';
        $ret['generators']['child']['component']['image'] =
            'KwcNewsletter_Kwc_Newsletter_Detail_Mail_Paragraphs_TextImage_Image_Component';
        return $ret;
    }
}
