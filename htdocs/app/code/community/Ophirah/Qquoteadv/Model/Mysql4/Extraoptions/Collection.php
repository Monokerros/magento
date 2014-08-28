<?php

class Ophirah_Qquoteadv_Model_Mysql4_Extraoptions_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('qquoteadv/extraoptions');
    }
}