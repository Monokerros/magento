<?php

/*
 * Cart2Quote CRM addon module
 * 
 * This addon module needs Cart2Quote
 * To be installed and configured proparly
 * 
 */

class Ophirah_Crmaddon_Block_Adminhtml_Crmaddon_Edit_Tab_Field_Buttonstemplate
    extends Varien_Data_Form_Element_Abstract
{
    public function __construct($attributes = array())
    {
        parent::__construct($attributes);
    }

    public function getElementHtml()
    {
        $value = $this->getValue();
        $custom1 = $this->getCustom1();
        $custom2 = $this->getCustom1();

        $class1 = array("scalable disabled", "scalable save");
        $class2 = array("scalable disabled", "scalable delete");

        $isActive = Mage::helper('crmaddon')->tabIsActive();
        $state = ($isActive === true) ? 1 : 0;

        $button1 = '<button id="crm_savetemplate" class="' . $class1[$state] . '" style="" onclick="saveCrmTemplate();" type="adminhtml/widget_button" title="Save Template">';
        $label1 = "<span>" . Mage::helper('crmaddon')->__('Save Template') . "</span></button>";
        $button2 = '<button id="crm_newtemplate" class="' . $class1[1] . '" style="" onclick="newCrmTemplate();" type="adminhtml/widget_button" title="New Template">';
        $label2 = "<span>" . Mage::helper('crmaddon')->__('Save as New Template') . "</span></button>";
        $button3 = '<button id="crm_deletetemplate" class="' . $class2[$state] . '" style="" onclick="deleteCrmTemplate();" type="adminhtml/widget_button" title="Delete Template">';
        $label3 = "<span>" . Mage::helper('crmaddon')->__('Delete Template') . "</span></button>";

        $html = $button2 . $label2;
        $html .= $button1 . $label1;
        $html .= $button3 . $label3;
        $html .= $this->getAfterElementHtml();
        return $html;
    }

}
