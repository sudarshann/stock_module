<?php

class Mowdirect_Emailimporter_Model_Defaultconfig extends Mage_Core_Model_Config_Data {

    public function save() {
        $allowed_misses_per_vendor = $this->getValue();
        $allowed_misses_per_vendor = preg_replace('#[^0-9]#', '', $allowed_misses_per_vendor);
        if ($allowed_misses_per_vendor > 13) {    //exit if we're less than 10 digits long
            throw new Mage_Core_Exception('Enter value below 12 ');
        }

        return parent::save();     //call original save method so whatever happened 
    }

}
