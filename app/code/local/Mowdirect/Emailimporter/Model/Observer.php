<?php

class Mowdirect_Emailimporter_Model_Observer {


    public function handleEmailCron() {
        Mage::log('Stock importer (info):  cron started ');
        $vendors = Mage::helper('emailimporter')->get_vendors();

        foreach ($vendors as $vendor) {
            Mage::log('Stock importer (info):  import inventry started for : '.$vendor['vendor_name']);
            if (!Mage::helper('emailimporter/Vendor')->check_cron_scheduled($vendor)) {
                continue;
            }

            Mage::helper('emailimporter/Vendor')->process_email_download_vendor($vendor);
            
            Mage::log('Stock importer (info):  import inventry end for : '.$vendor['vendor_name']);
        }

        Mage::log('Stock importer (info): cron ended ');
    }
    
    public function handleWeeklyReportCron(){
        Mage::log('Stock importer:  weekly report cron started ');
        $global_email = Mage::getStoreConfig('emailimporter/vendor_email/allowed_failures_email');
        $vendors = Mage::helper('emailimporter')->get_vendors();
        $email_output = '';
        
        foreach ($vendors as $vendor) {
            //$vendor_email = Mage::helper('emailimporter')->get_vendor_value($vendor['vendor_id'], 'email_recipient');
            $weekly_hit_count = Mage::helper('emailimporter')->get_vendor_value($vendor['vendor_id'], 'weekly_hit_count');
            $weekly_miss_count = Mage::helper('emailimporter')->get_vendor_value($vendor['vendor_id'], 'weekly_miss_count');
            $email_output .= '<tr><td class="action-content"> vendor : '.$vendor['vendor_name'];
            $email_output .= ' has weekly hit count of <p class="highlighted-text">'.$weekly_hit_count.'</p>';
            $email_output .= ' &  weekly miss count of <p class="highlighted-text">'.$weekly_miss_count.'</p></td></tr>';
            
        }
        
        Mage::helper('emailimporter/Email')->sendEmail(
                'emailimporter_weekly_report_template', 
                array('name' => 'Email importer', 'email' => $global_email), 
                $global_email, 
                $vendor['vendor_name'], 
                'Weekly Report on Email Importer', 
                array('email_output' => $email_output)
            );
        Mage::log('Stock importer:  weekly report cron ended ');
    }
}
