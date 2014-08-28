<?php

/*
 * Cart2Quote CRM addon module
 * 
 * This addon module needs Cart2Quote
 * To be installed and configured proparly
 * 
 */

class Ophirah_Crmaddon_Model_Crmaddontemplates
    extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('crmaddon/crmaddontemplates');
    }

    /**
     * Replace var data in message template with data
     * @param   String // $vars with variable data
     * @return  String      // String with template body in html
     */
    public function getBodyTemplate($vars)
    {

        // getting vars
        $customerName = $vars['customer']['name'];
        $senderName = $vars['sender']['name'];
        $message = (isset($vars['template'])) ? $vars['template'] : '';

        // Defining Arrays for replacement
        // first    - array with target text
        // second   - var replacement text
        $bodyReplace = array();
        $bodyReplace[] = array(array("{{var CRMcustomername}}"), $customerName);
        $bodyReplace[] = array(array("{{var CRMsendername}}"), $senderName);

        // replace text
        foreach ($bodyReplace as $replace) {
            $message = str_replace($replace[0], $replace[1], $message);
        }

        return $message;
    }

    /**
     * Retrieve template data from database
     *
     * @param   int // Template Id to load
     * @return  array()     // Template data
     */

    public function getCrmbodyTemplate($templateId)
    {
        $template = Mage::getModel('crmaddon/crmaddontemplates')->load($templateId);

        return $template->getData();
    }

}
