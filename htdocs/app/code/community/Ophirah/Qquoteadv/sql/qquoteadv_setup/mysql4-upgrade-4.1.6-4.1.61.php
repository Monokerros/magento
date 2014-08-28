<?php

$installer = $this;
$installer->startSetup();

// Adding follow up columns
$this->run("
    ALTER TABLE `{$this->getTable('quoteadv_customer')}` ADD `followup` date AFTER `reminder`;
    ALTER TABLE `{$this->getTable('quoteadv_customer')}` ADD `no_followup` tinyint(1) default '0' AFTER `no_reminder`;    
");


$installer->endSetup();
