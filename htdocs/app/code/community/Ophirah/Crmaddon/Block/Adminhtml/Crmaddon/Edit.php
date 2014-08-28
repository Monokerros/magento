<?php

/*
 * Cart2Quote CRM addon module
 * 
 * This addon module needs Cart2Quote
 * To be installed and configured proparly
 * 
 */

class Ophirah_Crmaddon_Block_Adminhtml_Crmaddon_Edit extends Ophirah_Qquoteadv_Block_Adminhtml_Qquoteadv_Edit
{
    public function __construct()
    {
        parent::__construct();

        // DEPRECATED
        // Refresh Page button
//        $onclick = "setLocation('".$this->getUrl('*/*/edit', array('id' => $this->getRequest()->getParam('id')))."')";
        /*        $this->_addButton('refresh', array(
                        'label'     => Mage::helper('crmaddon')->__('Refresh Page'),
                        'class'     => '',
                        'onclick'   => $onclick,
                ));
        */

        // Adding scripts to the form container
        $this->_formScripts[] = "
            function sendMessage(){
                editForm.submit($('edit_form').action =\"" . $this->getUrl('*/crmaddon/crmmessage', array('id' => $this->getRequest()->getParam('id'))) . "\");
            }

            function loadTemplate(){
                editForm.submit($('edit_form').action =\"" . $this->getUrl('*/crmaddon/loadtemplate', array('id' => $this->getRequest()->getParam('id'))) . "\");
            }

            function loadCrmTemplate(){
                editForm.submit($('edit_form').action =\"" . $this->getUrl('*/crmaddon/loadcrmtemplate', array('id' => $this->getRequest()->getParam('id'))) . "\");
            }

            function saveCrmTemplate(){
                editForm.submit($('edit_form').action =\"" . $this->getUrl('*/crmaddon/savecrmtemplate', array('id' => $this->getRequest()->getParam('id'))) . "\");
            }

            function newCrmTemplate(){
                editForm.submit($('edit_form').action =\"" . $this->getUrl('*/crmaddon/newcrmtemplate', array('id' => $this->getRequest()->getParam('id'))) . "\");
            }

            function deleteCrmTemplate(){
                editForm.submit($('edit_form').action =\"" . $this->getUrl('*/crmaddon/deletecrmtemplate', array('id' => $this->getRequest()->getParam('id'))) . "\");
            }

        ";

    }
}
