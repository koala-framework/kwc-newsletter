




<?php
class KwcNewsletter_Kwc_Newsletter_Update_20150309Legacy35008 extends Kwf_Update
{
    public function update()
    {
        $ret = parent::update();

        $db = Kwf_Registry::get('db');
        if ($db->query("SHOW COLUMNS FROM `kwc_newsletter_queue_log` LIKE 'searchtext'")->fetchColumn()) {
            $db->query("ALTER TABLE  `kwc_newsletter_queue` ADD  `send_process_pid` INT NULL;");
            $db->query("DELETE FROM`kwc_newsletter_queue` WHERE `status` = 'sending';");
            $db->query("ALTER TABLE `kwc_newsletter_queue` DROP `status`;");
            $db->query("ALTER TABLE  `kwc_newsletter` ADD  `resume_date` DATETIME NULL;");
            $db->query("ALTER TABLE  `kwc_newsletter_queue_log` ADD INDEX  `count` (  `newsletter_id` ,  `send_date` );");
            $db->query("ALTER TABLE  `kwc_newsletter` ADD INDEX (  `status` );");
            $db->query("ALTER TABLE `kwc_newsletter_queue_log` DROP `searchtext`;");
        }

        return $ret;
    }
}
