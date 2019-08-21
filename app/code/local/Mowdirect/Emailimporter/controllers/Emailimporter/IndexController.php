<?php

class Mowdirect_Emailimporter_Emailimporter_IndexController extends Mage_Adminhtml_Controller_Action {
    
    public function indexAction() {
        $gmail = Mage::helper('emailimporter/GmailConnect');
        $gmail->set_config();

        if (empty($_GET['code'])) {
            header("Location: ".$gmail->create_auth_url());
            die;
        }

    }
}
