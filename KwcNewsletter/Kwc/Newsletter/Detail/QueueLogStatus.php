<?php
class KwcNewsletter_Kwc_Newsletter_Detail_QueueLogStatus extends Kwf_Data_Abstract
{
    public function load($row, array $info = array())
    {
        switch ($row->{$this->getFieldname()}) {
            case 'sent':
                $ret = trlKwf('Sent');
                break;
            case 'failed':
                $ret = trlKwf('Sending failed');
                break;
            case 'usernotfound':
                $ret = trlKwf('Subscriber no longer available');
                break;
            default:
                $ret = null;
        }

        return $ret;
    }
}
