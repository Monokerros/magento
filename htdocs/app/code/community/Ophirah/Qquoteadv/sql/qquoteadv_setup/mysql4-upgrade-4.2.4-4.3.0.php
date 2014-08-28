<?php

$installer = $this;
$installer->startSetup();

// Add substatus
$this->run("
    ALTER TABLE `{$this->getTable('quoteadv_shipping_rate')}` ADD `active` TINYINT(1) DEFAULT '1' AFTER `address_id`;
");

$installer->endSetup();