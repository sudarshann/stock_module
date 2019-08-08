<?php
use function GuzzleHttp\json_encode;

class Mowdirect_Emailimporter_IndexController extends Mage_Core_Controller_Front_Action{

   /**
   * Index action
   *
   * @access public
   * @return void
   */
    public function indexAction() {
        
        
        $emailimporter_helper = Mage::helper('emailimporter');
        $vendors = $emailimporter_helper->get_vendors();
        var_dump(Mage::getStoreConfig('emailimporter/vendor_email/gmail_service_email_service_account'));
        //var_dump(Mage::getModel('udropship/vendor')->load(1)->setData('import_email_id','test1@gmail.com')->save());
        //var_dump($emailimporter_helper->set_vendor_value(1, 'import_email_id', 'test2@gmail.com' ));
        // var_dump($emailimporter_helper->create_new_batch(
        //             array('vendor_id' => '1',
        //                 'batch_type' => 'import_inventory',
        //                 'batch_status' => 'error'
        //             )
        //         )
        //     );
        die();

        foreach ($vendors as $vendor){
            
            echo '<p>vendor email: ' . $vendor['email'] . '</p>';
            echo '<p>Import Inventory Schedule: ' . $vendor['batch_import_inventory_schedule'] . '</p>';
            
            $custom_vars_combined = json_decode($vendor['custom_vars_combined'], true);
            echo '<p>Import Inventory Locations : ' . $custom_vars_combined['batch_import_inventory_locations'] . '</p>';
            echo '<hr>';
        }
    }

    public function gmailredirectAction(){
        //var_dump(Mage::helper('emailimporter/GmailConnect'));
        //$vendor_id = Mage::app()->getRequest()->getParam('vendor_id');
        
        $vendor_id = 1;
        $helper = Mage::helper('emailimporter');
        //$vendor = $helper->get_vendor($vendor_id);

        $gmail = Mage::helper('emailimporter/GmailConnect');
        $gmail->set_config();
        
        

        $vendor_accesstoken = $helper->get_vendor_value($vendor_id,'gmail_importer_access_token');
        
        $auth = array('token' => json_decode($vendor_accesstoken, true));
        
        if(empty($_GET['code']) && empty($vendor_accesstoken)){
            header("Location: ".$gmail->create_auth_url());
            die;
        } else if( !empty($_GET['code'])){
            $access_token = $gmail->get_token_by_code($_GET['code']);
            $vendor_accesstoken = json_encode($access_token);
            $helper->set_vendor_value($vendor_id,'gmail_importer_access_token', $vendor_accesstoken);
            $auth['code']= $_GET['code'];
        }
            
        
        $gmail->authenticate($auth);

        $gmail->download_csv_files_from_message(array('download_path'=>Mage::getConfig()->getVarDir()));
    }

}
