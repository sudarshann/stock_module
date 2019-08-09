<?php
use function GuzzleHttp\json_encode;

class Mowdirect_Emailimporter_IndexController extends Mage_Core_Controller_Front_Action{

   /**
   * Index action
   *
   * @access public
   * @return void
   */
    public function indexAction ( ) {
        var_dump( Mage::getStoreConfig('emailimporter/vendor_email/allowed_failures_email'));
        die();
        
        $emailimporter_helper = Mage::helper('emailimporter');
        $vendors = $emailimporter_helper->get_vendors();

        foreach ($vendors as $vendor){
            
            echo '<p>vendor email: ' . $vendor['email'] . '</p>';
            echo '<p>Import Inventory Schedule: ' . $vendor['batch_import_inventory_schedule'] . '</p>';
            
            $custom_vars_combined = json_decode($vendor['custom_vars_comFbined'], true);
            echo '<p>Import Inventory Locations : ' . $custom_vars_combined['batch_import_inventory_locations'] . '</p>';
            echo '<hr>';
        }
    }

    public function gmailredirectAction(){
        $gmail = Mage::helper('emailimporter/GmailConnect');
        $gmail->set_config();
        
        if(empty($_GET['code'])){
            header("Location: ".$gmail->create_auth_url());
            die;
        }

        $gmail->authenticate(['code'=>$_GET['code']]);
        //$access_token = $gmail->get_token_by_code($_GET['code']);
        $vendor_accesstoken = json_encode($gmail->get_token());
        ?>
            <h3>ACCESS Token</h3>
            <p>Copy this access token and paste in the vendor->preference->Email Stock Importer (Section) -> Gmail Importer Access Token</p>
            <p style="width: 375px;line-break: normal; border: 1px solid #000;padding: 10px; word-break: break-all">
            <?php print_r($vendor_accesstoken); ?>
            </p>
        <?php 
        die();   
    }

    public function process_gmailAction(){
       var_dump(Mage::getModel('emailimporter/observer')->handleCron());
    }

}
