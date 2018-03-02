<?php
class KwcNewsletter_Kwc_Newsletter_Subscribe_MailEditable_Events extends Kwc_Abstract_Events
{
    public function getListeners()
    {
        $ret = parent::getListeners();
        $ret[] = array(
            'class' => Kwc_Abstract::getChildComponentClass($this->_class, 'content'),
            'event' => 'Kwf_Component_Event_Component_HasContentChanged',
            'callback' => 'onContentChanged'
        );
        return $ret;
    }

    public function onContentChanged(Kwf_Component_Event_Component_HasContentChanged $event)
    {
        $this->fireEvent(
            new Kwf_Component_Event_Component_ContentChanged($this->_class, $event->component->parent)
        );
    }
}
