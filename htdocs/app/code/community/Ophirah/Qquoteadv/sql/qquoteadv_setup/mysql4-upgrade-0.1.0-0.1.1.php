<?php

$installer = $this;
$setup = new Mage_Eav_Model_Entity_Setup('core_setup');
$installer->startSetup();

/**
 * Adding Attributes
 */

// Check for existing id
$entityTypeId = (int)$setup->getEntityTypeId('catalog_product');
$id = (int)$setup->getAttributeId('catalog_product', 'allowed_to_quotemode');

if ($id == 0) { // Adding Attribute

    $setup->addAttribute('catalog_product', 'allowed_to_quotemode', array(
        'group' => 'General',
        'input' => 'select',
        'type' => 'int',
        'label' => 'Allowed to Quote Mode',
        'source' => 'qquoteadv/source_alloworder',
        'backend' => 'eav/entity_attribute_backend_array',
        'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
        'visible' => true,
        'required' => false,
        'default_value' => '0'
    ));

} else { // Updating Attribute

    $setup->updateAttribute('catalog_product', 'allowed_to_quotemode', array(
        'frontend_input' => 'select',
        'backend_type' => 'int',
        'frontend_label' => 'Allowed to Quote Mode',
        'source_model' => 'qquoteadv/source_alloworder',
        'backend_model' => 'eav/entity_attribute_backend_array',
        'is_required' => false,
        'default_value' => '0'
    ));

}


/* // DEPRECATED CODE
$setup->addAttribute('catalog_product', 'allowed_to_quotemode', array(
    'group'     	=> 'General',
    'input'             => 'select',
    'type'              => 'int',	
    'label'             => 'Allowed to Quote Mode',
    'source'            => 'qquoteadv/source_alloworder',    
    'backend'           => 'eav/entity_attribute_backend_array',
    'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'visible'           => true,
    'required'		=> false,	
    'default_value'     => '0'
));
*/

$installer->endSetup();
