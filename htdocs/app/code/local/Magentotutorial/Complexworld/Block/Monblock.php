<?php
	class Magentotutorial_Complexworld_Block_Monblock extends Mage_Core_Block_Template
	{
		public function methodblock()
		{
			$weblog2 = Mage::getModel('complexworld/eavblogpost');
                        $weblog2->load(1);
                        var_dump($weblog2);
						//sleep(2);
			return 'informations about my block !!' ;
		}
	}
?>
