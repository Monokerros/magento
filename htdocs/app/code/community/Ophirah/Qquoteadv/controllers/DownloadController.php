<?php
require_once('Mage/Sales/controllers/DownloadController.php');

class Ophirah_Qquoteadv_DownloadController extends Mage_Sales_DownloadController
{
    /**
     * {@inheritDoc}
     */
    public function downloadCustomOptionAction()
    {
        $id = $this->getRequest()->getParam('id');
        if (is_numeric($id)) {
            parent::downloadCustomOptionAction();
            return;
        }

        $downloadInfo = unserialize(base64_decode($id));
        $product = Mage::getModel('qquoteadv/qqadvproduct')->load($downloadInfo['product']);
        if (!$product->getId()) {
            $this->_forward('noRoute');
            return;
        }
        $options = unserialize($product->getData('options'));
        if (!isset($options[$downloadInfo['option']])) {
            $this->_forward('noRoute');
            return;
        }
        $info = $options[$downloadInfo['option']];

        try {
            $this->_downloadFileAction($info);
        } catch (Exception $e) {
            $this->_forward('noRoute');
        }
        exit(0);
    }
}
