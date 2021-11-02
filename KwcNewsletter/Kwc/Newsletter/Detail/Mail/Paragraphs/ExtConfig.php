<?php
class KwcNewsletter_Kwc_Newsletter_Detail_Mail_Paragraphs_ExtConfig extends Kwc_Paragraphs_ExtConfig
{
    protected function _getConfig()
    {
        $ret = parent::_getConfig();
        $ret['paragraphs']['xtype'] = 'kwc.newsletter.detail.mail.paragraphs';
        return $ret;
    }
}
