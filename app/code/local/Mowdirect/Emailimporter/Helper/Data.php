<?php

class Mowdirect_Emailimporter_Helper_Data extends Mage_Core_Helper_Abstract {

    public function get_vendors() {

        return Mage::getModel('udropship/vendor')->getCollection()->getData();
    }

    public function get_vendor($vendor_id) {

        return Mage::getModel('udropship/vendor')->load($vendor_id);
    }

    public function get_vendor_value($vendor_id, $coloumn_value) {

        return $this->get_vendor($vendor_id)->getData($coloumn_value);
    }

    public function set_vendor_value($vendor_id, $coloumn_value, $vendor_value) {

        $set_vendor = $this->get_vendor($vendor_id)->setData($coloumn_value, $vendor_value);

        return $set_vendor->save();
    }

    public function sendMailAction($args) {

        $mail = Mage::getModel('core/email');

        $mail->setType('html');
        $mail->setToName('The Stock Mangement bot');
        $mail->setBody('Mail Text / Mail Content');
        if (empty($args['subject']) || empty($args['to_mail']) || empty($args['message'])) {
            return false;
        }
        $mail->setSubject($args['subject']);
        $mail->setToEmail($args['to_mail']);
        $mail->setFromName($args['message']);
        $mail->setBodyHTML($args['message']);


        try {
            $mail->send();
        } catch (Exception $e) {
            print_r($e);
        }
    }

    /* batches */

    public function get_batchs() {

        return Mage::getModel('udbatch/batch')->getCollection()->getData();
    }

    public function get_batch($batch_id) {

        return Mage::getModel('udbatch/batch')->load($batch_id);
    }

    public function get_batch_value($batch_id, $coloumn_value) {

        return $this->get_batch($batch_id)->getData($coloumn_value);
    }

    public function create_new_batch($batch_data = array()) {

        $batch_model = Mage::getModel('udbatch/batch')->setData($batch_data);

        try {
            $batch_id = $batch_model->save()->getId();
        } catch (Exception $e) {
            return $e->getMessage();
        }
        return $batch_id;
    }

    public function set_batch_value($batch_id, $coloumn_value, $batch_value) {

        $set_batch = $this->get_batch($batch_id)->setData($coloumn_value, $batch_value);

        return $set_batch->save();
    }

    public function get_batch_dists() {

        return Mage::getModel('udbatch/batch_dist')->getCollection()->getData();
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
