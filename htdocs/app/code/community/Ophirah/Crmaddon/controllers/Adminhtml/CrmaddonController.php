<?php

/*
 * Cart2Quote CRM addon module
 * 
 * This addon module needs Cart2Quote
 * To be installed and configured proparly
 * 
 */

class Ophirah_Crmaddon_Adminhtml_CrmaddonController
    extends Mage_Adminhtml_Controller_Action
{
    // Defining constants
    CONST XML_PATH_CRMADDON_EMAIL_TEMPLATE = 'crmaddon_emails_crmaddon_container';
    CONST CHECKBOX_ENABLED = "on";

    // required fields
    public function getRequired($option = NULL)
    {

        $return = array();
        $return['sendMail'] = array('crm_subject' => 'subject',
            'crm_message' => 'message'
        );

        return $return[$option];
    }

    /**
     *  Process Form data from Cart2Quote module
     *  CRM message post action.
     */
    public function crmmessageAction()
    {
        // Get data from Post        
        $crmData = $this->getCrmdata();
        $quote_id = $crmData['crm_id'];

        // check empty fields
        $required = $this->getRequired('sendMail');
        foreach ($crmData as $key => $value) {
            if ($value == NULL || $value == '') {
                if (key_exists($key, $required)) {
                    $message = $this->__("Datafield %s is empty", $required[$key]);
                    Mage::getSingleton('adminhtml/session')->addError($message);
                }
            }
        }

        // set return path
        $returnPath = $this->getBaseReturnPath($quote_id, $crmData) . '/crmaddon/1';

        // Check for a valid Enterprise License
        if (!Mage::helper('qquoteadv')->validLicense('CRMaddon', $crmData['createHash'])) {
            $errorMsg = Ophirah_Crmaddon_Helper_Data::CRMADDON_UPGRADE_MESSAGE;
            $errorLink = Ophirah_Crmaddon_Helper_Data::CRMADDON_UPGRADE_LINK;
            Mage::getSingleton('adminhtml/session')->addError(__($errorMsg, $errorLink));
            $this->_redirect($returnPath);
            return;
        }

        if (!isset($errorMsg) && !isset($message)) {
            try {
                $sendMail = $this->sendEmail($crmData);

                if (empty($sendMail)) {
                    $message = $this->__("CRM message couldn't be sent to the client");
                    Mage::getSingleton('adminhtml/session')->addError($message);
                }

            } catch (Exception $e) {
                $message = $this->__("CRM message couldn't be sent to the client");
                Mage::log($e->getMessage());
                Mage::getSingleton('adminhtml/session')->addError($message);
            }
        };
        $this->_redirect($returnPath);
    }

    /**
     *  Select CRM_addon data only
     *  from the Form Post data
     *
     * @return Array()
     */
    public function getCrmdata()
    {
        $return['createHash'] = null;
        foreach ($this->getRequest()->getPost() as $key => $value) {
            if (substr($key, 0, 4) == "crm_" || $key == 'createHash') {
                $return[$key] = $value;
            }

            if ($key == 'crm_notifyCustomer' && $value == self::CHECKBOX_ENABLED) {
                $return[$key] = 1;
            }
        }

        return $return;
    }

    /**
     * Send email to client to informing about the quote proposition
     * @param   Array ()     // $params customer address
     */
    public function sendEmail($crmData)
    {
        //Create an array of variables to assign to template
        $vars = array();
        $storeId = $crmData['crm_storeId'];

        // Setting vars
        $vars['crmaddonBody'] = $crmData['crm_message'];

        // Prepare data for saving to database
        $saveItems = array('created_at', 'updated_at', 'quote_id', 'status', 'email_address', 'subject', 'template_id', 'message');
        $saveData = $this->prepareSaveData($crmData);

        // Check if customer needs to be notified
        if (!isset($saveData['customer_notified'])):
            $res = true;
        elseif ($saveData['customer_notified'] == 1):
            $template = Mage::getModel('core/email_template');

            $default_template = Mage::getStoreConfig('crmaddon/emails/crmaddon_container', $storeId);

            if ($default_template) {
                $templateId = $default_template;
            } else {
                $templateId = self::XML_PATH_CRMADDON_EMAIL_TEMPLATE;
            }

            // get locale of quote sent so we can sent email in that language
            $storeLocale = Mage::getStoreConfig('general/locale/code', $storeId);

            if (is_numeric($templateId)) {
                $template->load($templateId);
            } else {
                $template->loadDefault($templateId, $storeLocale);
            }

            (isset($crmData['crm_subject'])) ? $subject = $crmData['crm_subject'] : $subject = $template['template_subject'];

            $sender = Mage::getModel('qquoteadv/qqadvcustomer')->load($saveData['quote_id'])->getEmailSenderInfo();

            $template->setSenderName($sender['name']);
            $template->setSenderEmail($sender['email']);
            $template->setTemplateSubject($subject);
            $template->setDesignConfig(array('store' => $storeId));

            /**
             * Opens the qquote_request.html, throws in the variable array
             * and returns the 'parsed' content that you can use as body of email
             */
            $processedTemplate = $template->getProcessedTemplate($vars);

            /*
             * getProcessedTemplate is called inside send()
             */
            $res = $template->send($crmData['crm_customerEmail'], $crmData['crm_customerName'], $vars);

        endif;

        if (!empty($res)) {
            // save data to DB
            $model = Mage::getModel('crmaddon/crmaddonmessages')->setData($saveData)->save();
        }

        return $res;
    }


    /**
     *  Load selected message template
     *  for CRMaddon textarea
     */
    public function loadtemplateAction()
    {
        $crmData = $this->getCrmdata();
        $msgtemplate = $crmData['crm_message_template'];
        $quote_id = $crmData['crm_id'];

        // Check Cart2Quote license
        Mage::helper('crmaddon')->checkLicense(null, $crmData['createHash']);

        $returnPath = $this->getBaseReturnPath($quote_id, $crmData) . '/crmtmpl/' . $msgtemplate;

        $this->_redirect($returnPath);
    }

    /**
     *  Load selected crm bodytemplate
     *  for CRMaddon textarea
     */
    public function loadcrmtemplateAction()
    {
        $crmData = $this->getCrmdata();
        $bodytemplate = $crmData['crm_bodyId'];
        $quote_id = $crmData['crm_id'];

        // Check Cart2Quote license
        Mage::helper('crmaddon')->checkLicense(null, $crmData['createHash']);

        $returnPath = $this->getBaseReturnPath($quote_id, $crmData) . '/crmbodytmpl/' . $bodytemplate;

        $this->_redirect($returnPath);
    }

    /**
     *  Save crm bodytemplate
     *  from CRMaddon textarea
     */
    public function savecrmtemplateAction()
    {
        $crmData = $this->getCrmdata();
        $quote_id = $crmData['crm_id'];
        $bodyTmplId = $crmData['crm_bodytemplateid'];

        // Check Cart2Quote license
        Mage::helper('crmaddon')->checkLicense(null, $crmData['createHash']);

        if (!isset($crmData['crm_templatedefault'])) {
            $crmData['crm_templatedefault'] = 0;
        }

        //Check default setting
        if ((int)$crmData['crm_templatedefault'] == 1) {
            $this->resetDefault();
        }

        // set return path
        $returnPath = $this->getBaseReturnPath($quote_id, $crmData) . '/crmbodytmpl/' . $bodyTmplId;
        // get template from DB
        $template = Mage::getModel('crmaddon/crmaddontemplates')->load($bodyTmplId);
        // collect save data array
        $saveData = $this->prepareSavetemplateData($crmData);
        // set data
        $template->setData($saveData);
        // save and return
        $this->saveTemplate($template, $returnPath);

    }

    /**
     *  Create new crm bodytemplate
     *  from CRMaddon textarea
     */
    public function newcrmtemplateAction()
    {
        $crmData = $this->getCrmdata();
        $quote_id = $crmData['crm_id'];
        $bodyTmplId = $crmData['crm_bodytemplateid'];

        // Check Cart2Quote license
        Mage::helper('crmaddon')->checkLicense(null, $crmData['createHash']);

        if (!isset($crmData['crm_templatedefault'])) {
            $crmData['crm_templatedefault'] = 0;
        }

        //Check default setting
        if ((int)$crmData['crm_templatedefault'] == 1) {
            $this->resetDefault();
        }

        // set return path
        $returnPath = $this->getBaseReturnPath($quote_id, $crmData);
        // Check unique template name
        $templateNames = Mage::helper('crmaddon')->getTemplates();
        foreach ($templateNames as $templateName) {
            if (trim($crmData['crm_templatename']) == trim($templateName['name'])) {
                $message = Mage::helper('crmaddon')->__("CRM template name allready exists");
            }
        }

        if (isset($message)) {
            Mage::getSingleton('adminhtml/session')->addError($message);
            $this->_redirect($returnPath);
        } else {
            // collect save data array
            $saveData = $this->prepareSavetemplateData($crmData);

            // template_id needs to be unset for creating new template
            unset($saveData['template_id']);
            $save = Mage::getModel('crmaddon/crmaddontemplates')->setData($saveData);

            $this->saveTemplate($save, $returnPath, true);
        }
    }

    /**
     *  Delete crm bodytemplate
     *  from database
     */
    public function deletecrmtemplateAction()
    {
        $crmData = $this->getCrmdata();
        $quote_id = $crmData['crm_id'];
        // Check Cart2Quote license
        Mage::helper('crmaddon')->checkLicense(null, $crmData['createHash']);

        // set return path
        $defaultTemplate = $this->getDefaultTemplate();
        $returnPath = $this->getBaseReturnPath($quote_id, $crmData) . '/crmbodytmpl/' . $defaultTemplate[0]['template_id'];

        $templateId = (int)$crmData['crm_bodyId'];

        try {
            if (!empty($templateId)) {
                $delete = Mage::getModel('crmaddon/crmaddontemplates')->load($templateId)->delete();
            } else {
                $delete = '';
            }

            if (empty($delete)) {
                $message = $this->__("CRM template couldn't be deleted from the database");
                Mage::getSingleton('adminhtml/session')->addError($message);
            } else {
                $message = $this->__("CRM template has been succesfully deleted from the database");
                Mage::getSingleton('adminhtml/session')->addSuccess($message);

            }

        } catch (Exception $e) {
            $message = $this->__("CRM template couldn't be deleted from the database");
            Mage::log($e->getMessage());
            Mage::getSingleton('adminhtml/session')->addError($message);
        }

        $this->_redirect($returnPath);
    }

    /**
     *  Saving data from Form
     *  to the database
     *
     * @param   Array ()     // $saveData   - Prepared data from form
     * @param   Array ()     // $returnPath - Path to redirect
     * @return  Array()     // Data with keyname as the database column names
     */
    public function saveTemplate($saveData, $returnPath, $new = false)
    {
        try {
            $save = $saveData->save();

            if (empty($save)) {
                $message = $this->__("CRM template couldn't be saved to the database");
                Mage::getSingleton('adminhtml/session')->addError($message);
            } else {
                $message = $this->__("CRM template has succesfully been saved to the database");
                Mage::getSingleton('adminhtml/session')->addSuccess($message);

            }

        } catch (Exception $e) {
            $message = $this->__("CRM template couldn't be saved to the database");
            Mage::log($e->getMessage());
            Mage::getSingleton('adminhtml/session')->addError($message);
        }

        if ($new === true) {
            $DB_templates = Mage::getModel('crmaddon/crmaddontemplates')
                ->getCollection()
                ->addFieldToFilter('name', $saveData['name']);

            foreach ($DB_templates as $DB_template) {
                $bodyTmplId = $DB_template->getData('template_id');
            }

            $returnPath = $returnPath . '/crmbodytmpl/' . $bodyTmplId;
        }

        $this->_redirect($returnPath);
    }

    /**
     *  Prepare data from Form to save
     *  to the database
     *
     * @param   Array ()     // Data from form
     * @return  Array()     // Data with keyname as the database column names
     */
    public function prepareSaveData($crmData)
    {

        $returnData = array();
        $translateArray = array('quote_id' => 'crm_id',
            'email_address' => 'crm_customerEmail',
            'subject' => 'crm_subject',
            'template_id' => 'crm_message_template',
            'message' => 'crm_message',
            'customer_notified' => 'crm_notifyCustomer'
        );

        foreach ($translateArray as $key => $value) {
            if (isset($crmData[$value])):
                $crmData[$value] = trim($crmData[$value]);
                if ($key == 'message') {
                    $crmData[$value] = htmlentities($crmData[$value], ENT_QUOTES, "UTF-8");
                }
                $returnData[$key] = $crmData[$value];
            endif;
        }

        $returnData['created_at'] = NOW();
        $returnData['updated_at'] = NOW();

        return $returnData;
    }


    /**
     *  Prepare data from Form to save
     *  to the database
     *
     * @param   Array ()     // Data from form
     * @return  Array()     // Data with keyname as the database column names
     */
    public function prepareSavetemplateData($crmData)
    {
        $saveData = array();
        // at the moment unused variable
        $crmData['crm_status'] = 1;
        $translateArray = array('template_id' => 'crm_bodytemplateid',
            'name' => 'crm_templatename',
            'subject' => 'crm_templatesubject',
            'template' => 'crm_templatebody',
            'default' => 'crm_templatedefault',
            'status' => 'crm_status'
        );

        foreach ($translateArray as $key => $value) {
            $crmData[$value] = trim($crmData[$value]);
            if ($key == 'template') {
                $crmData[$value] = htmlentities($crmData[$value], ENT_QUOTES, "UTF-8");
            }
            $saveData[$key] = $crmData[$value];
        }

        return $saveData;
    }


    /**
     *  Creates returnpath
     *
     * @param  decimal // QuoteId
     * @return string      // Returnpath
     */

    public function getBaseReturnPath($quote_id, $crmData)
    {
        if ($crmData['crm_moduleName'] == NULL || $crmData['crm_moduleName'] == 'admin') {
            $crmData['crm_moduleName'] = '*';
        }
        $return = $crmData['crm_moduleName'] . '/' . $crmData['crm_controllerName'] . '/' . $crmData['crm_actionName'] . '/id/' . $quote_id;

        return $return;
    }

    public function getDefaultTemplate()
    {
        $defaultemplate = array();
        $DB_defaultTemplates = Mage::getModel('crmaddon/crmaddontemplates')
            ->getCollection()
            ->setOrder('template_id', 'ASC');

        foreach ($DB_defaultTemplates as $DB_default) {
            if ($DB_default->getData('default') == 1) {
                $defaultemplate[] = $DB_default->getData();
            }
        }

        return $defaultemplate;
    }

    public function resetDefault()
    {
        $defaultTemplate = $this->getDefaultTemplate();

        foreach ($defaultTemplate as $default) {
            Mage::getModel('crmaddon/crmaddontemplates')
                ->load($default['template_id'])
                ->setData('default', 0)
                ->save();
        }

    }

}
