<?php

/*
 * Cart2Quote CRM addon module
 * 
 * This addon module needs Cart2Quote
 * To be installed and configured proparly
 * 
 */

class Ophirah_Crmaddon_Model_Mysql4_Crmaddonmessages_Collection
    extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('crmaddon/crmaddonmessages');
    }
}