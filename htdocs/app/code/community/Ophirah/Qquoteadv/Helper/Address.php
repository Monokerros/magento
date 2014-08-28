<?php

class Ophirah_Qquoteadv_Helper_Address
    extends Mage_Core_Helper_Abstract
{
    CONST ADDRESS_TYPE_BILLING = 'billing';
    CONST ADDRESS_TYPE_SHIPPING = 'shipping';

    /**
     * Array with address fields that can
     * be filled out and stored with the quote
     *
     * @return Array
     */
    public function addressFieldsArray()
    {
        return array('prefix',
            'firstname',
            'middlename',
            'lastname',
            'suffix',
            'company',
            'country_id',
            'region',
            'region_id',
            'city',
            'address',
            'postcode',
            'telephone',
            'fax',
            'vat_id',
            'vat_is_valid',
            'vat_request_id',
            'vat_request_date',
            'vat_request_success'
        );
    }

    /**
     * Addresstypes
     *
     * @return array
     */
    public function getAddressTypes()
    {
        return array(self::ADDRESS_TYPE_BILLING, self::ADDRESS_TYPE_SHIPPING);
    }

    /* Adding Quote address to customer
     * 
     * @param   int/Mage_Customer_Model_Customer
     * @param   array                          // Array with address information
     * @param   array                           // Variables for default settings
     */
    public function addQuoteAddress($customerId, $addressData, $vars = NULL)
    {
        if ($customerId instanceof Mage_Customer_Model_Customer) {
            $customerId = $customerId->getId();
        }

        if ($vars == NULL) {
            $vars['saveAddressBook'] = 1;
            $vars['defaultShipping'] = 0;
            $vars['defaultBilling'] = 0;
        }

        $customAddress = Mage::getModel('customer/address');
        $customAddress->setData($addressData)
            ->setCustomerId($customerId)
            ->setSaveInAddressBook($vars['saveAddressBook'])
            ->setIsDefaultShipping($vars['defaultShipping'])
            ->setIsDefaultBilling($vars['defaultBilling']);

        try {
            $customAddress->save();
        } catch (Exception $e) {
            Mage::log($e->getMessage());
        }
    }

    /**
     * Add new address in database
     * table: 'quoteadv_quote_address'
     *
     * @param integer $quoteId
     * @param array $addressData
     * @return boolean
     */
    public function addAddress($quoteId, $addressData, $check = null)
    {
        if (!(int)$quoteId) {
            return false;
        }
        $addressTypes = $this->getAddressTypes();
        $sameAsBillling = '0';
        $prevData = null;
        foreach ($addressTypes as $type) {
            if (isset($addressData[$type])) {
                $typeData = $addressData[$type];
                if (is_array($typeData)) {
                    $addData = $typeData;
                } elseif (is_object($typeData)) {
                    $addData = $typeData->getData();
                }
            }

            // add Billing before Shipping
            if ($prevData == $addData && $addData != null) {
                $sameAsBillling = '1';
            }

            $newAddress = Mage::getModel('qquoteadv/quoteaddress');
            if (isset($addData)) {
                $newAddress->addData($addData);
                unset($addData);
            }
            if ($type == self::ADDRESS_TYPE_SHIPPING && $sameAsBillling == '1') {
                $newAddress->setData('same_as_billing', $sameAsBillling);
            } else {

            }
            $newAddress->setData('quote_id', $quoteId);
            $newAddress->setData('address_type', $type);

            try {
                $newAddress->save();
            } catch (Exception $e) {
                Mage::log($e->getMessage());
            }

            $prevData = $addData;
        }
    }

    /**
     * Update address associated with the quote
     *
     * @param Ophirah_Qquoteadv_Model_Qqadvcustomer $quote
     */
    public function updateAddress(Ophirah_Qquoteadv_Model_Qqadvcustomer $quote)
    {
        $quoteAddresses = $this->getAddresses($quote);
        $addressCollection = $this->getAddressCollection($quote->getData('quote_id'));

        if ($addressCollection):
            foreach ($addressCollection as $address):
                $type = 'shippingAddress';
                $addressType = self::ADDRESS_TYPE_SHIPPING;
                if ($address->getData('address_type') == self::ADDRESS_TYPE_BILLING) {
                    $type = 'billingAddress';
                    $addressType = self::ADDRESS_TYPE_BILLING;
                }
                if (isset($quoteAddresses[$type])) {
                    $address->addData($quote->getData());
                    $address->addData($quoteAddresses[$type]);
                    // Make sure the address_type remains
                    $address->setData('address_type', $addressType);
                    if (!$address->getData('same_as_billing')) {
                        $address->setData('same_as_billing', '0');
                    }
                    $address->save();
                }

            endforeach;
        endif;
    }

    /**
     * Get addresses associated with the
     * quote in an array
     *
     * @param integer $quoteId
     * @return boolean / array
     */
    public function getAddressCollectionArray($quoteId)
    {
        $return = false;
        if (!(int)$quoteId) {
            return $return;
        }

        // collect addresses from table
        $DBaddresses = $this->getAddressCollection($quoteId);

        if ($DBaddresses) {
            foreach ($DBaddresses as $DBaddress) {
                if ($DBaddress) {
                    $return[$DBaddress->getData('address_type')] = $DBaddress;
                }
            }
        }

        return $return;
    }

    /**
     * Retrieve address collection
     * from database
     *
     * @param integer $quoteId
     * @return boolean / Ophirah_Qquoteadv_Model_Mysql4_Quoteaddress_Collection
     */
    public function getAddressCollection($quoteId)
    {
        if ((int)$quoteId) {
            $return = Mage::getModel('qquoteadv/quoteaddress')
                ->getCollection()
                ->addFieldToFilter('quote_id', array('eq' => $quoteId))
                ->load();

            if (count($return) > 0) {
                return $return;
            } else {
                // For older quotes try building address first
                $this->buildQuoteAdresses(Mage::getModel('qquoteadv/qqadvcustomer')->load($quoteId), false);

                $return = Mage::getModel('qquoteadv/quoteaddress')
                    ->getCollection()
                    ->addFieldToFilter('quote_id', array('eq' => $quoteId))
                    ->load();

                if ($return) {
                    return $return;
                }
            }
        }

        return false;
    }

    /**
     * Collect Mage_Sales_Model_Quote_Address from
     * Ophirah_Qquoteadv_Model_Qqadvcustomer quote addresses
     *
     * @param   Ophirah_Qquoteadv_Model_Qqadvcustomer $quote
     * @return  Array
     */
    public function buildQuoteAdresses(Ophirah_Qquoteadv_Model_Qqadvcustomer $quote, $collect = true)
    {

        $customerId = $quote->getData('customer_id');
        $storeId = $quote->getData('store_id');
        $quoteCollection = array();
        $return = array();
        // extract address info
        $quoteAddresses = $this->getAddresses($quote);

        if ($collect === true) {
            $quoteCollection = $this->getAddressCollectionArray($quote->getData('quote_id'));
        }

        if (isset($quoteCollection[self::ADDRESS_TYPE_BILLING])) {
            $billingAddress = $quoteCollection[self::ADDRESS_TYPE_BILLING];
            // update 'updated at'
            $billingAddress->setData('updated_at', $quote->getData('updated_at'));
            // set 'address' same as 'street'
            // TODO: remove all 'address' from code
            $billingAddress->setData('address', $billingAddress->getData('street'));
            $billingAddress->save();
        } else {

            // build billingaddres
            /** @var Ophirah_Qquoteadv_Model_Quoteaddress */
            $billingAddress = Mage::getModel('qquoteadv/quoteaddress');
            $billingAddress->setData($quote->getData());
            $addressData = $this->getQuoteAddress($customerId, $quoteAddresses['billingAddress'], $storeId, self::ADDRESS_TYPE_BILLING);
            $billingAddress->addData($addressData->getData());
            $billingAddress->save();
        }

        $return['billingAddress'] = $billingAddress;

        if (isset($quoteCollection[self::ADDRESS_TYPE_SHIPPING])) {
            $shippingAddress = $quoteCollection[self::ADDRESS_TYPE_SHIPPING];
            // update 'updated at'
            $shippingAddress->setData('updated_at', $quote->getData('updated_at'));
            // set 'address' same as 'street'
            // TODO: remove all 'address' from code
            $shippingAddress->setData('address', $shippingAddress->getData('street'));
            // Create old quote 'shipping_data'
            $shippingAddress = $this->convertToShipping($shippingAddress);
            $shippingAddress->save();
        } else {

            // build shippingaddres
            /** @var Ophirah_Qquoteadv_Model_Quoteaddress */
            $shippingAddress = Mage::getModel('qquoteadv/quoteaddress');
            $shippingAddress->setData($quote->getData());
            $addressData = $this->getQuoteAddress($customerId, $quoteAddresses['shippingAddress'], $storeId, self::ADDRESS_TYPE_SHIPPING);
            $shippingAddress->addData($addressData->getData());
            $shippingAddress->save();
        }

        $return['shippingAddress'] = $shippingAddress;

        return $return;

    }

    /**
     * Update 'shipping_*' data from
     * quote with shipping data from database
     * Needed when converting back.
     * 'shipping_' data gets converted to default address data.
     *
     * @param Ophirah_Qquoteadv_Helper_Address
     * @return Ophirah_Qquoteadv_Helper_Address
     */
    public function convertToShipping($shippingAddress)
    {
        $addressData = $this->addressFieldsArray();

        foreach ($addressData as $field) {
            $shippingAddress->setData('shipping_' . $field, $shippingAddress->getData($field));
        }

        return $shippingAddress;

    }

    /**
     * Builds array with seperated
     * shipping and billing address
     *
     * @param   Ophirah_Qquoteadv_Model_Qqadvcustomer $quote
     * @return  Array
     */
    public function getAddresses(Ophirah_Qquoteadv_Model_Qqadvcustomer $quote)
    {

        $returnData = ($quote->getData('address_type')) ? $quote->getData('address_type') : 'all';
        $addressData = $this->addressFieldsArray();

        foreach ($addressData as $data) {
            $shippingData[$data] = $quote->getData('shipping_' . $data);
            $billingData[$data] = $quote->getData($data);
        }

        // set address types
        $billingData['address_type'] = self::ADDRESS_TYPE_BILLING;
        $shippingData['address_type'] = self::ADDRESS_TYPE_SHIPPING;

        // Fix naming issue
        // set street data
        if (isset($billingData['address'])) {
            $billingData['street'] = $billingData['address'];
        }
        if (isset($shippingData['address'])) {
            $shippingData['street'] = $shippingData['address'];
        }

        if ($returnData == self::ADDRESS_TYPE_SHIPPING || $returnData == 'all') {
            $return['shippingAddress'] = $shippingData;
        }
        if ($returnData == self::ADDRESS_TYPE_BILLING || $returnData == 'all') {
            $return['billingAddress'] = $billingData;
        }

        return $return;

    }

    /**
     * Creates a Mage_Sales_Model_Quote_Address object
     * from the address array
     *
     * @param   Object /int/string       $customer        // instanceof Mage_Customer_Model_Customer
     * @param   Array $quoteAddress
     * @param   int $storeId
     * @param   string $addressType
     *
     * @return  Mage_Sales_Model_Quote_Adress
     */
    public function getQuoteAddress($customer, $quoteAddress, $storeId, $addressType)
    {

        try {
            if (!is_object($customer)) {
                if (!is_array($customer)) {
                    $customerId = (int)$customer;
                }

            } else {
                $customerId = $customer->getId();
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }

        $addressArray = $this->addressFieldsArray();
        /* @var Mage_Sales_Model_Quote_Address */
        $returnAddress = Mage::getModel('sales/quote_address')
            ->setStoreId($storeId)
            ->setAddressType($addressType)
            ->setCustomerId($customerId)
            ->setStreet($quoteAddress['address']);

        //Add other data
        foreach ($addressArray as $field) {
            if ($field != 'address') {
                $returnAddress->setData($field, $quoteAddress[$field]);
            }
        }

        return $returnAddress;

    }

    /**
     * Addres params to fill out
     *
     * @return  array       // Address parameters
     */
    public function getAddressParams()
    {

        // Address information
        $addressParams['addressFields'] = array('address',
            'postcode',
            'city',
            'country_id',
            'region_id',
            'region'
        );
        // Customer information
        $addressParams['customerFields'] = array('prefix',
            'firstname',
            'middlename',
            'lastname',
            'suffix',
            'telephone',
            'company',
            'email',
            'fax',
            'vat_id'
        );
        return $addressParams;

    }

    /*  Copy address information between
     *  billing and shipping if "are the same"
     *  is selected
     * 
     *  @param      array       // Addres Params from post
     *  @return     array       // complete address info
     */

    public function buildAddress($paramsAddress)
    {

        $addressParams = $this->getAddressParams();

        $emptyBillField = false;
        $emptyShipField = false;
        $regionIsSet = false;
        $regionShipIsSet = false;
        $regionBillIsSet = false;

        // Shipping is Billing
        if (isset($paramsAddress['shipIsBill'])) {
            foreach ($addressParams['customerFields'] as $field) {
                $value = (isset($paramsAddress[$field])) ? $paramsAddress[$field] : '';
                $paramsAddress['shipping_' . $field] = $value;
                $paramsAddress['shipping'][$field] = $value;
            }
            foreach ($addressParams['addressFields'] as $field) {
                $value = (isset($paramsAddress[$field])) ? $paramsAddress[$field] : '';
                if ($field == 'region' || $field == 'region_id') {
                    if ($value != '') {
                        $regionIsSet = true;
                    }
                } elseif ($value == '') {
                    $emptyBillField = true;
                    $emptyShipField = true;
                }
                $fieldAlt = ($field == 'address') ? 'street' : $field;
                $paramsAddress['shipping_' . $field] = $value;
                $paramsAddress['shipping'][$fieldAlt] = $value;
            }
            $paramsAddress['billing'] = $paramsAddress['shipping'];

            // Billing is Shipping
        } elseif (isset($paramsAddress['billIsShip'])) {

            foreach ($addressParams['customerFields'] as $field) {
                $value = (isset($paramsAddress[$field])) ? $paramsAddress[$field] : '';
                $paramsAddress['billing'][$field] = $value;
                $paramsAddress['shipping_' . $field] = $value;
            }
            foreach ($addressParams['addressFields'] as $field) {
                $value = (isset($paramsAddress['shipping_' . $field])) ? $paramsAddress['shipping_' . $field] : '';
                if ($field == 'region' || $field == 'region_id') {
                    if ($value != '') {
                        $regionIsSet = true;
                    }
                } elseif ($value == '') {
                    $emptyBillField = true;
                    $emptyShipField = true;
                }
                $fieldAlt = ($field == 'address') ? 'street' : $field;
                $paramsAddress[$field] = $value;
                $paramsAddress['billing'][$fieldAlt] = $paramsAddress[$field];
            }
            $paramsAddress['shipping'] = $paramsAddress['billing'];

            // Both addresses are given or are empty
        } else {

            foreach ($addressParams['customerFields'] as $field) {
                $value = (isset($paramsAddress[$field])) ? $paramsAddress[$field] : '';
                $paramsAddress['shipping_' . $field] = $value;
                $paramsAddress['billing'][$field] = $value;
                $paramsAddress['shipping'][$field] = $value;
            }
            foreach ($addressParams['addressFields'] as $field) {
                $valueBill = (isset($paramsAddress[$field])) ? $paramsAddress[$field] : '';
                $valueShip = (isset($paramsAddress['shipping_' . $field])) ? $paramsAddress['shipping_' . $field] : '';
                if ($field == 'region' || $field == 'region_id') {
                    if ($valueBill != '') {
                        $regionBillIsSet = true;
                    }
                    if ($valueShip != '') {
                        $regionShipIsSet = true;
                    }
                } else {
                    if ($valueBill == '') {
                        $emptyBillField = true;
                    }
                    if ($valueShip == '') {
                        $emptyShipField = true;
                    }
                }
                $fieldAlt = ($field == 'address') ? 'street' : $field;
                $paramsAddress['billing'][$fieldAlt] = $valueBill;
                $paramsAddress['shipping'][$fieldAlt] = $valueShip;
            }

            if ($regionBillIsSet === true && $regionShipIsSet === true) {
                $regionIsSet = true;
            }

        }

        // remove invalid adresses
        if ($emptyBillField === true || $regionIsSet === false) {
            $paramsAddress['billing'] = array();
        }
        if ($emptyShipField === true || $regionIsSet === false) {
            $paramsAddress['shipping'] = array();
        }

        return $paramsAddress;
    }

    /*  Fill address with provided information
     * 
     *  @param      array       // address info to fill out
     *  @return     array       // with address info
     * 
     */
    public function fillAddress($addressInfo, $paramsAddress, $prefix = NULL)
    {
        $addressParams = $this->getAddressParams();

        foreach ($addressParams as $addressParam) {
            foreach ($addressParam as $field) {
                if ($field != "email") {
                    $fieldAlt = ($field == 'address') ? 'street' : $field;
                    if (isset($addressInfo[$fieldAlt])) {
                        $paramsAddress[$prefix . $field] = $addressInfo[$fieldAlt];
                    }
                }
            }
        }

        return $paramsAddress;
    }

    /**
     * Retrieve quote address info by
     * provided address type
     *
     * @param integer $quoteId
     * @param string $type
     * @return boolean | Ophirah_Qquoteadv_Model_Quoteaddress
     */
    public function getAddressInfoByType($quoteId, $type)
    {
        $collection = $this->getAddressCollection($quoteId);
        if ($collection) {
            foreach ($collection as $address) {
                if ($address->getData('address_type') == $type) {
                    return $address;
                }
            }
        }
        return false;
    }

}
