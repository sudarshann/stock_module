<?php
require_once(Mage::getBaseDir('lib') . '/google-client-api/autoload.php');

class Mowdirect_Emailimporter_Helper_Vendor extends Mage_Core_Helper_Abstract {
    

    public function check_cron_scheduled($vendor) {

        if (empty($vendor['batch_import_inventory_schedule'])) {
            Mage::log("Stock importer (info):  skiped email import no cron schudeled for : " . $vendor['vendor_name']);
            return false;
        }

        $last_cron_run = Mage::helper('emailimporter')->get_vendor_value($vendor['vendor_id'], 'last_cron_run');
        
        if (empty($last_cron_run)) {
            return true;
        }
        
        try {
            $cron = Cron\CronExpression::factory($vendor['batch_import_inventory_schedule']);

            $import_inventory_schudle = strtotime($cron->getNextRunDate($last_cron_run)->format('Y-m-d H:i:s'));
            
            if ($import_inventory_schudle < time()) {
                return true;
            }
            
            Mage::log('Stock importer (info):  skiped email import cron schudeled time not reached for : ' . $vendor['vendor_name']);
            return false;           
            
        } catch (exception $e) {          
            Mage::log('Stock importer (error):  Invalid cron schudeled for : ' . $vendor['vendor_name'] );
            Mage::log('Stock importer (error): ' . $e);
            return false;
        }
    }

    public function process_email_download_vendor($vendor) {
        $emailimporter_helper = Mage::helper('emailimporter');
        $vendor_accesstoken = $emailimporter_helper->get_vendor_value($vendor['vendor_id'], 'gmail_importer_access_token');
        $vendor_refresh_token = $emailimporter_helper->get_vendor_value($vendor['vendor_id'], 'gmail_importer_refresh_token');
        
        if (empty($vendor_accesstoken)) {
            Mage::log('Stock importer (info):  skiped email import no access token for :' . $vendor['vendor_name']);
            return false;
        }

        Mage::log('Stock importer (info): Processing Email download attachment for : ' . $vendor['vendor_name']);
        $gmail_api = Mage::helper('emailimporter/GmailConnect');
        $gmail_api->set_config();

        $auth = array('token' => json_decode($vendor_accesstoken, true),'refresh_token' => $vendor_refresh_token);
        // gmail auth fails
        $is_auth = $gmail_api->authenticate($auth);
        if ($is_auth != true) {
            $this->alert_gmail_auth_fail($vendor);
            return false;
        }

        Mage::helper('emailimporter')->set_vendor_value($vendor['vendor_id'], 'last_cron_run', date('Y-m-d H:i:s'));

        $download_path = preg_replace('#\/[^/]*$#', '', $emailimporter_helper->get_vendor_value($vendor['vendor_id'], 'batch_import_inventory_locations'));
        
        $response = $gmail_api->download_csv_files_from_message(
                array(
                    'download_path' => $download_path,
                    'subject' => $emailimporter_helper->get_vendor_value($vendor['vendor_id'], 'import_inventory_csv_regex')
                )
        );

        if ($response['email_found'] == 0) {
            $this->handel_email_miss($vendor);
        } else if ($response['email_found'] > 1) {
            $this->alert_too_many_email($vendor);
        }

        if (!$response['is_file_downloaded']) {
            $this->no_attachment_email($vendor);
        }else{
            $this->file_downloaded($vendor, $response['file_path']);
        }
        
        if ($response['invalid_attachment']) {
            $this->handel_invalid_attachment($vendor);
        }

    }
    
    public function file_downloaded($vendor, $file_path){
        $weekly_hit_count = Mage::helper('emailimporter')->get_vendor_value($vendor['vendor_id'], 'weekly_hit_count');
        $update_weekly_hit_count = empty($weekly_hit_count)? 1 : (int) $weekly_hit_count + 1;
        Mage::helper('emailimporter')->set_vendor_value($vendor['vendor_id'], 'weekly_hit_count', $update_weekly_hit_count);
        Mage::log('Stock importer (info): file download path '. $file_path );
        Mage::log('Stock importer (info): file download for : '. $vendor['vendor_name'] );
    }
    
    public function handel_invalid_attachment($vendor){
        Mage::log('Stock importer (error): Invalid Attachment');
        Mage::helper('emailimporter')->sendMailAction(array(
            'subject' => $vendor['vendor_name'] . ' Stock Management: Invalid Attachment',
            'to_mail' => Mage::getStoreConfig('emailimporter/vendor_email/allowed_failures_email'),
            'message' => $vendor['vendor_name'] . " there is no valid attachment"
        ));
    }

    public function alert_too_many_email($vendor) {
        Mage::log('Stock importer (error): Too many email found for :' . $vendor['vendor_name']);

        $message = "<td><h3>Dear Administrator</h3>,</br>For the Vendor " . $vendor['vendor_name'];
        $message .= " when we attempted to download stock, we found that there were more emails";
        $message .= "in the Gmail inbox that we expected. This suggests that the vendor is sending";
        $message .= "stock updates more frequently than is configured in their preferences within magento.";
        $message .= "Please check with the vendor with what frequency they are sending them and amend the";
        $message .= "cron schedule in Magento accordingly.</td>";

        Mage::helper('emailimporter')->sendMailAction(array(
            'subject' => $vendor['vendor_name'] . ' Stock Management: Too many emails arrived in allotted time-frame',
            'to_mail' => $this->get_vendor_email($vendor),
            'message' => $message,
            'name' => $vendor['vendor_name']
        ));
    }

    public function alert_gmail_auth_fail($vendor) {

        Mage::log('Stock importer (error): email auth failed may be token is invalid for : '. $vendor['vendor_name']);

        Mage::helper('emailimporter')->sendMailAction(array(
            'subject' => 'Stock Management: Canot connect to gmail',
            'to_mail' => $this->get_vendor_email($vendor),
            'message' => '<td><p>Email auth field on gmail-api needs correct auth on dropship -> vendor for vendor name :' . $vendor['vendor_name'].'<td><p>',
            'name' => $vendor['vendor_name']
        ));
    }

    public function handel_email_miss($vendor) {
        Mage::log('Stock importer (error): no inventory email found for : '. $vendor['vendor_name']);

        $description = 'Cron successfully but no email has found';
        $title = 'Stock Management: missed cron without email';
        Mage::helper('emailimporter')->create_new_admin_message($title, $description);

        $current_mail_miss_count = Mage::helper('emailimporter')->get_vendor_value($vendor['vendor_id'], 'cron_attachment_miss_count');
        $updated_mail_miss_count = empty($current_mail_miss_count) ? 1 : (int) $current_mail_miss_count + 1;
        if ($this->get_vendor_allowed_miss($vendor) < $updated_mail_miss_count) {
            $this->alert_misscount_verndor($vendor);
        }
        Mage::helper('emailimporter')->set_vendor_value($vendor['vendor_id'], 'cron_attachment_miss_count', $updated_mail_miss_count);
        
        $weekly_miss_count = Mage::helper('emailimporter')->get_vendor_value($vendor['vendor_id'], 'weekly_miss_count');
        $update_weekly_miss_count = empty($weekly_miss_count)? 1 : (int) $weekly_miss_count + 1;
        Mage::helper('emailimporter')->set_vendor_value($vendor['vendor_id'], 'weekly_miss_count', $update_weekly_miss_count);
    }

    public function alert_misscount_verndor($vendor) {
        Mage::log('Stock importer (error): Exceeding miss email for : ' . $vendor['vendor_name']);
        Mage::helper('emailimporter')->sendMailAction(array(
            'subject' => 'Stock Management: Exceeding miss email',
            'to_mail' => $this->get_vendor_email($vendor),
            'message' => '<td><p>No email and csv Attachment found on youremail ' . $vendor['vendor_name'].'</td></p>',
            'name' => $vendor['vendor_name']
        ));
        Mage::helper('emailimporter')->set_vendor_value($vendor['vendor_id'], 'cron_attachment_miss_count', '1');
    }

    public function no_attachment_email($vendor) {
        Mage::log('Stock importer (error): There is no attachment for vendor : ' . $vendor['vendor_name']);
        Mage::helper('emailimporter')->sendMailAction(array(
            'subject' => 'Stock Management: There is no Attachment found',
            'to_mail' => $this->get_vendor_email($vendor),
            'message' => 'No valid csv Attachment found on email for : ' . $vendor['vendor_name'].'',
            'name' => $vendor['vendor_name']
        ));
    }

    public function get_vendor_email($vendor) {
        $email = Mage::helper('emailimporter')->get_vendor_value($vendor['vendor_id'], 'email_recipient');
        if (empty($email)) {
            return Mage::getStoreConfig('emailimporter/vendor_email/allowed_failures_email');            
        }
        return $email;
    }

    public function get_vendor_allowed_miss($vendor) {
        $allowed_miss = Mage::helper('emailimporter')->get_vendor_value($vendor['vendor_id'], 'allowed_miss_per_vender');
        if (empty($allowed_miss)) {
            $global_allowed_miss = Mage::getStoreConfig('emailimporter/vendor_email/allowed_misses_per_vendor');
            if (empty($global_allowed_miss)) {
                return 2;
            }
            return $global_allowed_miss;
        }
        return $allowed_miss;
    }

}
