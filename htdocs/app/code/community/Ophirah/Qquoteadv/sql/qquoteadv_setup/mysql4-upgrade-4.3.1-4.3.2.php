<?php

$installer = $this;
$installer->startSetup();

/**
 * Adding Attributes
 */
$setup = new Mage_Eav_Model_Entity_Setup('core_setup');

// Check for existing id
$entityTypeId = (int)$setup->getEntityTypeId('catalog_product');
$id = (int)$setup->getAttributeId('catalog_product', 'group_allow_quotemode');

if ($id == 0) { // Adding Attribute

    $setup->addAttribute('catalog_product', 'group_allow_quotemode', array(
        'group' => 'General',
        'input' => 'text',
        'type' => 'int',
        'label' => 'Enable Quotations',
        'source' => null,
        'backend' => 'qquoteadv/catalog_product_attribute_backend_qquoteadv_group_allow',
        'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE,
        'visible' => true,
        'required' => false,
        'default_value' => '0'
    ));

} else { // Updating Attribute

    $setup->updateAttribute('catalog_product', 'group_allow_quotemode', array(
        'frontend_input' => 'text',
        'backend_type' => 'int',
        'frontend_label' => 'Enable Quotations',
        'backend_model' => 'qquoteadv/catalog_product_attribute_backend_qquoteadv_group_allow',
        'source_model' => null,
        'is_required' => false,
        'default_value' => '0'
    ));

}

$installer->endSetup();
