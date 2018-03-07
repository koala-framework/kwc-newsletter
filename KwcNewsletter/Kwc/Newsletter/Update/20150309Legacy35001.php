<?php
class KwcNewsletter_Kwc_Newsletter_Update_20150309Legacy35001 extends Kwf_Update
{
    public function update()
    {
        $ret = parent::update();

        $db = Kwf_Registry::get('db');
        if (!$db->fetchOne("SHOW TABLES LIKE 'kwc_newsletter_subscribecategories'")) {
            $db->query("RENAME TABLE kwc_newsletter_categories TO kwc_newsletter_subscribecategories;");
            $db->query("ALTER TABLE `kwc_newsletter_subscribecategories` CHANGE `category` `name` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;");
            $db->query("ALTER TABLE `kwc_newsletter_subscribecategories` CHANGE `kwf_pool_id` `category_id` SMALLINT( 5 ) UNSIGNED NOT NULL;");
        }

        $sql = "CREATE TABLE IF NOT EXISTS `kwc_newsletter_categories` (
          `id` int(10) unsigned NOT NULL auto_increment,
          `pos` smallint(6) NOT NULL,
          `category` varchar(255) NOT NULL,
          PRIMARY KEY  (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        $db->query($sql);

        if (!$db->fetchOne("SHOW TABLES LIKE 'kwc_newsletter_subscribers_to_category'")) {
            $db->query("RENAME TABLE `kwc_newsletter_subscribers_to_pool`  TO `kwc_newsletter_subscribers_to_category`;");
            $db->query("ALTER TABLE `kwc_newsletter_subscribers_to_category` CHANGE `pool_id` `category_id` SMALLINT UNSIGNED NOT NULL;");
        }

        return $ret;
    }
}
