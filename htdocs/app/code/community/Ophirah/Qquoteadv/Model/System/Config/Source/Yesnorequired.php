<?php

class Ophirah_Qquoteadv_Model_System_Config_Source_Yesnorequired
{
    protected $_options;

    public function toOptionArray($isMultiselect = false)
    {
        return array(
            array('value' => 0, 'label' => Mage::helper('adminhtml')->__('No')),
            array('value' => 1, 'label' => Mage::helper('adminhtml')->__('Yes')),
            array('value' => 2, 'label' => Mage::helper('adminhtml')->__('Yes and required'))
        );
    }
}
