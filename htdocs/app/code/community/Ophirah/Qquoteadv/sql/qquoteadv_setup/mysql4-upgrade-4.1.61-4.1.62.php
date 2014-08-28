<?php

$installer = $this;
$installer->startSetup();

// Add substatus
$this->run("
    ALTER TABLE `{$this->getTable('quoteadv_customer')}` ADD `substatus` VARCHAR(40) DEFAULT NULL AFTER `status`;
");

$installer->endSetup();
