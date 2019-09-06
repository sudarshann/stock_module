<?php

class Mowdirect_Emailimporter_Helper_Data extends Mage_Core_Helper_Abstract {

    public function sendMailAction($args) {
        $global_email = Mage::getStoreConfig('emailimporter/vendor_email/allowed_failures_email');
        
        if (empty($args['subject']) || empty($args['to_mail']) || empty($args['message']) || empty($args['name'])) {
            return false;
        }
        
        Mage::helper('emailimporter/Email')->sendEmail(
            'emailimporter_inventory_template', 
            array('name' => 'Email importer', 'email' => $global_email), 
            $args['to_mail'], 
            $args['name'], 
            $args['subject'], 
            array('email_output' => $args['message'])
        );

    }

    public function create_new_admin_message($title, $message) {

        $args = array('severity' => 4,
            'date_added' => date('Y-m-d H:i:s'),
            'title' => $title,
            'description' => $message,
            'is_read' => 0,
            'is_remove' => 0
        );
        return Mage::getModel('adminnotification/inbox')->setData($args)->save()->getId();
    }

}
