<?php
class KwcNewsletter_Kwc_Newsletter_Update_20150309Legacy35005 extends Kwf_Update
{
    public function update()
    {
        $ret = parent::update();

        $db = Kwf_Registry::get('db');
        if ($db->query("SHOW COLUMNS FROM `kwc_newsletter_queue` LIKE 'sent_date'")->fetchColumn()) {
            $db->query("ALTER TABLE `kwc_newsletter` ADD `count_sent` INT NULL ;");
            $db->query("ALTER TABLE `kwc_newsletter` ADD `last_sent_date` DATETIME NULL ;");

            $sql = "UPDATE `kwc_newsletter` n SET count_sent = ( SELECT count( * )
            FROM kwc_newsletter_queue q
            WHERE newsletter_id = n.id
            AND STATUS = 'sent' ) ;";
            $db->query($sql);

            $sql = "UPDATE `kwc_newsletter` n SET last_sent_date = ( SELECT max(sent_date)
            FROM kwc_newsletter_queue q
            WHERE newsletter_id = n.id
            AND STATUS = 'sent' ) ;";
            $db->query($sql);

            $db->query("DELETE FROM `kwc_newsletter_queue` WHERE STATUS = 'sent';");
            $db->query("DELETE FROM `kwc_newsletter_queue` WHERE STATUS = 'sending';");
            $db->query("DELETE FROM `kwc_newsletter_queue` WHERE STATUS = 'userNotFound';");
            $db->query("ALTER TABLE `kwc_newsletter_queue` CHANGE `status` `status` ENUM( 'queued', 'sending' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'queued';");
            $db->query("ALTER TABLE `kwc_newsletter_queue` DROP `sent_date`;");
        }

        return $ret;
    }
}
