<?php
class KwcNewsletter_Kwc_Newsletter_Subscribe_Update_20170306SubscriberLog extends Kwf_Update
{
    public function update()
    {
        $db = Kwf_Registry::get('db');

        if (!$db->fetchOne("SHOW TABLES LIKE 'kwc_newsletter_subscriber_logs'")) {
            $sql = 'CREATE TABLE IF NOT EXISTS `kwc_newsletter_subscriber_logs` (
              `id` int(11) NOT NULL,
              `subscriber_id` int(11) NOT NULL,
              `date` datetime NOT NULL,
              `ip` varchar(15) NULL,
              `message` text NOT NULL,
              `source` text NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
            $db->query($sql);

            $db->query('ALTER TABLE `kwc_newsletter_subscriber_logs` ADD PRIMARY KEY (`id`), ADD KEY `subscriber_id` (`subscriber_id`);');
            $db->query('ALTER TABLE `kwc_newsletter_subscriber_logs` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;');
        }
    }
}
