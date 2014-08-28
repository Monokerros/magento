<?php

class Ophirah_Qquoteadv_IndexController extends Mage_Core_Controller_Front_Action
{
    CONST XML_PATH_QQUOTEADV_REQUEST_EMAIL_TEMPLATE = 'qquoteadv/emails/request';
    CONST XML_PATH_QQUOTEADV_REQUEST_PROPOSAL_EMAIL_TEMPLATE = 'qquoteadv/emails/proposal';
    CONST DEFAULT_PASSWORD = 'quote123';

    protected $_isEmailExists = false;

    protected $_isAjax = false;
    public $params = null;

    /**
     * Get customer session data
     */
    public function getCustomerSession()
    {
        return Mage::getSingleton('customer/session');
    }

    /**
     * Get core session data
     */
    public function getCoreSession()
    {
        return Mage::getSingleton('core/session');
    }

    /**
     * Filter the request parameter
     * filter case occurs when the product is moved to quote from cart and vice-versa
     * @return array
     */
    public function getFilteredParams()
    {

        $params = $this->getRequest()->getParams();

        // if cartid is present in request parameter
        // occurs when product is moved from cart to quote
        if ($this->getRequest()->getParam('cartid')) {
            if ($this->getRequest()->getParam('cartid') == '0') {
                return $params;
            } else {
                return $params['cart'][$this->getRequest()->getParam('cartid')];
            }
        }
        // if quoteid is present in request parameter
        // occurs when product is moved from quote to cart
        elseif ($this->getRequest()->getParam('quoteid')) {
            return $params['quote'][$this->getRequest()->getParam('quoteid')];
        }
        // if both are not present in request paramter
        // occurs when product is added to quote from product detail page
        else {
            return $params;
        }
    }

    /**
     * Insert quote data
     * Useful when all products from cart page are added to quote
     */
    public function addAction()
    {
        $params = $this->getFilteredParams();

        // Assign $params to object
        $this->params = $params;
        // dispatch event to alter the param data
        // can be useful for other modules that
        // alter product params
        Mage::dispatchEvent('ophirah_qquoteadv_addQuoteRequestItem_before', array('prodParams' => $this));
        // Reassign $params with object data
        if ($this->params) {
            $params = $this->params;
        }

        if (array_key_exists('cart', $params)) {
            foreach ($params['cart'] as $key => $value) {

                $this->addFilterAction($value);
            }
        } else {
            $this->addFilterAction($params);
        }
    }

    /**
     * Insert product to quote
     *
     */
    public function addItemAction()
    {
        if (Mage::helper('qquoteadv')->isActiveConfirmMode()) {
            $link = Mage::getUrl('qquoteadv/view/outqqconfirmmode');
            $message = $this->__("To add item to the Quote mode <a href='%s'>log out</a> from Quote confirmation mode.", $link);
            $this->getCoreSession()->addNotice($message);
            $this->_redirectReferer(Mage::getUrl('*/*'));
            return;
        }

        /** Notes
         * case 1:
         *         simple product (is not allowed to quote ) was added to cart then other simple product should be added to quote
         *         So needed to remove items from card (remove to quote only allowed items) then add quoted item to quote
         *
         */

        $this->addAction();

    }

    public function addItemAjaxAction()
    {
        $this->_isAjax = true;
        $this->addItemAction();
    }

    /**
     * Module Magento Mechanics uses multi order configurables
     * @param   ->  array $params with multi order
     * @return  ->  array $params with multiple products
     */

    public function explodeMultiOrder($params)
    {

        $sa = $params['sa'];

        foreach ($sa as $key => $value) {

            $newParams = array();
            $newParams['product'] = $params['product'];
            $newParams['related_product'] = $params['related_product'];
            $newParams['qty'] = ($params['qtys'][$key] > 0) ? $params['qtys'][$key] : 0;
            $newParams['super_attribute'] = $value;

            if ($newParams['qty'] > 0) {
                self::addFilterAction($newParams);
            }
        }

        return;

    }


    /**
     * Convert param attributeEncode to unserialized attribute
     *
     * @param   => array $params with attributeEncode key
     * @return  => array $params with unserialized attribute
     */
    public function attributeDecode($params)
    {
        $attribute = unserialize(base64_decode($params['attributeEncode']));
        unset($attribute['uenc']);
        return $attribute;
    }


    /**
     * Insert filter quote data
     */
    public function addFilterAction($params)
    {

        // Magento Mechanics - Configurable Product Grid View
        if (isset($params['is_multi_order'])) {
            if (count($params['sa']) > 0) {
                $params = $this->explodeMultiOrder($params);
            }

        } else {

            // set the qty to 1 if it is empty
            //if($params['qty'] == ''||!is_numeric($params['qty'])) {
            if (!isset($params['qty']) || !is_numeric($params['qty'])) {
                $params['qty'] = 1;
            }

            /**
             * if addAction is called from cart or quote page
             * from cart/quote page, the parameter is serialized string and is passed as base64 encoded form
             * hence, we have to decode it
             */
            if (array_key_exists('attributeEncode', $params)) {
                $superAttribute = serialize(self::attributeDecode($params));
            } else {
                $superAttribute = serialize($params);
            }

            // if the product is Grouped Product
            if (isset($params['super_group'])) {
                $superGroup = $params['super_group'];

                if (array_sum($superGroup) > 0) {
                    // adding each super group product separately as simple product
                    foreach ($superGroup as $key => $value) {
                        // don't add product if it have quantity value 0
                        if ($value != 0 && is_numeric($value)) {
                            $groupParams['product'] = $key;
                            $groupParams['qty'] = (int)$value;
                            // Quick Quote Mode
                            if (isset($params['customer'])) {
                                $groupParams['customer'] = $params['customer'];
                            }
                            $this->addDataAction($groupParams, $superAttribute);
                        }
                    }
                } else {
                    if(!$this->_isAjax){
                        $this->getCoreSession()->addNotice($this->__('Please specify product quantity.'));
                        $this->_redirectReferer(Mage::getUrl('*/*'));
                    } else {
                        $block = $this->getLayout()->createBlock('core/template')->setTemplate('qquoteadv/ajaxerror.phtml');
                        $output = $block->toHtml();

                        $totalText = Mage::helper('qquoteadv')->totalItemsText();

                        $array = array("result" => 1, "html" => $output, "itemstext" => $totalText);
                        $json = json_encode($array);
                        $this->getResponse()->setBody($json);

                    }
                }
            } else {

                $this->addDataAction($params, $superAttribute);

            }
        }
    }

    protected function _redirect($path, $arguments = array())
    {
        if ($this->_isAjax) {
            $this->_returnAjax();
        } else {
            parent::_redirect($path, $arguments);
        }
    }

    protected function _redirectUrl($url)
    {
        if ($this->_isAjax) {
            $this->_returnAjax();
        } else {
            parent::_redirectUrl($url);
        }
    }

    protected function _redirectReferer($defaultUrl = null)
    {
        if ($this->_isAjax) {
            $this->_returnAjax();
        } else {
            parent::_redirectReferer($defaultUrl);
        }
    }

    protected function _return()
    {
        if ($this->_isAjax) {
            $this->_returnAjax();
        }
    }

    protected function _returnAjax()
    {
        $msg = Mage::getSingleton('core/session')->getMessages();

        //TODO: add checking for quote confirmation mode  		
        $errors = count($msg->getErrors());
        $product = Mage::registry('product');

        if ($errors) {
            $array = array("result" => 0, "producturl" => $product->getProductUrl());
            $json = json_encode($array);
            $this->getResponse()->setHeader('Content-type', 'application/json');
            $this->getResponse()->setBody($json);
        } else {
            $msg = Mage::getSingleton('core/session')->getMessages(true);
            $this->loadLayout();
            $this->getLayout()->getMessagesBlock()->addMessages($msg);
            $this->getLayout()->getBlock('ajaxadd')->setData('product', $product);
            $this->getLayout()->getBlock('ajaxadd')->setData('errors', $errors);
            $this->_initLayoutMessages('core/session');
            //$this->renderLayout();

            $output = $this->getLayout()->getOutput();
            $totalText = Mage::helper('qquoteadv')->totalItemsText();

            Mage::getSingleton('core/translate_inline')->processResponseBody($output);
            $array = array("result" => 1, "html" => $output, "itemstext" => $totalText);
            $json = json_encode($array);
            $this->getResponse()->setBody($json);
        }

    }

    /**
     * Insert quote data (main data add function)
     * @param array $params post parameter for product
     * @param string $superAttribute
     */
    public function addDataAction($params, $superAttribute)
    {
        $modelCustomer = Mage::getModel('qquoteadv/qqadvcustomer');
        $modelProduct = Mage::getModel('qquoteadv/qqadvproduct');
        $checkQty = null;

        if ($this->getCustomerSession()->isLoggedIn()) {
            $qcustomer = array(
                'created_at' => NOW(),
                'updated_at' => NOW(),
                'customer_id' => $this->getCustomerSession()->getId(),
                'store_id' => Mage::app()->getStore()->getStoreId()
            );
        } else {
            $qcustomer = array(
                'created_at' => NOW(),
                'updated_at' => NOW(),
                'store_id' => Mage::app()->getStore()->getStoreId()
            );
        }

        try {
            // if quote_id is not set then insert into qquote_customer table and set quote_id
            if ($this->getCustomerSession()->getQuoteadvId() == NULL) {
                // save data to qquote_customer table and getting inserted row id
                $qId = $modelCustomer->addQuote($qcustomer)->getQuoteId();
                // setting inserted row id of qquote_customer table into session
                $this->getCustomerSession()->setQuoteadvId($qId);
            }

            $hasOption = 0;
            $options = '';
            if (isset($params['options'])) {
                $options = $params['options'];
            }
            if (isset($superAttribute)) {
                $attr = unserialize($superAttribute);

                if (isset($attr['options'])) {
                    $options = $attr['options'];
                    $params['qty'] = $attr['qty'];
                }
            }
            $params['qty'] = $params['qty'] > 0 ? $params['qty'] : 1;
            $params['options'] = $options;

            // Declare Params Object
			// TODO:
			// remove $params use from code below
			// Replace with $paramsObj
            $paramsObj = new Varien_Object($params);

            // Decalre Customer Object
            // Available in Quick Quote Mode
            $customer = new Varien_Object();
            if(isset($params['customer'])){
                $customer->addData($params['customer']);
                // remove data from $paramsObj
                $paramsObj->unsetData('customer');
            }

            $product = Mage::getModel('catalog/product')->load($paramsObj->getData('product'));
            $product->getTypeInstance(true)->prepareForCartAdvanced($paramsObj, $product);
            if ($paramsObj->getData('options')) {
                $hasOption = 1;
                $options = serialize($paramsObj->getData('options'));
            } else {
                $options = '';
            }
            if ($options && $superAttribute) {
                $superAttribute = unserialize($superAttribute);
                $superAttribute['options'] = unserialize($options);
                $superAttribute = serialize($superAttribute);
            }

            /**
             * check if the customer has already added the particular product
             * if the product is already added by the customer then add only the quantity for that row
             * otherwise add new row for product
             */

            $productsCollection = $modelProduct->getCollection()
                ->addFieldToFilter('quote_id', $this->getCustomerSession()->getQuoteadvId())
                ->addFieldToFilter('product_id', $paramsObj->getData('product'));

            if ($hasOption) {
                $productsCollection->addFieldToFilter('has_options', $hasOption);
                $productsCollection->addFieldToFilter('options', $options);
            }

            $product = Mage::getModel('catalog/product')->load($paramsObj->getData('product'));
            $product_url = $product->getData('url_path');

            try {
                Mage::register('product', $product);
            } catch (Exception $e) {
                Mage::unregister('product');
                Mage::register('product', $product);
            }

            if ($productsCollection->getData() != array()) {
                $pID = $paramsObj->getData('product');
                $pInfo = Mage::getModel('catalog/product')->load($pID);

                if ($pInfo->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {

                    $isFound = false;
                    $type = $pInfo->getTypeId();

                    foreach ($productsCollection as $item) {

                        if (Mage::helper('qquoteadv/catalog_product_data')->compareConfigurable($pID, $superAttribute, $item->getAttribute())) {

                            $isFound = true;
                            // adding qty to product if the customer has previously added in the current session
                            $qtySum = array('qty' => $paramsObj->getData('qty') + $item->getQty());

                            // Quantity check Configurables simple product
                            $check = $this->checkProdTypeQty($attr, $qtySum['qty'], $type);

                            if ($check !== false) {
                                $url = $this->getRequest()->getServer('HTTP_REFERER');
                                $checkQty = new Varien_Object();
                                $checkQty->setHasError(true);
                                $checkQty->setProductUrl($url);
                                $checkQty->setMessage($check);
                            } else {
                                $checkQty = $modelProduct->updateProduct($item->getId(), $qtySum);
                            }

                            break;
                        }

                    }

                    if (!$isFound) {

                        if ($pInfo->getAllowedToQuotemode()) {

                            $qproduct = array(
                                'quote_id' => $this->getCustomerSession()->getQuoteadvId(),
                                'product_id' => $paramsObj->getData('product'),
                                'qty' => $paramsObj->getData('qty'),
                                'attribute' => $superAttribute,
                                'has_options' => $hasOption,
                                'options' => $options,
                                'store_id' => Mage::app()->getStore()->getStoreId()
                            );

                            // Quantity check Configurables simple product
                            $check = $this->checkProdTypeQty($attr, $paramsObj->getData('qty'), $type);

                            if ($check !== false) {
                                $url = $this->getRequest()->getServer('HTTP_REFERER');
                                $checkQty = new Varien_Object();
                                $checkQty->setHasError(true);
                                $checkQty->setProductUrl($url);
                                $checkQty->setMessage($check);
                            } else {
                                $checkQty = $modelProduct->addProduct($qproduct);
                            }
                        }
                    }


                } elseif ($pInfo->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
                    $isFound = false;
                    $type = $pInfo->getTypeId();

                    foreach ($productsCollection as $item) {
                        if (Mage::helper('qquoteadv/catalog_product_data')->compareBundles($pID, $superAttribute, $item->getAttribute())) {
                            $isFound = true;
                            // adding qty to product if the customer has previously added in the current session
                            $qtySum = array('qty' => $paramsObj->getData('qty') + $item->getQty());

                            // Quantity check bundeld simple products
                            $check = $this->checkProdTypeQty($paramsObj->getData(), $qtySum['qty'], $type);

                            if ($check !== false) {
                                $url = $this->getRequest()->getServer('HTTP_REFERER');
                                $checkQty = new Varien_Object();
                                $checkQty->setHasError(true);
                                $checkQty->setProductUrl($url);
                                $checkQty->setMessage($check);
                            } else {
                                $checkQty = $modelProduct->updateProduct($item->getId(), $qtySum);
                            }

                            break;
                        }
                    }

                    if (!$isFound) {

                        if ($pInfo->getAllowedToQuotemode()) {

                            $qproduct = array(
                                'quote_id' => $this->getCustomerSession()->getQuoteadvId(),
                                'product_id' => $paramsObj->getData('product'),
                                'qty' => $paramsObj->getData('qty'),
                                'attribute' => $superAttribute,
                                'has_options' => $hasOption,
                                'options' => $options,
                                'store_id' => Mage::app()->getStore()->getStoreId()
                            );

                            // Quantity check Configurables simple product
                            $check = $this->checkProdTypeQty($paramsObj->getData(), $paramsObj->getData('qty'), $type);

                            if ($check !== false) {
                                $url = $this->getRequest()->getServer('HTTP_REFERER');
                                $checkQty = new Varien_Object();
                                $checkQty->setHasError(true);
                                $checkQty->setProductUrl($url);
                                $checkQty->setMessage($check);
                            } else {
                                $checkQty = $modelProduct->addProduct($qproduct);
                            }

                        }
                    }

                } else {

                    foreach ($productsCollection as $item) {
                        // adding qty to product if the customer has previously added in the current session
                        $qtySum = array('qty' => $paramsObj->getData('qty') + $item->getQty());
                        $checkQty = $modelProduct->updateProduct($item->getId(), $qtySum);
                        break;
                    }
                }

            } else {

                $paramsAttr = ($paramsObj->getData('attribute'))?$paramsObj->getData('attribute'): array();
                if ($paramsObj->getData('product') || isset($paramsAttr['product'])) {
                    if (isset($paramsAttr['product'])) {
                        $_product = Mage::getModel('catalog/product')->load($paramsAttr['product']);
                    } else {
                        $_product = Mage::getModel('catalog/product')->load($paramsObj->getData('product'));
                    }

                    $type = $_product->getTypeId();

                    if ($type == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
                        if ($_product->getAllowedToQuotemode()) {

                            $qproduct = array(
                                'quote_id' => $this->getCustomerSession()->getQuoteadvId(),
                                'product_id' => $paramsObj->getData('product'),
                                'qty' => $paramsObj->getData('qty'),
                                'attribute' => $superAttribute,
                                'has_options' => $hasOption,
                                'options' => $options,
                                'store_id' => Mage::app()->getStore()->getStoreId()
                            );

                            // Quantity check bundled simple product
                            $check = $this->checkProdTypeQty($paramsObj->getData(), $paramsObj->getData('qty'), $type);

                            if ($check !== false) {
                                $url = $this->getRequest()->getServer('HTTP_REFERER');
                                $checkQty = new Varien_Object();
                                $checkQty->setHasError(true);
                                $checkQty->setProductUrl($url);
                                $checkQty->setMessage($check);
                            } else {
                                $checkQty = $modelProduct->addProduct($qproduct);
                            }

                        }
                    }

                    // Quantity check product                     
                    if ($_product->getTypeId()) {

                        if (isset($attr) && $_product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
                            $check = $this->checkProdTypeQty($attr, $paramsObj->getData('qty'), $_product->getTypeId());
                        } elseif ($paramsObj->getData() && $_product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
                            $check = $this->checkProdTypeQty($paramsObj->getData(), $paramsObj->getData('qty'), $_product->getTypeId());
                        } else {
                            $check = $this->checkProdTypeQty($_product, $paramsObj->getData('qty'), $_product->getTypeId());
                        }

                        if ($check != false) {
                            $url = $this->getRequest()->getServer('HTTP_REFERER');
                            $checkQty = new Varien_Object();
                            $checkQty->setHasError(true);
                            if (isset($product_url) && $_product->getTypeId() != Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
                                $url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK) . $product_url;
                            }
                            $checkQty->setProductUrl($url);
                            $checkQty->setMessage($check);
                        }

                    }

                    if ($_product->getAllowedToQuotemode() && is_null($checkQty)) {

                        $qproduct = array(
                            'quote_id' => $this->getCustomerSession()->getQuoteadvId(),
                            'product_id' => $paramsObj->getData('product'),
                            'qty' => $paramsObj->getData('qty'),
                            'attribute' => $superAttribute,
                            'has_options' => $hasOption,
                            'options' => $options,
                            'store_id' => Mage::app()->getStore()->getStoreId()
                        );
                        $checkQty = $modelProduct->addProduct($qproduct);
                    }
                }
            }

            if (is_null($checkQty)) { // product has not been added redirect with error

                $checkQty = new Varien_Object();
                $checkQty->setHasError(true);
                if (!$checkQty->getMessage()) {
                    $checkQty->setMessage(Mage::helper('qquoteadv')->__('product can not be added to quote list'));
                }

                $url = $this->getRequest()->getServer('HTTP_REFERER');
                $checkQty->setProductUrl($url);
            }

            if ($checkQty->getHasError()) {
                $this->getCoreSession()->addError($checkQty->getMessage());
                $this->_redirectUrl($checkQty->getProductUrl());
                return;
            }

            $product = Mage::getModel('catalog/product')->load($paramsObj->getData('product'));

            /**
             * deleting the item from cart if cartid is set in the url
             * i.e. if the addAction is called from 'Move to quote' button of cart page
             * in this case, we have to add the item to quote and delete from cart
             */

            // Create Quotation from Quick Quote Form
            if (Mage::getStoreConfig('qquoteadv/quick_quote/quick_quote_mode') == "1" && $customer->getData()) {
                // Create a request item
                $requestParams = array();
                $base2QuoteRate = $modelCustomer->getBase2QuoteRate();
                $finalPrice = Mage::helper('qquoteadv')->_applyPrice($modelProduct->getId());

                // Set Request Item parameters
                $requestParams['quote_id'] = $this->getCustomerSession()->getQuoteadvId();
                $requestParams['product_id'] = $modelProduct->getProductId();
                $requestParams['request_qty'] = $paramsObj->getData('qty');
                $requestParams['quoteadv_product_id'] = $modelProduct->getId();
                $requestParams['owner_base_price'] = $finalPrice;
                $requestParams['original_price'] = $finalPrice;
                $requestParams['owner_cur_price'] = $finalPrice * $base2QuoteRate;
                $requestParams['original_cur_price'] = $finalPrice * $base2QuoteRate;

                // Save Request Item
                $requestItemModel = Mage::getModel('qquoteadv/requestitem');
                $requestItemModel->addItem($requestParams);

                // Create Quote
                $this->quoteRequestAction();

                // Return to success page
                $this->_redirect('qquoteadv/index/success');
                return;
            }

            $succesMsg = $this->__('Product %s successfully added to Quote Request', $product->getName());
            if (array_key_exists('cartid', $paramsObj->getData())) {
                if ($product->getAllowedToQuotemode()) {
                    $this->getCoreSession()->addSuccess($succesMsg);
                }
                $this->_redirect('qquoteadv/index');
                return;
            } elseif (!Mage::getStoreConfig('qquoteadv/general/redirect_to_quotation', Mage::app()->getStore()->getStoreId())) {
                $backUrl = $this->_getRefererUrl();
                $this->getCoreSession()->addSuccess($succesMsg);
                $this->_redirectUrl($backUrl);
                return;
            } else {

                if ($this->_isAjax) {
                    $this->getCoreSession()->addSuccess($succesMsg);
                }
                $this->_redirect('*/*/');
                return;
            }

        } catch (Exception $e) {
            if ($this->getCoreSession()->getUseNotice(true)) {
                $this->getCoreSession()->addNotice($e->getMessage());
            } else {
                $messages = array_unique(explode("\n", $e->getMessage()));
                foreach ($messages as $message) {
                    $this->getCoreSession()->addError($message);
                }
            }
        }

        $this->_return();
    }

    public function checkProdTypeQty($prodData, $qty, $type)
    {
        $return = false;
        $childItems = array();

        if ($prodData['product']) {
            $product = Mage::getModel('catalog/product')->load($prodData['product']);
        } else {
            $product = $prodData;
        }

        if ($type == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {

            $bundleSelection = Mage::getModel('qquoteadv/bundle')->getBundleOptionsSelection($product, $prodData);

            $childItems = array();
            foreach ($bundleSelection as $bundleItem) {
                foreach ($bundleItem['value'] as $option) {
                    if (isset($option['id'])) { // Only check if a product is selected
                        $childItems[$bundleItem['option_id']] = array('id' => $option['id'], 'qty' => $option['qty']);
                    }
                }
            }

        }

        if ($type == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
            $checkProduct = Mage::getModel('catalog/product_type_configurable')
                ->getProductByAttributes($prodData['super_attribute'], $product);
        } else {
            $checkProduct = $product;
        }

        if (count($childItems) > 0) {
            foreach ($childItems as $childItem) {
                if ($childItem['id'] > 0 && $childItem['qty'] > 0) {
                    $product = Mage::getModel('catalog/product')->load($childItem['id']);
                    $check = Mage::helper('qquoteadv')->checkQuantities($product, $childItem['qty']);
                    if ($check->getHasError()) {
                        $return .= $check->getMessage() . "<br />";
                    }
                }
            }
        } else {
            $check = Mage::helper('qquoteadv')->checkQuantities($checkProduct, $qty);
            if ($check->getHasError()) {
                $return = $check->getMessage();
            }
        }

        return $return;
    }

    /**
     * Move item to cart
     *
     */
    public function moveAction()
    {
        if ($this->isActiveConfMode()) {
            $this->_redirectReferer(Mage::getUrl('*/*'));
            return;
        }

        if ($this->getRequest()->isPost()) {

            $params = $this->getRequest()->getPost('quote', array());
            //$params = $this->getFilteredParams();

            if (count($params) > 0) {
                $errorCart = array();
                $errorQuote = array();
                foreach ($params as $lineId => $param) {
                    $param['attributeEncode'] = unserialize(base64_decode($param['attributeEncode']));

                    // updating attribute product quantity with the product quantity
                    $param['attributeEncode']['qty'] = $param['qty'];

                    $product = Mage::getModel('catalog/product')->load($param['product']);
                    try {
                        // add item to cart
                        Mage::getModel('checkout/cart')->addProduct($product, $param['attributeEncode'])->save();
                    } catch (Exception $e) {
                        $errorCart[] = $this->__("Item %s wasn't  moved to Shopping cart", $product->getName());
                    }

                    try {
                        // remove item to quote mode
                        Mage::getModel('qquoteadv/qqadvproduct')->deleteQuote($lineId);
                    } catch (Exception $e) {
                        $errorQuote[] = $this->__("Item %s wasn't  removed from Quote mode", $product->getName());
                    }
                }

                $error = '';
                if (count($errorCart) > 0) {
                    $error .= implode("\n", $errorCart);
                }
                if (count($errorQuote) > 0) {
                    $error .= implode("\n", $errorQuote);
                }

                if (strlen($error) > 2) {
                    $this->getCoreSession()->addError($error);
                    $this->_redirect('*/*/');
                    return;
                } else {
                    $this->getCoreSession()->addSuccess($this->__('All items were moved to cart successfully.'));
                    $this->_redirect('checkout/cart/');
                    return;
                }
            }
        }

        $this->_redirect('checkout/cart/');
        //$this->_redirect('*/*/');
    }

    /**
     * Delete product from quote
     *
     */
    public function deleteAction()
    {
        // get the product id to delete
        $id = $this->getRequest()->getParam('id');

        $modelProduct = Mage::getModel('qquoteadv/qqadvproduct');

        try {
            // delete the row from quote_product table
            //$modelProduct->setId($id)->delete();
            $modelProduct->deleteQuote($id);
            $this->getCoreSession()->addSuccess($this->__('Item was deleted successfully.'));
        } catch (Exception $e) {
            $this->getCoreSession()->addError($this->__("Can't remove item"));
        }
        $this->_redirectReferer(Mage::getUrl('*/*'));
    }

    public function indexAction()
    {
        if (!Mage::helper('qquoteadv')->isEnabled()) {
            $this->_forward('404');
            return;
        }

        $this->loadLayout();
        $this->_initLayoutMessages('core/session');
        $this->_initLayoutMessages('customer/session');
        $this->renderLayout();
    }

    /**
     * Initialize quote request before saving
     */

    protected function _initQuoteRequestSave($skip = false)
    {
        $itemsData = array();
        $productsData = array();

        $paramsQuote = $this->getRequest()->getPost('quote', array());
        $paramsProduct = $this->getRequest()->getPost('quote_request', array());

        $quoteId = $this->getCustomerSession()->getQuoteadvId();

        $currencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();

        if (is_array($paramsProduct) & count($paramsProduct) > 0) {
            foreach ($paramsProduct as $quoteadvProductId => $items) {
                $productId = $items['product_id'];

                // Get Qty for request
                // Could be in different formats
                // according to installed modules
                if (isset($items['qty'])) {
                    $orderQty = $items['qty'];
                } elseif (isset($paramsQuote[$quoteadvProductId]['qty'])) {
                    $orderQty = $paramsQuote[$quoteadvProductId]['qty'];
                } else {
                    $orderQty = 1;
                }
                if (!is_array($orderQty)) {
                    $orderQty = array($orderQty);
                }

                $items['attribute'] = self::attributeDecode($paramsQuote[$quoteadvProductId]);
                $items['attribute']['qty'] = $orderQty[0];
                $items['attribute'] = serialize($items['attribute']);

                //preparing items
                if (isset($orderQty)) {
                    foreach ($orderQty as $index => $qty) {
                        $qty = ($qty > 0) ? $qty : 1;
                        if (is_numeric($qty) && $qty > 0) {

                            //#originalPrice
                            $ownerPrice = Mage::helper('qquoteadv')->_applyPrice($quoteadvProductId, $qty);
                            $originalPrice = Mage::helper('qquoteadv')->_applyPrice($quoteadvProductId, 1);
                            //#current currency price
//                            $currencyCode = Mage::app()->getStore()->getCurrentCurrencyCode(); //DEPRECATED, Allready declared
                            $ownerCurPrice = Mage::helper('qquoteadv')->_applyPrice($quoteadvProductId, $qty, $currencyCode);
                            $originalCurPrice = Mage::helper('qquoteadv')->_applyPrice($quoteadvProductId, 1, $currencyCode);

                            $itemsData[] = array(
                                'quote_id' => $quoteId,
                                'product_id' => $productId,
                                'request_qty' => $qty,
                                'owner_base_price' => $ownerPrice,
                                'original_price' => $originalPrice,
                                'owner_cur_price' => $ownerCurPrice,
                                'original_cur_price' => $originalCurPrice,
                                'quoteadv_product_id' => $quoteadvProductId
                            );
                        }
                    }
                }

                //preparing product notes
                $clientRequest = NULL;
                if (isset($items['client_request'])) {
                    $clientRequest = trim($items['client_request']);
                    if ($clientRequest == $this->__('Be advised to enter your comments')) {
                        $clientRequest = "";
                    }
                }

                //update quoteadv product item
                $productsData[] = array(
                    'id' => $quoteadvProductId,
                    'qty' => $items['qty'][0],
                    'attribute' => $items['attribute'],
                    'client_request' => $clientRequest
                );
            }
        }

        //# Customer information
        $helperAddress = Mage::helper('qquoteadv/address');
        $paramsAddress = $this->getRequest()->getPost('customer', array());

        //region handler for select and input fields
        if (isset($paramsAddress['region_id'])) {
            if ($_regionCollection = Mage::getModel('directory/region')->load($paramsAddress['region_id'])) {
                // If region is set but is not the current name. Region id is invalid 
                if (isset($paramsAddress['region']) && $paramsAddress['region'] != '' && $paramsAddress['region'] != $_regionCollection->getName()) {
                    $paramsAddress['region_id'] = '';
                } elseif ($_regionCollection) {
                    $paramsAddress['region'] = $_regionCollection->getName();
                }
            }
        }

        if (isset($paramsAddress['shipping_region_id'])) {
            if ($_regionCollection = Mage::getModel('directory/region')->load($paramsAddress['shipping_region_id'])) {
                // If region is set but is not the current name. Region id is invalid 
                if (isset($paramsAddress['shipping_region']) && $paramsAddress['shipping_region'] != '' && $paramsAddress['shipping_region'] != $_regionCollection->getName()) {
                    $paramsAddress['shipping_region_id'] = '';
                } elseif ($_regionCollection) {
                    $paramsAddress['shipping_region'] = $_regionCollection->getName();
                }
            }
        }

        // separate billing and shipping information
        $paramsAddress = $helperAddress->buildAddress($paramsAddress);

        //#customer account and address
        if (isset($paramsAddress['email'])) {
            //#create new account and autologin
            if (!$this->getCustomerSession()->isLoggedIn() && !$this->_isEmailExists()) {
                $this->_createCustomerAccount($paramsAddress['email'], $paramsAddress['firstname'], $paramsAddress['lastname']);
            }

            $customerId = $this->getCustomerSession()->getId();
            if (empty($customerId)) {
                $customerId = $this->getCustomerSession()->getNotConfirmedId();
            }

            //EMAIL IS REGESTERED BUT CUSTOMER IS STILL NOT LOGGED IN
            if (empty($customerId) && $this->_isEmailExists()) {
                $email = trim($paramsAddress['email']);
                $customer = Mage::getModel('customer/customer')
                    ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
                    ->loadByEmail($email);
                $customerId = $customer->getId();
            }

            if (empty($customerId)) throw new Exception('Customer id does not exist. Cannot place quote request');

            if ($customerId) {
                $paramsAddress['customer_id'] = $customerId;

                // Setting address for known customer
                if (isset($paramsAddress['address']) and $paramsAddress['address'] != "") {
                    $paramsAddress['street'] = $paramsAddress['address'];
                    $paramsAddress['shipping_street'] = $paramsAddress['address'];

                    $billing_address = $this->getRequest()->getPost('billing_address', '');

                    // Use default address
                    if (($billing_address == 'use_default_billing')) {
                        $primAddress = $this->getCustomerSession()->getCustomer()->getPrimaryBillingAddress();

                        $paramsAddress = $helperAddress->fillAddress($primAddress->getData(), $paramsAddress);
                        $paramsAddress['billing'] = $primAddress->getData();

                        //#shipping info
                        if ($shippingAddress = $this->getCustomerSession()->getCustomer()->getPrimaryShippingAddress()) {

                            $shippingAddress = $this->getCustomerSession()->getCustomer()->getPrimaryShippingAddress();

                            $paramsAddress = $helperAddress->fillAddress($shippingAddress->getData(), $paramsAddress, "shipping_");
                            $paramsAddress['shipping'] = $shippingAddress->getData();

                        } else {
                            // Set Shipping Address as Billing Address
                            $paramsAddress = $helperAddress->fillAddress($primAddress->getData(), $paramsAddress, "shipping_");
                            $paramsAddress['shipping'] = $primAddress->getData();
                        }

                    } else {

                        // Add New Address
                        try {

                            $address = false;
                            $same = false;
                            $vars = array('saveAddressBook' => 1, 'defaultShipping' => 0, 'defaultBilling' => 0);
                            $varsOrg = $vars;
                            $helperAddress = Mage::helper('qquoteadv/address');

                            // If billing and shipping are the same, add one address
                            if (isset($paramsAddress['billIsShip']) || isset($paramsAddress['shipIsBill'])) {
                                $vars['defaultShipping'] = 1;
                                $same = true;
                            }

                            if (isset($paramsAddress['billing']) && count($paramsAddress['billing']) > 0) {
                                $vars['defaultBilling'] = 1;
                                $helperAddress->addQuoteAddress($customerId, $paramsAddress['billing'], $vars);
                                $address = true;
                            }

                            if (isset($paramsAddress['shipping']) && count($paramsAddress['shipping']) > 0 && $same === false) {
                                $varsOrg['defaultShipping'] = 1;
                                $helperAddress->addQuoteAddress($customerId, $paramsAddress['shipping'], $varsOrg);
                                $address = true;
                            }

                            // Fallback for original code
                            if ($address === false) {
                                //#add new billing address
                                /*	                        $address = Mage::getModel('customer/address')
                                                                            ->setData($paramsAddress)
                                                                            ->setCustomerId($customerId)
                                                                            ->setIsDefaultBilling(true)
                                                                            ->setIsDefaultShipping(true)
                                                                            ->save();
                                */
                            }

                            //$this->getCoreSession()->addSuccess($this->__('The address was successfully saved'));
                        } catch (Exception $e) {
                            Mage::log($this->__("Billing address wasn't added"));
                        }

                    }
                }
            }
        }

        $paramsAddress['created_at'] = NOW();
        $paramsAddress['store_id'] = Mage::app()->getStore()->getStoreId();
        $paramsAddress['itemprice'] = (Mage::getStoreConfig('qquoteadv/general/itemprice', $paramsAddress['store_id']) == 1) ? 1 : 0;
        if (!$skip):
            $paramsAddress['updated_at'] = NOW();
            $paramsAddress['status'] = Ophirah_Qquoteadv_Model_Status::STATUS_REQUEST;
            $paramsAddress['currency'] = $currencyCode;
            $paramsAddress['increment_id'] = Mage::getModel('qquoteadv/entity_increment_numeric')->getNextId($paramsAddress['store_id']);
            $paramsAddress['create_hash'] = Mage::helper('qquoteadv')->getCreateHash($paramsAddress['increment_id']);
        endif; // $skip

        return array('itemsData' => $itemsData, 'productsData' => $productsData, 'paramsAddress' => $paramsAddress);
    }

    /**
     * Store temporary Quote data in Session
     *
     * Used for Quote Request page to
     * store client request comments
     *
     * TODO: store in database to save the
     * Quote for the customer to place the
     * quote request later
     */
    public function storeQuoteAction()
    {
        // Store post data in customer session
        if ($this->getRequest()->getPost()) {
            $postData = $this->getCustomerSession()->setQuoteData($this->getRequest()->getPost());
            if($quoteData = $postData->getQuoteData()){
                // Check for Quote Id
                if(isset($quoteData['quote_id'])){
                    $qquoteadv = Mage::getModel('qquoteadv/qqadvcustomer')->load($quoteData['quote_id']);
                    // Add current customer data to the quote
                    $qquoteadv->addData($quoteData['customer']);
                    // Set customer data in the session
                    $this->getCustomerSession()->setData('quoteAddresses', Mage::helper('qquoteadv/address')->getAddresses($qquoteadv));
                }
            }
        }

        // Redirect to correct url
        if ($this->getRequest()->getParam('url') == 'continue') {
            if ($this->getCustomerSession()->getData('lastUrl')) {
                $url = $this->getCustomerSession()->getData('lastUrl');
                $this->_redirectUrl($url);
            } elseif ($this->getCustomerSession()->getData('continue_shopping_url')) {
                $url = $this->getCustomerSession()->getData('continue_shopping_url');
                $this->_redirectUrl($url);
            } else {
                $this->_redirectUrl(Mage::getUrl('*/*'));
            }
        } else {
            $this->_redirectUrl(Mage::getUrl('*/*'));
        }
    }

    /**
     * Save customer request
     */
    public function quoteRequestAction($skip = false)
    {
        try {
        $message = '';
        $welcome = true;
        $email = '';

        $helper = Mage::app()->getHelper('qquoteadv');
        $quoteId = $this->getCustomerSession()->getQuoteadvId();

        if ($quoteId && $this->getRequest()->isPost()) {

            $customerData = $this->getRequest()->getPost('customer');
            // Implode Multi select - Extra Options
            $postData = Mage::getModel('qquoteadv/extraoptions')->implodeOptions($customerData);

            if ($postData !== false) {
                $this->getRequest()->setPost('customer', $postData);
            }

            if (!$this->getCustomerSession()->isLoggedIn()) {
                try {

                    $email = $customerData['email'];

                    if (!Zend_Validate::is($email, 'EmailAddress')) {
                        Mage::throwException($this->__('Please enter an valid email address'));
                    }

                    if ($helper->userEmailAlreadyExists($email)) {
                        $this->_setIsEmailExists(true);
                        // If disable account check is no, show message
                        if (Mage::getStoreConfig('qquoteadv/general/disable_exist_account_check') == 0) {
                            Mage::throwException($this->__('Email already exists'));
                        }
                    }
                } catch (Exception $e) {
                    $this->getCoreSession()->addException($e, $this->__('%s', $e->getMessage()));
                    $welcome = false;
                }
            }

            if (!$welcome && !$skip) {
                $this->_redirect("*/*");
            } else {
                // Check Customer and customer addressdata
                $data = $this->_initQuoteRequestSave($skip);

                //#1 insert product with requested quantities values: "one to many"
                //# product_id / qty1 ;   product_id / qty2			
                if (isset($data['itemsData'])) {
                    foreach ($data['itemsData'] as $item) {
                        $resultIsQuoteable = Mage::helper('qquoteadv')->isQuoteable($item['product_id'], $item['request_qty']);
                        if ($resultIsQuoteable->getHasErrors()) {
                            $errors = $resultIsQuoteable->getErrors();
                            if (isset($errors[0])) {
                                $this->getCoreSession()->addError($errors[0]);
                                $url = $_SERVER['HTTP_REFERER'];
                                return $this->_redirectUrl($url);
                            }
                        }
                        try {
                            Mage::getModel('qquoteadv/requestitem')->addItem($item);
//                           $requestitem = Mage::getModel('qquoteadv/requestitem')->addData($item);
//                           $requestitem->save();

                        } catch (Exception $e) {
                            Mage::log($e->getMessage());
                            $message = $this->__('Can not add one of the items to quote request.');
                            $this->getCoreSession()->addError($message);
                        }
                    }
                }
                //#2 need update data with client's notes for exists temporary product
                try {
                    Mage::getModel('qquoteadv/qqadvproduct')->updateQuoteProduct($data['productsData']);
                } catch (Exception $e) {
                    $message = $this->__('Can not add client note request to the product.');
                    $this->getCoreSession()->addError($message);
                }

                //#3 need update clients quote address info
                try {
                    Mage::getModel('qquoteadv/qqadvcustomer')->addCustomer($quoteId, $data['paramsAddress']);
                } catch (Exception $e) {
                    $message = $this->__('Can not add customer address.');
                    $this->getCoreSession()->addError($message);
                }

                Mage::helper('qquoteadv')->sentAnonymousData('request', 'f', $quoteId);

                //#5 set next quote id
                $customerId = $data['paramsAddress']['customer_id']; //$this->getCustomerSession()->getId();
                // DEPRECATED !!
//                if($customerId) {$this->_setNextQuoteadvId($customerId); }

                //#auto proposal
                $autoProposal = Mage::getStoreConfig('qquoteadv/general/auto_proposal');
                $currencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();

                $baseCurrency = Mage::app()->getBaseCurrencyCode();
                if ($currencyCode != $baseCurrency) {
                    $rates = Mage::getModel('directory/currency')->getCurrencyRates($baseCurrency, $currencyCode);
                    $rate = $rates[$currencyCode];
                } else {
                    $rate = 1;
                }

                // Quote gets loaded, mind the attached address information gets stored!
                /** @var $_quoteadv Ophirah_Qquoteadv_Model_Qqadvcustomer */
                $_quoteadv = Mage::getModel('qquoteadv/qqadvcustomer')->load($quoteId);
                $_quoteadv->setCurrency($currencyCode);
                $_quoteadv->setBaseToQuoteRate($rate);

                /** @var $helper Ophirah_Qquoteadv_Helper_Data */
                $helper = Mage::app()->getHelper('qquoteadv');

                if (!$skip):
                    //#Assigned to user
                    $helper->assignQuote($_quoteadv, $this->getRequest()->getPost('user_id'));

                    // Set Expiry Date Proposal
                    $_quoteadv->setExpiry($helper->getExpiryDate());
                    $_quoteadv->setNoExpiry(0);
                endif; //skip

                //disable sales_quote_item_qty_set_after observer
                Mage::register('QtyObserver', 'disable');

                try {
                    // set quote address
                    // Could be skipped now address is set
                    // during the 'addCustomer' method
                    // Address object can contain more information
                    // then the quote object address
                    $_quoteadv->getAddress();
                    $_quoteadv->updateAddress($_quoteadv);
                    $_quoteadv->collectTotals();
                    $_quoteadv->save();

                    // Enable sales_quote_item_qty_set_after observer
                    Mage::unregister('QtyObserver');
                } catch (Exception $e) {
                    Mage::log($e->getMessage());
                }

                if (!$skip):
                    if ($autoProposal && $this->sendAutoProposalEmail() && Mage::helper('qquoteadv')->validLicense('auto-proposal', array($_quoteadv->getData('create_hash'), $_quoteadv->getData('increment_id')))) {
                        $_quoteadv->setStatus(Ophirah_Qquoteadv_Model_Status::STATUS_AUTO_PROPOSAL);
                        Mage::helper('qquoteadv')->sentAnonymousData('auto-proposal', 'f', $quoteId);
                    }

                    try {
                        $_quoteadv->save();
                    } catch (Exception $e) {
                        Mage::log($e->getMessage());
                    }

                    //#4 email with quote place result
                    $this->sendEmail($data['paramsAddress']);

                    if (empty($message)) {

                        $newsletter_enabled = Mage::getStoreConfig('qquoteadv/quote_form/newsletter_subscribe');
                        if ($newsletter_enabled && $this->getRequest()->getPost('newsletter')) {
                            $session = Mage::getSingleton('core/session');
                            $newsletter = $this->getRequest()->getPost('newsletter');
                            $email = Mage::getModel('qquoteadv/qqadvcustomer')->load($quoteId)->getData('email');
                            $customer = Mage::getModel('customer/customer')->load($customerId);

                            if ($newsletter == "on") {
                                try {
                                    $status = Mage::getModel('newsletter/subscriber')->subscribe($email);
                                    $subscriber = Mage::getModel('newsletter/subscriber')->loadByEmail($email);
                                    $subscriber->setCustomerId($customer->getId());
                                    $subscriber->save();

                                    if ($status == Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE) {
                                        $session->addSuccess($this->__('Confirmation request has been sent.'));
                                    } else {
                                        $session->addSuccess($this->__('Thank you for your subscription.'));
                                    }
                                } catch (Mage_Core_Exception $e) {
                                    $session->addException($e, $this->__('There was a problem with the subscription: %s', $e->getMessage()));
                                } catch (Exception $e) {
                                    $session->addException($e, $this->__('There was a problem with the subscription.'));
                                }
                            }
                        }

                        $this->_redirect('*/*/success/');
                        return;
                    } else {
                        // $this->_redirect("*/*");
                    }
                endif; //skip

                if ($skip) {
                    /** @var Ophirah_Qquoteadv_Model_Qqadvcustomer */
                    return $_quoteadv;
                }
            }
        } else {

            $this->_redirectReferer(Mage::getUrl('*/*'));
        }
        } catch (Exception $e) {
            $this->getCoreSession()->addError($e->getMessage());
            $this->_redirectReferer(Mage::getUrl('*/*'));
        }
    }

    /**
     * Get Estimated Shipping rates from
     * quote request data
     *
     * @return bool / array     // found shipping methods and rates
     */
    public function quoteShippingEstimateAction()
    {
        $_quoteadv = $this->quoteRequestAction(true);
        $_quoteadv->getAddress()->clearRates();

        // clear session data
        Mage::getSingleton('customer/session')->setData('quoteRatesList', null);
        if ($_quoteadv instanceof Ophirah_Qquoteadv_Model_Qqadvcustomer) {
            $ratesList = Mage::getModel('qquoteadv/quoteshippingrate')->buildOptions($_quoteadv);
            $addresses = Mage::helper('qquoteadv/address')->buildQuoteAdresses($_quoteadv);

            // CheckCountryID
            $shipping = new Varien_Object();
            if (isset($addresses['shippingAddress'])) {
                $shipping = $addresses['shippingAddress'];
            }

            if ($ratesList && $shipping->getData('country_id')) {
                // Add customer information to session
                $customer = new Varien_Object();
                $customer->setData('email', $_quoteadv->getData('email'));
                $customer->setData('firstname', $_quoteadv->getData('firstname'));
                $customer->setData('lastname', $_quoteadv->getData('lastname'));
                // Add filled out addresses to session
                $addresses = Mage::helper('qquoteadv/address')->buildQuoteAdresses($_quoteadv);
                Mage::getSingleton('customer/session')->setData('quoteCustomer', $customer);
                Mage::getSingleton('customer/session')->setData('quoteRatesList', $ratesList);
                Mage::getSingleton('customer/session')->setData('quoteAddresses', $addresses);
                Mage::getSingleton('customer/session')->setData('quoteRateRequest', true);
                // return to frontend
                $this->_redirect("*/*");
            }
        }

        return false;
    }

    /**
     * Show success message
     */
    public function successAction()
    {
        $quoteId = $this->getCustomerSession()->getQuoteadvId();
        $quote = Mage::getModel('qquoteadv/qqadvcustomer')->load($quoteId);

        $this->getCustomerSession()->setQuoteadvId(null);
        $this->loadLayout();
        $block = $this->getLayout()->getBlock('qquote.success');

        $block->setData('quote', $quote);
        $this->renderLayout();
    }

    /**
     * Show Quote success message
     */
    public function quotesuccessAction()
    {
        $quoteId = $this->getCustomerSession()->getQuoteadvId();
        $quote = Mage::getModel('qquoteadv/qqadvcustomer')->load($quoteId);

        $this->getCustomerSession()->setQuoteadvId(null);
        $this->loadLayout();
        $block = $this->getLayout()->getBlock('qquote.quotesuccess');

        $block->setData('quote', $quote);
        $this->renderLayout();
    }


    /**
     * Send email to administrator informing about the quote
     * @param array $params customer address
     */
    public function sendEmail($params)
    {
        //Create an array of variables to assign to template
        $vars = array();

        $quoteId = $this->getCustomerSession()->getQuoteadvId();
        $customer_id = $params['customer_id']; //$this->getCustomerSession()->getId();

        /* @var $_quoteadv Ophirah_Qquoteadv_Model_Qqadvcustomer */
        $_quoteadv = Mage::getModel('qquoteadv/qqadvcustomer')->load($quoteId);

        //Vars into email templates
        $vars = array(
            'quote' => Mage::getModel('qquoteadv/qqadvcustomer')->load($quoteId),
            'customer' => Mage::getModel('customer/customer')->load($customer_id),
            'quoteId' => $quoteId
        );

        $recipientEmail = $params['email'];
        $recipientName = $vars['customer']->getName();

        /**
         * $templateId can be set to numeric or string type value.
         * You can use Id of transactional emails (found in
         * "System->Trasactional Emails"). But better practice is
         * to create a config for this and use xml path to fetch
         * email template info (whatever from file/db).
         */
        $template = Mage::getModel('qquoteadv/core_email_template');

        $disabledEmail = Ophirah_Qquoteadv_Model_System_Config_Source_Email_Templatedisable::VALUE_DISABLED_EMAIL;
        $quoteadv_param = Mage::getStoreConfig('qquoteadv/emails/request');
        if ($quoteadv_param != $disabledEmail):

            if ($quoteadv_param) {
                $templateId = $quoteadv_param;
            } else {
                $templateId = self::XML_PATH_QQUOTEADV_REQUEST_EMAIL_TEMPLATE;
            }

            if (is_numeric($templateId)) {
                $template->load($templateId);
            } else {
                $template->loadDefault($templateId);
            }

            $subject = $template['template_subject'];
            $sender = $_quoteadv->getEmailSenderInfo();

            $template->setSenderName($sender['name']);
            $template->setSenderEmail($sender['email']);
            $template->setTemplateSubject($subject);

            $bcc = Mage::getStoreConfig('qquoteadv/emails/bcc', $_quoteadv->getStoreId());
            if ($bcc) {
                $bccData = explode(";", $bcc);
                $template->addBcc($bccData);
            }

            if ((bool)Mage::getStoreConfig('qquoteadv/emails/send_linked_sale_bcc', $_quoteadv->getStoreId())) {
                $template->addBcc(Mage::getModel('admin/user')->load($_quoteadv->getUserId())->getEmail());
            }

            /**
             * Opens the qquoteadv_request.html, throws in the variable array
             * and returns the 'parsed' content that you can use as body of email
             */
            $processedTemplate = $template->getProcessedTemplate($vars);

            /*
             * getProcessedTemplate is called inside send()
             */
            $res = $template->send($recipientEmail, $recipientName, $vars);

            if (empty($res)) {
                $message = $this->__("Qquote request email was't sent to admin for quote #%s", $quoteId);
                Mage::log($message);
            }

        endif;

    }

    /**
     * Generate a random password
     *
     * @param int $length
     * @return string           // Random password
     */
    protected function _generatePassword($length = 8)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?";
        $password = substr(str_shuffle($chars), 0, $length);
        return $password;
    }

    /**
     * Add customer account with random password
     *
     * @param $email
     * @param $firstname
     * @param $lastname
     * @return $this
     */
    protected function _createCustomerAccount($email, $firstname, $lastname)
    {
        // DEPRECATED SINCE v4.3.0
        $pass = Mage::getStoreConfig('qquoteadv/emails/user_password', Mage::app()->getStore()->getId());
        if ($pass) {
            $password = $pass;
        } else {
            $password = self::DEFAULT_PASSWORD;
        }

        // NEW RANDOM PASSWORD METHOD
        $password = $this->_generatePassword(6);

        $is_subscribed = 0;

        //# NEW USER REGISTRATION
        if ($email && !$this->getCustomerSession()->isLoggedIn()) {
            $cust = Mage::getModel('customer/customer');
            $cust->setWebsiteId(Mage::app()->getStore()->getWebsiteId())->loadByEmail($email);

            //#create new user
            if (!$cust->getId()) {
                $customerData = array(
                    'firstname' => $firstname,
                    'lastname' => $lastname,
                    'email' => $email,
                    'password' => $password,
                    'password_hash' => md5($password),
                    'is_subscribed' => $is_subscribed,
                );

                $customer = Mage::getModel('qquoteadv/customer_customer');

                //$customer->setStoreId($storeId);
                $customer->setWebsiteId(Mage::app()->getStore()->getWebsiteId());
                $customer->setData($customerData);
                $customer->save();

                //# Emails part
                if ($customer->isConfirmationRequired()) {

                    $this->getCustomerSession()->setNotConfirmedId($customer->getId());
                    $customer->sendNewAccountEmail('confirmation', $this->getCustomerSession()->getBeforeAuthUrl(), Mage::app()->getStore()->getId());
                    //$message = $this->__('Account confirmation is required. Please check your email for the confirmation link.');
                    //$this->getCoreSession()->addNotice($message);

                } else {
                    $this->getCustomerSession()->login($email, $password);
                    $customer->sendNewAccountEmail('registered_qquoteadv', '', Mage::app()->getStore()->getId());
                }
            }
        }
        return $this;
    }

    /**
     * Searching user by email.
     *
     */
    public function useJsEmailAction()
    {
        $customer = Mage::getModel('customer/customer');
        if ($this->getCustomerSession()->isLoggedIn()) {
            return;
        }

        // try to confirm by email
        $email = $this->getRequest()->getPost('email');
        if ($email) {
            if (!Zend_Validate::is($email, 'EmailAddress')) {
                // $this->__('Please enter an valid email address');
                return;
            } else {
                $customer->setWebsiteId(Mage::app()->getStore()->getWebsiteId())->loadByEmail($email);
                if (!$customer->getId()) {
                    print('notexists');
                } else
                    print('exists');
            }
        }
        return;
    }

    protected function _switch2Order($postData = array())
    {
        //1 quoteid
        //2 get all products by quote
        //3 move them to the shopping cart
        $errorCart = array();
        $errorsQuote = array();

        // if quote_id is not set then insert into qquote_customer table and get quote_id
        if ($this->getCustomerSession()->getQuoteadvId() == NULL) {
            $qcustomer = array('created_at' => NOW(),
                'updated_at' => NOW()
            );

            // save data to qquote_customer table and getting inserted row id
            $qId = Mage::getModel('qquoteadv/qqadvcustomer')->addQuote($qcustomer)->getQuoteId();
            // setting inserted row id of qquote_customer table into session
            $this->getCustomerSession()->setQuoteadvId($qId);
        }

        $productNames = array();
        $products = Mage::getModel('qquoteadv/qqadvproduct')->getQuoteProduct($this->getCustomerSession()->getQuoteadvId());

        foreach ($products as $key => $product) {

            $item = Mage::getModel('catalog/product')->load($product->getProductId());

            $param['attributeEncode'] = unserialize($product->getAttribute());

            //# updating attribute product quantity with the product quantity
            //!!! overrload old qty value from field 'attribute' quote table to real request qty
            $param['attributeEncode']['qty'] = $product->getQty();

            try {
                //#n2o if( $item->getData('allowed_to_ordermode')) {
                // add item to cart
                if (!$item->isSalable()) {
                    throw new Exception('is not salable');
                } else {
                    Mage::getModel('checkout/cart')->addProduct($item, $param['attributeEncode'])->save();
                } //}
            } catch (Exception $e) {
                $errorsQuote[] = $this->__("Item %s wasn't  moved to Shopping cart", $item->getName());
            }

            try {
                // remove item to quote mode
                Mage::getModel('qquoteadv/qqadvproduct')->deleteQuote($key);
            } catch (Exception $e) {
                $errorsQuote[] = $this->__("Item %s wasn't  removed from Quote mode", $item->getName());
            }
        }

        if (count($postData) > 0 && $postData['product']) {
            $productId = $postData['product'];
            $item = Mage::getModel('catalog/product')->load($productId);
            $param['attributeEncode'] = $postData;

            $qty = (empty($postData['qty'])) ? 1 : $postData['qty'];

            $param['attributeEncode']['qty'] = $qty;

            try {
                Mage::getModel('checkout/cart')->addProduct($item, $param['attributeEncode'])->save();
            } catch (Exception $e) {
                //die('=='.$e->getMessage());
                $errorsQuote[] = $e->getMessage();
            }
        }

        foreach ($errorsQuote as $err) {
            $this->getCoreSession()->addError($err);
        }

        $this->getCoreSession()->setCartWasUpdated(true);

        if (count($errorsQuote)) return false;

        return true;
    }

    public function switch2OrderAction()
    {
        if ($this->isActiveConfMode()) {
            $this->_redirectReferer(Mage::getUrl('*/*'));
            return;
        }

        $result = $this->_switch2Order();
        if ($result) $this->getCoreSession()->addSuccess($this->__('Item(s) were moved from Quote to Order mode successfully.'));

        $this->_redirect('checkout/cart/');
    }

    protected function _swith2Quote()
    {
        $result = false;
        $cartHelper = Mage::helper('checkout/cart');
        $cart = $cartHelper->getItemsCount();

        if ($cart > 0) {
            $session = Mage::getSingleton('checkout/session');

            foreach ($session->getQuote()->getAllVisibleItems() as $item) {

                $productId = $item->getProductId();
                $product = Mage::getModel('catalog/product')->load($productId);
                $isAllow = $product->getAllowedToQuotemode();
                if ($isAllow) {
                    $superAttribute = $item->getProduct()->getTypeInstance(true)->getOrderOptions($item->getProduct());

                    $optionalAttrib = '';
                    if (isset($superAttribute['info_buyRequest'])) {
                        if (isset($superAttribute['info_buyRequest']['uenc']))
                            unset($superAttribute['info_buyRequest']['uenc']);

                        $optionalAttrib = serialize($superAttribute['info_buyRequest']);
                    }

                    $params = array(
                        'cartid' => $item->getId(),
                        'product' => $item->getProductId(),
                        'qty' => $item->getQty(),
                        'attributeEncode' => ''
                    );


                    $this->addDataAction($params, $optionalAttrib);
                }
            }

            $result = true;
        }
        //if

        return $result;
    }

    //case: called from shopping cart page
    public function switch2QquoteAction()
    {

        if ($this->isActiveConfMode()) {
            $this->_redirectReferer(Mage::getUrl('*/*'));
            return;
        }

        $result = $this->_swith2Quote();

        $this->_redirect('qquoteadv/index/');
    }

    protected function isActiveConfMode()
    {
        if (Mage::helper('qquoteadv')->isActiveConfirmMode()) {
            $link = Mage::getUrl('qquoteadv/view/outqqconfirmmode');
            $message = Mage::helper('qquoteadv')->__("You are in a quote confirmation mode, <a href='%s'>log out</a>.", $link);
            $this->getCoreSession()->addNotice($message);
            return true;
        }

        return false;
    }

    protected function _isEmailExists()
    {
        return $this->_isEmailExists;
    }

    protected function _setIsEmailExists($param)
    {
        $this->_isEmailExists = $param;
    }

    protected function _setNextQuoteadvId($customerId)
    {

        //#init next quote id
        $date = NOW();
        $qcustomer = array(
            'created_at' => $date,
            'updated_at' => $date,
            'customer_id' => $customerId
        );

        $nextQuoteId = Mage::getModel('qquoteadv/qqadvcustomer')->addQuote($qcustomer)->getQuoteId();

        //# set next quote id into session
        $this->getCustomerSession()->setQuoteId($nextQuoteId);
        return $this;
    }

    /**
     * Action to reconfigure quote item
     */
    public function configureAction()
    {
        // Extract item and product to configure
        $id = (int)$this->getRequest()->getParam('id');
        $quoteItem = null;
        $productId = null;
        $productOptions = null;

        if ($id) {
            $quoteid = $this->getCustomerSession()->getQuoteadvId();
            $data = Mage::getModel('qquoteadv/qqadvproduct')->getCollection()
                ->addFieldToFilter("quote_id", $quoteid)
                ->addFieldToFilter("id", $id);
            foreach ($data as $row) {
                $quoteItem = $row;
                break;
            }
        }

        if (!$quoteItem) {
            $this->getCoreSession()->addError($this->__('Quote item is not found.'));
            $this->_redirect('qquoteadv/index');
            return;
        } else {
            $productId = $quoteItem['product_id'];
            $productOptions = unserialize($quoteItem['attribute']);
        }

        try {
            $params = new Varien_Object();
            $params->setCategoryId(false);
            $params->setConfigureMode(true);
            if (count($productOptions)) {
                $params->setBuyRequest(new Varien_Object($productOptions));
            }

            Mage::helper('catalog/product_view')->prepareAndRender($productId, $this, $params);
        } catch (Exception $e) {
            $this->getCoreSession()->addError($this->__('Cannot configure product.'));
            Mage::logException($e);
            $this->_redirect('*/*/');
            return;
        }
    }

    /**
     * Update product configuration for a quote item
     */
    public function updateItemOptionsAction()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $params = $this->getRequest()->getParams();

        if (!isset($params['options'])) {
            $params['options'] = array();
        }
        try {
            $quoteItem = Mage::getModel('qquoteadv/qqadvproduct')->load($id);
            if (!$quoteItem) {
                Mage::throwException($this->__('Quote item is not found.'));
            }
            if (isset($params['qty'])) {
                $filter = new Zend_Filter_LocalizedToNormalized(
                    array('locale' => Mage::app()->getLocale()->getLocaleCode())
                );
                $params['qty'] = $filter->filter($params['qty']);
                if ($params['qty'] > 0) {

                    $modelProduct = Mage::getModel('qquoteadv/qqadvproduct');
                    $pid = $modelProduct->load($id)->getData('product_id');
                    $checkQty = Mage::helper('qquoteadv')->isQuoteable($pid, $params['qty']);
                    if ($checkQty->getHasErrors()) {
                        $errors = $checkQty->getErrors();
                        if (isset($errors[0])) {
                            $this->getCoreSession()->addError($errors[0]);
                            $this->_redirectUrl($this->_getRefererUrl());
                            return;
                        }
                    }

                    $quoteItem->setQty($params['qty']);
                }
            }

            $params = new Varien_Object($params);
            $product = Mage::getModel('catalog/product')->load($params['product']);
            $product->getTypeInstance(true)->prepareForCartAdvanced($params, $product);

            $attribute = $params->toArray();
            $oldAttribute = unserialize($quoteItem->getAttribute());
            if (isset($oldAttribute['options'])) {
                if (isset($attribute['options'])) {
                    $attribute['options'] += $oldAttribute['options'];
                } else {
                    $attribute['options'] = $oldAttribute['options'];
                }
            }
            $quoteItem->setAttribute(serialize($attribute));

            //#options
            if (isset($params['options']) && count($params['options']) > 0) {
                $quoteItem->setHasOptions(1);

                $options = serialize($params['options']);
                $quoteItem->setOptions($options);
            }
            $quoteItem->save();

        } catch (Mage_Core_Exception $e) {
            if ($this->getCoreSession()->getUseNotice(true)) {
                $this->getCoreSession()->addNotice($e->getMessage());
            } else {
                $messages = array_unique(explode("\n", $e->getMessage()));
                foreach ($messages as $message) {
                    $this->getCoreSession()->addError($message);
                }
            }
            $this->_redirect('*/*/configure', array('id' => $id));


        } catch (Exception $e) {
            $this->getCoreSession()->addException($e, $this->__('Cannot update the item.'));
            Mage::logException($e);
            $this->_redirect('*/*/configure', array('id' => $id));
        }
        $this->_redirect('*/*');
    }

    /**
     * Send email to client to informing about the quote proposition
     * @param array $params
     * $params['email'], $params['name']
     */
    public function sendAutoProposalEmail()
    {
        $this->quoteId = (int)$this->getRequest()->getParam('id');
        $quoteId = $this->getCustomerSession()->getQuoteadvId();
        /* @var $_quoteadv Ophirah_Qquoteadv_Model_Qqadvcustomer */
        $_quoteadv = Mage::getModel('qquoteadv/qqadvcustomer')->load($quoteId);
        if (!Mage::helper('qquoteadv')->validLicense('auto-proposal', array($_quoteadv->getData('create_hash'), $_quoteadv->getData('increment_id')))) {
            return false;
        }

        //Create an array of variables to assign to template
        $vars = array();
        $vars['quote'] = $_quoteadv;
        $vars['customer'] = Mage::getModel('customer/customer')->load($_quoteadv->getCustomerId());

        $template = Mage::getModel('core/email_template');

        $quoteadv_param = Mage::getStoreConfig('qquoteadv/emails/proposal', $_quoteadv->getStoreId());
        if ($quoteadv_param) {
            $templateId = $quoteadv_param;
        } else {
            $templateId = self::XML_PATH_QQUOTEADV_REQUEST_PROPOSAL_EMAIL_TEMPLATE;
        }

        if (is_numeric($templateId)) {
            $template->load($templateId);
        } else {
            $template->loadDefault($templateId);
        }

        $vars['attach_pdf'] = $vars['attach_doc'] = false;

        //Create pdf to attach to email

        if (Mage::getStoreConfig('qquoteadv/attach/pdf', $_quoteadv->getStoreId())) {
            $pdf = Mage::getModel('qquoteadv/pdf_qquote')->getPdf($_quoteadv);
            $realQuoteadvId = $_quoteadv->getIncrementId() ? $_quoteadv->getIncrementId() : $_quoteadv->getId();
            try {
                $file = $pdf->render();
                $name = Mage::helper('qquoteadv')->__('Price_proposal_%s', $realQuoteadvId);
                $template->getMail()->createAttachment($file, 'application/pdf', Zend_Mime::DISPOSITION_ATTACHMENT, Zend_Mime::ENCODING_BASE64, $name . '.pdf');
                $vars['attach_pdf'] = true;

            } catch (Exception $e) {
                Mage::log($e->getMessage());
            }

        }

        if ($doc = Mage::getStoreConfig('qquoteadv/attach/doc', $_quoteadv->getStoreId())) {
            $pathDoc = Mage::getBaseDir(Mage_Core_Model_Store::URL_TYPE_MEDIA) . DS . 'quoteadv' . DS . $doc;
            try {
                $file = file_get_contents($pathDoc);

                $info = pathinfo($pathDoc);
                //$extension = $info['extension']; 
                $basename = $info['basename'];
                $template->getMail()->createAttachment($file, '', Zend_Mime::DISPOSITION_ATTACHMENT, Zend_Mime::ENCODING_BASE64, $basename);
                $vars['attach_doc'] = true;
            } catch (Exception $e) {
                Mage::log($e->getMessage());
            }
        }

        $remark = Mage::getStoreConfig('qquoteadv/general/qquoteadv_remark', $_quoteadv->getStoreId());
        if ($remark) {
            $vars['remark'] = $remark;
        }

        $subject = $template['template_subject'];

        $vars['link'] = Mage::getUrl("qquoteadv/view/view/", array('id' => $quoteId));

        $sender = $_quoteadv->getEmailSenderInfo();
        $template->setSenderName($sender['name']);
        $template->setSenderEmail($sender['email']);

        $template->setTemplateSubject($subject);
        $bcc = Mage::getStoreConfig('qquoteadv/emails/bcc', $_quoteadv->getStoreId());
        if ($bcc) {
            $bccData = explode(";", $bcc);
            $template->addBcc($bccData);
        }

        if ((bool)Mage::getStoreConfig('qquoteadv/emails/send_linked_sale_bcc', $_quoteadv->getStoreId())) {
            $template->addBcc(Mage::getModel('admin/user')->load($_quoteadv->getUserId())->getEmail());
        }

        /**
         * Opens the qquote_request.html, throws in the variable array
         * and returns the 'parsed' content that you can use as body of email
         */
        $processedTemplate = $template->getProcessedTemplate($vars);

        /*
         * getProcessedTemplate is called inside send()
         */
        $res = $template->send($_quoteadv->getEmail(), $vars['customer']->getName(), $vars);

        return $res;
    }

    public function goToQuoteAction()
    {
        $quoteId = (int)$this->getRequest()->getParam('id');
        $hash = $this->getRequest()->getParam('hash');
        $my = $this->getRequest()->getParam('my');

        $quote = Mage::getModel('qquoteadv/qqadvcustomer')->load($quoteId);
        $origUrlHash = $quote->getUrlHash();

        $autoConfirm = '';
        $statusAllowed = Mage::getModel('qquoteadv/status')->statusAllowed();
        if (Mage::getStoreConfig('qquoteadv/emails/auto_confirm', $quote->getStoreId()) == 1 && in_array($quote->getStatus(), $statusAllowed)) {
            $autoConfirm = $this->getRequest()->getParam('autoConfirm');
        }

        $configured = Mage::getStoreConfig('qquoteadv/emails/link_auto_login', $quote->getStoreId());
        $allowed = Mage::helper('qquoteadv')->validLicense('email-auto-login', array($quote->getData('create_hash'), $quote->getData('increment_id')));

        if ($configured && $allowed && $hash === $origUrlHash) {
            $customer = Mage::getModel('customer/customer')->load($quote->getCustomerId());
            $customerSession = Mage::getSingleton('customer/session');

            $customerSession->setCustomerAsLoggedIn($customer);
            $customerSession->renewSession();

            if (!$my && !$autoConfirm) {
                $this->_redirect('*/view/view/', array('id' => $quoteId));
            } elseif (!$my && $autoConfirm) {
                $this->_redirect('*/view/confirm/', array('id' => $quoteId));
            } elseif ($my == "quote") {
                $this->_redirect('*/view/view/', array('id' => $quoteId));
            } else {
                $this->_redirect('*/view/history/');
            }

        } else {
            ($my == "quotes") ? $this->_redirectUrl(Mage::getUrl('*/view/history/')) : $this->_redirectUrl(Mage::getUrl('*/view/view/', array('id' => $quoteId)));
        }
    }

    public function clearQuoteAction()
    {
        $this->_clearQuote();
        $this->_redirectReferer();
        return;
    }

    private function _clearQuote()
    {
        $products = Mage::helper('qquoteadv')->getQuote();
        foreach ($products as $product) {
            $product->deleteQuote($product->getId());
        }

        return;
    }

}
