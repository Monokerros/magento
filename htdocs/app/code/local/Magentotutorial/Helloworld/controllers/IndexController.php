<?php
	class Magentotutorial_Helloworld_IndexController extends Mage_Core_Controller_Front_Action {

	public function indexAction(){
	
		$this->loadLayout();
		$this->renderLayout();
	}
	public function thisAction(){
	
		$thing_1 = new Varien_Object();
		$thing_1->setName('Richard');
		$thing_1->setAge(24);
		
		$thing_2 = new Varien_Object();
		$thing_2->setName('Jane');
		$thing_2->setAge(12);

		$thing_3 = new Varien_Object();
		$thing_3->setName('Spot');
		$thing_3->setLastName('The Dog');
		$thing_3->setAge(7);
		
		$collection_of_things = new Varien_Data_Collection();
		$collection_of_things 
			->addItem($thing_1)
			->addItem($thing_2)
			->addItem($thing_3);
	}
	
	public function paramsAction() {
		echo '<dl>';
		foreach($this->getRequest()->getParams() as $key=>$value) {
			echo '<dt><strong>Param: </strong>'.$key.'</dt>';
			echo '<dt><strong>Value: </strong>'.$value.'</dt>';
		}
		echo '</dl>';
	}
	public function goodbyeAction(){
		$this->loadLayout();
		$this->renderLayout();
	}
}
?>
