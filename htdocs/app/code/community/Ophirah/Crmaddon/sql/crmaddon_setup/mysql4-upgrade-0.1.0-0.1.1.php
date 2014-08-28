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

$messageBody = "&lt;p&gt;&lt;strong&gt;Hello {{var CRMcustomername}},&lt;/strong&gt;&lt;br /&gt;&lt;br /&gt;This is the default template for CRM messages to send to the customer. Edit or update this CRM template and save it as a new template. This way you can add as many CRM templates as you want.&lt;br /&gt;&lt;br /&gt;With the WYSYWIG editor it&#039;s even possible to use default markup in the CRM templates and messages!&lt;br /&gt;You can use:&lt;br /&gt;&lt;br /&gt;&lt;strong&gt;Bold&lt;/strong&gt;&lt;br /&gt;&lt;em&gt;Italic&lt;/em&gt;&lt;br /&gt;&lt;span style=&quot;text-decoration: underline;&quot;&gt;Underlined&lt;/span&gt;&lt;br /&gt;&lt;br /&gt;If you to use custom variables as the customer name and the sender name, you can use: - &lt;em&gt;between double curly braces {{ &amp;hellip; }}&amp;nbsp; &lt;/em&gt;:&lt;br /&gt;&lt;br /&gt;var CRMcustomername&lt;br /&gt;var CRMsendername&lt;br /&gt;&lt;br /&gt;It&#039;s all here in the CRM addon module!!&lt;br /&gt;&lt;br /&gt;with Kind Regards,&lt;br /&gt;{{var CRMsendername}}&lt;/p&gt;";

$sql = "INSERT INTO `{$this->getTable('quoteadv_crmaddon_templates')}` (`name`,`subject`,`template`,`default`, `status`) VALUES ('Default Template', 'Default Template Subject', '{$messageBody}', 1, 1)";
$connection = Mage::getSingleton('core/resource')->getConnection('core_write');
$connection->query($sql);

$installer->endSetup();
