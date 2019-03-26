<?php
namespace KwcNewsletter\Bundle\UpdatesProvider;

use KwfBundle\UpdatesProvider\UpdatesProviderInterface;

class UpdatesProvider implements UpdatesProviderInterface
{
    public function getUpdates()
    {
        return \Kwf_Util_Update_Helper::getUpdatesForDir('KwcNewsletter\\Bundle\\Update');
    }
}
