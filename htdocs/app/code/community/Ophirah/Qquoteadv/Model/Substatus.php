<?php

class Ophirah_Qquoteadv_Model_Substatus
    extends Mage_Core_Model_Abstract
{
    // Character used for dividing
    // status and substatus
    const EXPLODE_CHARACTER = '.';

    // #### DEFINE SUBSTATUSES ####
    // substatus value is string
    // in this format:
    // [STATUSID][EXPOLODE_CHARACTER][SUBSTATUSID]
    // 
    // EXAMPLE:
    // SUBSTATUS FOR STATUS_REJECTED (value = 30) will look like: 
    // const SUBSTATUS_REJECTED_SUB100  = '30.100';
    //
    // MULTOPLE SUBSTATUSES LOOK LIKE:
    // const SUBSTATUS_REJECTED_SUB100  = '30.100';
    // const SUBSTATUS_REJECTED_SUB200  = '30.200';
    // etc...
    //

    // 30 - Substatus Rejected
    const SUBSTATUS_REJECTED_SUB100 = '30.100';

    // 40 - Substatus Cancelled
    const SUBSTATUS_CANCELLED_SUB100 = '40.100';

    // 50 - Substatus Proposal 
    const SUBSTATUS_PROPOSAL_SUB100 = '50.100';
    const SUBSTATUS_PROPOSAL_SUB200 = '50.200';
    const SUBSTATUS_PROPOSAL_SUB300 = '50.300';


    // 52 - Substatus Proposal Saved
    const SUBSTATUS_SAVED_SUB100 = '52.100';
    const SUBSTATUS_SAVED_SUB200 = '52.200';

    /**
     * create array of all substatuses
     *
     * @return array
     */
    static public function substatuses()
    {

        $substatuses = array(
            self::SUBSTATUS_REJECTED_SUB100 => Mage::helper('qquoteadv')->__('SUBSTATUS_REJECTED_SUB100'),
            self::SUBSTATUS_CANCELLED_SUB100 => Mage::helper('qquoteadv')->__('SUBSTATUS_CANCELLED_SUB100'),
            self::SUBSTATUS_PROPOSAL_SUB100 => Mage::helper('qquoteadv')->__('SUBSTATUS_PROPOSAL_SUB100'),
            self::SUBSTATUS_PROPOSAL_SUB200 => Mage::helper('qquoteadv')->__('SUBSTATUS_PROPOSAL_SUB200'),
            self::SUBSTATUS_PROPOSAL_SUB300 => Mage::helper('qquoteadv')->__('SUBSTATUS_PROPOSAL_SUB300'),
            self::SUBSTATUS_SAVED_SUB100 => Mage::helper('qquoteadv')->__('SUBSTATUS_SAVED_SUB100'),
            self::SUBSTATUS_SAVED_SUB200 => Mage::helper('qquoteadv')->__('SUBSTATUS_SAVED_SUB200'),
        );

        if (count($substatuses) > 0) {
            return $substatuses;
        }

        return false;

    }

    // #################### From here no customization needed #######################

    /**
     * Create Option Array for massupdate action
     *
     * @return array
     */
    static public function getChangeSubOptionArray($statusArray, $substatus = false)
    {
        $returnArray = array();
        $subStatusArray = self::_createSubStatusArray();

        if (is_array($subStatusArray) && count($subStatusArray) > 0) {
            foreach ($statusArray as $mainStatus) {
                $returnArray[] = $mainStatus;
                if (isset($subStatusArray[$mainStatus['value']])):
                    foreach ($subStatusArray[$mainStatus['value']] as $k => $v) {
                        $label = ($substatus) ? $mainStatus['label'] . ' ' . Mage::helper('qquoteadv')->__($v) : $mainStatus['label'];
                        $returnArray[] = array('value' => $mainStatus['value'] . self::EXPLODE_CHARACTER . $k, 'label' => $label);
                    }
                endif;
            }
        }

        return $returnArray;
    }

    /**
     * Create Option Array for Option listing
     *
     * @return array
     */
    static public function getSubOptionArray($statusArray, $substatus = false)
    {
        $returnArray = array();
        $subStatusArray = self::_createSubStatusArray();

        if (is_array($subStatusArray) && count($subStatusArray) > 0) {
            foreach ($statusArray as $mainId => $mainLabel) {
                $returnArray[$mainId] = $mainLabel;
                if (isset($subStatusArray[$mainId])):
                    foreach ($subStatusArray[$mainId] as $k => $v) {
                        $label = ($substatus) ? $mainLabel . ' ' . $v : $mainLabel;
                        $returnArray[$mainId . self::EXPLODE_CHARACTER . $k] = $label;
                    }
                endif;
            }
        }

        return $returnArray;
    }


    /**
     * Create ordered array of substatusses
     *
     * @return Array
     */
    static protected function _createSubStatusArray()
    {

        $returnArray = array();
        $subStatusArray = self::substatuses();

        foreach ($subStatusArray as $k => $v) {
            $subKeys = explode(self::EXPLODE_CHARACTER, $k);
            if (is_array($subKeys) && count($subKeys) >= 2) {
                $returnArray[$subKeys[0]][$subKeys[1]] = $v;
            }
        }

        return $returnArray;
    }

    /**
     * Check for substatus
     *
     * @param string $status
     * @return Varien Object
     */
    public function getStatus($status)
    {
        $subStatus = explode(self::EXPLODE_CHARACTER, $status);

        if (count($subStatus) > 1) {
            $this->setStatus((int)$subStatus[0]);
            $this->setSubstatus($status);
        } else {
            $this->setStatus((int)$status);
            $this->setSubstatus();
        }

        return $this;
    }

    /**
     * Get parent status from substatus
     *
     * @param int | string $subStatus
     * @return boolean | Array
     */
    static public function getParentStatus($subStatus)
    {
        $statusArray = explode(self::EXPLODE_CHARACTER, $subStatus);

        if (isset($statusArray[0]) && $statusArray[0] != null) {
            return (int)$statusArray[0];
        }
        return false;
    }

    static public function getCurrentStatus($status, $substatus = null)
    {
        $gridOptionArray = Mage::getModel('qquoteadv/status')->getGridOptionArray(true);
        if ($substatus != null && self::getParentStatus($substatus) == $status) {
            return $gridOptionArray[$substatus];
        }
        if (isset($gridOptionArray[$status])) {
            return $gridOptionArray[$status];
        } else {
            // If status is not found
            // set a default status
            return Ophirah_Qquoteadv_Model_Status::STATUS_PROPOSAL_BEGIN;
        }
    }

    /**
     * Prevent setting hold status again
     * when unholding quote
     *
     * @param Ophirah_Qquoteadv_Model_Qqadvcustomer $quote
     */
    public function checkUnholdStatus(Ophirah_Qquoteadv_Model_Qqadvcustomer $quote)
    {
        // Check for old status
        if ($this->getData('status') == Ophirah_Qquoteadv_Model_Status::STATUS_PROPOSAL_SAVED) {
            $this->setData('substatus', null);
            if (Mage::helper('qquoteadv')->isDate($quote->getData('proposal_sent'))) {
                $this->setData('status', Ophirah_Qquoteadv_Model_Status::STATUS_PROPOSAL);
            } else {
                $this->setData('status', Ophirah_Qquoteadv_Model_Status::STATUS_PROPOSAL_BEGIN);
            }
        }

        return $this;
    }

}
