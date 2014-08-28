<?php

/**
 * Quote Totals
 */
class Ophirah_Qquoteadv_Model_Quotetotal
    extends Mage_Core_Model_Abstract
{
    protected $_quoteStore = null;
    protected $_totalRate = null;
    protected $_totalCurrency = null;
    protected $_totalCurrencyCode = null;
    protected $_totalCost = null;
    protected $_totalQty = null;
    protected $_totalOrgcost = null;
    protected $_totalQuotecost = null;
    protected $_totalMargin = null;
    protected $_totalProfit = null;
    protected $_totalItems = null;
    protected $_totalCoupon = null;
    protected $_totalSubtotal = null;
    protected $_totalTax = null;
    protected $_totalShipping = null;
    protected $_totalGrandTotal = null;
    protected $_totalQuoteTotal = array();
    protected $_collectedTotals = array();

    protected $_totalExtra = array();

    // Setting values
    public function setQuoteStore($store)
    {
        $this->_quoteStore = $store;
    }

    public function setTotalRate($rate)
    {
        $this->_totalRate = $rate;
    }

    public function setTotalCurrency($currency)
    {
        $this->_totalCurrency = $currency;
    }

    public function setTotalCurrencyCode($currencyCode)
    {
        $this->_totalCurrencyCode = $currencyCode;
    }

    public function setTotalCost($cost)
    {
        $this->_totalCost = $cost;
    }

    public function setTotalQty($qty)
    {
        $this->_totalQty = $qty;
    }

    public function setTotalOrgcost($orgcost)
    {
        $this->_totalOrgcost = $orgcost;
    }

    public function setTotalQuotecost($quotecost)
    {
        $this->_totalQuotecost = $quotecost;
    }

    public function setTotalMargin($margin)
    {
        $this->_totalMargin = $margin;
    }

    public function setTotalProfit($profit)
    {
        $this->_totalProfit = $profit;
    }

    public function setTotalItems($totalItems)
    {
        $this->_totalItems = $totalItems;
    }

    public function setTotalCoupon($totalCoupon)
    {
        $this->_totalCoupon = $totalCoupon;
    }

    public function setTotalSubtotal($totalSubtotal)
    {
        $this->_totalSubtotal = $totalSubtotal;
    }

    public function setTotalTax($totalTax)
    {
        $this->_totalTax = $totalTax;
    }

    public function setTotalShipping($totalShipping)
    {
        $this->_totalShipping = $totalShipping;
    }

    public function setTotalGrandTotal($totalGrandTotal)
    {
        $this->_totalGrandTotal = $totalGrandTotal;
    }

    public function setTotalQuoteTotal($totalQuoteTotal)
    {
        $this->_totalQuoteTotal = $totalQuoteTotal;
    }

    public function setCollectedTotals($collectedTotals)
    {
        $this->_collectedTotals = $collectedTotals;
    }


    // Getting values
    public function getQuoteStore()
    {
        return $this->_quoteStore;
    }

    public function getTotalRate()
    {
        return $this->_totalRate;
    }

    public function getTotalCurrency()
    {
        return $this->_totalCurrency;
    }

    public function getTotalCurrencyCode()
    {
        return $this->_totalCurrencyCode;
    }

    public function getTotalCost()
    {
        return $this->_totalCost;
    }

    public function getTotalQty()
    {
        return $this->_totalQty;
    }

    public function getTotalOrgcost()
    {
        return $this->_totalOrgcost;
    }

    public function getTotalQuotecost()
    {
        return $this->_totalQuotecost;
    }

    public function getTotalMargin()
    {
        return $this->_totalMargin;
    }

    public function getTotalProfit()
    {
        return $this->_totalProfit;
    }

    public function getTotalItems()
    {
        return $this->_totalItems;
    }

    public function getTotalCoupon()
    {
        return $this->_totalCoupon;
    }

    public function getTotalSubtotal()
    {
        return $this->_totalSubtotal;
    }

    public function getTotalTax()
    {
        return $this->_totalTax;
    }

    public function getTotalShipping()
    {
        return $this->_totalShipping;
    }

    public function getTotalGrandTotal()
    {
        return $this->_totalGrandTotal;
    }

    public function getTotalQuoteTotal()
    {
        return $this->_totalQuoteTotal;
    }

    public function getCollectedTotals()
    {
        return $this->_collectedTotals;
    }


    /**
     * Calculating GP margin
     *
     * @return integer      | Margin GP
     */
    public function getTotalGPMarginCalculated()
    {

        $quotePrice = $this->getTotalQuotecost();
        $costPrice = $this->getTotalCost();
        $margin = 0;

        if ($quotePrice > 0) {
            $margin = round(((($quotePrice - $costPrice) / $quotePrice) * 100), 0);
        }

        return $margin . ' %';
    }

    /**
     * Setting QuoteTotals to the Quote
     *
     * @param Ophirah_Qquoteadv_Model_Qqadvcustomer $quote
     * @return \Ophirah_Qquoteadv_Model_Qqadvcustomer
     */
    public function setQuoteTotal(Ophirah_Qquoteadv_Model_Qqadvcustomer $quote)
    {
        $quote->setQuoteTotal($this->_totalQuoteTotal);
        return $quote;
    }

    /**
     * Add total to Quote Total
     *
     * @param array         | $totalData
     */
    public function addQuoteTotal($totalData)
    {

        if (!is_array($totalData)) {
            throw new Exception('variable is not an array');
        }

        $total = array();
        try {
            $total['label'] = $totalData['label'];
            $total['value'] = $totalData['value'];
            $total['order'] = $totalData['order'];
            $total['strong'] = (isset($totalData['strong'])) ? $totalData['strong'] : '';
            $total['foot'] = (isset($totalData['foot'])) ? $totalData['foot'] : '';
            $total['code'] = (isset($totalData['code'])) ? $totalData['code'] : '';

        } catch (Exception $e) {
//            Mage::Log($e->getMessage());
            Mage::logException($e);
            return false;
        }

        $this->_totalQuoteTotal[] = $total;

        return true;

    }

    /**
     * Get tax settings for store
     *
     * @param  boolean // for quote default tax
     * @return string
     */
    public function getTaxSetting($default = false)
    {
        $helper = Mage::helper('qquoteadv');
        if (Mage::getStoreConfig('tax/calculation/price_includes_tax', $this->getQuoteStore()) == 1):
            if ($default === true) {
                $tax = $helper->__('Incl. default Tax');
            } else {
                $tax = $helper->__('Incl. Tax');
            }
        else:
            $tax = $helper->__('Excl. Tax');
        endif;

        return $tax;
    }

    /**
     * Setting the basic totals for the quote
     *
     */
    public function setBasicQuoteTotals()
    {

        $helper = Mage::helper('qquoteadv');
        $tax = $this->getTaxSetting(true);

        $addTotals = array();

        // Subtotal (Original)
        $addTotals[] = array('label' => $helper->__('Subtotal Original (%s)', $tax),
            'value' => $this->_totalOrgcost,
            'order' => 10,
            'strong' => 0
        );

        // Discount (Quote)
        $addTotals[] = array('label' => $helper->__('Adjustment Quote (%s)', $tax),
            'value' => ($this->_totalQuotecost - $this->_totalOrgcost),
            'order' => 20,
            'strong' => 0,
            'code' => 'quote_reduction'
        );

        // Subtotal (Quote)
        if (is_array($this->_totalSubtotal)) {
            $subTotal = $this->getTotalSubtotal();

            foreach ($subTotal as $k => $v) {
                $addTotals[] = array('label' => $v['label'],
                    'value' => $v['value'],
                    'order' => $v['order'],
                    'strong' => (isset($v['strong'])) ? $v['strong'] : 0,
                    'foot' => (isset($v['foot'])) ? $v['foot'] : 0,
                    'name' => (isset($v['name'])) ? $v['name'] : 0,
                    'code' => (isset($v['code'])) ? $v['code'] : 0
                );
            }
        } else {
            $addTotals[] = array('label' => $helper->__('Quote subtotal (%s)', $tax),
                'value' => $this->_totalSubtotal,
                'order' => 30,
                'strong' => 1,
                'foot' => 1,
                'code' => 'subtotal'
            );
        }

        // Tax
        $addTotals[] = array('label' => $helper->__('Tax'),
            'value' => $this->_totalTax,
            'order' => 90,
            'strong' => 0,
            'foot' => 1,
            'code' => 'tax'
        );

        // Shipping
        if ($this->_totalShipping['value'] > 0):
            $addTotals[] = array('label' => $this->_totalShipping['label'],
                'value' => $this->_totalShipping['value'],
                'order' => 70,
                'strong' => 0,
                'foot' => 1,
                'code' => 'shipping'
            );
        endif;

        // Grand Total
        if ($this->_totalGrandTotal == null) {
            $subTotal = $this->getTotalSubtotal();
            if (is_array($subTotal)) {
                $subTotal = $subTotal[0]['value'];
            }
            $this->_totalGrandTotal = $subTotal + $this->_totalTax - $this->_totalCoupon;
        }

        if (is_array($this->_totalGrandTotal)) {
            $grandTotal = $this->getTotalGrandTotal();
            foreach ($grandTotal as $k => $v) {
                $addTotals[] = array('label' => $v['label'],
                    'value' => $v['value'],
                    'order' => $v['order'],
                    'strong' => (isset($v['strong'])) ? $v['strong'] : 0,
                    'foot' => (isset($v['foot'])) ? $v['foot'] : 0,
                    'name' => (isset($v['name'])) ? $v['name'] : 0,
                    'code' => (isset($v['code'])) ? $v['code'] : 0
                );
            }
        } else {
            $addTotals[] = array('label' => $helper->__('Grand Total'),
                'value' => $this->_totalGrandTotal,
                'order' => 100,
                'strong' => 1,
                'foot' => 1,
                'code' => 'grand_total'
            );
        }

        // Profit (Quote)
        if (is_array($this->_totalSubtotal)) {
            $subtotal = $this->getTotalSubtotal();
            $profit = ($subtotal[0]['value'] - $this->_totalCost - $this->_totalCoupon);
        } else {
            $profit = ($this->_totalSubtotal - $this->_totalCost - $this->_totalCoupon);
        }

        $addTotals[]    = array(    'label' => $helper->__('Quote profit', $tax).'*',
            'value' => $profit,
            'order' => 110,
            'strong'=> 0,
            'foot'  => 1,
            'name'  => 'profit',
            'code'  => 'profit'
        );


        if ($profit <= 0) {
            $this->setData('no_profit', true);
        } else {
            $this->setData('no_profit', '');
        }

        // Extra Totals
        if (count($this->_totalExtra) > 0 && is_array($this->_totalExtra)) {
            $order = 60; // after shipping;
            foreach ($this->_totalExtra as $totalExtra) {
                if ($totalExtra['value']) {
                    $addTotals[] = array('label' => $helper->__($totalExtra['label']),
                        'value' => $totalExtra['value'],
                        'strong' => 0,
                        'order' => $order,
                        'code' => $helper->__($totalExtra['code']),
                    );
                    $order++;
                }
            }
        }

        if (count($addTotals) > 0) {
            foreach ($addTotals as $total) {
                $this->addQuoteTotal($total);
            }
        }
    }

    /**
     * Sorting totals according order value
     *
     * @return array
     *
     * TODO: $this->getTotalQuoteTotal() can be different from given object
     */
    public function sortQuoteTotals()
    {

        $totalFoot = array();
        $totalBody = array();

        foreach ($this->getTotalQuoteTotal() as $total):

            if (isset($total['foot']) && $total['foot'] == 1) {
                if (array_key_exists($total['order'], $totalFoot)) {
                    $totalFoot[] = $total;
                }
                $totalFoot[$total['order']] = $total;
            } else {
                if (array_key_exists($total['order'], $totalBody)) {
                    $totalBody[] = $total;
                }
                $totalBody[$total['order']] = $total;
            }

        endforeach;

        ksort($totalFoot);
        ksort($totalBody);

        $return['totalFoot'] = $totalFoot;
        $return['totalBody'] = $totalBody;

        return $return;

    }

    /**
     * Apply discount
     *
     * @param int // $couponId
     * @param int // $totalSubtotal amount
     */
    public function applyDiscount($couponId, $totalSubtotal)
    {
        $this->getDiscount($couponId, $totalSubtotal);
        $this->_totalSubtotal = ($this->_totalQuotecost - $this->_totalCoupon);
    }

    /**
     * Define allowed salesrule types
     *
     * @return array
     */
    public function allowedRuleTypes()
    {

        $ruleTypes = array(Mage_SalesRule_Model_Rule::BY_PERCENT_ACTION,
            Mage_SalesRule_Model_Rule::BY_FIXED_ACTION,
            Mage_SalesRule_Model_Rule::CART_FIXED_ACTION
        );
        return $ruleTypes;
    }

    /**
     * Calculating Quote discount amount
     *
     * @param int // $couponId
     * @param int // $totalSubtotal amount
     */
    public function getDiscount($couponId, $totalSubtotal)
    {
        $discount = 0;
        if ($couponId === false) {
            $this->setTotalCoupon($discount);
        } else {
            $salesRule = Mage::getModel('salesRule/rule')->load($couponId, 'rule_id');
            if (!$salesRule) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('qquoteadv')->__('Could not load Coupon Code'));
            } else {
                $action = $salesRule->getData('simple_action');
                $amount = $salesRule->getData('discount_amount');

                if (!in_array($action, $this->allowedRuleTypes())) {
                    Mage::getSingleton('adminhtml/session')->addError(Mage::helper('qquoteadv')->__('Coupon Type \'%s\' not supported', $action));
                } else {

                    switch ($action):
                        case Mage_SalesRule_Model_Rule::BY_PERCENT_ACTION:
                            $discount = $totalSubtotal * ($amount / 100);
                            break;
                        case Mage_SalesRule_Model_Rule::BY_FIXED_ACTION:
                            $discount = $amount;
                            break;
                        case Mage_SalesRule_Model_Rule::CART_FIXED_ACTION:
                            $discount = $amount;
                            break;
                    endswitch;
                }
            }

            $this->setTotalCoupon($discount);
            $this->addDiscount(Mage::getModel('qquoteadv/qqadvcustomer')->getCouponCodeById($couponId));
        }

    }

    /**
     * Adding discount to totals
     *
     * @param array // $couponCode
     */
    public function addDiscount($couponCode = null)
    {
        $helper = Mage::helper('qquoteadv');
        $tax = $this->getTaxSetting();

        $value = -1 * ($this->_totalCoupon);

        if ($couponCode != null) {
            $label = $helper->__('Discount (%s)', $couponCode);
        } else {
            $label = $helper->__('Discount');
        }

        // Discount (Coupon)
        $discount = array('label' => $label,
            'value' => $value,
            'order' => 60,
            'strong' => 0,
            'foot' => 1,
            'code' => 'discount'
        );

        $this->addQuoteTotal($discount);
    }

    public function updateTotals($couponId = null)
    {
        $tax = $this->getTaxSetting();
        $taxAdd = ' (' . $tax . ')';
        $inclTax = Mage::helper('tax')->__(' (incl. Tax)');
        $exclTax = Mage::helper('tax')->__(' (excl. Tax)');

        if ($collectedTotals = $this->getCollectedTotals()) {
            foreach ($collectedTotals as $key => $total) {
                switch ($key):
                    case 'subtotal':
                        if (Mage::getStoreConfig('tax/cart_display/subtotal', $this->getQuoteStore()) == 3):
                            $subtotal[] = array('label' => $total['title'] . $exclTax,
                                'value' => $total['value_excl_tax'],
                                'strong' => 1,
                                'code' => $key,
                                'order' => 30
                            );

                            $subtotal[] = array('label' => $total['title'] . $inclTax,
                                'value' => $total['value_incl_tax'],
                                'strong' => 1,
                                'code' => $key . '-incl',
                                'order' => 40
                            );
                        else:
                            if (Mage::getStoreConfig('tax/cart_display/subtotal', $this->getQuoteStore()) == 2) {
                                $taxSet = $inclTax;
                            } else {
                                $taxSet = $exclTax;
                            }
                            $subtotal[] = array('label' => $total['title'] . $taxSet,
                                'value' => $total['value'],
                                'strong' => 1,
                                'code' => $key,
                                'order' => 40
                            );
                        endif;
                        $this->setTotalSubtotal($subtotal);
                        break;
                    case 'discount':
                        $this->setTotalCoupon(-1 * $total['value']);
                        $label = $total['title'];
                        break;
                    case 'tax':
                        $this->setTotalTax($total['value']);
                        break;
                    case 'shipping':
                        $shipping = array('label' => $total['title'],
                            'value' => $total['value']
                        );
                        if ($total['value'] > 0) {
                            $this->setTotalShipping($shipping);
                        }
                        break;
                    case 'grand_total':
                        if (Mage::getStoreConfig('tax/cart_display/grandtotal', $this->getQuoteStore()) == 0) {
                            $grandTotal[] = array('label' => $total['title'] . $exclTax,
                                'value' => $total['value'] - $this->getTotalTax(),
                                'order' => 80,
                                'strong' => 1,
                                'code' => $key,
                                'foot' => 1
                            );
                            $grandTotal[] = array('label' => $total['title'] . $inclTax,
                                'value' => $total['value'],
                                'order' => 100,
                                'strong' => 1,
                                'code' => $key,
                                'foot' => 1
                            );
                        } else {
                            $grandTotal[] = array('label' => $total['title'],
                                'value' => $total['value'],
                                'order' => 100,
                                'strong' => 1,
                                'code' => $key,
                                'foot' => 1
                            );
                        }

                        $this->setTotalGrandTotal($grandTotal);
                        break;


                    default:
                        if (isset($total['title']) && isset($total['value'])) {
                            $add = array('label' => $total['title'],
                                'value' => $total['value'],
                                'code' => $key
                            );

                            $this->_totalExtra[] = $add;
                        }
                        break;

                endswitch;

            }
        }

        // clear Totals
        $this->setTotalQuoteTotal(null);
        //recalculate totals
        $this->setBasicQuoteTotals();
        // add discount
        if ($this->getTotalCoupon() > 0) {
            $this->addDiscount(Mage::getModel('qquoteadv/qqadvcustomer')->getCouponCodeById($couponId));
        }
    }
}

?>
