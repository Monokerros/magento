<?php

class Ophirah_Qquoteadv_Block_Catalog_Product_View_Addtoquotebutton extends Mage_Catalog_Block_Product_View
{
    public function isHideAddToCartToButton()
    {
        return Mage::getConfig()->getModuleConfig('Ophirah_Not2Order')->is('active', 'true') && $this->getProduct()->getData('quotemode_conditions') > 0 ? Mage::helper('not2order')->autoHideCartButton(Mage::helper('qquoteadv')->hideQuoteButton($this->getProduct())) : false;
    }

    public function isHideAddToQuoteButton()
    {
        $isEnabled = Mage::helper('qquoteadv')->isEnabled() == 1 ? true : false;
        $isAllowedToQuote = $this->getProduct()->getData('allowed_to_quotemode') == 1 ? true : false;
        $isHideQuoteButton = Mage::helper('qquoteadv')->hideQuoteButton($this->getProduct());
        $isDetailPageActivated = Mage::getStoreConfig('qquoteadv/layout/layout_update_detailpage_activated') == 1 ? true : false;
        return !$isEnabled || !$isAllowedToQuote || $isHideQuoteButton || !$isDetailPageActivated;
    }

    public function getActionQuote()
    {
        $isAjax = Mage::getStoreConfig('qquoteadv/layout/ajax_add');
        $url = $this->helper('qquoteadv/catalog_product_data')->getUrlAdd2Qquoteadv($this->getProduct());
        $actionQuote = "addQuote('" . $url . "', $isAjax );";

        if (Mage::getStoreConfig('qquoteadv/quick_quote/quick_quote_mode') == "1") {
            // Set Quick Quote Action
            $actionQuote = "$('quickQuoteWrapper').show()";
        }

        return $actionQuote;
    }
}
