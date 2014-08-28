<?php
/*
 * Cart2Quote CRM addon module
 * 
 * This addon module needs Cart2Quote
 * To be installed and configured proparly
 * 
 */

$installer = $this;

$installer->startSetup();

$installer->run("
    DROP TABLE IF EXISTS `{$installer->getTable('quoteadv_crmaddon_messages')}`;
    DROP TABLE IF EXISTS `{$installer->getTable('quoteadv_crmaddon_templates')}`;

    CREATE TABLE `{$installer->getTable('quoteadv_crmaddon_messages')}` (
            `message_id` int(10) unsigned NOT NULL auto_increment,
            `created_at` datetime NOT NULL default '0000-00-00 00:00:00',
            `updated_at` datetime NOT NULL default '0000-00-00 00:00:00',        
            `quote_id` int(10) unsigned default '0',
            `status` tinyint(1) NOT NULL default '1',
            `email_address` text default NULL,
            `subject` varchar(255) default NULL,
            `template_id` int(10) unsigned default '0',        
            `message` text default NULL,        
            PRIMARY KEY  (`message_id`),
            KEY `FK_ quoteadv_crmaddon_messages_quote_id` (`quote_id`),
            KEY `FK_ quoteadv_crmaddon_messages_template_id` (`template_id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='CRMaddon messages';

    ALTER TABLE `{$installer->getTable('quoteadv_crmaddon_messages')}`
        ADD CONSTRAINT `FK_ quoteadv_crmaddon_messages_quote_id` FOREIGN KEY (`quote_id`) REFERENCES `{$installer->getTable('quoteadv_customer')}` (`quote_id`) ON DELETE CASCADE ON UPDATE CASCADE,
        ADD CONSTRAINT `FK_ quoteadv_crmaddon_messages_template_id` FOREIGN KEY (`template_id`) REFERENCES `{$installer->getTable('quoteadv_crmaddon_templates')}` (`template_id`) ON DELETE CASCADE ON UPDATE CASCADE
    ;

    CREATE TABLE `{$installer->getTable('quoteadv_crmaddon_templates')}` (
            `template_id` int(10) unsigned NOT NULL auto_increment,
            `name` varchar(255) default NULL,
            `subject` text default NULL,
            `template` text default NULL,
            `default` tinyint(1) NOT NULL default '0',        
            `status` tinyint(1) NOT NULL default '1',	
            PRIMARY KEY  (`template_id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='CRMaddon templates';
    
");

$installer->endSetup();