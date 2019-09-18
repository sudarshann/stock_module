<?php

class Mowdirect_Emailimporter_Model_Observer {


    public function handleEmailCron() {
        Mage::log('Stock importer (info):  cron started ');
        $vendors = Mage::helper('emailimporter/Vendor')->get_vendors();
        $vendor_model = Mage::getModel('udropship/vendor');

        $vendors_to_update = new Varien_Data_Collection();
        foreach ($vendors as $key => $vendor) {
            Mage::log('Stock importer (info):  import inventry started for : '.$vendor['vendor_name']);

            if (!Mage::helper('emailimporter/Vendor')->check_cron_scheduled($vendor)) {
                continue;
            }

            $update_vendor_varien = new Varien_Object();
            $update_vendor_varien->setVendorId($vendor['vendor_id']);

            Mage::helper('emailimporter/Vendor')->process_email_download_vendor($vendor, $update_vendor_varien);

            Mage::log('Stock importer (info):  import inventry end for : '.$vendor['vendor_name']);

            $vendors_to_update->addItem($update_vendor_varien);

        }

        Mage::helper('emailimporter/Vendor')->update_bluk_vendor($vendor_model, $vendors_to_update->toArray());

        unset($vendors);
        unset($vendors_to_update);
        
        Mage::log('Stock importer (info): cron ended ');
    }

    public function handleWeeklyReportCron() {
        Mage::log('Stock importer (info):  weekly report cron started ');

        $global_email = Mage::getStoreConfig('emailimporter/vendor_email/allowed_failures_email');
        $vendors = Mage::helper('emailimporter/Vendor')->get_vendors();

        $email_output = '<thead><th>Vendor</th><th>Hits</th><th>Misses</th></thead>';
        $email_output .= '<tbody>';
        foreach ($vendors as $vendor) {
            $email_output .= '<tr>';
            $email_output .= '<td class="action-content">'.$vendor['vendor_name'].'</td>';
            $email_output .= ' <td><p class="highlighted-text">'.$vendor['weekly_hit_count'].'</p></td>';
            $email_output .= ' <td><p class="highlighted-text">'.$vendor['weekly_miss_count'].'</p></td>';
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
