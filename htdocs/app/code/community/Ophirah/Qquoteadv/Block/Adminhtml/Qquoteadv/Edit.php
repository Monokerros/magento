<?php

class Ophirah_Qquoteadv_Block_Adminhtml_Qquoteadv_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();

        $this->_objectId = 'id';
        $this->_blockGroup = 'qquoteadv';
        $this->_controller = 'adminhtml_qquoteadv';
        // $status = Mage::registry('qquote_data')->getData('status'); // DEPRECATED
        // back, reset, save, and delete are the default button
        // removing buttons
        // $this->_removeButton('save');
        $this->_removeButton('reset');

        // DEPRECATED
        /*        
        if(  
           $status == Ophirah_Qquoteadv_Model_Status::STATUS_ORDERED ||
           $status == Ophirah_Qquoteadv_Model_Status::STATUS_CONFIRMED
        ){
          $this->_updateButton('save', 'onclick', 'return false;');
          $this->_updateButton('save', 'class', 'disabled');
          
        }
         */

        $this->_updateButton('save', 'label', Mage::helper('qquoteadv')->__('Save Quote'));
        $this->_updateButton('save', 'onclick', 'save()');

        $this->_updateButton('delete', 'label', Mage::helper('qquoteadv')->__('Cancel Quote'));

        // On Hold Button
        $onclick = 'hold();';
        $style = '';
        $hold = 1;
        if (Mage::registry('qquote_data')->getData('status') == Ophirah_Qquoteadv_Model_Status::STATUS_PROPOSAL_SAVED) {
            $hold = 2;
            $this->_addButton('hold', array(
                'label' => Mage::helper('qquoteadv')->__('Unhold'),
                'class' => $style,
                'onclick' => $onclick,
            ));
        } else {
            $this->_addButton('hold', array(
                'label' => Mage::helper('qquoteadv')->__('Hold'),
                'class' => $style,
                'onclick' => $onclick,
            ));

        }

        // Print Button
        $onclick = 'printPDF();';
        $style = '';
        $this->_addButton('print', array(
            'label' => Mage::helper('qquoteadv')->__('Print'),
            'class' => $style,
            'onclick' => $onclick,
        ));


        /*
        $this->_addButton('saveandcontinue', array(
            'label'     => Mage::helper('adminhtml')->__('Save And Continue Edit'),
            'onclick'   => 'saveAndContinueEdit()',
            'class'     => 'save',
        ), -100);
        */

        $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('qquote_content') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'qquote_content');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'qquote_content');
                }
            }

            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
            
            function printPDF(){
                editForm.submit($('edit_form').action =\"" . $this->getPrintUrl() . "\");;
            }

          function save(){
                editForm.submit($('edit_form').action =\"" . $this->getUrl('*/*/save', array('id' => $this->getRequest()->getParam('id'))) . "\");
            }       
            
          function hold(){
                editForm.submit($('edit_form').action =\"" . $this->getUrl('*/*/save', array('id' => $this->getRequest()->getParam('id'), 'hold' => $hold)) . "\");
            }    

        ";

    }

    public function getHeaderText()
    {
        $quote_id = Mage::registry('qquote_data')->getData('quote_id');
        $increment_id = Mage::registry('qquote_data')->getData('increment_id');
        $created_at = Mage::registry('qquote_data')->getData('created_at');

        $text = Mage::helper('qquoteadv')->__('Quote # %s | Quote Date %s',
            $increment_id ? $increment_id : $quote_id,
            $this->formatDate($created_at, 'medium', true)
        );
        return $text;
    }

    public function getPrintUrl()
    {
        $quote_id = Mage::registry('qquote_data')->getData('quote_id');
        return $this->getUrl('*/*/pdfqquoteadv/id/' . $quote_id);
    }

    public function getConvertUrl()
    {
        $quote_id = Mage::registry('qquote_data')->getData('quote_id');
        return $this->getUrl('*/*/convert/id/' . $quote_id);
    }

}
