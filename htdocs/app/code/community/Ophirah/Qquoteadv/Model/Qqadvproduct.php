<?php

class Ophirah_Qquoteadv_Model_Qqadvproduct extends Mage_Core_Model_Abstract
{
    public $_default_thumb_size = 75;
    public $imageType = 'thumbnail';
    public $imgSize = null;

    public function _construct()
    {
        parent::_construct();
        $this->_init('qquoteadv/qqadvproduct');
    }

    /**
     * Delete product from quote
     * @param integer $id id
     */
    public function deleteQuote($id)
    {
        $this->setId($id)
            ->delete();
        return $this;
    }

    /**
     * Get product for the particular quote
     * @param integer $quoteId
     * @return object product information
     */
    public function getQuoteProduct($quoteId)
    {
        return $this->getCollection()
            ->addFieldToFilter('quote_id', $quoteId);
    }

    /**
     * Load product from database by productId
     * or pass product if instance of
     * Mage_Catalog_Model_Product
     *
     * @param Mage_Catalog_Model_Product $product
     * @return boolean|\Mage_Catalog_Model_Product
     */

    public function loadProduct($product)
    {
        // Get Product from Database
        if (is_string($product)) {
            $product = (int)$product;
        }

        if (is_int($product)) {
            return Mage::getModel('catalog/product')->load($product);
        } elseif ($product instanceof Mage_Catalog_Model_Product) {
            return $product;
        } else {
            $message = 'Could not determine product';
            Mage::log($message);
            return false;
        }
    }

    public function getQuoteItemCost($product, $quoteProductId)
    {
        // Get Product
        $quoteProduct = $this->loadProduct($product);

        if ($quoteProduct) {
            // Composite Product
            if ($quoteProduct->isComposite()) {
                $quoteChildren = $this->collectQuoteItemChildren($quoteProduct, $quoteProductId);
                $childCost = 0;
                foreach ($quoteChildren as $child) {
                    $qty = ($child->getData('quote_item_qty') > 0) ? $child->getData('quote_item_qty') : 1;
                    $childCost += $child->getCost() * $qty;
                }
                return $childCost;
            } else {
                return $quoteProduct->getCost();
            }
        }

        return false;
    }

    /**
     * Add children to Parent Item
     *
     * @param integer / Mage_Catalog_Model_Product              $product
     * @param integer / Ophirah_Qquoteadv_Model_Qqadvproduct    $quoteProductId
     * @return Mage_Catalog_Model_Product
     */

    public function getQuoteItemChildren($product, $quoteProductId)
    {
        // Get Product
        $quoteProduct = $this->loadProduct($product);

        if ($quoteProduct && $quoteProductId) {
            // Composite Product
            if ($quoteProduct->isComposite()) {
                $quoteChildProduct = $this->collectQuoteItemChildren($quoteProduct, $quoteProductId);
            }

            if (isset($quoteChildProduct) && count($quoteChildProduct) > 0) {
                $quoteProduct->setChildren($quoteChildProduct);
            }

            return $quoteProduct;
        }

        return false;
    }

    /**
     * Retrieve childitems for Parent Item
     *
     * @param integer / Mage_Catalog_Model_Product              $product
     * @param integer / Ophirah_Qquoteadv_Model_Qqadvproduct    $quoteProductId
     * @return Mage_Catalog_Model_Product
     */
    public function collectQuoteItemChildren($product, $quoteProductId)
    {
        $quoteChildProduct = array();

        // Get Product from Database
        $quoteProduct = $this->loadProduct($product);

        // Configurable Product
        if ($quoteProduct->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
            $childProduct = $this->getConfChildProduct($quoteProductId);
            /* REMARK:
             * Increment check method filters out items with
             * Parent_Item_Id set, so leave this unset for now
             */
//            $childProduct->setParentItemId($quoteItem);
            // Create link with parent Quote Item
            $childProduct->setParentQuoteItemId($quoteProductId);

            $quoteChildProduct[] = $childProduct;
        }

        // Bundle Product
        if ($quoteProduct->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
            $buyRequest = unserialize(Mage::getModel('qquoteadv/qqadvproduct')
                    ->load($quoteProductId)
                    ->getData('attribute')
            );

            $quoteChildProduct = $this->getBundleOptionProducts($quoteProduct, $buyRequest, $quoteProductId);
        }

        return $quoteChildProduct;
    }

    /**
     * Retrieve Custom Options from
     * Product or ProductId
     *
     * @param int|Mage_Catalog_Model_Product $product
     * @return \Varien_Object|boolean
     */
    public function getCustomOptionsArray($product)
    {
        if (is_int($product)) {
            $product = Mage::getModel('catalog/product')->load($productId);
        }

        if (is_object($product) && $product instanceof Mage_Catalog_Model_Product && $product->getOptions()):
            // collect Product Options
            $prodOptions = new Varien_Object();
            foreach ($product->getOptions() as $option) {
                $valuesArray = array();
                $optionTypeId = $option->getOptionId();
                $values = $option->getValuesCollection();
                if ($values) {
                    foreach ($values->getData() as $value) {
                        $valuesArray[$value['option_type_id']] = $value;
                    }
                }

                $prodOptions->setData($optionTypeId, $valuesArray);
            }
            return $prodOptions;

        endif;

        return false;
    }

    /**
     * Retrieve product image for Quote Product
     *
     * @param Ophirah_Qquoteadv_Model_Qqadvproduct $product
     * @param string / int $thumbsize
     * @return string product image url
     */
    public function getItemPicture($product = null, $thumbsize = null, $cache = true, $imageType = null)
    {

        // Make sure thumbnail size is an integer
        if (!$thumbsize == null && !is_int($thumbsize)) {
            $thumbsize = (int)$thumbsize;
        }

        // Load product if none is given
        if ($product == null) {
            $product = Mage::getModel('catalog/product')->load($this->getProductId());
        }

        // give thumbnail default size if none is given
        if ($thumbsize == null) {
            $thumbsize = $this->_default_thumb_size;
        }

        // Get right product to load image from
        if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
            $imageProduct = $this->getConfChildProduct($this->getId());
        } else {
            $imageProduct = $product;
        }

        // Load product image
        if ($imageType == null) {
            $imageType = $this->imageType;
        }

        if ($cache === true) {
            $image = Mage::helper('catalog/image')->init($imageProduct, $imageType);

            // Resize image if needed
            if (!$thumbsize === false) {
                $image->resize($thumbsize);
            }
        } else {
            //GET NON-CHACHED IMAGE URL
            $image =Mage::getModel('catalog/product_media_config')->getMediaUrl($imageProduct->getImage());
            //CREATE WRONG URL
            //$image = Mage::getBaseUrl() . 'media/catalog/product' . $imageProduct->getData($imageType);

            // Resize image if needed
            if (!$thumbsize === false) {

                // get picture dimensions
                $cacheImage = Mage::helper('catalog/image')->init($imageProduct, $imageType);
                $newDim = Mage::helper('qquoteadv/catalog_product_data')->getItemPictureDimensions($cacheImage, $thumbsize);
                $this->imgSize = new Varien_Object();
                $this->imgSize->setData($newDim);
            }

        }

        return (string)$image;
    }

    /**
     *  For configurable products,
     *  get configured simple product
     * @param integer $productQuoteId
     * @return childproductId
     */
    public function getConfChildProduct($productQuoteId)
    {
        $quote_prod = unserialize(Mage::getModel('qquoteadv/qqadvproduct')
                ->load($productQuoteId)
                ->getData('attribute')
        );

        $product = Mage::getModel('catalog/product')->load($quote_prod['product']);
        $childProduct = Mage::getModel('catalog/product_type_configurable')
            ->getProductByAttributes($quote_prod['super_attribute'], $product);

        return Mage::getModel('catalog/product')->load($childProduct->getId());
    }

    /**
     *  For bundeld products,
     *  get bundle child products
     * @param integer $productQuoteId
     * @return childproductIds
     */
    public function getBundleChildProduct($productQuoteId)
    {
        $quote_prod = unserialize(Mage::getModel('qquoteadv/qqadvproduct')
                ->load($productQuoteId)
                ->getData('attribute')
        );
        $product = Mage::getModel('catalog/product')->load($quote_prod['product']);
        $childProductArray = $product->getTypeInstance(true)->getChildrenIds($product->getId(), false);

        return $childProductArray;
    }

    /*
     * @param   object      // Mage_Catalog_Model_Product
     * @param   array       // Buy Request Bundle Parent Item
     * @param   integer     // Quote Product Id
     * @return  object      // Mage_Catalog_Model_Product
     */
    public function getBundleOptionProducts($product, $buyRequest, $quoteProductId = NULL)
    {
        $bundleOptions = Mage::getModel('qquoteadv/bundle')->getBundleOptionsSelection($product, $buyRequest);
        foreach ($bundleOptions as $option) {
            foreach ($option['value'] as $optionItem) {
                $childId = $optionItem['id'];
                $qty = $optionItem['qty'];
                $childProd = Mage::getModel('catalog/product')->load($childId);
                /* REMARK:
                 * Increment check method filters out items with
                 * Parent_Item_Id set, so leave this unset for now
                 */
//                $childProduct->setParentItemId($quoteItem);
                // Create link with parent Quote Item
                if ($quoteProductId != NULL) {
                    $childProd->setParentQuoteItemId($quoteProductId);
                }
                $childProd->setQuoteItemQty($qty);
                $quoteChildProduct[] = $childProd;

            }
        }

        return $quoteChildProduct;
    }

    /**
     * Add product for the particular quote to qquote_product table
     * @param array $params product information to be added
     *
     */
    public function addProduct($params)
    {

        $checkQty = $this->checkQuantities($params['product_id'], $params['qty']);
        if ($checkQty->getHasError()) {
            return $checkQty;
        }

        $this->setData($params)
            ->save();

        return $this;
    }

    /**
     * Update product if the product is already added to the table by the customer for the particular session
     * @param integer $id row id to be updated
     * @param array $params array of field(s) to be updated
     */
    public function updateProduct($id, $params)
    {
        $pid = $this->load($id)->getData('product_id');

        $checkQty = $this->checkQuantities($pid, $params['qty']);
        if ($checkQty->getHasError()) {
            return $checkQty;
        }


        $this->addData($params)
            ->setId($id)
            ->save();

        return $this;
    }

    public function updateQuoteProduct($params)
    {
        foreach ($params as $key => $arr) {
            $item = Mage::getModel('qquoteadv/qqadvproduct')->load($arr['id']);
            try {
                $item->setQty($arr['qty']);
                if ($arr['client_request']) {
                    $item->setClientRequest($arr['client_request']);
                }
                if (array_key_exists('attribute', $arr)) {
                    $item->setAttribute($arr['attribute']);
                }
                $item->save();
            } catch (Exception $e) {

            }
        }
        return $this;
    }

    /**
     * Update Product Qty
     * Used for tier selection
     *
     * @param int $itemId
     * @param int $itemQty
     */
    public function updateProductQty($itemId, $itemQty)
    {
        if (!(int)$itemId && !(int)$itemQty) {
            return false;
        }
        $item = Mage::getModel('qquoteadv/qqadvproduct')->load($itemId);
        if ($item && $itemQty > 0) {
            try {
                $attribute = unserialize($item->getAttribute());
                $attribute['qty'] = (string)$itemQty;
                $item->setAttribute(serialize($attribute));
                $item->setQty((string)$itemQty);
                $item->save();
            } catch (Exception $e) {
                Mage::log($e->getMessage());
                return false;
            }
            return true;
        }
        return false;
    }

    public function getIdsByQuoteId($quoteId)
    {
        $ids = array();
        $collection = Mage::getModel('qquoteadv/qqadvproduct')->getCollection()
            ->addFieldToFilter('quote_id', $quoteId);

        foreach ($collection as $item) {
            $ids[] = $item->getId();
        }

        return $ids;
    }

    public function checkQuantities($id, $qty)
    {
        return Mage::helper('qquoteadv')->checkQuantities($id, $qty);
    }


    public function checkQtyIncrements($id, $qty)
    {
        return Mage::helper('qquoteadv')->checkQtyIncrements($id, $qty);
    }


    /*
     * Create Array with quoted products and custom prices
     *
     * @param   $quoteId -> Quote Id
     * @return  Array with products and custom prices
     */
    public function getQuoteCustomPrices($quoteId)
    {

        // Get Custom Quote product price data from database
        $quoteItems = Mage::getModel('qquoteadv/requestitem')->getCollection()
            ->addFieldToFilter('quote_id', $quoteId);

        // Create Array with custom quote prices, per tier
        $quoteProductPrices = array();
        foreach ($quoteItems as $quoteItem) {
            $quoteProductPrices[$quoteItem->getData('quoteadv_product_id')][$quoteItem->getData('request_qty')] = $quoteItem->getData();
        }


        // Get Custom Quote product data from database
        $quoteProducts = Mage::getModel('qquoteadv/qqadvproduct')->getCollection()
            ->addFieldToFilter('quote_id', $quoteId);

        foreach ($quoteProducts as $quoteProduct) {

            // Get Attribute from product
            $attribute = unserialize($quoteProduct->getData('attribute'));

            // If product is configurable, super_attribute is set
            if (isset($attribute['super_attribute'])) {
                $childProd = $this->getConfChildProduct($quoteProduct->getData('id'));

                $childInfoArray = array('entity_id', 'sku', 'allowed_to_quotemode');

                foreach ($childInfoArray as $prodData) {
                    $childInfo[$prodData] = $childProd->getData($prodData);
                }

                $quoteProduct->setData('child_item', $childInfo);
            }

            // If product is bundle, bundle_option is set
            if (isset($attribute['bundle_option'])) {
                // Get childproduct Id's
                $childProdIds = $this->getBundleChildProduct($quoteProduct->getData('id'));

                // get original bundle price
                $bundlePrice = Mage::getModel('catalog/product')->load($quoteProduct->getData('product_id'))->getPrice();

                //init vars
                $bundleInfo = array();
                $childPricesArray = array();
                $childCostsArray = array();

                $prodPrices = array();
                $prodCosts = array();

                if($bundlePrice == 0){
                    //Item is bundle with dynamic pricing

                    // create array with child id's and original child price and cost
                    foreach ($attribute['bundle_option'] as $key => $bindeOptionId) {
                        $childId = Mage::getModel('bundle/selection')->load($bindeOptionId)->getData('product_id');
                        $prod = Mage::getModel('catalog/product')->load($childId);

                        //if qty is more than one, check for tier pricings
                        if($attribute['bundle_option_qty'][$key] > 1){
                            $tierPirces = $prod->getTierPrice();
                            if(isset($tierPirces) && !empty($tierPirces)){
                                //select the corect tier price from the array
                                foreach ($tierPirces as $tierPirce) {
                                    if($tierPirce['price_qty'] == $attribute['bundle_option_qty'][$key]){
                                        $prodPrice = $tierPirce['price'] * $attribute['bundle_option_qty'][$key];
                                        break;
                                    } else {
                                        $prodPrice = $prod->getPrice() * $attribute['bundle_option_qty'][$key];
                                    }
                                }
                            } else {
                                $prodPrice = $prod->getPrice() * $attribute['bundle_option_qty'][$key];
                            }
                            $prodCost = $prod->getCost() * $attribute['bundle_option_qty'][$key];
                        } else {
                            //don't check for tier pricing
                            $prodPrice = $prod->getPrice();
                            $prodCost = $prod->getCost();
                        }

                        //fallback to product price if product cost is not available.
                        if($prodCost == null){
                            $prodCost = $prodPrice;
                        }
                        $prodPrices[$childId] = $prodPrice;
                        $prodCosts[$childId] = $prodCost;
                    }
                    $childPricesArray[$quoteProduct->getData('id')] = $prodPrices;
                    $childCostsArray[$quoteProduct->getData('id')] = $prodCosts;

                } else {
                    //Item is bundle with fixed pricing

                    // create array with child id's and original child price and cost
                    foreach ($childProdIds as $childProdId) {
                        $prodPrices = array();
                        $prodCosts = array();

                        foreach ($childProdId as $childId) {
                            $prod = Mage::getModel('catalog/product')->load($childId);
                            $prodPrice = $prod->getPrice();
                            $prodCost = $prod->getCost();
                            $prodPrices[$childId] = $prodPrice;
                            $prodCosts[$childId] = $prodCost;
                        }
                        $childPricesArray[$quoteProduct->getData('id')] = $prodPrices;
                        $childCostsArray[$quoteProduct->getData('id')] = $prodCosts;
                    }
                }

                $bundleInfo['bundle_orgprice'] = $bundlePrice;
                $bundleInfo['child_orgprices'] = $childPricesArray;
                $bundleInfo['child_costs'] = $childCostsArray;
                // set info in object
                $quoteProduct->setData('bundle_info', $bundleInfo);
            }

            // set custom price
            $customBasePrice = array();
            $customCurPrice = array();

            if(!empty($quoteProductPrices)) {
                $quoteProductPricesProductIds = $quoteProductPrices[$quoteProduct->getData('id')];

                foreach ($quoteProductPricesProductIds as $key => $value) {
                    $customBasePrice[$key] = $value['owner_base_price'];
                    $customCurPrice[$key] = $value['owner_cur_price'];
                }
            }

            $quoteProduct->setData('custom_base_price', $customBasePrice);
            $quoteProduct->setData('custom_cur_price', $customCurPrice);
        }

        return $quoteProducts;
    }

    /*
     * Set custom prices to item object
     *
     * @param   $quoteCustomPrices  -> Array with custom prices
     * @param   $quoteId            -> Quote Item
     * @param   $optionCount        -> Counter for current product option number
     * @return  Quote item object with custom prices
     */

    public function getCustomPriceCheck($quoteCustomPrices, $item, $optionCount = null)
    {

        // Get product id the current item belongs to
        if ($item->getBuyRequest()->getData('product')) {
            $buyRequest = $item->getBuyRequest();
            $product_id = $buyRequest->getData('product');
        } else {
            $product_id = null;
        }

        // Check if current item has a custom price.
        foreach ($quoteCustomPrices as $requestId => $quoteCustomPrice) {

            $attribute = unserialize($quoteCustomPrice->getData('attribute'));

            // Basic Compare
            $compareQuote = $quoteCustomPrice->getData('product_id');
            $compareItem = $item->getData('product_id');

            // For products with options and parent-child relations
            // Dynamic bundle options can have different object with the same product_id
            if (isset($product_id) && $product_id == $quoteCustomPrice->getData('product_id')) {

                // Item Costprice
                $itemCost = $this->getQuoteItemCost($item->getProduct(), $quoteCustomPrice->getData('id'));

                // Custom Options
                if (isset($buyRequest['options'])) {
                    if ($item->getData('product_type') == Mage_Catalog_Model_Product_Type::TYPE_SIMPLE) {
                        $compareQuote = $attribute['options'];
                        $compareItem = $buyRequest['options'];
                    }
                }


                // Configurable products
                if (isset($buyRequest['super_attribute'])) {
                    if ($item->getData('product_type') == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
                        $compareQuote = $attribute['super_attribute'];
                        $compareItem = $buyRequest['super_attribute'];
                    }
                }

                // Bundled Products
                if (isset($buyRequest['bundle_option'])) {

                    if ($item->getData('product_type') == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {

                        $quoteCustomPrice->getData('bundle_info') ? $bundleInfo = $quoteCustomPrice->getData('bundle_info') : '';

                        $requestHandeled = Mage::registry('requests_handeled');
                        //if there is no bundle info, or this is the wrong bundle, continue.
                        if($bundleInfo == '' || !isset($bundleInfo['child_orgprices'][$requestId]) || in_array($requestId, $requestHandeled)){
                            continue;
                        } else {
                            $requestHandeled[] = $requestId;
                            Mage::unregister('requests_handeled');
                            Mage::register('requests_handeled', $requestHandeled);
                        }

// The code for fixed bundle is commented out, because a fixed bundle whit multiple options doen't get added the way it
// is suposed to be, therefor te commented out code is untested.
//                        if ($bundleInfo['bundle_orgprice'] == 0) {

                        /*
                             * Item is bundle with dynamic pricing
                             */
                        $price = 0;
                        if (isset($bundleInfo['child_orgprices'])) {
                            foreach ($bundleInfo['child_orgprices'] as $child_prices) {
                                if(isset($child_prices) && !empty($child_prices) && is_array($child_prices)){
                                    foreach ($child_prices as $id => $child_price) {
                                        $price += $child_price;
                                    }
                                }
                            }
                        }

                        $cost = 0;
                        if (isset($bundleInfo['child_costs'])) {
                            foreach ($bundleInfo['child_costs'] as $child_costs) {
                                if(isset($child_costs) && !empty($child_costs) && is_array($child_costs)){
                                    foreach ($child_costs as $id => $child_cost) {
                                        $cost += $child_cost;
                                    }
                                }
                            }
                        }

                        $item->setData('quote_org_price', $price);
                        $item->setData('quote_item_cost', $cost);

                        $customBasePrice = $quoteCustomPrice->getData('custom_base_price');
                        $customCurPrice = $quoteCustomPrice->getData('custom_cur_price');

                        $item->setData('qty', $quoteCustomPrice->getData('qty'));
                        $item->setData('custom_base_price', $customBasePrice[$quoteCustomPrice->getData('qty')]);
                        $item->setData('custom_cur_price', $customCurPrice[$quoteCustomPrice->getData('qty')]);

                        return $item;

//                        } else {
//
//                            /*
//                             * For bundle products with fixed prices
//                             * the prices are allready set for the bundle parent item.
//                             */
//
//                            if ($quoteCustomPrice->getData('bundle_info')) {
//                                $bundleInfo = $quoteCustomPrice->getData('bundle_info');
//                                $childOrgPrices = $bundleInfo['child_orgprices'];
//
//                                $customBasePrice = $quoteCustomPrice->getData('custom_base_price');
//                                $customCurPrice = $quoteCustomPrice->getData('custom_cur_price');
//                                $divide = count($childOrgPrices) * $item->getData('qty');
//
//                                $childCustomBasePrice = $customBasePrice[$quoteCustomPrice->getData('qty')] / $divide;
//                                $childCustomCurPrice = $customCurPrice[$quoteCustomPrice->getData('qty')] / $divide;
//
//                                $item->setData('custom_base_price', $childCustomBasePrice);
//                                $item->setData('custom_cur_price', $childCustomCurPrice);
//                                $item->setData('quote_item_cost', $itemCost);
//
//                            }
//
//                            return $item;
//                        }

                        //these lines are never reatched
                        //$compareQuote = $attribute['bundle_option'];
                        //$compareItem = $buyRequest['bundle_option'];

                    }
                }


                // Grouped products
                if (isset($buyRequest['super_product_config']) || isset($attribute['super_product_config'])) {
                    $compareQuote = !empty($attribute['super_product_config']) ? $attribute['super_product_config'] : $compareQuote;
                    $compareItem = !empty($buyRequest['super_product_config']) ? $buyRequest['super_product_config'] : $buyRequest;
                }

            }

            if ($compareQuote == $compareItem) {

                $customBasePrice = $quoteCustomPrice->getData('custom_base_price');
                $customCurPrice = $quoteCustomPrice->getData('custom_cur_price');

                $item->setData('qty', $quoteCustomPrice->getData('qty'));
                $item->setData('custom_base_price', $customBasePrice[$quoteCustomPrice->getData('qty')]);
                $item->setData('custom_cur_price', $customCurPrice[$quoteCustomPrice->getData('qty')]);
                $item->setData('quote_item_cost', $itemCost);
                //$itemCost       = $this->getQuoteItemCost($item->getProduct(), $quoteCustomPrice->getData('id'));
                //$item->setData('quote_item_cost', $customCurPrice[$quoteCustomPrice->getData('qty')]);
            }

        }

        return $item;
    }


    /*
     * Gets the amount of selected options
     *
     * @params  $buyRequest -> the products buy request
     * @return  $return     -> number of selected options
     */
    public function getCountMax($buyRequest)
    {

        $return = 0;

        // array of possible options in buyRequest
        $optionAttributes = array("options", "super_attribute", "bundle_option");

        foreach ($optionAttributes as $optionAttribute) {
            if ($buyRequest->getData($optionAttribute)) {
                $return = count($buyRequest->getData($optionAttribute));
            }
        }

        return $return;

    }
	
	public function getProductsByQuoteId($quoteId)
	{
		$collection = Mage::getModel('qquoteadv/qqadvproduct')->getCollection();
		$collection->addFieldToSelect('*');
		$collection->addFieldToFilter('quote_id',$quoteId);
        $collection->load();
		
		return $collection;
	}
}
