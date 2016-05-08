DROP TABLE IF EXISTS `spms_message`;

CREATE TABLE `spms_message` (
  `id_message` BIGINT unsigned NOT NULL AUTO_INCREMENT,
  `id_queue` BIGINT unsigned NOT NULL,
  `handled` BIGINT,
  `content` text NOT NULL,
  `checksum` char(32) NOT NULL DEFAULT '',
  `timeout` BIGINT NOT NULL,
  `created` BIGINT NOT NULL,
  `log` text,
  PRIMARY KEY (`id_message`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `spms_queue`;

CREATE TABLE `spms_queue` (
  `id_queue` BIGINT unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) UNIQUE NOT NULL,
  `created` BIGINT NOT NULL,
  PRIMARY KEY (`id_queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `spms_log`;

CREATE TABLE `spms_log` (
  `id_log` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `created` int(32) NOT NULL,
  `message` text NOT NULL,
  PRIMARY KEY (`id_log`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

