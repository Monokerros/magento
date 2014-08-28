<?php

$installer = $this;
$installer->startSetup();

// Add substatus
$this->run("
    ALTER TABLE `{$this->getTable('quoteadv_customer')}` ADD `itemprice` TINYINT(1) DEFAULT '1' AFTER `followup`;
");

$installer->endSetup();
