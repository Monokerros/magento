<?php
	$installer = $this;
	$installer->startSetup();
	$installer->createEntityTables(
		$this->getTable('complexworld/eavblogpost')
	);
	$installer->addEntityType('complexworld_eavblogpost', array(
		//entity_mode is the URI you'd pass into the Mage::getModel()) call thanks for telling me
		'entity_model'	=> 'complexworld/eavblogpost',
		
		//table refers to the resource URI complexworld/eavblogpost
		//<complexworld_resource>...<eavblogpost><table>eavblog_posts</table> <--that place
		'table'		=> 'complexworld/eavblogpost',
	));
	$installer->addAttribute('complexworld_eavblogpost', 'title', array(
	//the EAV attribute type, NOT a MySQL varchar
		'type'		=> 'varchar',
		'label'		=> 'Title',
		'input'		=> 'text',
		'class'		=> '',
		'backend'	=> '',
		'frontend'	=> '',
		'source'	=> '',
		'required'	=> true,
		'user_defined'	=> true,
		'default'	=> '',
		'unique'	=> false,
	));
	$installer->addAttribute('complexworld_eavblogpost', 'content', array(
		'type'		=> 'text',
		'label'		=> 'Content',
		'input'		=> 'textarea',
	));
	$this->addAttribute('complexworld_eavblogpost', 'date', array(
		'type'		=> 'datetime',
		'label'		=> 'Post Date',
		'input'		=> 'datetime',
		'required'	=> false,
	));
	$installer->endSetup();
?>
