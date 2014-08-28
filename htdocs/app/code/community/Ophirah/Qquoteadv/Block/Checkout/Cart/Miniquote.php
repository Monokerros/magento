<?php

class Ophirah_Qquoteadv_Block_Checkout_Cart_Miniquote extends Ophirah_Qquoteadv_Block_Qquote
{
    public function getTotalQty()
    {
        return Mage::helper('qquoteadv')->getTotalQty();
    }
}
