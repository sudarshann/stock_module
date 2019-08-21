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
    
    public function handleWeeklyReportCron() {
        Mage::log('Stock importer (info):  weekly report cron started ');
        
        $global_email = Mage::getStoreConfig('emailimporter/vendor_email/allowed_failures_email');
        $vendors = Mage::helper('emailimporter')->get_vendors();

        $email_output .= '<thead><th>Vendor</th><th>Hits</th><th>Misses</th></thead>';
        $email_output .= '<tbody>';
        foreach ($vendors as $vendor) {
            $weekly_hit_count = Mage::helper('emailimporter')->get_vendor_value($vendor['vendor_id'], 'weekly_hit_count');
            $weekly_miss_count = Mage::helper('emailimporter')->get_vendor_value($vendor['vendor_id'], 'weekly_miss_count');
            
            $email_output .= '<tr>';
            $email_output .= '<td class="action-content">'.$vendor['vendor_name'].'</td>';
            $email_output .= ' <td><p class="highlighted-text">'.$weekly_hit_count.'</p></td>';
            $email_output .= ' <td><p class="highlighted-text">'.$weekly_miss_count.'</p></td>';
            $email_output .= '</tr>';
        }
        $email_output .= '</tbody>';

        Mage::helper('emailimporter/Email')->sendEmail(
                'emailimporter_weekly_report_template', 
                array('name' => 'The Magento Stock Bot', 'email' => $global_email), 
                $global_email, 
                "The Magento Stock Bot", 
                'Weekly Report on Email Import for different Vendors', 
                array('email_output' => $email_output)
            );
        Mage::log('Stock importer (info):  weekly report cron ended ');
    }
}
