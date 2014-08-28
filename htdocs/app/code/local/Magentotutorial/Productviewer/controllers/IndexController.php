<?php
	class Magentotutorial_Productviewer_IndexController extends Mage_Core_Controller_Front_Action {
	
		public function indexAction(){
			//echo "hello?".PHP_EOL;
			$model = Mage::getModel('catalog/product/entity')->load();
			$yes = array('this', 'is', 'an array');
			//echo is_array($yes) ? 'Array' : 'not an Array';
			//echo is_array($model) ? 'Array' : 'not an Array';
			var_dump($model);
			$SKU = 002;
			$productid = Mage::getModel('catalog/product')
						->getIdBySku(trim($SKU));
 
			// Initiate product model
			$product = Mage::getModel('catalog/product');
 
			// Load specific product whose tier price want to update
			$product ->load($productid);
 
			print_r($product->getData());
		}
	}
?>
