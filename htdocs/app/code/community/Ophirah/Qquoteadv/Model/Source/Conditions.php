<?php

class Ophirah_Qquoteadv_Model_Source_conditions
    extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{
    public function getAllOptions()
    {
        $options = array(
            array(
                'value' => '0',
                'label' => 'Always show Add to Quote Button'
            ),
            array(
                'value' => '1',
                'label' => 'Show Add to Quote only when price is \'0.00\''
            ),
            /*
                array(
                        'value' => '2',
                        'label' => 'Show Add to Quote only when out of Stock'
                ),
                array(
                        'value' => '3',
                        'label' => 'Show Add to Quote when out of Stock or price is \'0.00\''
                )
             * 
             */
        );
        return $options;
    }

    public function getOptionText($value)
    {
        $options = $this->getAllOptions();
        foreach ($options as $option) {
            if (is_array($value)) {
                if (in_array($option['value'], $value)) {
                    return $option['label'];
                }
            } else {
                if ($option['value'] == $value) {
                    return $option['label'];
                }
            }
        }
        return false;
    }
}

?>