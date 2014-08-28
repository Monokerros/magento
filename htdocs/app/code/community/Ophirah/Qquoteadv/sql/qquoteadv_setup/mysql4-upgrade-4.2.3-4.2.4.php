<?php

$installer = $this;
$installer->startSetup();

// Add substatus
$this->run("
    ALTER TABLE `{$this->getTable('quoteadv_quote_address')}` ADD `vat_id` text DEFAULT null;
    ALTER TABLE `{$this->getTable('quoteadv_quote_address')}` ADD `vat_is_valid` smallint(6) DEFAULT null;
    ALTER TABLE `{$this->getTable('quoteadv_quote_address')}` ADD `vat_request_id` text DEFAULT null;
    ALTER TABLE `{$this->getTable('quoteadv_quote_address')}` ADD `vat_request_date` text DEFAULT null;
    ALTER TABLE `{$this->getTable('quoteadv_quote_address')}` ADD `vat_request_success` smallint(6) DEFAULT null;
");

$installer->endSetup();