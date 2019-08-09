<?php 

class Mowdirect_Emailimporter_Model_Observer {

    private $log_file =  'cronReport-email-importer.log';
    public function handleCron() {


        Mage::log('Stock importer cron started :', null, $this->log_file );

        $vendors = Mage::helper('emailimporter')->get_vendors();

        foreach( $vendors as $vendor ) {
            $this->process_email_download_vendor($vendor);      
        }

        Mage::log('Stock importer cron ended :', null, $this->log_file);
    }

    public function process_email_download_vendor($vendor){

        $email_impoter_log_file =  'cronReport-email-importer.log';
        $gmail_api = Mage::helper('emailimporter/GmailConnect');
        $gmail_api->set_config();

        $emailimporter_helper = Mage::helper('emailimporter');
        $vendor_accesstoken = $emailimporter_helper->get_vendor_value($vendor['vendor_id'],'gmail_importer_access_token');
        $auth = array('token.' => json_decode($vendor_accesstoken, true));

        if($gmail_api->authenticate($auth) != true){
            
            Mage::helper('emailimporter')->sendMailAction(array(
                'subject' => 'Stock Management: Canot connect to gmail',
                'to_mail'=> Mage::getStoreConfig('emailimporter/vendor_email/allowed_failures_email'),
                'message' => 'Email auth field on gmail-api needs correct auth'
            ));
            
            return false;
        }

        $vendor_import_inventry_path = preg_replace('#\/[^/]*$#', '', $emailimporter_helper->get_vendor_value($vendor['vendor_id'],'batch_import_inventory_locations'));
        
        $query_gmail=array(
            'download_path' => $vendor_import_inventry_path,
            'subject' => $emailimporter_helper->get_vendor_value($vendor['vendor_id'],'import_inventory_csv_regex')
        );

        $response = $gmail_api->download_csv_files_from_message($query_gmail);
        
        if($response['is_downloaded']){
            
            Mage::log('file download ' . $response['is_downloaded'] , null, $this->log_file);

            Mage::helper('emailimporter')->set_vendor_value($vendor['vendor_id'], 'gmail_import_last_downloaded', date('Y-m-d H:i:s'));
            
        }else{
            Mage::log('file download ' . $response['is_downloaded'] , null, $this->log_file);
        }

    }
    
    public function checkMessages($observer) {
        $notifications = Mage::getSingleton('emailimporter/notification');
        $notifications->addMessage("I was sent by Yourextension");
        return $observer;
    }

  
}