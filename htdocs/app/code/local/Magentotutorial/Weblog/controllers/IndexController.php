<?php
class Magentotutorial_Weblog_IndexController extends Mage_Core_Controller_Front_Action {
	public function indexAction() {
	echo 'hi';
	}
	public function testModelAction() {
		$params = $this->getRequest()->getParams();
		$blogpost = Mage::getModel('weblog/blogpost');
		echo("Loading the blogpost with an ID of ".$params['id']."\r\n");
		$blogpost->load($params['id']);
		$data = $blogpost->getData();
		var_dump($data);
	}
	public function makepostAction() {
		$blogpost = Mage::getModel('weblog/blogpost');
		$blogpost->setTitle('its a title yaas');
                $blogpost->setPost('its a post so beautiful');
                $blogpost->save();
                echo 'post with ID '. $blogpost->getId(). ' created' . "\r\n";

	}
	public function editFirstPostAction(){
	$blogpost = Mage::getModel('weblog/blogpost');
	$blogpost->load(1);
	$blogpost->setTitle("The First post!");
	$blogpost->save();
	echo 'post edited';
	}
	public function deleteFirstPostAction(){
	$blogpost = Mage::getModel('weblog/blogpost');
	$blogpost->load(1);
	$blogpost->delete();
	echo 'post removed';
	}
	public function showAllBlogPostsAction(){
		$posts = Mage::getModel('weblog/blogpost')->getCollection();
		foreach($posts as $blogpost){
			echo '<h3>'.$blogpost->getTitle().'</h3>';
			echo n12br($blogpost->getPost());
		}
	}
}

