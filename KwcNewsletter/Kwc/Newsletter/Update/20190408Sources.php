<?php
use \KwcNewsletter\Bundle\Model\Subscribers;

class KwcNewsletter_Kwc_Newsletter_Update_20190408Sources extends Kwf_Update
{
    public function update()
    {
        $db = Kwf_Registry::get('db');

        $db->query('ALTER TABLE `kwc_newsletter_categories` ADD `newsletter_source` VARCHAR(255) NOT NULL AFTER `newsletter_component_id`, ADD INDEX (`newsletter_source`);');
        $db->query('ALTER TABLE `kwc_newsletter_subscribers` ADD `newsletter_source` VARCHAR(255) NOT NULL AFTER `newsletter_component_id`, ADD INDEX (`newsletter_source`);');

        $db->query('UPDATE `kwc_newsletter_categories` SET `newsletter_source` = "' . Subscribers::DEFAULT_NEWSLETTER_SOURCE . '"');
        $db->query('UPDATE `kwc_newsletter_subscribers` SET `newsletter_source` = "' . Subscribers::DEFAULT_NEWSLETTER_SOURCE . '"');

    }

}
