<?php
namespace KwcNewsletter\Bundle\Model\Row;

class NewsletterQueueLogs extends \Kwf_Model_Proxy_Row
{
    public function getRecipient()
    {
        $componentId = "{$this->getParentRow('Newsletter')->component_id}_{$this->newsletter_id}_mail";
        $c = \Kwf_Component_Data_Root::getInstance()->getComponentByDbId($componentId, array('ignoreVisible' => true));

        return $c->getComponent()->getRecipientFromShortcut($this->recipient_model_shortcut, $this->recipient_id);
    }
}
