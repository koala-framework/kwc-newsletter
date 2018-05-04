<?php
namespace KwcNewsletter\Bundle\Model\Row;

class NewsletterQueues extends \Kwf_Model_Proxy_Row
{
    public function getRecipient()
    {
        $componentId = "{$this->getParentRow('Newsletter')->component_id}_{$this->newsletter_id}_mail";
        $c = \Kwf_Component_Data_Root::getInstance()->getComponentByDbId($componentId, array('ignoreVisible' => true));

        return $c->getComponent()->getRecipientFromShortcut($this->recipient_model_shortcut, $this->recipient_id);
    }

    protected function _beforeDelete()
    {
        $newsletter = $this->getParentRow('Newsletter');
        if (in_array($newsletter->status, array('start', 'stop', 'finished'))) {
            throw new \Kwf_ClientException(trlKwf('Can only add users to a paused newsletter'));
        }

        parent::_beforeDelete();
    }
}
