<?php

/*
 * Cart2Quote CRM addon module
 * 
 * This addon module needs Cart2Quote
 * To be installed and configured proparly
 * 
 */

class Ophirah_Crmaddon_Helper_Data extends Mage_Core_Helper_Abstract
{
    // Defining constants
    CONST CRMADDON_NOTICE = "The CRM module is part of the Cart2Quote Enterprise version. <a href='%s' target='blank'>Upgrade or change to a paid plan</a> to unlock more features";
    CONST CRMADDON_NOTICE_LINK = "http://www.cart2quote.com/magento-quotation-module-pricing.html?utm_source=clientwebsite&utm_medium=clientwebsite&utm_term=upgradeCRM&utm_content=upgradeCRM&utm_campaign=upgradeCRM";
    CONST CRMADDON_UPGRADE_LINK = "http://www.cart2quote.com/magento-quotation-module-pricing/magento-cart2quote-enterprise.html?utm_source=clientwebsite&utm_medium=clientwebsite&utm_term=upgradeCRM&utm_content=upgradeCRM&utm_campaign=upgradeCRM";
    CONST CRMADDON_UPGRADE_MESSAGE = "To use the CRM module of Cart2Quote and send messages to your customers <a href='%s' target='blank'>upgrade</a> to Cart2Quote Enterprise";

    /*
     *  Retrieve array with message templates
     *  from crmaddontemplates table
     * 
     *  @return Array()     // Database data
     */
    public function getTemplates()
    {
        $templates = array();
        $default = array();
        $DB_templates = Mage::getModel('crmaddon/crmaddontemplates')
            ->getCollection()
            ->addFieldToFilter('status', 1);

        foreach ($DB_templates as $DB_template) {
            if ($DB_template->getData('default') == 1) {
                $default['default'] = $DB_template->getData();
            }

            $templates[$DB_template->getTemplateId()] = $DB_template->getData();

        }

        $templates = $default + $templates;

        return $templates;
    }

    public Function createOptions($templates)
    {
        // create options from template array
        $options = array();
        foreach ($templates as $key => $value):
            if ($key != $templates['default']['template_id']) {
                $options[$value['template_id']] = $value['name'];
            }
        endforeach;

        return $options;
    }

    /*
    *  Retrieve array with messages
    *  from crmaddonmesages table
    *
     * @param  decimal     // Quote Id
    *  @return Array()     // Database data
    */
    public function getMessages($quote_id)
    {
        $messages = array();

        $DB_messages = Mage::getModel('crmaddon/crmaddonmessages')
            ->getCollection()
            ->addFieldToFilter('quote_id', $quote_id)
            ->setOrder('message_id', 'DESC');

        foreach ($DB_messages as $DB_message) {
            // replace message tekst with Html stripped tekst
            $newMsg = $this->stripHtmlTags($DB_message->getData('message'));
            // get short message
            $shortMsg = $this->getShortMsg($newMsg);

            $DB_message->setData('message', $newMsg);
            $DB_message->setData('message_1', $shortMsg['message_1']);
            $DB_message->setData('message_2', $shortMsg['message_2']);

            $messages[] = $DB_message->getData();

        }

        // create empty message if no messages are found
        if (empty($messages)) {
            $messages[] = array('message' => ' ',
                'subject' => ' ',
                'created_at' => NOW()
            );
        }

        return $messages;
    }

    /**
     *  Strip Html markup, and
     *  insert break tags
     *
     * @param  string // message text
     * @return string     // new string
     */
    public function stripHtmlTags($message)
    {
        // Defining Arrays for replacement
        // first    - array with target text
        // second   - temp replacement text
        // third    - final replacement text, if different from first
        $totalReplace = array();
        $totalReplace[] = array(array("<br>", "<br/>", "<br />"), "[[!!##BREAK##!!]]", "<br />");
        $totalReplace[] = array(array("<p>"), "[[!!##POPEN##!!]]");
        $totalReplace[] = array(array("</p>"), "[[!!##PCLOSE##!!]]");

        // trim and decode message
        $trimMessage = (trim(html_entity_decode($message)));
        // replace text with temp text and strip Html
        foreach ($totalReplace as $replacetext) {
            $replaceMsg = str_replace($replacetext[0], $replacetext[1], $trimMessage);
            $trimMessage = $replaceMsg;
        }
        // strip remaining tags
        $replaceMsg = strip_tags($replaceMsg);
        // replace temptext with html tag        
        foreach ($totalReplace as $replacetext) {
            if (empty($replacetext[2])) {
                $replacetext[2] = $replacetext[0][0];
            }
            $returnMsg = str_replace($replacetext[1], $replacetext[2], $replaceMsg);
            $replaceMsg = $returnMsg;
        }

        return $returnMsg;
    }

    /**
     *  Cut message in 2 pieces
     *  for shorttext display
     *
     * @param  string // message text
     * @return array()    // array with short message
     */
    public function getShortMsg($message)
    {
        $shortMsglength = Mage::getStoreConfig('crmaddon/emails/crmaddon_shortmsg');

        $return = array();
        if (strlen($message) >= $shortMsglength) {
            $return['message_1'] = trim(substr($message, 0, $shortMsglength));
            $return['message_2'] = trim(substr($message, $shortMsglength));

        }

        return $return;
    }

    public function tabIsActive()
    {
        if (Mage::app()->getRequest()->getParam('crmbodytmpl')) {
            return true;
        }
        return false;
    }

    /*
     * CRMaddon Module is only available
     * fot Enterprise users of Cart2Quote
     * 
     * @param   mixed      // Message to display, with or without link
     */
    final function checkLicense($notice = NULL)
    {

        // Check for a valid Enterprise License
        if ($notice == NULL) {
            $notice['message'] = Ophirah_Crmaddon_Helper_Data::CRMADDON_NOTICE;
            $notice['link'] = Ophirah_Crmaddon_Helper_Data::CRMADDON_NOTICE_LINK;
        }

        if (!Mage::helper('qquoteadv')->validLicense('CRMaddon')) {
            if (!is_array($notice)) {
                Mage::getSingleton('adminhtml/session')->addNotice(__($notice));
            } else {
                Mage::getSingleton('adminhtml/session')->addNotice(__($notice['message'], $notice['link']));
            }
        }

    }

}
