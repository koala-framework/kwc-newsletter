<?php
namespace KwcNewsletter\Bundle\Importer\Excluder;

interface ExcluderInterface
{
    /**
     * @param string $email
     * @return boolean
     */
    public function isExcluded($email);
}
