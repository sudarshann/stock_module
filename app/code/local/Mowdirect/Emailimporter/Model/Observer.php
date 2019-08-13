<?php

require_once(Mage::getBaseDir('lib') . '/google-client-api/autoload.php');

class Mowdirect_Emailimporter_Model_Observer {

    private $log_file = '/cronReport-email-importer.log';

    public function handleCron() {
        Mage::log('Stock importer:  cron started ');

        $vendors = Mage::helper('emailimporter')->get_vendors();

        foreach ($vendors as $vendor) {

            if ($this->check_cron_scheduled($vendor)) {
                continue;
            }

            $this->process_email_download_vendor($vendor);
        }

        Mage::log('Stock importer: cron ended ');
    }

    public function check_cron_scheduled($vendor) {

        if (empty($vendor['batch_import_inventory_schedule'])) {
            return false;
        }

        $last_cron_run = Mage::helper('emailimporter')->get_vendor_value($vendor['vendor_id'], 'last_cron_run');
        if (empty($last_cron_run)) {
            Mage::log('Stock importer:  skiped import did not match import inventory cron schudeled for ' . $vendor['vendor_name']);
            return false;
        }

        try {
            $cron = Cron\CronExpression::factory($vendor['batch_import_inventory_schedule']);

            $import_inventory_schudle = strtotime($cron->getNextRunDate($last_cron_run)->format('Y-m-d H:i:s'));

            if ($import_inventory_schudle > time()) {
                return false;
            }
            return true;
        } catch (exception $e) {
            Mage::log('Stock importer:  Invalid cron schudeled for ' . $vendor['vendor_name']);
            return false;
        }
    }

    public function process_email_download_vendor($vendor) {
        $emailimporter_helper = Mage::helper('emailimporter');
        $vendor_accesstoken = $emailimporter_helper->get_vendor_value($vendor['vendor_id'], 'gmail_importer_access_token');
        if (empty($vendor_accesstoken)) {
            return false;
        }

        Mage::log('Stock importer: Processing Email download attachment for vendor : ' . $vendor['vendor_name']);
        $gmail_api = Mage::helper('emailimporter/GmailConnect');
        $gmail_api->set_config();

        $auth = array('token' => json_decode($vendor_accesstoken, true));

        // gmail auth fails
        if ($gmail_api->authenticate($auth) != true) {
            $this->alert_gmail_auth_fail($vendor);
            return false;
        }

        Mage::helper('emailimporter')->set_vendor_value($vendor['vendor_id'], 'last_cron_run', date('Y-m-d H:i:s'));


        $response = $gmail_api->download_csv_files_from_message(
                array(
                    'download_path' => preg_replace('#\/[^/]*$#', '', $emailimporter_helper->get_vendor_value($vendor['vendor_id'], 'batch_import_inventory_locations')),
                    'subject' => $emailimporter_helper->get_vendor_value($vendor['vendor_id'], 'import_inventory_csv_regex')
                )
        );

        if ($response['email_found'] == 0) {
            $this->handel_email_mis($vendor);
        } else if ($response['email_found'] > 1) {
            $this->alert_too_many_email($vendor);
        }

        if (!$response['is_file_downloaded']) {
            $this->no_attachment_email($vendor);
        }
        
        if (!$response['invalid_attachment']) {
            $this->handel_invalid_attachment($vendor);
        }

        //is_download_log
        Mage::log('Stock importer: file download ' . $response['is_downloaded']);
    }
    
    public function handel_invalid_attachment($vendor){
        Mage::log('Stock importer: Invalid Attachment');
        Mage::helper('emailimporter')->sendMailAction(array(
            'subject' => $vendor['vendor_name'] . ' Stock Management: Invalid Attachment',
            'to_mail' => Mage::getStoreConfig('emailimporter/vendor_email/allowed_failures_email'),
            'message' => $vendor['vendor_name'] . " there is no valid attachment"
        ));
    }

    public function alert_too_many_email($vendor) {
        Mage::log('Stock importer: Too many email found');

        $message = "Dear Administrator,</br>For the Vendor " . $vendor['vendor_name'];
        $message .= " when we attempted to download stock, we found that there were more emails";
        $message .= "in the Gmail inbox that we expected. This suggests that the vendor is sending";
        $message .= "stock updates more frequently than is configured in their preferences within magento.";
        $message .= "Please check with the vendor with what frequency they are sending them and amend the";
        $message .= "cron schedule in Magento accordingly.";

        Mage::helper('emailimporter')->sendMailAction(array(
            'subject' => $vendor['vendor_name'] . ' Stock Management: Too many emails arrived in allotted time-frame',
            'to_mail' => $this->get_vendor_email($vendor),
            'message' => $message
        ));
    }

    public function alert_gmail_auth_fail($vendor) {

        Mage::log('Stock importer: email auth failed may be token is invalid');

        Mage::helper('emailimporter')->sendMailAction(array(
            'subject' => 'Stock Management: Canot connect to gmail',
            'to_mail' => $this->get_vendor_email($vendor),
            'message' => 'Email auth field on gmail-api needs correct auth on dropship -> vendor for vendor name :' . $vendor['vendor_name']
        ));
    }

    public function handel_email_mis($vendor) {
        Mage::log('Stock importer: no email found');

        $description = 'Cron successfully but no email has found';
        $title = 'Stock Management: mised cron without email';
        Mage::helper('emailimporter')->create_new_admin_message($title, $description);

        $current_mail_mis_count = Mage::helper('emailimporter')->get_vendor_value($vendor['vendor_id'], 'cron_attachment_mis_count');
        $updated_mail_mis_count = empty($current_mail_mis_count) ? 1 : (int) $current_mail_mis_count + 1;
        if ($this->get_vendor_allowed_mis() < $updated_mail_mis_count) {
            $this->alert_miscount_verndor($vendor);
        }
        Mage::helper('emailimporter')->set_vendor_value($vendor['vendor_id'], 'cron_attachment_mis_count', $updated_mail_mis_count);
    }

    public function alert_miscount_verndor($vendor) {
        Mage::log('Stock importer: Exceeding mis email for this vendor : ' . $vendor['vendor_name'], null, Mage::getConfig()->getVarDir('log') . $this->log_file, true);
        Mage::helper('emailimporter')->sendMailAction(array(
            'subject' => 'Stock Management: Exceeding mis email',
            'to_mail' => $this->get_vendor_email($vendor),
            'message' => 'No email and csv Attachment found on youremail ' . $vendor['vendor_name']
        ));
        Mage::helper('emailimporter')->set_vendor_value($vendor['vendor_id'], 'cron_attachment_mis_count', '1');
    }

    public function no_attachment_email($vendor) {
        Mage::log('Stock importer: There is no attachment for this vendor : ' . $vendor['vendor_name'], null, Mage::getConfig()->getVarDir('log') . $this->log_file, true);
        Mage::helper('emailimporter')->sendMailAction(array(
            'subject' => 'Stock Management: There is no Attachment found',
            'to_mail' => $this->get_vendor_email($vendor),
            'message' => 'No valid csv Attachment found on email ' . $vendor['vendor_name']
        ));
    }

    public function get_vendor_email($vendor) {
        $email = Mage::helper('emailimporter')->get_vendor_value($vendor['vendor_id'], 'email_recipient');
        if (empty($email)) {
            $global_value = Mage::getStoreConfig('emailimporter/vendor_email/allowed_failures_email');
            if (empty($global_value)) {
                return 2;
            }
            return $global_value;
        }
        return $email;
    }

    public function get_vendor_allowed_mis($vendor) {
        $allowed_miss = Mage::helper('emailimporter')->get_vendor_value($vendor['vendor_id'], 'allowed_mis_per_vender');
        if (empty($allowed_miss)) {
            return Mage::getStoreConfig('emailimporter/vendor_email/allowed_mises_per_vendor');
        }
        return $allowed_miss;
    }

}
