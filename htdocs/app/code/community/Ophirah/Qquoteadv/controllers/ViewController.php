<?php

class Ophirah_Qquoteadv_ViewController extends Mage_Core_Controller_Front_Action
{

    private $_quoteId = null;
    CONST XML_PATH_QQUOTEADV_REQUEST_CANCEL_EMAIL_TEMPLATE = 'qquoteadv/emails/proposal_cancel';
    CONST XML_PATH_QQUOTEADV_REQUEST_REJECT_EMAIL_TEMPLATE = 'qquoteadv/emails/proposal_reject';

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

        if (array_key_exists('cart', $params)) {
            foreach ($params['cart'] as $key => $value) {
                $this->addFilterAction($value);
            }
        } else {
            $this->addFilterAction($params);
        }
    }

    /**
     * Insert filter quote data
     */
    public function addFilterAction($params)
    {
        // set the qty to 1 if it is empty
        if ($params['qty'] == '' || !is_numeric($params['qty'])) {
            $params['qty'] = 1;
        }

        /**
         * if addAction is called from cart or quote page
         * from cart/quote page, the parameter is serialized string and is passed as base64 encoded form
         * hence, we have to decode it
         */
        if (array_key_exists('attributeEncode', $params)) {
            // $superAttribute = base64_decode($params['attributeEncode']);

            /**
             * unsetting 'uenc' key which is present in array when it is moved from cart to quote
             * uenc contains url of the product in base64_decode form
             */
            $testParams = unserialize(base64_decode($params['attributeEncode']));
            unset($testParams['uenc']);
            $superAttribute = serialize($testParams);
        } /**
         * if addAction is called from product detail page
         * from product detail page, parameter is passed as an array
         * hence, we have to serialize the array and make it string
         */ else {
            $superAttribute = serialize($params);
        }

        // if the product is Grouped Product
        if ($this->getRequest()->getParam('super_group')) {
            $superGroup = $this->getRequest()->getParam('super_group');

            if (array_sum($superGroup) > 0) {
                // adding each super group product separately as simple product
                foreach ($superGroup as $key => $value) {
                    // don't add product if it have quantity value 0
                    if ($value != 0 && is_numeric($value)) {
                        $groupParams['product'] = $key;
                        $groupParams['qty'] = (int)$value;
                        $this->addDataAction($groupParams, $superAttribute);
                    }
                }
            } else {
                $this->getCoreSession()->addNotice($this->__('Please specify product quantity.'));
                $this->_redirectReferer(Mage::getUrl('*/*'));
            }
        } else {
            $this->addDataAction($params, $superAttribute);
        }
    }

    /**
     * Insert quote data (main data add function)
     * @param array $params post parameter for product
     * @param string $superAttribute
     */
    public function addDataAction($params, $superAttribute)
    {
        if ($this->getCustomerSession()->isLoggedIn()) {
            $qcustomer = array('created_at' => NOW(), 'updated_at' => NOW(), 'customer_id' => $this->getCustomerSession()->getId());
        } else {
            $qcustomer = array('created_at' => NOW(), 'updated_at' => NOW());
        }

        $modelCustomer = Mage::getModel('qquoteadv/qqadvcustomer');
        $modelProduct = Mage::getModel('qquoteadv/qqadvproduct');
        try {
            // if quote_id is not set then insert into qquote_customer table and set quote_id
            if ($this->getCustomerSession()->getQuoteadvId() == NULL) {
                // save data to qquote_customer table and getting inserted row id
                $qId = $modelCustomer->addQuote($qcustomer)->getQuoteId();
                // setting inserted row id of qquote_customer table into session
                $this->getCustomerSession()->setQuoteadvId($qId);
            }

            /**
             * check if the customer has already added the particular product
             * if the product is already added by the customer then add only the quantity for that row
             * otherwise add new row for product
             */
            $dataInProduct = array();
            $dataInProduct = $modelProduct->getCollection()
                ->addFieldToFilter('quote_id', $this->getCustomerSession()->getQuoteadvId())
                ->addFieldToFilter('product_id', $params['product'])
                ->addFieldToFilter('attribute', $superAttribute);

            if ($dataInProduct->getData() != array()) {
                foreach ($dataInProduct as $item) {
                    // adding qty to product if the customer has previously added in the current session
                    $qtySum = array('qty' => $params['qty'] + $item->getQty());
                    $modelProduct->updateProduct($item->getId(), $qtySum);
                }
            } else {
                // save data with the quote_id to qquote_product table
                $qproduct = array('quote_id' => $this->getCustomerSession()->getQuoteadvId(), 'product_id' => $params['product'], 'qty' => $params['qty'], 'attribute' => $superAttribute);
                $modelProduct->addProduct($qproduct);
            }

            /**
             * deleting the item from cart if cartid is set in the url
             * i.e. if the addAction is called from 'Move to quote' button of cart page
             * in this case, we have to add the item to quote and delete from cart
             */
            if (array_key_exists('cartid', $params)) {
                Mage::getModel('checkout/cart')->removeItem($params['cartid'])->save();
                $this->getCoreSession()->addSuccess($this->__('Item was moved to quote successfully.'));
                $this->_redirectReferer(Mage::getUrl('*/*'));
                //$this->_redirect('*/*/');
            } else {
                $this->_redirect('*/*/');
            }
            //$this->_redirectReferer(Mage::getUrl('*/*'));
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
    }

    /**
     * Move item to cart
     *
     */
    public function moveAction()
    {
        $params = $this->getRequest()->getParams();

        $params = $this->getFilteredParams();
        $params['attributeEncode'] = unserialize(base64_decode($params['attributeEncode']));

        // updating attribute product quantity with the product quantity
        $params['attributeEncode']['qty'] = $params['qty'];

        $quoteId = $params['quoteid'];

        $product = Mage::getModel('catalog/product')->load($params['product']);

        // add item to cart
        Mage::getModel('checkout/cart')->addProduct($product, $params['attributeEncode'])->save();

        // delete item from quote
        Mage::getModel('qquoteadv/qqadvproduct')->deleteQuote($quoteId);

        $this->getCoreSession()->addSuccess($this->__('Item was moved to cart successfully.'));
        $this->_redirect('*/*/');
    }

    /**
     * Update product quantity from quote
     *
     */
    public function updateAction()
    {
        $quoteData = $this->getRequest()->getParam('quote');
        $modelProduct = Mage::getModel('qquoteadv/qqadvproduct');

        try {
            if (is_array($quoteData)) {
                // update quote list
                foreach ($quoteData as $key => $value) {
                    // delete product if qty is entered 0
                    if ($value['qty'] == 0) {
                        $modelProduct->deleteQuote($key);
                    } else {
                        $modelProduct->updateProduct($key, $value);
                    }
                }
            }
            $this->getCoreSession()->addSuccess($this->__('Quote list was updated successfully.'));
        } catch (Exception $e) {
            $this->getCoreSession()->addError($this->__("Can't update quote list"));
        }
        $this->_redirectReferer(Mage::getUrl('*/*'));
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

    /**
     * Show address form after user submits quote
     */
    public function addressAction()
    {
        $quoteId = $this->getCustomerSession()->getQuoteadvId();
        if ($quoteId) {
            $modelProduct = Mage::getModel('qquoteadv/qqadvproduct');
            $dataInProduct = $modelProduct->getCollection()
                ->addFieldToFilter('quote_id', $quoteId);

            if ($dataInProduct->getData() != array()) {
                $this->loadLayout();
                $this->renderLayout();
            } else {
                $this->getCoreSession()->addError($this->__('No item in quote.'));
                $this->_redirectReferer(Mage::getUrl('*/*'));
            }
        } else {
            $this->getCoreSession()->addError($this->__('No item in quote.'));
            $this->_redirectReferer(Mage::getUrl('*/*'));
        }
    }

    /**
     * Show success message
     */
    public function successAction()
    {
        // unset customer address
        $this->getCustomerSession()->unsQuoteId();

        $this->loadLayout();
        $this->renderLayout();
    }

    public function getQuoteadvId()
    {
        return $this->_quoteId;
    }

    public function setQuoteId($id)
    {
        $this->_quoteId = $id;
    }

    /**
     * Initialize requested quote object
     *
     * @return Ophirah_Quote_Model_Proposal
     */
    protected function _initQuote()
    {

        if (!$this->isCustomerLoggedIn()) {
            $this->_redirect('customer/account/login/');
        }

        Mage::dispatchEvent('quote_controller_init_before', array('controller_action' => $this));
        $quoteId = (int)$this->getRequest()->getParam('id');

        if (!$quoteId) {
            return false;
        }

        $quote = Mage::getModel('qquoteadv/qqadvproduct')->getCollection()
            //->setStoreId(Mage::app()->getStore()->getId())
            ->addFieldToFilter('quote_id', $quoteId);

        Mage::register('quote', $quote);

        try {
            Mage::dispatchEvent('quote_controller_init', array('quote' => $quote));
            Mage::dispatchEvent('quote_controller_init_after', array('quote' => $quote, 'controller_action' => $this));
        } catch (Mage_Core_Exception $e) {
            Mage::logException($e);
            return false;
        }

        return $quote;
    }

    /**
     * Quote proposal by quoteId
     *
     */
    public function viewAction()
    {
        if ($quote = $this->_initQuote()) {

            $quoteId = (int)$this->getRequest()->getParam('id');

            if ($quoteData = $this->checkUserQuote($quoteId, $this->getCustomerId())) {
                $currentCurrency = Mage::app()->getStore()->getCurrentCurrencyCode();
                //# show quote in case proposal was sent
                foreach ($quoteData as $key => $item) {
                    $currency = $item->getCurrency();
                    Mage::app()->getStore()->setCurrentCurrencyCode($currency);

                    if (Ophirah_Qquoteadv_Model_Status::STATUS_BEGIN == $item->getStatus()) {
                        $this->getCoreSession()->addNotice($this->__('Access denied!'));
                        $this->_forward('noRoute');
                        //return;
                    }
                }

                if ($item)
                    if (Ophirah_Qquoteadv_Model_Status::STATUS_REQUEST == $item->getStatus()) {
                        $msg = $this->__("Quote Request in Process, wait for price proposal Shop owner");
                        $this->getCoreSession()->addNotice($msg);
                    }

                $this->loadLayout();
                $this->renderLayout();
                Mage::dispatchEvent('quote_proposal_controller_view', array('quote' => $quote));
            } else {
                $this->getCoreSession()->addNotice($this->__('Access denied!'));
//                $this->_redirect('customer/account/');
                $this->_redirect('qquoteadv/view/history/');
//                return;
            }
        } else {
              $this->_redirect('qquoteadv/view/history/');
//            $this->_forward('noRoute');
        }
        return;
    }

    private function checkUserQuote($quoteId, $userId)
    {

        $quote = Mage::getModel('qquoteadv/qqadvcustomer')->getCollection()
            //->setStoreId(Mage::app()->getStore()->getId())
            ->addFieldToFilter('quote_id', $quoteId)
            ->addFieldToFilter('customer_id', $userId)//->load(true)
        ;

        return (count($quote) > 0) ? $quote : false;
    }

    /**
     * Quote proposal was rejected by client
     *
     * @return
     */
    public function rejectAction()
    {
        if ($quote = $this->_initQuote()) {

            $quoteId = (int)$this->getRequest()->getParam('id');

            if (!$quoteId) {
                return false;
            }

            if ($quoteData = $this->checkUserQuote($quoteId, $this->getCustomerId())) {
                foreach ($quoteData as $key => $item) {

                    $params = array(
                        'update_at' => NOW(),
                        'status' => Ophirah_Qquoteadv_Model_Status::STATUS_DENIED
                    );

                    Mage::getModel('qquoteadv/qqadvcustomer')->updateQuote($item->getId(), $params);
                }
            }


            //Mage::dispatchEvent('quote_proposal_controller_reject', array('quote'=>$quote));

            $resource = Mage::getSingleton('core/resource');
            $read = $resource->getConnection('core_read');
            $userTable = $resource->getTableName('quoteadv_customer');
            $select = $read->select()
                ->from($userTable)
                ->where('customer_id = ?', $this->getCustomerId())
                ->where('quote_id = ?', $quoteId);
            $user_info = $read->fetchRow($select);

            $realId = $quoteId;

            if (is_array($user_info) && !empty($user_info)) {
                $params['email'] = $user_info['email'];
                $params['firstname'] = $user_info['firstname'];
                $params['lastname'] = $user_info['lastname'];
                $params['quoteId'] = $quoteId;
                $params['customerId'] = $this->getCustomerId();
                $this->sendEmailReject($params);

                $realId = $user_info['increment_id'];
            }

            $this->getCoreSession()->addNotice($this->__('Quotation #%s was rejected', $realId));
            $this->_redirect('qquoteadv/view/view/id/' . $quoteId);
        } else {
            $this->_forward('noRoute');
        }
    }

    /**
     * Request proposal was accepted (confirmed) by client
     *
     */
    public function confirmAction()
    {
        $notice = '';
        $_helper = Mage::helper('cataloginventory');

        if ($quote = $this->_initQuote()) {

            $quoteId = (int)$this->getRequest()->getParam('id');
            $params = $this->getRequest()->getParams();
            // Load Quotation Data
            $_quote = Mage::getSingleton('qquoteadv/qqadvcustomer')->load($quoteId);
            $_quote->collectTotals();

            // Check for minimum Cart Amount
            $address = $_quote->getShippingAddress();
            $address->setAddressType('shipping');
            $minAmount = $address->validateMinimumAmount();

            if (!$minAmount && Mage::getStoreConfig('qquoteadv/general/quoteconfirmation') != "0") {
                $notice = Mage::getStoreConfig('sales/minimum_order/error_message') ?
                    Mage::getStoreConfig('sales/minimum_order/error_message') :
                    Mage::helper('checkout')->__('Subtotal must exceed minimum order amount');
                $this->getCoreSession()->addNotice($notice);
                Mage::helper('qquoteadv')->setActiveConfirmMode(false);
                $this->_redirect('qquoteadv/view/view/id/' . $quoteId);
                return $this;
            }

            if (!isset($params['requestQtyLine'])) {
                $params['requestQtyLine'] = $_quote->getAllRequestItemsForCart();
                $params['remove_item_id'] = '';

                if ($params['requestQtyLine'] === false) {
                    $this->_redirect('qquoteadv/view/view/id/' . $quoteId);
                    return $this;
                }
            }

            if ($quoteData = $this->checkUserQuote($quoteId, $this->getCustomerId())) {

                if (count($params['requestQtyLine']) > 0) {

                    //# Delete items from shopping cart before moving quote items to it
                    Mage::helper('qquoteadv')->setActiveConfirmMode(false); // disable first to clear the cart
                    $this->_clearShoppingCart();

                    // Check for Checkout Url
                    $useAltCheckout = false;
                    $altCheckoutUrl = false;
                    if (Mage::getStoreConfig('qquoteadv/quote_advanced/checkout_alternative_url')) {
                        $altCheckoutUrl = Mage::getStoreConfig('qquoteadv/quote_advanced/checkout_alternative_url');
                        $confAltCheckout = Mage::getStoreConfig('qquoteadv/quote_advanced/checkout_alternative', $_quote->getData('store_id'));
                        $useAltCheckout = ($confAltCheckout > 0 && $_quote->getData('alt_checkout') > 0) ? true : false;
                    }

                    if ($useAltCheckout === false):

                        // Add Salesrule
                        if ($_quote->getData('salesrule')) {
                            $couponCode = $_quote->getCouponCodeById($_quote->getData('salesrule'));
                        } else {
                            $couponCode = null;
                        }

                        //# Set QUOTE comfirmation mode to avoid manipulation with qty/price
                        Mage::helper('qquoteadv')->setActiveConfirmMode(true);
                        Mage::getSingleton('core/session')->proposal_quote_id = $quoteId;
                        //# Allow Quoteshiprate shipping method
                        Mage::getSingleton('core/session')->proposal_showquoteship = true;

                        Mage::app()->getStore()->setCurrentCurrencyCode($_quote->getCurrency());

                        foreach ($params['requestQtyLine'] as $keyProductReq => $requestId) {
                            $update = array();
                            $customPrice = 0;
                            $productId = null;

                            $x = Mage::getModel('qquoteadv/qqadvproduct')->load($keyProductReq);
                            $update['attributeEncode'] = unserialize($x->getData('attribute'));

                            $result = Mage::getModel('qquoteadv/requestitem')->getCollection()->setQuote($_quote)
                                ->addFieldToFilter('quoteadv_product_id', $keyProductReq)
                                ->addFieldToFilter('request_id', $requestId)
                                ->getData();

                            if ($item = $result[0]) {
                                $productId = $item['product_id'];
                                $product = Mage::getModel('catalog/product')->load($productId);

                                $update['attributeEncode']['qty'] = $item['request_qty'];

                                //# GET owner price
                                $customPrice = $item['owner_cur_price'];
                                $allowed2Ordermode = $product->getData('allowed_to_ordermode');

                                try {
                                    //# Trying to add item into cart
                                    if ($product->isSalable() or ($allowed2Ordermode == 0 && Mage::helper('qquoteadv')->isActiveConfirmMode(true))) {

                                        $maxSaleQty = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product)->getMaxSaleQty() * 1;
                                        if ($maxSaleQty > 0 && ($item['request_qty'] > $maxSaleQty)) {

                                            $notice = $_helper->__('The maximum quantity allowed for purchase is %s.', $maxSaleQty);
                                            $notice .= '<br />' . $_helper->__('Some of the products cannot be ordered in requested quantity.');

                                            continue;
                                        }

                                        if (Mage::helper('qquoteadv')->checkQuantities($product, $item['request_qty'])->getHasError() || Mage::helper('qquoteadv')->isInStock($product, $item['request_qty'])->getHasError()) {
                                            $notice = $_helper->__('Item %s is out of stock and cannot be ordered.', $product->getName());
                                            $this->getCoreSession()->addNotice($notice);
                                            return $this->_redirectReferer();
                                        }
                                        //# step1: register owner price for observer
                                        if (Mage::registry('customPrice')) {
                                            Mage::unregister('customPrice');
                                        }
                                        Mage::register('customPrice', $customPrice);

                                        //# step2: - add item to shopping cart
                                        //         - observer catch register owner price and set it for item adding for shopping cart

                                        // get Cart
                                        $cart = Mage::getModel('checkout/cart');

                                        $cart->addProduct($product, $update['attributeEncode'])
                                            ->setProposalQuoteId($quoteId);

                                        // Apply Coupon code to Cart
                                        if ($couponCode != null && !isset($couponCodeApplied)) {
                                            $cart->getQuote()->setCouponCode($couponCode);
                                            $couponCodeApplied = true;
                                        }

                                        //Setting Address Total Amounts in Cart Shipping address
                                        foreach ($cart->getQuote()->getAllAddresses() as $address) {
                                            // These Totals needs to be set to
                                            // check the minimal Checkout amount
                                            // See: Mage_Sales_Model_Quote::validateMinimumAmount()
                                            $updateAmounts = array('subtotal', 'discount');
                                            if ($address->getAddressType() == 'shipping') {
                                                foreach ($updateAmounts as $update) {
                                                    $address->setTotalAmount($update, $_quote->getAddress()->getTotalAmount($update));
                                                    $address->setBaseTotalAmount($update, $_quote->getAddress()->getBaseTotalAmount($update));
                                                }
                                            }
                                        }

                                        $cart->save();

                                        Mage::getSingleton('core/session')->setCartWasUpdated(true);
                                        Mage::dispatchEvent('checkout_cart_add_product_complete',
                                            array('product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse())
                                        );

                                        if (isset($customPrice)) {
                                            Mage::unregister('customPrice');
                                        }

                                    }
                                } catch (Mage_Core_Exception $e) {
                                    Mage::logException($e);
                                }
                            }
                        }

                        // Set Coupon Code message
                        if ($couponCode != null) {
                            if ($couponCode == $cart->getQuote()->getCouponCode()) {
                                $this->getCoreSession()->addSuccess($this->__('Coupon code "%s" is applied', $couponCode));
                            } else {
                                $this->getCoreSession()->addError($this->__('Coupon code "%s" could not be applied', $couponCode));
                            }
                        }

                    endif; // Check for Alternative Checkout URL

                    //# Set Quote status: STATUS_CONFIRMED
                    $data = array(
                        'updated_at' => NOW(),
                        'status' => Ophirah_Qquoteadv_Model_Status::STATUS_CONFIRMED
                    );


                    //# Disallow Quoteshiprate shipping method
                    Mage::getSingleton('core/session')->proposal_showquoteship = false;

                    Mage::getModel('qquoteadv/qqadvcustomer')->updateQuote($quoteId, $data)->save();
                    Mage::helper('qquoteadv')->sentAnonymousData('confirm', 'f', $quoteId);

                    if ($useAltCheckout === false && empty($notice)) {
                        Mage::getModel('qquoteadv/qqadvcustomer')->sendQuoteAccepted($quoteId);
                        $this->getCoreSession()->addSuccess($this->__('All items were moved to cart successfully.'));
                    } elseif ($useAltCheckout === false) {
                        $this->getCoreSession()->addNotice($notice);
                    }
                }

                // Redirect to checkout
                if (isset($altCheckoutUrl) && $useAltCheckout === true) {
                    $this->outqqconfirmmodeAction(false);
                    $this->_redirect($altCheckoutUrl);
                } elseif ($url = Mage::getStoreConfig('qquoteadv/general/checkout_url')) {
                    $this->_redirect($url);
                } else {
                    $this->_redirect('checkout/onepage/');
                }

            } else {
                $this->getCoreSession()->addNotice($this->__('Access denied!'));
                $this->_redirect('customer/account/');
                return;
            }
        } else {
            $this->_forward('noRoute');
        }
    }

    /**
     * 1. Set to Quote proposal status 'CANCELED'
     * 2. Create new quote  with clone current items from Quote proposal
     *
     */
    public function editAction()
    {

        if (Mage::helper('qquoteadv')->isActiveConfirmMode()) {
            $link = Mage::getUrl('qquoteadv/view/outqqconfirmmode');
            $message = Mage::helper('qquoteadv')->__("You are in a quote confirmation mode, <a href='%s'>log out</a>.", $link);
            $this->_redirectReferer(Mage::getUrl('*/*'));
        }


        if ($quote = $this->_initQuote()) {


            $quoteId = $this->getRequest()->getParam('id');

            if ($quoteData = $this->checkUserQuote($quoteId, $this->getCustomerId())) {

                //checking status to avoid huckers activity with quote
                if ($quoteData->getData('status') != Ophirah_Qquoteadv_Model_Status::STATUS_PROPOSAL) {

                    $this->_redirectReferer(Mage::getUrl('*/*'));
                }


                if ($this->isCustomerLoggedIn()) {
                    $data = array('created_at' => NOW(),
                        'updated_at' => NOW(),
                        'customer_id' => $this->getCustomerSession()->getId(),
                        'email' => Mage::getSingleton('customer/session')->getCustomer()->getEmail(),
                        'is_quote' => 1
                    );
                } else {
                    $data = array('created_at' => NOW(),
                        'updated_at' => NOW(),
                        'is_quote' => 1
                    );
                }

                $obj = Mage::getModel('qquoteadv/qqadvcustomer')->setData($data)->save();

                //# create new quote with clone items from current quote
                if ($_newQquoteid = $obj->getQuoteId()) {

                    $quoteProducts = Mage::getModel('qquoteadv/qqadvproduct')->getCollection()
                        //->setStoreId(Mage::app()->getStore()->getId())
                        ->addFieldToFilter('quote_id', $quoteId)//->load(true)
                    ;

                    $cloneProducts = array();
                    foreach ($quoteProducts as $key => $values) {

                        $cloneProduct = array(
                            'quote_id' => $_newQquoteid,
                            'product_id' => $values['product_id'],
                            'qty' => $values['qty'],
                            'attribute' => $values['attribute'],
                        );

                        Mage::getModel('qquoteadv/qqadvproduct')->setData($cloneProduct)->save();
                    }
                }

                //#set to current quote status 'CANCELED'
                foreach ($quoteData as $key => $item) {

                    $params = array(
                        'updated_at' => NOW(),
                        'status' => Ophirah_Qquoteadv_Model_Status::STATUS_CANCELED
                    );

                    Mage::getModel('qquoteadv/qqadvcustomer')->updateQuote($item->getId(), $params);
                }


                Mage::dispatchEvent('quote_proposal_controller_cancel', array('quote' => $quote));

                $resource = Mage::getSingleton('core/resource');
                $read = $resource->getConnection('core_read');
                $userTable = $resource->getTableName('quoteadv_customer');
                $select = $read->select()
                    ->from($userTable)
                    ->where('customer_id = ?', $this->getCustomerId())
                    ->where('quote_id = ?', $quoteId);
                $user_info = $read->fetchRow($select);

                if (is_array($user_info) && !empty($user_info)) {
                    $params['email'] = $user_info['email'];
                    $params['firstname'] = $user_info['firstname'];
                    $params['lastname'] = $user_info['lastname'];
                    $params['quoteId'] = $quoteId;
                    $params['customerId'] = $this->getCustomerId();
                    $this->sendEmailCancellation($params);
                }

                //#set new quote as current
                $this->getCustomerSession()->setQuoteadvId($_newQquoteid);

                $this->_redirect('qquoteadv/index/');
            } else {
                $this->getCoreSession()->addNotice($this->__('Access denied!'));
                $this->_redirect('customer/account/');
            }
        } else {
            $this->_forward('noRoute');
        }
    }

    /**
     * Update Totals in Block
     * When tier price is selected
     *
     */
    public function updateTotalsAction()
    {
        $request = $this->getRequest();
        if (is_array($updateInfo = $request->getPost('update_item'))) {
            try {
                $itemId = (int)$updateInfo['itemId'];
                $itemQty = (int)$updateInfo['itemQty'];

                /* @var Ophirah_Qquoteadv_Model_Qqadvproduct */
                Mage::getModel('qquoteadv/qqadvproduct')->updateProductQty($itemId, $itemQty);

            } catch (Exception $e) {
                Mage::log($e->getMessage());
            }
        }

        $this->_redirectReferer('*/*');
    }

    public function isCustomerLoggedIn()
    {
        return Mage::getSingleton('customer/session')->isLoggedIn();
    }

    public function getCustomerId()
    {
        return Mage::getSingleton('customer/session')->getCustomerId();
    }

    protected function _clearShoppingCart()
    {
        //Clear shopping cart
        foreach (Mage::getSingleton('checkout/session')->getQuote()->getItemsCollection() as $item) {
            Mage::getSingleton('checkout/cart')->removeItem($item->getId())->save();
        }

        //Clear entire session
        //Mage::getSingleton(�checkout/session�)->clear();
    }

    public function outqqconfirmmodeAction($notify = true)
    {
        Mage::helper('qquoteadv')->setActiveConfirmMode(false);
        $this->_clearShoppingCart();
        Mage::getModel('checkout/cart')->getQuote()->setProposalQuoteId(0)->save();

        if ($notify === true) {
            $message = $this->__("You log out successfully from Quote confirmation mode.");
            Mage::getSingleton('core/session')->addNotice($message);
        }

        $this->_redirect('checkout/cart');
    }

    /**
     * Send email to administrator informing about the quote cancellation
     * @param array $params customer address
     */
    public function sendEmailCancellation($params)
    {
        //Create an array of variables to assign to template
        $vars = array();

        $quoteId = $params['quoteId'];
        $customerId = $params['customerId'];
        //Vars into email templates
        $vars = array(
            'quote' => Mage::getModel('qquoteadv/qqadvcustomer')->load($quoteId),
            'customer' => Mage::getModel('customer/customer')->load($customerId),
            'quoteId' => $quoteId
        );

        /*
         * Loads the html file named 'qquote_request.html' from
         * app/locale/en_US/template/email/
         */

        $template = Mage::getModel('core/email_template');
        $disabledEmail = Ophirah_Qquoteadv_Model_System_Config_Source_Email_Templatedisable::VALUE_DISABLED_EMAIL;
        $quoteadv_param = Mage::getStoreConfig('qquoteadv/emails/proposal_cancel');
        if ($quoteadv_param != $disabledEmail):
            if ($quoteadv_param) {
                $templateId = $quoteadv_param;
            } else {
                $templateId = self::XML_PATH_QQUOTEADV_REQUEST_CANCEL_EMAIL_TEMPLATE;
            }

            if (is_numeric($templateId)) {
                $template->load($templateId);
            } else {
                $template->loadDefault($templateId);
            }

            $subject = $template['template_subject'];
            $sender = $vars['quote']->getEmailSenderInfo();

            $template->setSenderName(@$sender['name']);
            $template->setSenderEmail(@$sender['email']);
            $template->setTemplateSubject($subject);

            $bcc = Mage::getStoreConfig('qquoteadv/emails/bcc');
            if ($bcc) {
                $bccData = explode(";", $bcc);
                $template->addBcc($bccData);
            }
            /**
             * Opens the qquote_request.html, throws in the variable array
             * and returns the 'parsed' content that you can use as body of email
             */
            $processedTemplate = $template->getProcessedTemplate($vars);

            /*
             * getProcessedTemplate is called inside send()
             */
            $res = $template->send($params['email'], $params['firstname'] . " " . $params['lastname'], $vars);

            if (empty($res)) {
                $message = $this->__("Qquote cancel email was't sent to admin for quote #%s", $quoteId);
                Mage::log($message);
            }

        endif;
    }

    /**
     * Send email to administrator informing about the quote reject
     * @param array $params customer address
     */
    public function sendEmailReject($params)
    {
        //Create an array of variables to assign to template
        $vars = array();

        $quoteId = $params['quoteId'];
        $customerId = $params['customerId'];

        //Vars into email templates
        $vars = array(
            'quote' => Mage::getModel('qquoteadv/qqadvcustomer')->load($quoteId),
            'customer' => Mage::getModel('customer/customer')->load($customerId),
            'quoteId' => $quoteId
        );

        /*
         * Loads the html file named 'qquote_request.html' from
         * app/locale/en_US/template/email/
         */

        $template = Mage::getModel('core/email_template');
        $quoteadv_param = Mage::getStoreConfig('qquoteadv/emails/proposal_reject');
        $disabledEmail = Ophirah_Qquoteadv_Model_System_Config_Source_Email_Templatedisable::VALUE_DISABLED_EMAIL;
        if ($quoteadv_param != $disabledEmail):
            if ($quoteadv_param) {
                $templateId = $quoteadv_param;
            } else {
                $templateId = self::XML_PATH_QQUOTEADV_REQUEST_REJECT_EMAIL_TEMPLATE;
            }

            if (is_numeric($templateId)) {
                $template->load($templateId);
            } else {
                $template->loadDefault($templateId);
            }

            $subject = $template['template_subject'];
            $sender = $vars['quote']->getEmailSenderInfo();

            $template->setSenderName(@$sender['name']);
            $template->setSenderEmail(@$sender['email']);
            $template->setTemplateSubject($subject);

            $bcc = Mage::getStoreConfig('qquoteadv/emails/bcc');
            if ($bcc) {
                $bccData = explode(";", $bcc);
                $template->addBcc($bccData);
            }
            /**
             * Opens the qquote_request.html, throws in the variable array
             * and returns the 'parsed' content that you can use as body of email
             */
            $processedTemplate = $template->getProcessedTemplate($vars);

            /*
             * getProcessedTemplate is called inside send()
             */
            $res = $template->send($params['email'], $params['firstname'] . " " . $params['lastname'], $vars);

            if (empty($res)) {
                $message = $this->__("Qquote reject email was't sent to admin for quote #%s", $quoteId);
                Mage::log($message);
            }

        endif;

    }

    public function itemDeleteAction()
    {

        $quoteId = $this->getRequest()->getParam('id');
        $id = $this->getRequest()->getParam('remove_item_id');


        // get the unique item row id to delete
        if ($id && $quoteId) {

            if ($quoteData = $this->checkUserQuote($quoteId, $this->getCustomerId())) {

                $modelProduct = Mage::getModel('qquoteadv/qqadvproduct');

                try {
                    // delete the row from quote_product table
                    //$modelProduct->setId($id)->delete();
                    $modelProduct->deleteQuote($id);
                    $this->getCoreSession()->addSuccess($this->__('Item was deleted successfully.'));
                } catch (Exception $e) {
                    $this->getCoreSession()->addError($this->__("Can't remove item"));
                }
                $this->editAction();
            } else
                $this->_redirectReferer('*/*');
        } else
            $this->_redirectReferer('*/*');
    }

    /**
     * Action predispatch
     *
     * Check customer authentication for some actions
     */
    public function preDispatch()
    {
        parent::preDispatch();
        $action = $this->getRequest()->getActionName();
        $loginUrl = Mage::helper('customer')->getLoginUrl();

        if (!Mage::getSingleton('customer/session')->authenticate($this, $loginUrl)) {
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
        }
    }

    /**
     * Customer quoteadv history
     */
    public function historyAction()
    {
        $this->loadLayout();
        $this->_initLayoutMessages('catalog/session');

        $this->getLayout()->getBlock('head')->setTitle($this->__('My Quotes'));

        if ($block = $this->getLayout()->getBlock('customer.account.link.back')) {
            $block->setRefererUrl($this->_getRefererUrl());
        }
        $this->renderLayout();
    }

    public function printAction()
    {
        if (!$this->_loadValidQuote()) {
            return;
        }

        $this->loadLayout('print');
        $this->renderLayout();
    }

    /**
     * Try to load valid quote by id
     *
     * @param int $id
     * @return bool
     */
    protected function _loadValidQuote($id = null)
    {
        if (null === $id) {
            $id = (int)$this->getRequest()->getParam('id');
        }
        if (!$id) {
            $this->_forward('noRoute');
            return false;
        }

        if ($quote = $this->_initQuote()) {
            if ($quoteData = $this->checkUserQuote($id, $this->getCustomerId())) {
                return true;
            } else {
                $this->_redirect('*/*/history');
            }
        }
        return false;
    }

    public function pdfqquoteadvAction()
    {
        $quoteadvId = $this->getRequest()->getParam('id');
        $flag = false;
        if (!empty($quoteadvId)) {
            //foreach ($ids as $quoteadvId) {
            $_quoteadv = Mage::getModel('qquoteadv/qqadvcustomer')->getCollection()->addFieldToFilter('quote_id', $quoteadvId);


            $quoteItems = Mage::getModel('qquoteadv/qqadvproduct')->getCollection()
                ->addFieldToFilter('quote_id', $quoteadvId)
                ->load();

            if ($quoteItems->getSize()) {
                $flag = true;
                if (!isset($pdf)) {
                    $pdf = Mage::getModel('qquoteadv/pdf_qquote')->getPdf($_quoteadv);
                } else {
                    $pages = Mage::getModel('qquoteadv/pdf_qquote')->getPdf($quoteItems);
                    $pdf->pages = array_merge($pdf->pages, $pages->pages);
                }
            }
            //}
            if ($flag) {

                foreach ($_quoteadv as $item)
                    $realQuoteadvId = $item->getIncrementId() ? $item->getIncrementId() : $item->getId();

                $fileName = Mage::helper('qquoteadv')->__('Price_proposal_%s', $realQuoteadvId);

                return $this->_prepareDownloadResponse($fileName . '.pdf', $pdf->render(), 'application/pdf');
            } else {
                $this->_getSession()->addError($this->__('There are no printable documents related to selected quotes'));
                $this->_redirect('*/*/');
            }
        }
        $this->_redirect('*/*/');
    }

    /**
     * Declare headers and content file in responce for file download
     *
     * @param string $fileName
     * @param string $content set to null to avoid starting output, $contentLength should be set explicitly in that case
     * @param string $contentType
     * @param int $contentLength explicit content length, if strlen($content) isn't applicable
     * @return Mage_Adminhtml_Controller_Action
     */
    protected function _prepareDownloadResponse($fileName, $content, $contentType = 'application/octet-stream', $contentLength = null)
    {
        $session = Mage::getSingleton('admin/session');
        if ($session->isFirstPageAfterLogin()) {
            $this->_redirect($session->getUser()->getStartupPageUrl());
            return $this;
        }
        $this->getResponse()
            ->setHttpResponseCode(200)
            ->setHeader('Pragma', 'public', true)
            ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)
            ->setHeader('Content-type', $contentType, true)
            ->setHeader('Content-Length', is_null($contentLength) ? strlen($content) : $contentLength)
            ->setHeader('Content-Disposition', 'attachment; filename=' . $fileName)
            ->setHeader('Last-Modified', date('r'));
        if (!is_null($content)) {
            $this->getResponse()->setBody($content);
        }
        return $this;
    }
}
