<?php

/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Qquoteadv
 * @package     Mage_Qquoteadv
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Ophirah_Qquoteadv_Block_Totals extends Mage_Core_Block_Template
{
    /**
     * Associated array of totals
     * array(
     *  $totalCode => $totalObject
     * )
     *
     * @var array
     */
    protected $_totals;
    protected $_quote = null;
    protected $_defaultRenderer = 'checkout/total_default';

    /**
     * Initialize self totals and children blocks totals before html building
     *
     * @return Mage_Sales_Block_Order_Totals
     */
    protected function _beforeToHtml()
    {
        $this->_initTotals();
        foreach ($this->getChild() as $child) {
            if (method_exists($child, 'initTotals')) {
                $child->initTotals();
            }
        }
        return parent::_beforeToHtml();
    }

    /**
     * Get order object
     *
     * @return Mage_Sales_Model_Order
     */
    public function getQuote()
    {
        if ($this->_quote === null) {
            if ($this->hasData('quote')) {
                $this->_quote = $this->_getData('quote');
            } elseif (Mage::registry('current_quote')) {
                $this->_quote = Mage::registry('current_quote');
            }
        }
        return $this->_quote;
    }

    public function setOrder($order)
    {
        $this->_quote = $order;
        return $this;
    }

    /**
     * Get totals source object
     *
     * @return Mage_Sales_Model_Order
     */
    public function getSource()
    {
        return $this->getQuote();
    }

    protected function _initDiscount()
    {
        return $this;
    }

    /**
     * Initialize order totals array
     *
     * @return Mage_Sales_Block_Order_Totals
     */
    protected function _initTotals()
    {
        $source = $this->getSource();
        $source->collectTotals();
        $totals = $source->getAddress();

        // Get Sort Order
        $sortOrder = array('subtotal' => Mage::getStoreConfig('sales/totals_sort/subtotal', $this->getQuote()->getStoreId()),
            'discount' => Mage::getStoreConfig('sales/totals_sort/discount', $this->getQuote()->getStoreId()),
            'shipping' => Mage::getStoreConfig('sales/totals_sort/shipping', $this->getQuote()->getStoreId()),
            'tax' => Mage::getStoreConfig('sales/totals_sort/tax', $this->getQuote()->getStoreId()),
            'grandtotal' => Mage::getStoreConfig('sales/totals_sort/grand_total', $this->getQuote()->getStoreId())
        );

        $totalsOrder = array();
        // Ordering Totals
        foreach ($sortOrder as $k => $v) {
            if (!key_exists($v, $totalsOrder)) {
                $totalsOrder[$v] = $k;
            } else {
                $totalsOrder[] = $k;
            }
        }

        // Sorting Array
        ksort($totalsOrder);

        // Adding Totals in Order
        $this->_totals = array();

        // Add Quote Reduction Totals
        if (Mage::getStoreConfig('qquoteadv/general/adjustment', $this->getQuote()->getStoreId()) == 1) {
            if (Mage::getStoreConfig('tax/calculation/price_includes_tax', $store = NULL) == 1) {
                $taxLabel = $this->__('Incl. default Tax');
                $label = $this->__('Adjustment Quote (%s)', $taxLabel);
            } else {
                $label = $this->__('Adjustment Quote');
            }

            $this->_totals['quote_reduction'] = new Varien_Object(array(
                'code' => 'quote_reduction',
                'area' => 'body',
                'value' => -1 * $this->getQuote()->getQuoteReduction(),
                'label' => $label
            ));
        }

        // Add other totals
        foreach ($totalsOrder as $order):
            switch ($order) {
                case 'subtotal':
                    $this->_addSubTotal($totals);
                    break;
                case 'discount':
                    $this->_addDiscount($totals);
                    break;
                case 'shipping':
                    if (!$source->getIsVirtual() && ((float)$totals->getShippingAmount() || $totals->getShippingDescription())) {
                        $this->_addShipping($totals);
                    }
                    break;
                case 'tax':
                    if (Mage::getStoreConfig('tax/cart_display/grandtotal', $this->getQuote()->getStoreId()) == 0) {
                        $this->_addTax($totals);
                    }
                    break;
                case 'grandtotal':
                    $this->_addGrandTotal($totals, $source);
                    break;

            }
        endforeach;

        return $this;
    }

    /**
     *  Add Subtotal to Totals
     *
     * @param Ophirah_Qquoteadv_Model_Address $totals
     */
    public function _addSubTotal($totals)
    {
        if (Mage::getStoreConfig('tax/cart_display/subtotal', $this->getQuote()->getStoreId()) == 1):
            $this->_totals['subtotal'] = new Varien_Object(array(
                'code' => 'subtotal',
                'area' => 'body',
                'value' => $totals->getSubtotal(),
                'label' => $this->__('Subtotal')
            ));
        elseif (Mage::getStoreConfig('tax/cart_display/subtotal', $this->getQuote()->getStoreId()) == 2):
            $this->_totals['subtotal_incl'] = new Varien_Object(array(
                'code' => 'subtotal',
                'area' => 'body',
                'value' => $totals->getSubtotalInclTax(),
                'label' => $this->__('Subtotal (incl. tax)')
            ));
        elseif (Mage::getStoreConfig('tax/cart_display/subtotal', $this->getQuote()->getStoreId()) == 3):
            $this->_totals['subtotal_excl'] = new Varien_Object(array(
                'code' => 'subtotal',
                'area' => 'body',
                'value' => $totals->getSubtotal(),
                'label' => $this->__('Subtotal (excl. Tax)')
            ));
            $this->_totals['subtotal_incl'] = new Varien_Object(array(
                'code' => 'subtotal',
                'area' => 'body',
                'value' => $totals->getSubtotalInclTax(),
                'label' => $this->__('Subtotal (incl. tax)')
            ));
        endif;
    }

    /**
     * Add Shipping to Totals
     *
     * @param Ophirah_Qquoteadv_Model_Address $totals
     */
    protected function _addShipping($totals)
    {
        $this->_totals['shipping'] = new Varien_Object(array(
            'code' => 'shipping',
            'area' => 'body',
            'field' => 'shipping_amount',
            'value' => $totals->getShippingAmount(),
            'label' => $this->__('Shipping & Handling')
        ));
    }

    /**
     * Add Discount to Totals
     *
     * @param Ophirah_Qquoteadv_Model_Address $totals
     */
    protected function _addDiscount($totals)
    {
        if ($totals->getDiscountAmount() != 0) {
            if ($totals->getDiscountDescription()) {
                $discountLabel = $this->__('Discount (%s)', $totals->getDiscountDescription());
            } else {
                $discountLabel = $this->__('Discount');
            }
            $this->_totals['discount'] = new Varien_Object(array(
                'code' => 'discount',
                'field' => 'discount_amount',
                'area' => 'body',
                'value' => $totals->getDiscountAmount(),
                'label' => $discountLabel
            ));
        }

    }

    /**
     *  Add Tax to Totals
     *
     * @param Ophirah_Qquoteadv_Model_Address $totals
     */
    protected function _addTax($totals)
    {
        $this->_totals['tax'] = new Varien_Object(array(
            'code' => 'tax',
            'field' => 'tax',
            'area' => 'footer',
            'value' => $totals->getTaxAmount(),
            'label' => $this->__('Tax')
        ));
    }

    /**
     *  Add GrandTotal to Totals
     *
     * @param Ophirah_Qquoteadv_Model_Address $totals
     * @param Ophirah_Qquoteadv_Model_QquoteadvCustomer $source
     */
    protected function _addGrandTotal($totals, $source)
    {
        if (Mage::getStoreConfig('tax/cart_display/grandtotal', $this->getQuote()->getStoreId()) == 0):
            $this->_totals['grand_total'] = new Varien_Object(array(
                'code' => 'grand_total',
                'field' => 'grand_total',
                'strong' => true,
                'area' => 'footer',
                'value' => $totals->getGrandTotal(),
                'label' => $this->__('Grand Total')
            ));
        elseif (Mage::getStoreConfig('tax/cart_display/grandtotal', $this->getQuote()->getStoreId()) == 1):
            $this->_totals['grand_total_excl'] = new Varien_Object(array(
                'code' => 'grand_total',
                'field' => 'grand_total',
                'strong' => true,
                'area' => 'footer',
                'value' => ($totals->getGrandTotal() - $totals->getTaxAmount()),
                'label' => $this->__('Grand Total (excl. Tax)')
            ));
            $this->_totals['tax'] = new Varien_Object(array(
                'code' => 'tax',
                'field' => 'tax',
                'area' => 'footer',
                'value' => $totals->getTaxAmount(),
                'label' => $this->__('Tax')
            ));
            $this->_totals['grand_total_incl'] = new Varien_Object(array(
                'code' => 'grand_total',
                'field' => 'grand_total',
                'strong' => true,
                'area' => 'footer',
                'value' => $totals->getGrandTotal(),
                'label' => $this->__('Grand Total (incl. Tax)')
            ));
        endif;

        /**
         * Base grandtotal
         */
        if ($this->getQuote()->isCurrencyDifferent()) {
            $this->_totals['base_grandtotal'] = new Varien_Object(array(
                'code' => 'base_grandtotal',
                'area' => 'footer',
                'value' => $source->formatBasePrice($totals->getBaseGrandTotal()),
                'label' => $this->__('Grand Total to be Charged'),
                'is_formated' => true,
            ));
        }

    }


    /**
     * Add new total to totals array after specific total or before last total by default
     *
     * @param   Varien_Object $total
     * @param   null|string|last|first $after
     * @return  Mage_Sales_Block_Order_Totals
     */
    public function addTotal(Varien_Object $total, $after = null)
    {
        if ($after !== null && $after != 'last' && $after != 'first') {
            $totals = array();
            $added = false;
            foreach ($this->_totals as $code => $item) {
                $totals[$code] = $item;
                if ($code == $after) {
                    $added = true;
                    $totals[$total->getCode()] = $total;
                }
            }
            if (!$added) {
                $last = array_pop($totals);
                $totals[$total->getCode()] = $total;
                $totals[$last->getCode()] = $last;
            }
            $this->_totals = $totals;
        } elseif ($after == 'last') {
            $this->_totals[$total->getCode()] = $total;
        } elseif ($after == 'first') {
            $totals = array($total->getCode() => $total);
            $this->_totals = array_merge($totals, $this->_totals);
        } else {
            $last = array_pop($this->_totals);
            $this->_totals[$total->getCode()] = $total;
            $this->_totals[$last->getCode()] = $last;
        }
        return $this;
    }

    /**
     * Add new total to totals array before specific total or after first total by default
     *
     * @param   Varien_Object $total
     * @param   null|string $after
     * @return  Mage_Sales_Block_Order_Totals
     */
    public function addTotalBefore(Varien_Object $total, $before = null)
    {
        if ($before !== null) {
            if (!is_array($before)) {
                $before = array($before);
            }
            foreach ($before as $beforeTotals) {
                if (isset($this->_totals[$beforeTotals])) {
                    $totals = array();
                    foreach ($this->_totals as $code => $item) {
                        if ($code == $beforeTotals) {
                            $totals[$total->getCode()] = $total;
                        }
                        $totals[$code] = $item;
                    }
                    $this->_totals = $totals;
                    return $this;
                }
            }
        }
        $totals = array();
        $first = array_shift($this->_totals);
        $totals[$first->getCode()] = $first;
        $totals[$total->getCode()] = $total;
        foreach ($this->_totals as $code => $item) {
            $totals[$code] = $item;
        }
        $this->_totals = $totals;
        return $this;
    }

    /**
     * Get Total object by code
     *
     * @@return Varien_Object
     */
    public function getTotal($code)
    {
        if (isset($this->_totals[$code])) {
            return $this->_totals[$code];
        }
        return false;
    }

    /**
     * Delete total by specific
     *
     * @param   string $code
     * @return  Mage_Sales_Block_Order_Totals
     */
    public function removeTotal($code)
    {
        unset($this->_totals[$code]);
        return $this;
    }

    /**
     * Apply sort orders to totals array.
     * Array should have next structure
     * array(
     *  $totalCode => $totalSortOrder
     * )
     *
     *
     * @param   array $order
     * @return  Mage_Sales_Block_Order_Totals
     */
    public function applySortOrder($order)
    {
        return $this;
    }

    /**
     * get totals array for visualization
     *
     * @return array
     */
    public function getTotals($area = null)
    {
        $totals = array();
        if ($area === null) {
            $totals = $this->_totals;
        } else {
            $area = (string)$area;
            foreach ($this->_totals as $total) {
                $totalArea = (string)$total->getArea();
                if ($totalArea == $area) {
                    $totals[] = $total;
                }
            }
        }
        return $totals;
    }

    /**
     * Format total value based on order currency
     *
     * @param   Varien_Object $total
     * @return  string
     */
    public function formatValue($total)
    {
        if (!$total->getIsFormated()) {
            return $this->getQuote()->formatPrice($total->getValue());
        }
        return $total->getValue();
    }
}
