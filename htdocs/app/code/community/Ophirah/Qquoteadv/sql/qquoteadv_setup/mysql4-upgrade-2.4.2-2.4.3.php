<?php

$installer = $this;
$installer->startSetup();

$installer->run("DROP TABLE IF EXISTS `{$installer->getTable('qquoteadv/product_attribute_group_allow')}`;");

$table = $installer->getConnection()
    ->newTable($installer->getTable('qquoteadv/product_attribute_group_allow'))
    ->addColumn('value_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity' => true,
        'nullable' => false,
        'primary' => true,
    ), 'Value ID')
    ->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => false,
        'default' => '0',
    ), 'Entity ID')
    ->addColumn('all_groups', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned' => true,
        'nullable' => false,
        'default' => '1',
    ), 'Is Applicable To All Customer Groups')
    ->addColumn('customer_group_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned' => true,
        'nullable' => false,
        'default' => '0',
    ), 'Customer Group ID')
    ->addColumn('value', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned' => true,
        'nullable' => false,
        'default' => '0',
    ), 'Value')
    ->addColumn('website_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned' => true,
        'nullable' => false,
        'default' => '0',
    ), 'Website ID')
    ->addIndex($installer->getIdxName('qquoteadv/product_attribute_group_allow', array('entity_id')),
        array('entity_id'))
    ->addIndex($installer->getIdxName('qquoteadv/product_attribute_group_allow', array('customer_group_id')),
        array('customer_group_id'))
    ->addIndex($installer->getIdxName('qquoteadv/product_attribute_group_allow', array('website_id')),
        array('website_id'))


    ->setComment('Catalog Product Allow Quote Request Backend Table');


$installer->getConnection()->createTable($table);

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
        'source' => 'qquoteadv/catalog_product_attribute_backend_qquoteadv_group_allow',
        'backend' => 'eav/entity_attribute_backend_array',
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
        'source_model' => 'qquoteadv/catalog_product_attribute_backend_qquoteadv_group_allow',
        'backend_model' => 'eav/entity_attribute_backend_array',
        'is_required' => false,
        'default_value' => '0'
    ));

}

/* // DEPRECATED CODE
$setup->addAttribute('catalog_product', 'group_allow_quotemode', array(
        'group'     	=> 'General',
	'input'         => 'text',
        'type'          => 'int',	
        'label'         => 'Enable Quotations',
	'backend'       => 'qquoteadv/catalog_product_attribute_backend_qquoteadv_group_allow',
        'global'        => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE,
        'visible'       => true,
	'required'	=> false,	
	'default_value' => '0'
));
*/

$installer->endSetup();
