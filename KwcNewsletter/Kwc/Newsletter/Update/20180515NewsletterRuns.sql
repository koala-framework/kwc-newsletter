CREATE TABLE `kwc_newsletter_runs` (
  `id` int(11) NOT NULL,
  `newsletter_id` int(11) NOT NULL,
  `status` varchar(20) NOT NULL,
  `start` datetime NOT NULL,
  `runtime` int(11) NOT NULL,
  `log` mediumtext NOT NULL,
  `pid` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `kwc_newsletter_runs` ADD PRIMARY KEY (`id`);

ALTER TABLE `kwc_newsletter_runs` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
