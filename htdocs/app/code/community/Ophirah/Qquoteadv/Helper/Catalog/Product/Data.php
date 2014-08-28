<?php

class Ophirah_Qquoteadv_Helper_Catalog_Product_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Retrieve url for add product to cart
     * Will return product view page URL if product has required options
     *
     * @param Mage_Catalog_Model_Product $product
     * @param array $additional
     * @return string
     */
    public function getUrlAdd2QquoteadvList(Mage_Catalog_Model_Product $product, $additional = array())
    {
        $quoteAdvUrlPath = 'qquoteadv/index';
        //check if there are no required options
        $hasRequiredOptions = false;
        $request = new Varien_Object(array('qty' => 1));
        $resultPrepare = $product->getTypeInstance(true)->prepareForCartAdvanced($request, $product, null);
        if (is_string($resultPrepare)) $hasRequiredOptions = true;

        if ($product->getTypeInstance(true)->hasRequiredOptions($product) || $hasRequiredOptions) {
            $url = $product->getProductUrl();
            $link = (strpos($url, '?') !== false) ? '&' : '?';
            return $url . $link . 'options=cart&c2qredirect=1';
        }
        $url = "addItem";
        if (Mage::getStoreConfig('qquoteadv/layout/ajax_add') && Mage::getStoreConfig('qquoteadv/quick_quote/quick_quote_mode') != "1") $url = "addItemAjax";
        return Mage::getUrl($quoteAdvUrlPath . '/' . $url . '/', array("product" => $product->getId()));
    }


    public function getUrlAdd2Qquoteadv(Mage_Catalog_Model_Product $product, $additional = array())
    {
        $quoteAdvUrlPath = 'qquoteadv/index';
        $url = "addItem";
        if (Mage::getStoreConfig('qquoteadv/layout/ajax_add') && Mage::getStoreConfig('qquoteadv/quick_quote/quick_quote_mode') != "1") $url = "addItemAjax";
        return Mage::getUrl($quoteAdvUrlPath . '/' . $url . '/', array("product" => $product->getId()));
    }


    public function compareBundles($product_id, $options1, $options2)
    {
        $product = Mage::getModel('catalog/product')->load($product_id);
        if ($product instanceof Mage_Catalog_Model_Product) {
            $product2 = clone $product;

            $product->getTypeInstance()->prepareForCartAdvanced(new Varien_Object(unserialize($options1)), $product);
            $product2->getTypeInstance()->prepareForCartAdvanced(new Varien_Object(unserialize($options2)), $product2);

            $identity1 = $product->getCustomOption('bundle_identity');
            $identity2 = $product2->getCustomOption('bundle_identity');

            if (($identity1->getValue()) == ($identity2->getValue())) {
                return true;
            }

        }

        return false;
    }

    public function compareConfigurable($product_id, $options1, $options2)
    {
        $product = Mage::getModel('catalog/product')->load($product_id);
        if ($product instanceof Mage_Catalog_Model_Product) {
            $product2 = clone $product;

            $product->getTypeInstance()->prepareForCartAdvanced(new Varien_Object(unserialize($options1)), $product);
            $product2->getTypeInstance()->prepareForCartAdvanced(new Varien_Object(unserialize($options2)), $product2);

            $identity1 = $product->getCustomOption('attributes');
            $identity2 = $product2->getCustomOption('attributes');

            if ($identity1 instanceof Mage_Catalog_Model_Product_Configuration_Item_Option &&
                $identity2 instanceof Mage_Catalog_Model_Product_Configuration_Item_Option
            ) {
                if ($identity1->getValue() == $identity2->getValue()) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Calculate new image sizes from original ratio
     *
     * @param Mage_Catalog_Helper_Image $image
     * @param null $width
     * @param null $height
     * @return array
     */
    public function getItemPictureDimensions(Mage_Catalog_Helper_Image $image, $width = null, $height = null)
    {
        // Define variables
        $return = array();
        $newRatio = null;

        // Original image size
        $orgWidth = (int)$image->getOriginalWidth();
        $orgHeight = (int)$image->getOriginalHeight();

        if ($orgWidth && $orgHeight) {
            // Calculate original ratio
            $originalRatio = $orgWidth / $orgHeight;

            $newWidth = $orgWidth;
            $newHeight = $orgHeight;

            // Width is largest size
            if ($originalRatio > 1) {
                if (!$width == null && (int)$width > 0) {
                    $newWidth = $width;
                    $newHeight = $width / $originalRatio;
                } elseif (!$height == null && (int)$height > 0) {
                    $newWidth = $height;
                    $newHeight = $height / $originalRatio;
                }
                // Height is largest size
            } else {
                if (!$height == null && (int)$height > 0) {
                    $newWidth = $height * $originalRatio;
                    $newHeight = $height;
                } elseif (!$width == null && (int)$width > 0) {
                    $newWidth = $width * $originalRatio;
                    $newHeight = $width;
                }
            }

            $return['width'] = (int)$newWidth;
            $return['height'] = (int)$newHeight;
        }

        return $return;

    }
}
