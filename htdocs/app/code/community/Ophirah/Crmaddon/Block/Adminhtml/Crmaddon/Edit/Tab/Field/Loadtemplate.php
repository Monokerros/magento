<?php

/*
 * Cart2Quote CRM addon module
 * 
 * This addon module needs Cart2Quote
 * To be installed and configured proparly
 * 
 */

class Ophirah_Crmaddon_Block_Adminhtml_Crmaddon_Edit_Tab_Field_Loadtemplate
    extends Varien_Data_Form_Element_Abstract
{
    public function __construct($attributes = array())
    {
        parent::__construct($attributes);
    }

    public function getElementHtml()
    {
        $button = '<button id="crm_loadtemplate" class="scalable save" style="" onclick="loadCrmTemplate();" type="adminhtml/widget_button" title="Load Template">';
        $label = "<span>" . Mage::helper('crmaddon')->__('Load Template') . "</span></button>";

        $html = $button;
        $html .= $label;
        $html .= $this->getAfterElementHtml();
        return $html;
    }

}
