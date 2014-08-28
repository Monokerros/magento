<?php

class Ophirah_Qquoteadv_Model_Status
    extends Mage_Core_Model_Abstract
{
    CONST STATUS_BEGIN = 1;
    CONST STATUS_PROPOSAL_BEGIN = 10;
    CONST STATUS_REQUEST = 20;
    CONST STATUS_REQUEST_EXPIRED = 21;
    CONST STATUS_REJECTED = 30;
    CONST STATUS_CANCELED = 40;

    CONST STATUS_PROPOSAL = 50;
    CONST STATUS_PROPOSAL_EXPIRED = 51;
    CONST STATUS_PROPOSAL_SAVED = 52;
    CONST STATUS_AUTO_PROPOSAL = 53;

    CONST STATUS_DENIED = 60;
    CONST STATUS_CONFIRMED = 70;
    CONST STATUS_ORDERED = 71;

    static public function getOptionArray($substatus = false)
    {
        $optionArray = array(
            self::STATUS_BEGIN => Mage::helper('qquoteadv')->__('STATUS_BEGIN'),
            self::STATUS_PROPOSAL_BEGIN => Mage::helper('qquoteadv')->__('STATUS_PROPOSAL_BEGIN'),
            self::STATUS_REQUEST => Mage::helper('qquoteadv')->__('STATUS_REQUEST'),
            self::STATUS_REQUEST_EXPIRED => Mage::helper('qquoteadv')->__('STATUS_REQUEST_EXPIRED'),
            self::STATUS_PROPOSAL => Mage::helper('qquoteadv')->__('STATUS_PROPOSAL'),
            self::STATUS_PROPOSAL_EXPIRED => Mage::helper('qquoteadv')->__('STATUS_PROPOSAL_EXPIRED'),
            self::STATUS_PROPOSAL_SAVED => Mage::helper('qquoteadv')->__('STATUS_PROPOSAL_SAVED'),
            self::STATUS_AUTO_PROPOSAL => Mage::helper('qquoteadv')->__('STATUS_AUTO_PROPOSAL'),
            self::STATUS_CANCELED => Mage::helper('qquoteadv')->__('STATUS_CANCELED'),
            self::STATUS_DENIED => Mage::helper('qquoteadv')->__('STATUS_DENIED'),
            self::STATUS_CONFIRMED => Mage::helper('qquoteadv')->__('STATUS_CONFIRMED'),
            self::STATUS_ORDERED => Mage::helper('qquoteadv')->__('STATUS_ORDERED'),
        );

        // Add Substatuses
        if (Mage::getModel('qquoteadv/substatus')->substatuses() && $substatus === true) {
            $optionArray = Mage::getModel('qquoteadv/substatus')->getSubOptionArray($optionArray, $substatus);
            return $optionArray;
        }

        return $optionArray;

    }

    static public function getGridOptionArray($substatus = false)
    {
        $gridOptionArray = array(
            self::STATUS_REQUEST => Mage::helper('qquoteadv')->__('STATUS_REQUEST'),
            self::STATUS_PROPOSAL_BEGIN => Mage::helper('qquoteadv')->__('STATUS_PROPOSAL_BEGIN'),
            self::STATUS_REQUEST_EXPIRED => Mage::helper('qquoteadv')->__('STATUS_REQUEST_EXPIRED'),
            self::STATUS_PROPOSAL => Mage::helper('qquoteadv')->__('STATUS_PROPOSAL'),
            self::STATUS_PROPOSAL_EXPIRED => Mage::helper('qquoteadv')->__('STATUS_PROPOSAL_EXPIRED'),
            self::STATUS_PROPOSAL_SAVED => Mage::helper('qquoteadv')->__('STATUS_PROPOSAL_SAVED'),
            self::STATUS_AUTO_PROPOSAL => Mage::helper('qquoteadv')->__('STATUS_AUTO_PROPOSAL'),
            self::STATUS_CANCELED => Mage::helper('qquoteadv')->__('STATUS_CANCELED'),
            self::STATUS_DENIED => Mage::helper('qquoteadv')->__('STATUS_DENIED'),
            self::STATUS_CONFIRMED => Mage::helper('qquoteadv')->__('STATUS_CONFIRMED'),
            self::STATUS_ORDERED => Mage::helper('qquoteadv')->__('STATUS_ORDERED'),
        );

        // Check for substatuses
        if (Mage::getModel('qquoteadv/substatus')->substatuses() && $substatus === true) {
            $gridOptionArray = Mage::getModel('qquoteadv/substatus')->getSubOptionArray($gridOptionArray, $substatus);
        }

        return $gridOptionArray;


    }

    static public function getChangeOptionArray($substatus = false)
    {
        $changeOptionArray = array(
            array('value' => self::STATUS_PROPOSAL_BEGIN, 'label' => Mage::helper('qquoteadv')->__('STATUS_PROPOSAL_BEGIN')),
            array('value' => self::STATUS_REQUEST, 'label' => Mage::helper('qquoteadv')->__('STATUS_REQUEST')),
            array('value' => self::STATUS_REQUEST_EXPIRED, 'label' => Mage::helper('qquoteadv')->__('STATUS_REQUEST_EXPIRED')),
            array('value' => self::STATUS_PROPOSAL, 'label' => Mage::helper('qquoteadv')->__('STATUS_PROPOSAL')),
            array('value' => self::STATUS_PROPOSAL_EXPIRED, 'label' => Mage::helper('qquoteadv')->__('STATUS_PROPOSAL_EXPIRED')),
            array('value' => self::STATUS_PROPOSAL_SAVED, 'label' => Mage::helper('qquoteadv')->__('STATUS_PROPOSAL_SAVED')),
//            array('value' => self::STATUS_AUTO_PROPOSAL,    'label'=>Mage::helper('qquoteadv')->__('STATUS_AUTO_PROPOSAL')),
            array('value' => self::STATUS_CANCELED, 'label' => Mage::helper('qquoteadv')->__('STATUS_CANCELED')),
            array('value' => self::STATUS_DENIED, 'label' => Mage::helper('qquoteadv')->__('STATUS_DENIED')),
            array('value' => self::STATUS_CONFIRMED, 'label' => Mage::helper('qquoteadv')->__('STATUS_CONFIRMED')),
            array('value' => self::STATUS_ORDERED, 'label' => Mage::helper('qquoteadv')->__('STATUS_ORDERED')),
        );

        // Check for substatuses
        if (Mage::getModel('qquoteadv/substatus')->substatuses() && $substatus === true) {
            $changeOptionArray = Mage::getModel('qquoteadv/substatus')->getChangeSubOptionArray($changeOptionArray, $substatus);
        }

        return $changeOptionArray;
    }

    static public function statusAllowed()
    {

        $statusAllowed = array(self::STATUS_BEGIN,
            self::STATUS_PROPOSAL_BEGIN,
            self::STATUS_REQUEST,
            self::STATUS_PROPOSAL,
            self::STATUS_PROPOSAL_SAVED,
            self::STATUS_AUTO_PROPOSAL
        );

        return $statusAllowed;
    }

    /**
     * Statuses that needs to be filtered
     * for setting quote to expired.
     *
     * @return array
     */
    static public function statusExpire()
    {

        $statusExpire = array(self::STATUS_PROPOSAL,
            self::STATUS_PROPOSAL_SAVED,
            self::STATUS_AUTO_PROPOSAL
        );

        return $statusExpire;
    }

    /**
     * Create status update object for
     * Ophirah_Qquoteadv_Adminhtml_QquoteadvController::massStatusAction()
     *
     * @param string $status
     * @return \Varien_Object
     */
    public function getStatus($status)
    {
        // Check for substatuses
        if (Mage::getModel('qquoteadv/substatus')->substatuses()) {
            $return = Ophirah_Qquoteadv_Model_Substatus::getStatus($status);
        } else {
            $return = new Varien_Object();
            $return->setStatus((int)$status);
        }

        return $return;
    }

}
