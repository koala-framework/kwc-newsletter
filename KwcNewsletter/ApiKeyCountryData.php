<?php

class KwcNewsletter_ApiKeyCountryData extends Kwf_Data_Abstract
{
    public function load($row, array $info = array())
    {
        $subroot = Kwf_Component_Data_Root::getInstance()->getComponentByDbId($row->newsletter_component_id)->getSubroot();
        return $subroot->name;
    }
}
