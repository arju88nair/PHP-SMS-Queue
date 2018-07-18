CREATE TABLE `queue` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `body` varchar(255) DEFAULT NULL,
  `smsfrom` int(100) DEFAULT NULL,
  `smsto` int(100) DEFAULT NULL,
  `udh` varchar(255) DEFAULT NULL,
  `queuedDate` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;