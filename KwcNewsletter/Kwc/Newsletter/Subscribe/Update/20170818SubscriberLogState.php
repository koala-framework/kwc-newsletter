
<?php
class KwcNewsletter_Kwc_Newsletter_Subscribe_Update_20170818SubscriberLogState extends Kwf_Update
{
    public function update()
    {
        $ret = parent::update();

        $db = Kwf_Registry::get('db');
        if (!$db->query("SHOW COLUMNS FROM `kwc_newsletter_subscriber_logs` LIKE 'state'")->fetchColumn()) {
            $db->query("ALTER TABLE `kwc_newsletter_subscriber_logs` ADD `state` ENUM('subscribed', 'activated', 'unsubscribed') NULL AFTER `ip`;");
        }

        return $ret;
    }
}
