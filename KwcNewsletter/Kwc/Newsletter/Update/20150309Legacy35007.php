<?php
class KwcNewsletter_Kwc_Newsletter_Update_20150309Legacy35007 extends Kwf_Update
{
    public function update()
    {
        $ret = parent::update();

        $db = Kwf_Registry::get('db');
        if (!$db->fetchOne("SHOW TABLES LIKE 'kwc_newsletter_testmail_receiver'")) {
            $db->query("ALTER TABLE  `kwc_newsletter` ADD  `mails_per_minute` VARCHAR( 255 ) NOT NULL , ADD  `start_date` DATETIME NULL;");
            $db->query("ALTER TABLE  `kwc_newsletter` CHANGE  `status`  `status` ENUM(  'start',  'startLater',  'pause',  'stop',  'sending',  'finished' ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;");

            $sql = "CREATE TABLE IF NOT EXISTS `kwc_newsletter_testmail_receiver` (
            `id` INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
            `email` VARCHAR( 255 ) NOT NULL ,
            `newsletter_component_id` VARCHAR( 255 ) NOT NULL ,
            `last_sent_date` DATETIME NOT NULL
            ) ENGINE = INNODB CHARACTER SET utf8 COLLATE utf8_general_ci;";
            $db->query($sql);
        }

        return $ret;
    }
}
