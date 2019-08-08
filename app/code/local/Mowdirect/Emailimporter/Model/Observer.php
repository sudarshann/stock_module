<?php 

class Mowdirect_Emailimporter_Model_Observer {

    public function handleCron() {
        
        $vendors = Mage::helper('emailimporter')->get_vendors();

        //setting admin email config
        $gmail = Mage::helper('emailimporter/GmailConnect');
        $gmail->set_config();
        
        if(empty($_GET['code'])){
            header("Location: ".$gmail->create_auth_url());
            die;
        }

        
        foreach( $vendors as $vendor ) {
            $gmail->authenticate(['code'=>$_GET['code']]);

            $gmail->download_csv_files_from_message(array('download_path'=>Mage::getConfig()->getVarDir()));
        
        }
    }

    public function file_force_contents($filename, $data, $flags = 0) {
        if(!is_dir(dirname($filename)))
            mkdir(dirname($filename).'/', 0777, TRUE);
        return file_put_contents($filename, $data,$flags);
    }

    public function checkMessages($observer) {
        $notifications = Mage::getSingleton('emailimporter/notification');
        $notifications->addMessage("I was sent by Yourextension");
        return $observer;
    }

    public function beforeCrontab () {
        // $file_name =  'cron-' . time() . '.log';
        // $this->file_force_contents( Mage::getConfig()->getVarDir('log').'/'.$file_name,'test1 content'); 
    }
}