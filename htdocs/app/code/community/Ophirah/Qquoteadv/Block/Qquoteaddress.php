<?php

class Ophirah_Qquoteadv_Block_Qquoteaddress extends Mage_Core_Block_Template
{

    public function _prepareLayout()
    {
        return parent::_prepareLayout();
    }

    /**
     * Get customer session data
     * @return session data
     */
    public function getCustomerSession()
    {
        return Mage::getSingleton('customer/session');
    }

    public function getCustomerEmail()
    {
        return $this->getCustomerSession()->getCustomer()->getEmail();
    }

    public function isCustomerLoggedIn()
    {
        return $this->getCustomerSession()->isLoggedIn();
    }

    public function getValue($fieldname, $type)
    {
        if ($value = $this->_getRegisteredValue($type)) {

            // When quote data is stored
            // address data is an array
            // Create object from array
            if(is_array($value)){
                $newValue = new Varien_Object();
                $newValue->setData($value);
                $value= $newValue;
            }

            if ($fieldname == "street1") {
                $street = $value->getData('street');
                if (is_array($street)) {
                    $street = explode("\n", $street);
                    return $street[0];
                } else {
                    return "";
                }
            }

            if ($fieldname == "street2") {
                $street = $value->getData('street');

                if (is_array($street)) {
                    $street = explode("\n", $street);
                    return $street[1];
                } else {
                    return "";
                }
            }

            if ($fieldname == "email") {
                return $this->getCustomerSession()->getCustomer()->getEmail();
            }

            if ($fieldname == "country") {
                $countryCode = $value->getData("country_id");
                return $countryCode;
            }
            return $value->getData($fieldname);
        }
    }

    protected function _getRegisteredValue($type = 'billing')
    {

        // When Quote Shipping Estimate is requested
        // use data from session
        if ($quoteAddresses = $this->getCustomerSession()->getData('quoteAddresses')) {

            if ($type == 'billing' && isset($quoteAddresses['billingAddress'])) {
                return $quoteAddresses['billingAddress'];
            }

            if ($type == 'shipping' && isset($quoteAddresses['shippingAddress'])) {
                return $quoteAddresses['shippingAddress'];
            }
        }
        // Default data
        if ($type == 'billing') {
            return $this->getCustomerSession()->getCustomer()->getPrimaryBillingAddress();
        }

        if ($type == 'shipping') {
            return $this->getCustomerSession()->getCustomer()->getPrimaryShippingAddress();
        }
    }

    public function getLoginUrl()
    {

        if (!Mage::getStoreConfigFlag('customer/startup/redirect_dashboard')) {
            return $this->getUrl('customer/account/login/', array('referer' => $this->getUrlEncoded('*/*/*', array('_current' => true))));
        }

        return $this->getUrl('customer/account/login/');
    }

    /**
     * Retrieve storeConfigData from
     * config_data table
     *
     * @param $fieldPrefix
     * @param $field
     * @param string $storeId
     * @return bool|mixed
     */
    public function getStoreConfigSetting($fieldPrefix, $field, $storeId = "1")
    {
        $return = false;

        if ($fieldPrefix != null && $field != null) {
            $storeSetting = Mage::getStoreConfig($fieldPrefix . $field, $storeId);
            if ($storeSetting > 0) {
                $return = $storeSetting;
            }
        }

        return $return;
    }

    /**
     * Check is field is required in
     * the store config settings
     *
     * @param $fieldPrefix
     * @param $field
     * @param string $storeId
     * @return bool|Varien_Object
     */
    public function isRequired($fieldPrefix, $field, $storeId = "1")
    {
        $storeSetting = $this->getStoreConfigSetting($fieldPrefix, $field, $storeId);

        if (!$storeSetting) {
            return false;
        }

        $return = new Varien_Object;
        $required = '<span class="required">*</span>';
        $class = 'required-entry';
        if ((int)$storeSetting == 2) {
            $return->setData('required', $required);
            $return->setData('class', $class);
            return $return;
        }

        return $return;
    }
}
