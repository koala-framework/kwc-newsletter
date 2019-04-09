CREATE TABLE IF NOT EXISTS `kwc_newsletter_api_keys` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `newsletter_component_id` varchar(200) NOT NULL,
  `name` varchar(255) NOT NULL,
  `key` varchar(36) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
