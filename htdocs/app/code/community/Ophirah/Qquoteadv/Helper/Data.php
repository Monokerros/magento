<?php

class Ophirah_Qquoteadv_Helper_Data extends Mage_Core_Helper_Abstract
{
    CONST CREATE_HASH = "opensource";

    // the date to expire this module at - the format is Ymd for instance for 7th of may 2012 this
    // would be 20120507
    public $_expiryDate = 20140901;

    public $_expiryText = 'You have been using the full version of Cart2Quote, we hope you had an awesome experience <strong>saving both time and money</strong> managing your quotations with Cart2Quote.<br /><br />
                                You are now using the free version of Cart2Quote, but you can simply <strong>unlock the back-end features</strong> again by ordering a license.';

    public $_trialText = 'Currently all features of Cart2Quote are <strong>unlocked</strong> so you can experience the full power of this Magento extension.<br /><br />
                            In <strong>%s days</strong> this trial will end, but no worries you will still be able to use all the <strong>free features</strong> of Cart2Quote.';

    public $_wrongQuoteText = 'This Quote has been created with another version of Cart2Quote. To experience <strong>the full power of Cart2Quote</strong>, create a new quote with this free version!<br /><br />
                                You are now using a Free Trial version of Cart2Quote, but you can simply <strong>unlock the back-end features and all quotes again</strong> by ordering a license.';

    /**
     * Get Last Quote information from qquote_product table for the particular customer
     * @return quote object
     */
    public function getLatestQuote()
    {
        // get customer quote data for the latest quote he/she has made
        // is_quote = 2 => 2 is for unsubmitted quote, 1 is for submitted quote
        $customerQuoteId = 0;

        // get unsubmitted quote by the customer
        $quoteRemaining = Mage::getModel('qquoteadv/qqadvcustomer')->getCollection()
            ->addFieldToFilter('customer_id', Mage::getSingleton('customer/session')->getId())
            ->addFieldToFilter('is_quote', 2)
            ->setOrder('quote_id', 'desc')
            ->setPageSize(1);
        // getting array from collection
        $quoteRemaining = $quoteRemaining->getData();

        // get latest quote by the customer
        $latestQuote = Mage::getModel('qquoteadv/qqadvcustomer')->getCollection()
            ->addFieldToFilter('customer_id', Mage::getSingleton('customer/session')->getId())
            ->setOrder('quote_id', 'desc')
            ->setPageSize(1);
        // getting array from collection
        $latestQuote = $latestQuote->getData();
        //echo "<pre>";print_r($latestQuote);print_r($quoteRemaining);
        if ($latestQuote != array()) {
            if ($quoteRemaining != array()) {
                if ($quoteRemaining[0]['quote_id'] >= $latestQuote[0]['quote_id']) {
                    $customerQuoteId = $quoteRemaining[0]['quote_id'];
                }
            } else {
                $customerQuoteId = $latestQuote[0]['quote_id'];
            }
        }
        return $customerQuoteId;
    }

    /**
     * Get Product information from qquote_product table
     * @return quote object
     */
    public function getQuote()
    {
        // if the customer is logged in
        $customerInfo = '';
        $customerQuoteId = 0;
        if (Mage::getSingleton('customer/session')->getId()) {
            $customerQuoteId = $this->getLatestQuote();
        }
        // if the customer has done quote previously
        if ($customerQuoteId > 0) {
            // settting the session quote id with the latest quote done by the customer
            Mage::getSingleton('customer/session')->setQuoteId($customerQuoteId);
        }
        $quoteId = Mage::getSingleton('customer/session')->getQuoteadvId();

        $quoteProduct = Mage::getModel('qquoteadv/qqadvproduct')->getCollection()
            ->addFieldToFilter('quote_id', $quoteId);

        return $quoteProduct;

    }

    /**
     * Get total number of items in quote
     * @return integer total number of items
     */
    public function getTotalQty()
    {
        $totalQty = 0;
        $quoteId = Mage::getSingleton('customer/session')->getQuoteadvId();

        if (!$quoteId) return $totalQty;

        $products = $this->getQuote();
        foreach ($products as $key => $value) {
            $totalQty += $value['qty'];
        }
        return $totalQty;
    }

    /**
     * get Total items text
     * @return string
     */
    public function totalItemsText()
    {
        $count = $this->getTotalQty();
        if ($count == 1) {
            $text = $this->__('My Quote (%s item)', $count);
        } elseif ($count > 0) {
            $text = $this->__('My Quote (%s items)', $count);
        } else {
            $text = $this->__('My Quote');
        }

        return $text;

    }


    /**
     * Get product customize options
     * @param object $product
     * @param array $attribute
     * @return array || false
     */
    public function getSimpleOptions($product, $attribute)
    {
        if (@array_key_exists('options', $attribute)) {
            if (is_array($attribute['options'])) {
                $options = array();
                foreach ($attribute['options'] as $k => $v) {
                    if ($v != '') {
                        if (!is_object($product->getOptionById($k))) continue;
                        $label = $product->getOptionById($k)->getTitle();
                        $values = $product->getOptionById($k)->getValues();

                        if (is_array($v)) {
                            $finalValue = "";
                            foreach ($v as $sk => $sv) {
                                try {
                                    if ($product->getOptionById($k)->getType() == "date") {
                                        $value = Mage::helper('core')->formatDate($sv, 'medium', false);
                                    } else if ($product->getOptionById($k)->getType() == "file") {
                                        $value = $v['title'];
                                    } else {
                                        $value = $values[$sv]->getTitle();
                                    }

                                    $_option = $product->getOptionById($k);
                                    if ($product->getOptionById($k)->getType() == "image" || $product->getOptionById($k)->getType() == "imagecheckbox") {
                                        $collection = Mage::getModel("imageoption/imageoption")->getCollection()->getTitleById($value);
                                        $imageName = "";
                                        $title = "";
                                        if ($collection->getSize() > 0) {
                                            foreach ($collection as $col) {
                                                $imageName = $col->getFilename();
                                                $title = $col->getTitle();
                                                break;
                                            }
                                            $storex = Mage::getStoreConfig("checkout/optionscust/imageoptionx");
                                            $storey = Mage::getStoreConfig("checkout/optionscust/imageoptiony");
                                            $showTitle = Mage::getStoreConfig("checkout/optionscust/titleshow");
                                            if ($storey == "") {
                                                $url = Mage::helper("imageoption")->getResizedUrl($imageName, $storex);
                                            } else {
                                                $url = Mage::helper("imageoption")->getResizedUrl($imageName, $storex, $storey);
                                            }

                                            if ($showTitle == '1') {
                                                $finalValue .= '<img src="' . $url . '"/><br/><span class="cart-image-option-title">' . $title . '</span>';
                                            } else {
                                                $finalValue .= '<img src="' . $url . '"/><br/>';
                                            }

                                        }

                                    } else { //elseif($product->getOptionById($k)->getType()=="checkbox"){

                                        //if($finalValue)
                                        //   $finalValue.=', '.$product->getOptionById($k)->getValueById($sv)->getTitle();
                                        //else
                                        if ($product->getOptionById($k)->getType() == "date" || $product->getOptionById($k)->getType() == "file") {
                                            $finalValue = $value;
                                        } else
                                            $finalValue = $product->getOptionById($k)->getValueById($sv)->getTitle();
                                    }

                                } catch (Exception $e) {
                                    Mage::log($e->getMessage());
                                }
                            }
                            //foreach

                            $options[$label] = $finalValue;


                        } else {
                            try {

                                if (isset($values[$v]) && is_object($values[$v])) {
                                    $value = $values[$v]->getTitle();
                                } else {
                                    $value = $v; //#text field
                                }
                                $_option = $product->getOptionById($k);
                                if ($product->getOptionById($k)->getType() == "image" || $product->getOptionById($k)->getType() == "imagecheckbox") {

                                    $collection = Mage::getModel("imageoption/imageoption")->getCollection()->getTitleById($value);
                                    $imageName = "";
                                    $title = "";
                                    if ($collection->getSize() > 0) {
                                        foreach ($collection as $col) {
                                            $imageName = $col->getFilename();
                                            $title = $col->getTitle();
                                            break;
                                        }


                                        $storex = Mage::getStoreConfig("checkout/optionscust/imageoptionx");
                                        $storey = Mage::getStoreConfig("checkout/optionscust/imageoptiony");
                                        $showTitle = Mage::getStoreConfig("checkout/optionscust/titleshow");
                                        if ($storey == "") {
                                            $url = Mage::helper("imageoption")->getResizedUrl($imageName, $storex);
                                        } else {
                                            $url = Mage::helper("imageoption")->getResizedUrl($imageName, $storex, $storey);
                                        }

                                        if ($showTitle == '1') {
                                            $value = '<img src="' . $url . '"/><br/><span class="cart-image-option-title">' . $title . '</span>';
                                        } else {
                                            $value = '<img src="' . $url . '"/>';
                                        }

                                    } else {
                                        $value = "";
                                    }
                                }
                                $options[$label] = $value;
                            } catch (Exception $e) {
                                Mage::log($e->getMessage());
                            }
                        }
                    }
                }
                return $options;
            }
            return false;
        }
    }

    public function resizeImage($imageUrl, $imageResized, $width = 80, $height = 80)
    {
        if (!file_exists($imageResized) && file_exists($imageUrl)) {
            $imageObj = new Varien_Image($imageUrl);
            $imageObj->constrainOnly(TRUE);
            $imageObj->keepAspectRatio(TRUE);
            $imageObj->keepFrame(FALSE);
            $imageObj->resize($width, $height);
            $imageObj->save($imageResized);
        }
    }

    // DEPRECATED!!
    // USE Mage::getModel('qquoteadv/status')->getOptionArray();
    public function getStatusArray()
    {
        return Mage::getSingleton('qquoteadv/status')->getOptionArray();
    }

    public function getStatus($id)
    {
        $params = $this->getStatusArray();
        return (isset($params[$id])) ? $params[$id] : null; //$status = $params[$id];
    }


    public function getTableName($tablename)
    {
        $return = "";

        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $results = $write->query("show tables;");

        $found = false;


        while ($line = $results->fetch()) {
            if (!$found) {
                foreach ($line as $name => $var) {
                    if (strpos($var, $tablename) === strlen($var) - strlen($tablename)) {
                        $return = $var;
                        $found = true;
                    }
                }
            }
        }

        return $return;
    }

    // verify if email already exists in database
    public function userEmailAlreadyExists($email)
    {

        $return = false;
        $customer = Mage::getModel('customer/customer');

        $customer->setWebsiteId(Mage::app()->getStore()->getWebsiteId())->loadByEmail($email);

        if ($customer->getId()) {
            $return = true;
        }

        return $return;
    }

    /**
     * Return quote shipping price by quote id
     *
     * @return decimal
     */

    public function getQquoteShipPriceById($quoteId)
    {
        $qquote = Mage::getModel('qquoteadv/qqadvcustomer')->load($quoteId);
//       return $qquote->getShippingBasePrice();
        return $qquote->getShippingPrice();
    }

    /**
     * Return quote shipping type by quote id
     *
     * @return decimal
     */

    public function getShipTypeByQuote($quoteId = '')
    {
        if (empty($quoteId)) {
            $quoteId = Mage::app()->getHelper('qquoteadv')->getProposalQuoteId();
        }

        if ($quoteId) {
            $qquote = Mage::getModel('qquoteadv/qqadvcustomer')->load($quoteId);
            if ($type = $qquote->getShippingType())
                return $type;
            else
                return '';
        } else
            return '';
    }


    /**
     * Return quote id when client made confirmation and
     *
     * @return int
     */
    public function getProposalQuoteId()
    {
        return Mage::getSingleton('core/session')->proposal_quote_id;
    }

    /**
     * Is set quote shipping price for quote
     *
     * @return bool
     */
    public function isSetQuoteShipPrice()
    {
        $result = false;

        if ($quoteId = Mage::app()->getHelper('qquoteadv')->getProposalQuoteId()) {
            $quoteShipPrice = Mage::app()->getHelper('qquoteadv')->getQquoteShipPriceById($quoteId);
            //0.00 is also price
            if ($quoteShipPrice > -1)
                $result = true;
        }

        return $result;
    }

    /**
     * Return price for selected attributes by configurable product
     *
     * @param int $productId
     * @param sting $selectedAttributes
     * @return float
     */
    public function getConfigurableFinalPrice($productId, $selectedAttributes)
    {

        $price = 0;

        /**
         * Cusomer selected attributesgetConfigurableFinalPrice
         */
        $selectedAtrb = unserialize($selectedAttributes);

        /**
         * Load Product to get Super attributes
         */
        $product = Mage::getModel("catalog/product")->load($productId);
        /**
         * Get Configurable Type Product Instace and get Configurable attributes collection
         */
        $attributeCollection = $product->getTypeInstance()->getConfigurableAttributes();
        $attributes = array();
        $options = array();
        $store = Mage::app()->getStore();
        $products = array();
        $allProducts = $product->getTypeInstance()->getUsedProducts(null, $product->getId());

        foreach ($allProducts as $p) {
            //if ($p->isSaleable()) {
            $productId = $p->getId();

            foreach ($attributeCollection as $attribute) {
                $productAttribute = $attribute->getProductAttribute();
                $attributeValue = $p->getData($productAttribute->getAttributeCode());
                if (!isset($options[$productAttribute->getId()])) {
                    $options[$productAttribute->getId()] = array();
                }

                if (!isset($options[$productAttribute->getId()][$attributeValue])) {
                    $options[$productAttribute->getId()][$attributeValue] = array();
                }
                $options[$productAttribute->getId()][$attributeValue][] = $productId;
            }

            //}
        }

        foreach ($attributeCollection as $attribute) {
            $productAttribute = $attribute->getProductAttribute();
            $attributeId = $productAttribute->getId();

            $optionPrices = array();
            $prices = $attribute->getPrices(); //print_r($prices);
            if (is_array($prices)) {
                foreach ($prices as $value) {
                    if (!$this->_validateAttributeValue($attributeId, $value, $options)) {
                        continue;
                    }

                    if (isset($selectedAtrb['super_attribute']) && $value['value_index'] != $selectedAtrb['super_attribute'][$attributeId]) {
                        continue;
                    }

                    $optionPrices[] = $this->_preparePrice($value['pricing_value'], $value['is_percent']);

                }
            }

        }
        if (count($optionPrices)) {
            $price = array_sum($optionPrices);
        }

        return $price;
    }

    /**
     * Validating of super product option value
     *
     * @param array $attribute
     * @param array $value
     * @param array $options
     * @return boolean
     */
    protected function _validateAttributeValue($attributeId, &$value, &$options)
    {
        if (isset($options[$attributeId][$value['value_index']])) {
            return true;
        }

        return false;
    }

    protected function _preparePrice($price, $isPercent = false)
    {
        if ($isPercent && !empty($price)) {
            $price = $this->getProduct()->getFinalPrice() * $price / 100;
        }

        return $this->_convertPrice($price, true);
    }

    protected function _convertPrice($price, $round = false)
    {
        if (empty($price)) {
            return 0;
        }

        $price = Mage::app()->getStore()->convertPrice($price);
        if ($round) {
            $price = Mage::app()->getStore()->roundPrice($price);
        }


        return $price;
    }

    /**
     * Validation of super product option
     *
     * @param array $info
     * @return boolean
     */
    protected function _validateAttributeInfo(&$info)
    {
        if (count($info['options']) > 0) {
            return true;
        }
        return false;
    }

    /**
     * Get country name by country code
     * @param string $countryCode
     * @return string country name
     */
    public function getCountryName($countryCode)
    {
        return Mage::getModel('directory/country')->load($countryCode)->getName();
    }

    /**
     * Get region name by region code
     * @param string $regionCode
     * @return string region name
     */
    public function getRegionName($regionCode)
    {
        return Mage::getModel('directory/region')->load($regionCode)->getName();
    }

    public function isEnabled()
    {
        return Mage::getStoreConfig('qquoteadv/general/enabled', Mage::app()->getStore()->getStoreId());
    }

    /**
     * In case quote proposal will be confirmed then quote items will be removed to shopping cart with restriction to modify:
     *  -  qty per each item
     *  -  proposal price and items
     * So will be used param to check is current confirmation mode or not with quote proposal
     */
    public function isActiveConfirmMode($checkout = null)
    {
        $return = false;
        $value = Mage::getSingleton('core/session')->qquote_in_confirmation_mode;
        if (!empty($value) && (Mage::getStoreConfig('qquoteadv/general/quoteconfirmation') == 1 || $checkout === true)) {
            $return = true;
        }

        return $return;
    }

    public function setActiveConfirmMode($val)
    {
        if ($val)
            Mage::getSingleton('core/session')->qquote_in_confirmation_mode = $val;
        else
            Mage::getSingleton('core/session')->qquote_in_confirmation_mode = null;
    }

    // return array quotable items names
    public function getQuotableItems()
    {
        $items = array();

        foreach (Mage::getSingleton('checkout/session')->getQuote()->getAllVisibleItems() as $_item) {

            $allowed_to_quotemode = Mage::getModel("catalog/product")->load($_item->getProductId())->getAllowedToQuotemode();

            if ($allowed_to_quotemode) {
            } else {
                $items[] = $_item->getName();
            }

        }
        return $items;
    }

    protected function _prepareOptionPrice($price, $price_type = 'fixed', $optionPrice)
    {
        if ($price_type == 'fixed') {
            return $optionPrice;
        } else {
            if ($price > 0) {
                return $optionPrice * $price / 100;
            }
        }
        return $optionPrice;
    }

    function isValidHttp($value)
    {
        if (!preg_match('#^{{((un)?secure_)?base_url}}#', $value)) {
            $parsedUrl = parse_url($value);
            if (!isset($parsedUrl['scheme']) || !isset($parsedUrl['host'])) {
                return false;
            }
        }
        return true;
    }

    function getFullPath($path)
    {
        if (self::isValidHttp($path))
            return $path;
        else
            return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . $path;

    }

    public function getQuoteItem(Mage_Catalog_Model_Product $product, $attributes, $quote = null, Ophirah_Qquoteadv_Model_Qqadvproduct $quoteProduct = null, $originalPrice = false)
    {

        $product->setSkipCheckRequiredOption(true);
        if($originalPrice){
            //overwrite custom price
            $product->setData('special_price', $product->getPrice());
        }

        $productParams = new Varien_Object(unserialize($attributes));
        $virtualQuote = Mage::getModel('sales/quote');

        if ($quote == null) {
            $data = array('country_id' => 'US', 'region_id' => 12);
            $virtualQuote->getBillingAddress()->addData($data);
            $shippingAddress = $virtualQuote->getShippingAddress()->addData($data);
        } else {
            $shippingAddress = $quote->getShippingAddress();
            $data = array('country_id' => $shippingAddress->getData('country_id'),
                'region_id' => $shippingAddress->getData('region_id'));
            $virtualQuote->getBillingAddress()->addData($data);
            $shippingAddress = $virtualQuote->getShippingAddress()->addData($data);
        }

        try {
            $item = $virtualQuote->addProductAdvanced($product, $productParams);
            if ($quoteProduct && is_object($item) && is_array($item->getOptions())) {
                foreach ($item->getOptions() as $option) {
                    $optionId = $option->getCode();
                    if (strpos($optionId, 'option_') === false) continue;
                    $optionId = substr($optionId, 7);
                    if (!is_numeric($optionId)) continue;

                    $optionIdData = array('product' => $quoteProduct->getId(), 'option' => $optionId);
                    $option->setId(base64_encode(serialize($optionIdData)));
                }
            }

            $virtualQuote->collectTotals();
            return $virtualQuote;
        } catch (Exception $e) {
            //we receiving exception when product is out of stock
            return $virtualQuote;
        }
    }

    public function getQuoteItem2(Mage_Catalog_Model_Product $product, $attributes, $quote = null)
    {

        $product->setSkipCheckRequiredOption(true);
        if ($quote != null) {
            $quote2 = clone $quote;
            $items = $quote2->getAllItems();
            foreach ($items as $item) {
                // Product configuration is same as in other quote item
                $quote2->removeItem($item->getId());

            }

            $productParams = new Varien_Object(unserialize($attributes));


            try {
                $quote2->addProductAdvanced($product, $productParams);
                $quote2->collectTotals();
                return $quote2;
            } catch (Exception $e) {
                //we receiving exception when product is out of stock
                return $quote2;
            }
        }
    }

    /**
     *
     * @param array $address
     * @param string $prefix
     * @return string
     */
    public function getNameFormatted($address, $prefix = null)
    {

        $name = '';
        if ($address[$prefix . 'prefix']) {
            $name .= $address[$prefix . 'prefix'] . ' ';
        }

        $name .= $address[$prefix . 'firstname'] . ' ';

        if ($address[$prefix . 'middlename']) {
            $name .= $address[$prefix . 'middlename'] . ' ';
        }

        $name .= $address[$prefix . 'lastname'];

        if ($address[$prefix . 'suffix']) {
            $name .= ' ' . $address[$prefix . 'suffix'];
        }

        return trim($name);
    }

    /**
     * ### DEPRECATED, USE getNameFormatted()  ###
     * Get customer name from
     * shippingaddress
     *
     * @param array $quote
     * @return string
     */
    public function getShippingName($quote)
    {
        return $this->getNameFormatted($quote, 'shipping_');
    }

    /**
     * ### DEPRECATED, USE USE getNameFormatted() ###
     * Get customer name from
     * billingaddress
     *
     * @param array $quote
     * @return string
     */
    public function getBillingName($quote)
    {
        return $this->getNameFormatted($quote);
    }

    /**
     * Calculate price base on qty and product attributes
     * @param type $PK - table primary key
     * @param type $qty
     * @return null
     */
    public function _applyPrice($PK, $qty = 1, $currencyCode = false, $originalPrice = false)
    {
        $_collection = Mage::getModel('qquoteadv/qqadvproduct')->load($PK);
        if ($_collection) {
            $storeId = $_collection->getStoreId();
            if (!$storeId) {
                $storeId = Mage::app()->getStore()->getStoreId();
            }
            $_product = Mage::getModel('catalog/product')->setStoreId($storeId)->load($_collection->getProductId());

            $_product->setSkipCheckRequiredOption(true);
            $params = unserialize($_collection->getAttribute());
            $params['qty'] = $qty;

            if ($_product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
                return Mage::getModel('qquoteadv/bundle')->getOriginalPrice($_product, $params);
            }

            if($originalPrice){
                $quoteByProduct = Mage::helper('qquoteadv')->getQuoteItem($_product, serialize($params), null, null, true);
            } else {
                $quoteByProduct = Mage::helper('qquoteadv')->getQuoteItem($_product, serialize($params));
            }

            if ($quoteByProduct) {

                if (Mage::getStoreConfig('tax/calculation/price_includes_tax') == 1):
                    $basePrice = $quoteByProduct->getBaseGrandTotal();
                else:
                    $basePrice = $quoteByProduct->getBaseSubtotal();
                endif;

                $origPrice = $basePrice / ($qty > 0 ? $qty : 1);
            }
            if ($origPrice == 0) {
                $origPrice = $_product->getFinalPrice();
            }

            if ($currencyCode) {
                $baseCurrency = Mage::app()->getBaseCurrencyCode();
                $price = Mage::helper('directory')->currencyConvert($origPrice, $baseCurrency, $currencyCode);
            } else {
                $price = $origPrice;
            }

            return $price;
        }

        return null;
    }

    public function getOrderByC2Q($quote_id, $store_id)
    {
        $_collection = Mage::getModel('sales/order')->setStoreId($store_id)->getCollection()->addFieldToFilter("c2q_internal_quote_id", $quote_id);
        if ($_collection->getSize() > 0) {
            $data = array();

            foreach ($_collection as $order) {
                $data[$order->getId()] = $order->getIncrementId(); //$order->getRealOrderId()); //
            }
            return $data;
        } else {
            return null;
        }
    }


    public function getAdminEmail($id)
    {
        $uid = Mage::getModel('qquoteadv/qqadvcustomer')->load($id)->getData('user_id');
        $model = Mage::getModel('admin/user')->load($uid);
        if (!$model->getId()) {
            //return current session user email address
            return Mage::getSingleton('admin/session')->getUser()->getEmail();
        }
        return $model->getEmail(); //$model->getUsername();
    }


    public function getAdminName($id)
    {
        if (!$id) return null;
        $model = Mage::getModel('admin/user')->load($id);
        if (!$model->getId()) {
            return null;
        }

        return $model->getFirstname() . ' ' . $model->getLastname(); //$model->getUsername();
    }

    public function getAdmins()
    {
        return Mage::getModel('admin/user')->getCollection();
    }

    final public function hasExpired()
    {
        if ($this->_expiryDate < date("Ymd") and $this->_expiryDate !== false) return true;
        else    return false;
    }

    final public function validLicense($fnName, $createHash = NULL)
    {
        $standard = array(
            'create-edit-admin', // create & edit quotes in admin panel
            'convert-admin', // convert quotes to order in admin panel
            'my-quotes', // My Quote integration in Customer Dashboard
            'pdf-email-proposals', // Create PDF & email price proposals in mere seconds
            'attach-email-files', // Attach extra files with the price proposal email
            'auto-tier-prices' // Auto enter tier prices in price proposals*
        );
        $professional = array_merge($standard, array(
            'auto-proposal', // Auto submit quotations
            'link-rep2quote', // Link sales representatives to quotes
            'link-order2quote', // link orders to quotes
            'email-auto-login', // auto login links in emails

        ));

        $enterprise = array_merge($professional, array(
            'api', // API functionality for linking to CRM and ERP
            'export', // export quotes to csv
            'CRMaddon', // CRMaddon module
            'mass_update_quote_requests'
        ));

        $level = $this->getAccessLevel($createHash);

        switch ($level) {
            case null:
                return false;
                break;

            case 399430:
                $versionFns = $standard;
                break;

            case 599430:
                $versionFns = $professional;
                break;

            case 799430:
                $versionFns = $enterprise;
                break;
        }

        if (in_array($fnName, $versionFns)) return true;
        else return false;
    }

    // Check if quote is from current trial version
    // $createHash = array( 0 => [HASH], 1 => [INCREMENTID])
    final public function isTrialVersion($createHash = NULL)
    {
        if ($this->getAccessLevelFromKey() == null && $this->_expiryDate !== false) {
            if (is_array($createHash) && ($createHash[0] == $this->getCreateHash($createHash[1]))) {
                return true;
            }
        }

        return false;
    }


    final public function getAccessLevelFromKey()
    {
       return 799430;
    }

    final public function getAccessLevel($createHash = NULL)
    {
        // get access from license key
        $access = $this->getAccessLevelFromKey();

        // if no valid license found check for trial version
        if ($this->isTrialVersion($createHash) && !$this->hasExpired()) {
            $access = 799430;
        }

        return $access;
    }

    // Expiry Mail            
    public function getExpiryDate($id = null)
    {
        if ($id > 0) {
            $quote = Mage::getModel('qquoteadv/qqadvcustomer')->load($id);
            if ($quote->getData('expiry')) {
                $expiry = $quote->getData('expiry');
            }
        }

        if (!isset($expiry)) {
            $days = Mage::getStoreConfig('qquoteadv/general/expirtime_proposal', Mage::app()->getStore()->getStoreId());
            if (isset($days)) {
                $expiry = date('Y-m-d', strtotime("+$days days"));
            } else {
                $expiry = date('Y-m-d', Mage::getModel('core/date')->timestamp(time()));
            }
        }

        return $expiry;
    }

    // Reminder Mail
    public function getReminderDate($id = null)
    {
        if ($id > 0) {
            $quote = Mage::getModel('qquoteadv/qqadvcustomer')->load($id);
            if ($quote->getData('proposal_sent')) {
                $reminder = $quote->getData('reminder');
            }
        }

        if (!isset($reminder)) {
            $days = Mage::getStoreConfig('qquoteadv/general/send_reminder', Mage::app()->getStore()->getStoreId());
            if (isset($days)) {
                $reminder = date('Y-m-d', strtotime("+$days days"));
            } else {
                $reminder = date('Y-m-d', Mage::getModel('core/date')->timestamp(time()));
            }
        }

        return $reminder;
    }


    public function sentAnonymousData($action, $location, $quote_id)
    {

        $domain = (isset($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : 'no-domain.com';
        $level = $this->getAccessLevel();
        if ($level == null) {
            $level = 0;
        }
        if (Ophirah_Qquoteadv_Helper_Data::CREATE_HASH == "OpenSource") {
            $level = -1;
        }
        $is_trial = $this->isTrialVersion(Mage::registry('createHash')) || is_null($this->getAccessLevel());
        $version = Mage::getVersion();
        $params = array(
            "domain" => $domain,
            "action" => $action,
            "location" => $location,
            "level" => $level,
            "is_trial" => $is_trial,
            "version" => $version,
            "quote_id" => $quote_id
        );

        try {
            $client = Mage::getModel('qquoteadv/client')->sendRequest($params);

            if (isset($client['error'])) {
                Mage::logException(new Exception($client['error']['errormsg'], $client['error']['errno']));
            }
        } catch (Exception $e) {
            Mage::Log($e->getMessage());
        }

    }


    public function checkQuantities($item, $qty)
    {
        if (is_numeric($item)) {
            $_product = Mage::getModel('catalog/product')->load($item);
        } elseif ($item instanceof Mage_Catalog_Model_Product) {
            $_product = $item;
        } elseif (is_object($item) && $item->getData('product') instanceof Mage_Catalog_Model_Product) {
            $_product = $item->getData('product');
        } else {
            throw new Exception(Mage::helper('qquoteadv')->__("incorrect first parameter for checkQuantities should be product or product_id"));
        }
        $stockItem = $_product->getStockItem();
        $result = new Varien_Object();

        $result->setProductUrl($_product->getProductUrl());

        if ($_product->getTypeId() != Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE && $_product->getTypeId() != Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
            if ($stockItem->getMinSaleQty() && $qty < $stockItem->getMinSaleQty()) {
                $result->setHasError(true)
                    ->setMessage(Mage::helper('cataloginventory')->__('The minimum quantity allowed for quotation for %s is %s.', $_product->getName(), $stockItem->getMinSaleQty() * 1))
                    ->setErrorCode('qty_min')
                    ->setQuoteMessage(Mage::helper('cataloginventory')->__('%s cannot be quotated for in requested quantity.', $_product->getName()))
                    ->setQuoteMessageIndex('qty');

                return $result;
            }

            if ($stockItem->getMaxSaleQty() && $qty > $stockItem->getMaxSaleQty()) {
                $result->setHasError(true)
                    ->setMessage(
                        Mage::helper('cataloginventory')->__('The maximum quantity allowed for quotation for %s is %s.', $_product->getName(), $stockItem->getMaxSaleQty() * 1)
                    )
                    ->setErrorCode('qty_max')
                    ->setQuoteMessage(Mage::helper('cataloginventory')->__('%s cannot be ordered in requested quantity.', $_product->getName()))
                    ->setQuoteMessageIndex('qty');
                return $result;
            }

            if ($stockItem->getQty() && $qty > $stockItem->getQty() && $this->manageStock($_product) && !$stockItem->getBackorders()) {
                $result->setHasError(true)
                    ->setMessage(Mage::helper('cataloginventory')->__('%s cannot be quotated for the requested quantity.', $_product->getName()))
                    ->setErrorCode('qty_available')
                    ->setQuoteMessage(Mage::helper('cataloginventory')->__('%s cannot be ordered in requested quantity.', $_product->getName()))
                    ->setQuoteMessageIndex('qty');
                return $result;
            }
        }

        if (is_object($item) && !$item->getParentItemId()) {
            $result->addData($this->checkQtyIncrements($item, $qty)->getData());
        }

        return $result;
    }

    public function isInStock($item)
    {
        if (is_numeric($item)) {
            $_product = Mage::getModel('catalog/product')->load($item);
        } elseif ($item->getData('product') instanceof Mage_Catalog_Model_Product) {
            $_product = $item->getData('product');
        } elseif ($item instanceof Mage_Catalog_Model_Product) {
            $_product = $item;
        } else {
            throw new Exception(Mage::helper('qquoteadv')->__("incorrect parameter for isInStock should be product or product_id"));
        }

        $result = new Varien_Object();
        if ($_product->getData('is_in_stock') == 0 && $this->manageStock($_product)) {
            $result->setHasError(true)
                ->setProductUrl($_product->getProductUrl())
                ->setMessage(
                    Mage::helper('qquoteadv')->__('item %s is out of stock.', $_product->getName())
                )
                ->setErrorCode('out_of_stock');
        }

        return $result;
    }

    public function checkQtyIncrements($item, $qty)
    {
        if (is_numeric($item)) {
            $_product = Mage::getModel('catalog/product')->load($item);
        } elseif ($item instanceof Mage_Catalog_Model_Product) {
            $_product = $item;
        } elseif ($item->getData('product') instanceof Mage_Catalog_Model_Product) {
            $_product = $item->getData('product');
        } else {
            throw new Exception(Mage::helper('qquoteadv')->__("incorrect first parameter for checkQtyIncrements should be product or product_id"));
        }

        $result = new Varien_Object();

        if (is_object($_product) && !$_product->getParentItemId()) {
            $stockItem = $_product->getStockItem();
            $qtyIncrements = $stockItem->getQtyIncrements();

            if ($qtyIncrements && ($qty % $qtyIncrements != 0)) {
                $result->setHasError(true)
                    ->setProductUrl($_product->getProductUrl())
                    ->setQuoteMessage(
                        Mage::helper('qquoteadv')->__('%s cannot be added to the quotation in the requested quantity.', $_product->getName())
                    )
                    ->setErrorCode('qty_increments')
                    ->setQuoteMessageIndex('qty');


                $result->setMessage(
                    Mage::helper('qquoteadv')->__('%s is available for quotation in increments of %s only.', $_product->getName(), $qtyIncrements * 1)
                );

            }
        }

        return $result;
    }

    public function isQuoteable($product, $qty)
    {
        $errors = array();
        // Quantity errors have priority above increments
        $resultQuantities = $this->checkQuantities($product, $qty);
        if ($resultQuantities->getHasError()) {
            $errors[] = $resultQuantities->getMessage();
        } else {
            $resultIncrement = $this->checkQtyIncrements($product, $qty);
            if ($resultIncrement->getHasError()) {
                $errors[] = $resultIncrement->getMessage();
            }
        }
        if (!$resultQuantities->getHasError() && !$resultIncrement->getHasError()) {
            $resultInStock = $this->isInStock($product);
            if ($resultInStock->getHasError()) {
                $errors[] = $resultInStock->getMessage();
            }
        }
        $result = new Varien_Object();
        if (count($errors)) {
            $result->setHasErrors(true)
                ->setErrors($errors);
        }

        return $result;
    }

    /**
     * Check if inventory needs to be checked
     * for the product
     *
     * @param Mage_Catalog_Model_Product $product
     * @return bool
     */
    public function manageStock(Mage_Catalog_Model_Product $product)
    {
        // Check Store setting for stock management
        if ((int)Mage::getStoreConfig('cataloginventory/item_options/manage_stock') == 0) {
            return false;
        }
        // Check product setting
        if ((int)$product->getStockItem()->getManageStock() == 0) {
            return false;
        }

        return true;

    }

    /*  Check if product is congigurable
     *  if so, only add the ordered simple product
     *  with quantity of the configurable product
     *
     *  @param  object              // Mage_Catalog_Model_Product
     *  @param  integer             // Qty
     *  @return integer || false
     */
    public function isConfigurable($item, $qty)
    {

        $return = false;
        $item_id = $item->getId();
        $product = $item->getData('product');
        $session = Mage::getSingleton('adminhtml/session');

        // Check if simple product is child of the stored configurable
        if ($session->getConfParent()) {
            foreach ($session->getConfParent() as $parent) {
                if ($item->getData('parent_item_id') == $parent['item_id']) {
                    $return = $parent['qty'];
                }
            }
        }

        // for configurable products get array of children
        if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {

            $conf_children = Mage::getModel('catalog/product_type_configurable')->getChildrenIds($product->getId());

            if ($conf_children) {
                $parent_data = $session->getConfParent();
                $parent_data[] = array(
                    'item_id' => $item_id,
                    'children' => $conf_children,
                    'qty' => $qty
                );

            }
            // store data in the admin session
            $session->setConfParent($parent_data);
        }

        return $return;
    }


    public function getAutoLoginUrl($quote, $my = false)
    {
        if (is_numeric($quote)) $quote = Mage::getModel('qquoteadv/qqadvcustomer')->load($quote);
        if ($quote instanceOf Ophirah_Qquoteadv_Model_QqadvCustomer) {
            $configured = Mage::getStoreConfig('qquoteadv/emails/link_auto_login', $quote->getStoreId());
            $allowed = $this->validLicense('email-auto-login', array($quote->getData('create_hash'), $quote->getData('increment_id')));
            if ($configured && $allowed) {
                $parameters = array("id" => $quote->getId(), "hash" => $quote->getUrlHash(), "_store" => $quote->getStoreId());
                if ($my):
                    ($my == 2) ? $parameters['my'] = "quote" : $parameters['my'] = "quotes";
                endif;

                $autoConfirm = Mage::getStoreConfig('qquoteadv/emails/auto_confirm', $quote->getStoreId());
                if ($autoConfirm > 0) $parameters['autoConfirm'] = $autoConfirm;

                return Mage::getUrl('qquoteadv/index/gotoquote', $parameters);
            } else {
                if ($my != 2) return Mage::getUrl('qquoteadv/view/history');
                return Mage::getUrl('qquoteadv/view/view', array("id" => $quote->getId(), "_store" => $quote->getStoreId()));
            }
        } else {
            return Mage::getUrl('qquoteadv/view/history', array("_store" => $quote->getStoreId()));
        }
    }

    public function getAllowedToQuoteMode($product)
    {

        $allowed = $product->getAllowedToQuotemode();
        $customerGroupId = Mage::getSingleton('customer/session')->getCustomerGroupId();
        $allowGroups = $product->getGroupAllowQuotemode();
        if (is_array($allowGroups)) {
            foreach ($allowGroups as $allowRow) {
                if ((int)$allowRow['cust_group'] == $customerGroupId) {
                    $allowed = (int)$allowRow['value'];
                };
            }
        }
        return $allowed;
    }

    /**
     * @return Mage_Admin_Model_User
     */
    public function getAdminUser()
    {
        $adminSessionName = 'adminhtml';
        /** @var $currentSession Mage_Core_Model_Session */
        $currentSession = Mage::getSingleton('core/session');
        $currentSessionName = $currentSession->getSessionName();

        //fix if magento doesn't use the normal session id
        if(!isset($_COOKIE[$adminSessionName])){
            $adminSessionName = "PHPSESSID";
        }

        if (isset($_COOKIE[$currentSessionName]) && isset($_COOKIE[$adminSessionName])) {
            $adminSessionId = $_COOKIE[$adminSessionName];

            //load the log object of this user id
            $logAdmin = Mage::getModel('qquoteadv/qqadvlogadmin')->load($adminSessionId, 'session_id');

            //check if it already exists
            if ($adminId = $logAdmin->getData("admin_id")) {
                return Mage::getModel('admin/user')->load($adminId);
            }

        }
        return null;
    }

    /**
     * Get admin user ID for next rotation
     *
     * @param int|null $roleId
     * @return int
     */
    public function getNextAssignToAdmin($roleId = null)
    {
        if ($roleId === null) {
            $roleId = (int)Mage::getStoreConfig('qquoteadv/sales_representatives/auto_assign_role');
        }

        /** @var $model Ophirah_Qquoteadv_Model_Qqadvrotation */
        $model = Mage::getModel('qquoteadv/qqadvrotation');

        try {
            return $model->getNextUserId($roleId);
        } catch (Zend_Db_Statement_Exception $e) {
            Mage::log('Could not save next user ID rotation. This is probably due to a badly configured role ID', Zend_Log::ERR);
            Mage::logException($e);
            return 0;
        }
    }

    /**
     * Get admin user ID previously handeled this customer
     *
     * @param Mage_Customer_Model_Customer $customer
     * @return int
     */
    public function getPreviousAssignedAdmin(Ophirah_Qquoteadv_Model_Qqadvcustomer $quote = null, $customerId = null, $backend = false)
    {
        /* @var $collection Ophirah_Qquoteadv_Model_Mysql4_Qqadvcustomer_Collection */
        $collection = Mage::getModel('qquoteadv/qqadvcustomer')->getCollection();

        if ($customerId === null) {
            $customerId = $quote->getCustomerId();
        }

        if ($backend === false) {
            $collection
                ->addOrder('created_at')
                ->addFieldToFilter('main_table.customer_id', $customerId)
                ->addFieldToFilter('main_table.is_quote', 1)
                ->addFieldToFilter('u.is_active', 1)->setPageSize(1);
        } else {
            $collection
                ->addOrder('created_at')
                ->addFieldToFilter('main_table.customer_id', $customerId)
                ->addFieldToFilter('main_table.is_quote', 1);
        }

        $cloneColl = clone($collection);
        if ($quote !== null) {
            $quoteId = ($quote->getId()) ? $quote->getId() : $cloneColl->getFirstItem()->getQuoteId();
            $collection->addFieldToFilter('main_table.quote_id', array('neq' => $quoteId));
        }

        $collection->getSelect()->joinInner(array('u' => $collection->getResource()->getTable('admin/user')), 'main_table.user_id = u.user_id');

        return $collection->getFirstItem()->getUserId();
    }

    /**
     * @param Ophirah_Qquoteadv_Model_Qqadvcustomer $quote
     * @param int $loggedInAdmin
     */
    public function assignQuote(Ophirah_Qquoteadv_Model_Qqadvcustomer $quote, $loggedInAdmin = null)
    {
        $quote->setUserId($this->getExpectedQuoteAdminId($quote, $loggedInAdmin));
    }

    /**
     * @param Ophirah_Qquoteadv_Model_Qqadvcustomer $quote
     * @param int $loggedInAdmin
     * @return Mage_Admin_Model_User|null
     */
    public function getExpectedQuoteAdmin(Ophirah_Qquoteadv_Model_Qqadvcustomer $quote, $loggedInAdmin = null)
    {
        $adminId = $this->getExpectedQuoteAdminId($quote, $loggedInAdmin);
        if ($adminId == null) {
            return null;
        }

        return $this->getAdmin($adminId);
    }

    /**
     * @param int $adminId
     * @return Mage_Admin_Model_User|$admin
     */
    public function getAdmin($adminId)
    {
        /* @var $admin Mage_Admin_Model_User */
        $admin = Mage::getModel('admin/user');
        $admin->load($adminId);
        if (!$admin->getId()) {
            return null;
        }
        return $admin;
    }

    /**
     * @param Ophirah_Qquoteadv_Model_Qqadvcustomer $quote
     * @param int $loggedInAdmin
     * @return int
     */
    public function getExpectedQuoteAdminId(Ophirah_Qquoteadv_Model_Qqadvcustomer $quote, $loggedInAdmin = null, $backend = false)
    {
        if ($quote->getUserId()) {
            return $quote->getUserId();
        }

        if ((bool)Mage::getStoreConfig('qquoteadv/sales_representatives/auto_assign_previous')) {
            $adminId = $this->getPreviousAssignedAdmin($quote, null, $backend);
            if ($adminId) {
                return $adminId;
            }
        }

        if ((bool)Mage::getStoreConfig('qquoteadv/sales_representatives/auto_assign_login')) {
            if ($loggedInAdmin === null) {
                $admin = $this->getAdminUser();
                if ($admin && $admin->getId()) {
                    $loggedInAdmin = $admin->getId();
                }
            }

            if ($loggedInAdmin) {
                return $loggedInAdmin;
            }
        }

        if ((bool)Mage::getStoreConfig('qquoteadv/sales_representatives/auto_assign_rotate')) {
            return $this->getNextAssignToAdmin();
        }

        return null;
    }

    /**
     * Unique hash for specific
     * Cart2Quote version
     *
     * @param string $incrementId
     * @return string
     */
    final public function getCreateHash($incrementId)
    {
        return self::_createHash($incrementId);
    }

    final protected function _createHash($incrementId)
    {
        return md5($incrementId . $this->_expiryDate . Ophirah_Qquoteadv_Helper_Data::CREATE_HASH);
    }

    public function hideQuoteButton(Mage_Catalog_Model_Product $product)
    {
        $return = false;
        $returnStock = false;
        $returnPrice = false;
        if ($product->getData('allowed_to_quotemode') == 1 && $product->getData('quotemode_conditions') > 0) {
            // Check Product Price for dynamic bundle
            if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE && $product->getPriceType() == 0) {
                $priceModel = $product->getPriceModel()->getTotalPrices($product, null, true, false);
                if (is_array($priceModel)) {
                    foreach ($priceModel as $bundlePrice) {
                        if ($bundlePrice > 0) {
                            $returnPrice = true;
                        }
                    }
                }
            } else {
                if ($product->getPrice() > 0) {
                    $returnPrice = true;
                }
            }

            // Check Stock
            if ((int)$product->getStockItem()->getIsInStock() == 1) {
                $returnStock = true;
            }

        }

        // Check Product Attribute
        switch ($product->getData('quotemode_conditions')) {
            case 1:
                // Show when price is '0.00'
                $return = $returnPrice;
                break;
            case 2:
                // Show when out of stock
                $return = $returnStock;
                break;
            case 3:
                // Show when price is '0.00'
                // or when out of stock
                if ($returnStock === true && $returnPrice === true) {
                    $return = true;
                }
                break;
        }

        return $return;
    }

    /**
     * Check if Beta Features are enabled
     *
     * @param int $storeId
     * @return bool
     */
    public function betaIsEnabled($storeId = 0)
    {
        $return = false;
        if (is_int((int)$storeId) && Mage::getStoreConfig('qquoteadv/general/beta', $storeId)) {
            if ((int)Mage::getStoreConfig('qquoteadv/general/beta', $storeId) > 0) {
                $return = true;
            }
        }
        return $return;
    }

    /**
     * Check if date value
     * contains date data
     *
     * @param $date
     * @return bool
     */
    public function isDate($date)
    {
        $subDate = (int)substr($date, 0, 4);
        $intDate = (int)preg_replace(' /\D/', '', $date);
        if ($subDate > 0 && $intDate > 0) {
            return true;
        }

        return false;
    }

}
