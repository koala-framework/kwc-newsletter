CREATE TABLE IF NOT EXISTS `kwc_newsletter_deleted_subscriber_hashes` (
  `id` char(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `kwc_newsletter_deleted_subscriber_hashes`
 ADD PRIMARY KEY (`id`);
