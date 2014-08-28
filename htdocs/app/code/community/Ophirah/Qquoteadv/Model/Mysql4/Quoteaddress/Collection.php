<?php

class Ophirah_Qquoteadv_Model_Mysql4_Quoteaddress_Collection
    extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('qquoteadv/quoteaddress');
    }
}