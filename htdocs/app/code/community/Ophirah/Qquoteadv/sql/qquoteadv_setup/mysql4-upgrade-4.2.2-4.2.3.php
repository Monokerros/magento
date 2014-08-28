<?php

$installer = $this;
$setup = new Mage_Eav_Model_Entity_Setup('core_setup');
$installer->startSetup();

// Add Alternative Checkout
$this->run("
    ALTER TABLE `{$this->getTable('quoteadv_customer')}` ADD `alt_checkout` TINYINT(1) DEFAULT '0' AFTER `itemprice`;
");

$installer->endSetup();
