ALTER TABLE `kwc_newsletter_queues` ADD `create_date` DATETIME NOT NULL ;
UPDATE `kwc_newsletter_queues` SET `create_date` = NOW();
