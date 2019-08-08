<?php 
class Mowdirect_Emailimporter_Helper_Data extends Mage_Core_Helper_Abstract {

    

    public function match_vendor_email_subject( $subject_regex, $subject ) {
        return preg_match($subject_regex, $subject);
    }

    public function get_vendors () {
        return Mage::getModel('udropship/vendor')->getCollection()->getData();
    }

    public function get_vendor ( $vendor_id ){
        return Mage::getModel('udropship/vendor')->load($vendor_id);
    }

    public function get_vendor_value ( $vendor_id,  $coloumn_value){
        return $this->get_vendor($vendor_id)->getData($coloumn_value);
    }

    public function set_vendor_value ( $vendor_id, $coloumn_value, $vendor_value ) {
        $set_vendor = $this->get_vendor($vendor_id)->setData($coloumn_value, $vendor_value);
        
        return $set_vendor->save();

    }
    

    public function get_vendor_email_importer_data ( $vendor_id ) {

        $vendor = $this->get_vendor($vendor_id);
        $custom_data = json_decode($vendor['custom_vars_combined'], true);
        $email_import_data = array(
            'import_email_id' => $custom_data['import_email_id'],
            'email_password' => $custom_data['email_password'],
            'allowed_misses' => $custom_data['allowed_misses'],
            'missed_total_email' => $custom_data['missed_total_email'],
            'email_recipient' => $custom_data['email_recipient'],
            'allowed_miss_per_vender' => $custom_data['allowed_miss_per_vender'],
            'gmail_importer_refresh_token' => $custom_data['gmail_importer_refresh_token'],
            'import_inventory_csv_regex' => $custom_data['import_inventory_csv_regex'],
        );
        

        return $email_import_data;
    }

    /* batches */

    public function get_batchs () {
        return Mage::getModel('udbatch/batch')->getCollection()->getData();
    }

    public function get_batch ( $batch_id ) {
        return Mage::getModel('udbatch/batch')->load($batch_id);
    }

    public function create_new_batch ( $batch_data = array()) {
        $batch_model = Mage::getModel('udbatch/batch')->setData($batch_data);
        $batch_id = 0;
        try{
            $batch_id = $batch_model->save()->getId();
        } catch (Exception $e){
            $batch_id = $e->getMessage();   
        }
        return $batch_id;
    }

    public function set_batch_value ( $batch_id, $coloumn_value, $batch_value ) {
        $set_batch = $this->get_batch($batch_id)->setData($coloumn_value, $batch_value);
        
        return $set_batch->save();

    }

    public function get_batch_dists () {
        return Mage::getModel('udbatch/batch_dist')->getCollection()->getData();
    }
    
}  