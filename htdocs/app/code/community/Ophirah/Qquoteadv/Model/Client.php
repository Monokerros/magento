<?php

class Ophirah_Qquoteadv_Model_Client
{


    const TIMEOUT = 2; //timeout in seconds to stop blocking

    protected $_client;

    protected $_ophirah_uri;

    public function __construct()
    {
        $this->_ophirah_uri = 'http://dashboard.cart2quote.com/stats-api/ws.php';
    }

    private function _getClient()
    {
        if (!$this->_client instanceof Varien_Http_Client) {
            $this->_client = new Varien_Http_Client();
        }

        return $this->_client;
    }

    private function _prepareParams($params)
    {
        try {
            $encParams = Zend_Json::encode($params);
        } catch (Exception $e) {
            Mage::Log('can not json encode params:' . $e->getMessage());
            $encParams = Zend_Json::encode(array('error', 'encoding'));
        }

        return array('qquoteadv_params' => $encParams);
    }

    public function sendRequest($params)
    {
        $params = $this->_prepareParams($params);

        $client = $this->_getClient()
            ->setUri($this->_ophirah_uri)
            ->setMethod(Zend_Http_Client::POST)
            ->resetParameters()
            ->setParameterPost($params)
            ->setConfig(array('timeout' => self::TIMEOUT));
        try {
            $response = $client->request('POST');
            $result = Zend_Json::decode($response->getBody());
        } catch (Exception $e) {
            $result = false;
            Mage::Log($e->getMessage());
        }
        return $result;
    }
}