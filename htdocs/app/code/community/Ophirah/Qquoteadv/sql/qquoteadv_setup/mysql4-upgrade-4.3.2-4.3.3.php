<?php

$installer = $this;
$installer->startSetup();

// Add substatus
$this->run("
DROP TABLE IF EXISTS  `{$installer->getTable('quoteadv_log_admin')}`;

CREATE TABLE `{$installer->getTable('quoteadv_log_admin')}` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `admin_id` bigint(20) unsigned NOT NULL COMMENT 'Visitor ID',
  `session_id` varchar(64) DEFAULT NULL COMMENT 'Session ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Quoteadv Log Admin Table';

");

$installer->endSetup();