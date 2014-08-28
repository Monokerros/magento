<?php

class Ophirah_Qquoteadv_Block_Adminhtml_Qquoteadv_Renderer_Status
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    /**
     * Show status and substatus in Grid
     *
     * @param Varien_Object $row
     * @return array
     */
    public function render(Varien_Object $row)
    {
        // Retrieve values
        $status = (int)$row->getData('status');
        $substatus = $row->getData('substatus');
        // Get array of all statuses incl. substatuses
        $gridOptionArray = Mage::getModel('qquoteadv/status')->getGridOptionArray(true);

        // Build combined array if substatuses exists
        if ($substatus && Ophirah_Qquoteadv_Model_Substatus::substatuses()):
            if (Mage::getModel('qquoteadv/substatus')->getParentStatus($substatus) == $status) {
                return $gridOptionArray[$substatus];
            } else {
                return $gridOptionArray[$status];
            }
        endif;

        // Return only main statuses
        return $gridOptionArray[$status];
    }
}
