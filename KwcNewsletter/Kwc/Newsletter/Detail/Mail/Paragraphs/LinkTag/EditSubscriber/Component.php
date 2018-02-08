<?php
class KwcNewsletter_Kwc_Newsletter_Detail_Mail_Paragraphs_LinkTag_EditSubscriber_Component
    extends KwcNewsletter_Kwc_Newsletter_Detail_Mail_Paragraphs_LinkTag_Abstract_Component
{
    public static function getSettings($param = null)
    {
        $ret = parent::getSettings($param);
        $ret['componentName'] = trlKwfStatic('Newsletter Subscriber settings');
        return $ret;
    }

    protected function _getTargetComponent()
    {
        return $this->_getNewsletterComponent()->getChildComponent('_editSubscriber');
    }
}
