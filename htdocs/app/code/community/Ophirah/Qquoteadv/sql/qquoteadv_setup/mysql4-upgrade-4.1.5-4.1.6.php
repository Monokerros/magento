<?php

$installer = $this;
$installer->startSetup();

// Extra Options table
// To be used to create a custom option field
$this->run("
  DROP TABLE IF EXISTS `{$this->getTable('quoteadv_extraoptions')}`;

  CREATE TABLE `{$this->getTable('quoteadv_extraoptions')}` (
    `option_id` int(10) unsigned NOT NULL auto_increment,
    `option_type` int(10) DEFAULT NULL,
    `value` TEXT DEFAULT NULL,
    `label` TEXT DEFAULT NULL,
    `order` int(10) DEFAULT NULL,
    `title` int(10) DEFAULT NULL,
    `status` tinyint(1) NOT NULL default '1',
    PRIMARY KEY  (`option_id`)
  ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='Quotes';  
");

// Extra Email settings, Trial Hash and Salesrule
$this->run("
    ALTER TABLE `{$this->getTable('quoteadv_customer')}` ADD `proposal_sent` datetime NOT NULL default '0000-00-00 00:00:00' AFTER `created_at`;
    ALTER TABLE `{$this->getTable('quoteadv_customer')}` ADD `no_reminder` tinyint(1) default '0' AFTER `no_expiry`;
    ALTER TABLE `{$this->getTable('quoteadv_customer')}` ADD `reminder` date AFTER `expiry`;
    ALTER TABLE `{$this->getTable('quoteadv_customer')}` ADD `create_hash` VARCHAR(40) DEFAULT NULL AFTER `hash`;
    ALTER TABLE `{$this->getTable('quoteadv_customer')}` ADD `salesrule` INT DEFAULT NULL AFTER `status`;
");

$installer->endSetup();
