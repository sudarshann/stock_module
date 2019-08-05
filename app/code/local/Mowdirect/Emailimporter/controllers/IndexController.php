<?php

class Mowdirect_Emailimporter_IndexController extends Mage_Core_Controller_Front_Action{

   /**
   * Index action
   *
   * @access public
   * @return void
   */
    public function indexAction() {

        $vendors = Mage::getModel('udropship/vendor')->getCollection()->getData();
        foreach ($vendors as $vendor){
            
            echo '<p>vendor email: ' . $vendor['email'] . '</p>';
            echo '<p>Import Inventory Schedule: ' . $vendor['batch_import_inventory_schedule'] . '</p>';
            
            $custom_vars_combined = json_decode($vendor['custom_vars_combined'], true);
            echo '<p>Import Inventory Locations : ' . $custom_vars_combined['batch_import_inventory_locations'] . '</p>';
            echo '<hr>';
        }
    }
}
