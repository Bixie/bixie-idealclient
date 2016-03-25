CREATE TABLE IF NOT EXISTS `ide_idealcheckout` (
  `id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` varchar(64) DEFAULT NULL,
  `order_code` varchar(64) DEFAULT NULL,
  `order_params` text,
  `store_code` varchar(64) DEFAULT NULL,
  `gateway_code` varchar(64) DEFAULT NULL,
  `language_code` varchar(2) DEFAULT NULL,
  `country_code` varchar(2) DEFAULT NULL,
  `currency_code` varchar(3) DEFAULT NULL,
  `transaction_id` varchar(64) DEFAULT NULL,
  `transaction_code` varchar(64) DEFAULT NULL,
  `transaction_params` text,
  `transaction_date` int(11) unsigned DEFAULT NULL,
  `transaction_amount` decimal(10,2) unsigned DEFAULT NULL,
  `transaction_description` varchar(100) DEFAULT NULL,
  `transaction_status` varchar(16) DEFAULT NULL,
  `transaction_url` varchar(255) DEFAULT NULL,
  `transaction_payment_url` varchar(255) DEFAULT NULL,
  `transaction_success_url` varchar(255) DEFAULT NULL,
  `transaction_pending_url` varchar(255) DEFAULT NULL,
  `transaction_failure_url` varchar(255) DEFAULT NULL,
  `transaction_notify_url` varchar(255) DEFAULT NULL,
  `transaction_log` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;