<?php

class Ophirah_Qquoteadv_Model_Quote_Total_C2qtotal extends Mage_Sales_Model_Quote_Address_Total_Abstract
{
    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        parent::collect($address);

        $items = $this->_getAddressItems($address);
        $quote = $address->getQuote();

        if ($quote->getData('quote_id')){

            // Get custom quote prices for the products by quoteId
            $quoteCustomPrices = Mage::getModel('qquoteadv/qqadvproduct')->getQuoteCustomPrices($quote->getData('quote_id'));

            $optionCount = 0;
            $optionId = 0;
            $countMax = 0;

            // Clear original price information
            $orgFinalBasePrice = 0;
            $orgBasePrice = 0;
            $quoteFinalBasePrice = 0;
            $quoteCostPrice = 0;
            $calcOrgPrice = true;

            // Only Calculate Original Prices Once
            if ($quote->getData('orgFinalBasePrice') > 0) {
                $calcOrgPrice = false;
            }

            Mage::register('requests_handeled', array()); //needed for dynamic bundles
            foreach ($items as $item) {
                if($item->getParentItem() == null){

                    // Counter for option products
                    if ($optionId != $item->getBuyRequest()->getData('product')) {
                        $countMax = Mage::getModel('qquoteadv/qqadvproduct')->getCountMax($item->getBuyRequest());
                    }
                    if ($optionCount == $countMax) {
                        $optionCount = $optionId = 0;
                    }
                    if ($optionId == $item->getBuyRequest()->getData('product') && $optionId != 0) {
                        $optionCount++;
                    }
                    $optionId = $item->getBuyRequest()->getData('product');

                    // Check if quote item has a custom price
                    $item = Mage::getModel('qquoteadv/qqadvproduct')->getCustomPriceCheck($quoteCustomPrices, $item, $optionCount);

                    // Reset Original Price
                    // And add new item original prices
                    $itemFinalPrice = 0;
                    $itemCostPrice = 0;
                    $itemBasePrice = 0;
                    if ($calcOrgPrice === true){
                        if (!$item->getData('parent_item_id')) {
                            if ($item->getProductType() == "bundle") {
                                if ($item->getData('quote_org_price') > 0 && $item->getProduct()->getQty() > 0) {
                                    // Item Original Price
                                    $itemFinalPrice = $item->getData('quote_org_price') * $item->getProduct()->getQty();
                                    // Item Base Price
                                    $itemBasePrice = $itemFinalPrice;
                                }
                            } else {
                                // Item Original Price
                                $itemProductFinalPrice = $item->getProduct()->getFinalPrice();
                                $itemProductPrice = $item->getProduct()->getPrice();
                                $itemProductQty = $item->getProduct()->getQty();

                                $itemFinalPrice = $itemProductFinalPrice * $itemProductQty;
                                //TODO test sub-total discount special price

                                if($itemProductPrice == 0){
                                    $itemBasePrice = $itemProductFinalPrice * $itemProductQty;
                                } else {
                                    $itemBasePrice = $itemProductPrice * $itemProductQty;
                                }
                                // Item Cost Price
                            }

                            // Store item cost price
                            $itemCostPrice = (float)$item->getData('quote_item_cost') * $item->getProduct()->getQty();
                            if ($itemCostPrice > 0) {
                                $quoteCostPrice += $itemCostPrice;
                            }
                            // Store item original price
                            $orgFinalBasePrice += $itemFinalPrice;
                            $orgBasePrice += $itemBasePrice;
                            // Store Original Total with quote
                            $quote->setData('orgFinalBasePrice', $orgFinalBasePrice);
                            $quote->setData('orgBasePrice', $orgBasePrice);
                            $quote->setData('quoteBaseCostPrice', $quoteCostPrice);
                        }
                    }

                    // set custom price, if available
                    if ($item->getData('custom_base_price') != NULL && $item->getData('custom_base_price') > 0) {

                        // New custom Price
                        $rowTotal = $item->getData('custom_base_price');
                        $baseRowTotal = $item->getData('custom_base_price');

                        // Store item custom price
                        $itemQuotePrice = $item->getData('custom_base_price') * $item->getProduct()->getQty();
                        $quoteFinalBasePrice += $itemQuotePrice;
                        $quote->setData('quoteFinalBasePrice', $quoteFinalBasePrice);

                        // remove original item price from subtotal
                        $address->setTotalAmount(
                            'subtotal', $address->getSubtotal() - $item->getRowTotal()
                        );
                        $address->setBaseTotalAmount(
                            'subtotal', $address->getBaseSubtotal() - $item->getBaseRowTotal()
                        );

                        // Set custom price for the product
                        $item->setPrice($rowTotal)
                            ->setBaseOriginalPrice($baseRowTotal)
                            ->calcRowTotal();

                    }
                    $item->setQtyToAdd(0);
                } else {
                    // object is a child
                }
            }
            Mage::unregister('requests_handeled'); //needed for dynamic bundles
        }

        return $this;
    }

}
