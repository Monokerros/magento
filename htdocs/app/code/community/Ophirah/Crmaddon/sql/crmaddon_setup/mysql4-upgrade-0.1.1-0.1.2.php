<?php
/*
 * Cart2Quote CRM addon module
 * 
 * This addon module needs Cart2Quote
 * To be installed and configured properly
 * 
 */
$installer = $this;
$installer->startSetup();

// Insert new Column
$sql = "ALTER TABLE `{$this->getTable('quoteadv_crmaddon_messages')}` ADD `customer_notified` TINYINT(1) DEFAULT NULL AFTER `status`";
$result = $installer->getConnection()->query($sql);


// If messages exist, update status
// to 'customer notified'
$sql = "SELECT `message_id` , `customer_notified` FROM `{$this->getTable('quoteadv_crmaddon_messages')}` WHERE `customer_notified` IS NULL";
$result = $installer->getConnection()->query($sql);

if (isset($result)):
    foreach ($result as $item) {

        $update = "UPDATE {$this->getTable('quoteadv_crmaddon_messages')} SET `customer_notified`='1' WHERE (`message_id`='{$item['message_id']}')";
        $installer->run($update);
    }

endif;
$installer->endSetup();
