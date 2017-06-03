DROP TABLE IF EXISTS `error_log`;

CREATE TABLE `error_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `datetime` datetime DEFAULT NULL,
  `error` text,
  `error_hash` varchar(40) DEFAULT NULL,
  `error_type` varchar(255) DEFAULT NULL,
  `error_file` text,
  `url` text,
  `file` text,
  `log_type` varchar(255) DEFAULT NULL,
  `imported_date` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `error_hash` (`error_hash`),
  KEY `error_type` (`error_type`),
  KEY `datetime` (`datetime`),
  KEY `datetime_2` (`datetime`,`error_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `error_log_status`;

CREATE TABLE `error_log_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `error_log_hash` varchar(40) DEFAULT NULL,
  `resolved_date` datetime DEFAULT NULL,
  `first_occurence_datetime` datetime DEFAULT NULL,
  `last_occurence_datetime` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `error_log_hash` (`error_log_hash`),
  KEY `resolved_date` (`resolved_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;