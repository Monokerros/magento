<?php

class Ophirah_Qquoteadv_Model_Massupdate
{
    const ALLOW_TO_QUOTEMODE_NOT_SET = 0;
    const ALLOW_TO_QUOTEMODE_ISSET = 1;
    const ALLOW_TO_QUOTEMODE__ISSET_WITH_CORRESPONDING_VALUE = 2;
    const TRANSACTION_QUERY_LIMIT_COUNT = 500;

    private $_allowed_quote_mode;
    private $_product_id_ranges;

    private $_added_queries_to_transaction = 0;
    private $_success_commit_queries = 0;
    private $_added_products_to_query = 0;
    private $_success_commit_products = 0;

    private $_mass_update_done = false;

    private function setAllowedQuoteMode($allowed_quote_mode)
    {
        $this->_allowed_quote_mode = $allowed_quote_mode;
    }

    private function setProductIdRanges($product_id_ranges)
    {
        $this->_product_id_ranges = $product_id_ranges;
    }

    private function getAllowedQuoteMode()
    {
        return $this->_allowed_quote_mode;
    }

    private function getProductIdRanges()
    {
        return $this->_product_id_ranges;
    }

    private function getEntityTypeId()
    {
        return Mage::getModel('eav/config')->getAttribute('catalog_product', 'allowed_to_quotemode')->getEntityTypeId();
    }

    private function getAttributeIdAllowedToQuoteMode()
    {
        return Mage::getModel('eav/config')->getAttribute('catalog_product', 'allowed_to_quotemode')->getAttributeId();
    }

    public function startMassUpdateAllowedToQuote($allowed_quote_mode, $product_id_ranges)
    {
        if ($product_id_ranges != "") {
            $this->setAllowedQuoteMode($allowed_quote_mode);
            $this->setProductIdRanges($product_id_ranges);

            $connection = $this->getConnection(true);
            $this->executeQueries($connection);
            $connection->closeConnection();

            $message = 'Updated ' . $this->_success_commit_products . ' records';
            Mage::getSingleton('adminhtml/session')->addNotice($message);
        } else {
            $message = 'Please specify product id ranges.';
            Mage::getSingleton('adminhtml/session')->addNotice($message);
        }
    }


    private function checkConnectionAction($connection)
    {
        $this->beginTransaction($connection);
        $this->beginCommit($connection);
    }

    private function beginTransaction($connection)
    {
        if ($this->_added_queries_to_transaction == 0) {
            $connection->beginTransaction();
        }
    }

    private function beginCommit($connection)
    {
        if ($this->_added_queries_to_transaction == $this::TRANSACTION_QUERY_LIMIT_COUNT || ($this->_mass_update_done)) {
            try {
                $connection->commit();
                $this->_success_commit_queries += $this->_added_queries_to_transaction;
                $this->_success_commit_products += $this->_added_products_to_query;
                $this->_added_queries_to_transaction = 0;
            } catch (Exception $e) {
                $connection->rollback();
                $this->_success_commit_queries -= $this->_added_queries_to_transaction;
                $this->_success_commit_products -= $this->_added_products_to_query;
            }
        }
    }

    private function executeQueries($connection)
    {
        $products = $this->getProductsWithUpdateRanges();
        if (isset($products['inserts'])) {
            foreach ($products['inserts'] as $insert_products) {
                $this->setInsertQuery($connection, $this->getAttributeIdAllowedToQuoteMode(), $insert_products->getEntityId(), $this->getEntityTypeId(), $this->getAllowedQuoteMode());
                $this->_added_products_to_query++;
            }
        }
        if (isset($products['update_ranges'])) {
            foreach ($products['update_ranges'] as $update_range) {
                $this->setUpdateQuery($connection, null, $this->getAllowedQuoteMode(), $update_range);
                $this->_added_products_to_query += ($update_range['end_id'] - $update_range['start_id']) + 1;
            }
        }
        if (isset($products['updates'])) {
            foreach ($products['updates'] as $update_product) {
                $product_is_in_update_range = false;
                foreach ($products['update_ranges'] as $update_range) {
                    if ($update_product->getEntityId() >= $update_range['start_id'] && $update_product->getEntityId() <= $update_range['end_id']) {
                        $product_is_in_update_range = true;
                    }
                }
                if (!$product_is_in_update_range) {
                    $this->setUpdateQuery($connection, $update_product->getEntityId(), $this->getAllowedQuoteMode());
                    $this->_added_products_to_query++;
                }
            }
        }

        $this->_mass_update_done = true;
        $this->checkConnectionAction($connection);
    }

    private function setInsertQuery($connection, $attribute_id, $entity_id, $entity_type_id, $mode)
    {
        $fields = array();
        $fields['attribute_id'] = $attribute_id;
        $fields['entity_id'] = $entity_id;
        $fields['entity_type_id'] = $entity_type_id;
        $fields['value'] = $mode;
        $connection->insert($this->getTableName(), $fields);
        $this->_added_queries_to_transaction++;
        $this->checkConnectionAction($connection);
    }


    private function setUpdateQuery($connection, $entity_id, $mode, $range = null)
    {
        $fields = array();
        $fields['value'] = $mode;
        $where = array();
        $where[] = $connection->quoteInto('attribute_id = ?', $this->getAttributeIdAllowedToQuoteMode());
        $where[] = $connection->quoteInto("entity_type_id = ? ", $this->getEntityTypeId());
        if ($range == null) {
            $where[] = $connection->quoteInto("entity_id = ? ", $entity_id);
        } else {
            $where[] = $connection->quoteInto("entity_id >= ? ", $range['start_id']);
            $where[] = $connection->quoteInto("entity_id <= ? ", $range['end_id']);
        }
        $connection->update($this->getTableName(), $fields, $where);
        $this->_added_queries_to_transaction++;
        $this->checkConnectionAction($connection);
    }


    private function getTableName()
    {
        return Mage::getModel('core/resource')->getTableName('catalog_product_entity_int');
    }

    private function getConnection($core_write = false)
    {
        if ($core_write)
            return Mage::getModel('core/resource')->getConnection('core_write');
        else
            return Mage::getModel('core/resource')->getConnection('core_read');
    }


    private function getProductsWithUpdateRanges()
    {
        $update_product_id_ranges = array();
        $start_id = null;
        $action_products = $this->determineQueryAction();

        for ($i = 0; $i < count($action_products['updates']); $i++) {
            if ($start_id == null) {
                $start_id = $action_products['updates'][$i]->getEntityId();
            }
            if (isset($action_products['updates'][$i + 1])) {
                if ($action_products['updates'][$i + 1]->getEntityId() > ($action_products['updates'][$i]->getEntityId() + 1)) {
                    if ($start_id != $action_products['updates'][$i]->getEntityId()) {
                        $range['start_id'] = $start_id;
                        $range['end_id'] = $action_products['updates'][$i]->getEntityId();
                        $update_product_id_ranges[] = $range;
                    }
                    $start_id = null;
                    $end_id = null;
                }
            } elseif ($start_id != null && $action_products['updates'][$i]->getEntityId() != $start_id) {
                $range['start_id'] = $start_id;
                $range['end_id'] = $action_products['updates'][$i]->getEntityId();
                $update_product_id_ranges[] = $range;
            }
        }
        $action_products['update_ranges'] = $update_product_id_ranges;
        return $action_products;
    }

    private function determineQueryAction()
    {
        $query_action_products = array();
        $query_action_products['inserts'] = array();
        $query_action_products['updates'] = array();
        foreach ($this->getProductsInIdRange() as $in_range_product) {
            switch ($this->getStatusProductAllowedToQuote($this->getAttributeIdAllowedToQuoteMode(), $in_range_product->getEntityId(), $this->getEntityTypeId(), $this->getAllowedQuoteMode())) {
                case $this::ALLOW_TO_QUOTEMODE_ISSET:
                    $query_action_products['updates'][] = $in_range_product;
                    break;
                case $this::ALLOW_TO_QUOTEMODE_NOT_SET:
                    $query_action_products['inserts'][] = $in_range_product;
                    break;
                case $this::ALLOW_TO_QUOTEMODE__ISSET_WITH_CORRESPONDING_VALUE:
                    break;
            }
        }
        return $query_action_products;
    }

    private function getStatusProductAllowedToQuote($attribute_id, $entity_id, $entity_type_id, $mode)
    {
        $connection = $this->getConnection();
        $select = $connection->select()->from($this->getTableName(), array('value'))->where('attribute_id=?', $attribute_id)->where('entity_id=?', $entity_id)->where('entity_type_id=?', $entity_type_id);
        $row = $connection->fetchRow($select);
        $connection->closeConnection();

        if (!$row) {
            $status = $this::ALLOW_TO_QUOTEMODE_NOT_SET;
        } else if ($row['value'] == $mode) {
            $status = $this::ALLOW_TO_QUOTEMODE__ISSET_WITH_CORRESPONDING_VALUE;
        } else {
            $status = $this::ALLOW_TO_QUOTEMODE_ISSET;
        }
        return $status;
    }

    private function getProducts()
    {
        return Mage::getModel('catalog/product')->getCollection()->addAttributeToSort('entity_id', 'asc');
    }

    private function isProductIdInRange($product)
    {
        $ranges = $this->formatMassUpdateAllowedToQuoteRanges();
        foreach ($ranges as $range) {
            if ($product->getId() >= $range['start'] && $product->getId() <= $range['end']) {
                return true;
            }
        }
        return false;
    }

    private function formatMassUpdateAllowedToQuoteRanges()
    {
        $ranges_string = explode(",", $this->getProductIdRanges());
        $ranges = array();
        $index = 0;
        foreach ($ranges_string as $range_string) {
            if (!empty($range_string)) {
                if (strpos($range_string, '-') !== false) {
                    $split_range_string = explode("-", $range_string);

                    $ranges[$index]['start'] = intval(trim($split_range_string[0]));
                    $ranges[$index]['end'] = intval(trim($split_range_string[1]));
                } else {
                    $ranges[$index]['start'] = intval(trim($range_string));
                    $ranges[$index]['end'] = intval(trim($range_string));
                }
                $index++;
            }
        }
        return $ranges;
    }

    private function getProductsInIdRange()
    {
        $in_id_range_products = array();
        foreach ($this->getProducts() as $product) {
            if ($this->isProductIdInRange($product)) {
                $in_id_range_products[] = $product;
            }
        }

        return $in_id_range_products;
    }

}