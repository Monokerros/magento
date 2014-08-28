<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();
//$installer->run("ALTER TABLE {$this->getTable('quoteadv_customer')} ADD `increment_id` varchar(50) NOT NULL DEFAULT '' AFTER `store_id`;");

//this line is more save if increment_id already exists
$installer->getConnection()->addColumn($installer->getTable('quoteadv_customer'),
    'increment_id',
    'VARCHAR( 50 ) NOT NULL DEFAULT "" AFTER store_id');

$installer->endSetup(); 
