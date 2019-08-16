<?php

use function GuzzleHttp\json_encode;

class Mowdirect_Emailimporter_IndexController extends Mage_Core_Controller_Front_Action {
    
    public function indexAction(){
        // This section is used only to debug the cron functionality manually by url.
        // Uncomment only when testing.
        // var_dump(Mage::getModel('emailimporter/Observer')->handleEmailCron());
        // die();
    }
    
    public function gmailredirectAction() {
        $gmail = Mage::helper('emailimporter/GmailConnect');
        $gmail->set_config();

        if (empty($_GET['code'])) {
            header("Location: " . $gmail->create_auth_url());
            die();
        }

        $gmail->authenticate(['code' => $_GET['code']]);
        
        //$access_token = $gmail->get_token_by_code($_GET['code']);
        $vendor_accesstoken = json_encode($gmail->get_token());
        $vendor_refresh_token = $gmail->get_refresh_token();
        ?>
        <h3>ACCESS Token</h3>
        <p>Copy this access token and paste in the vendor->preference->Email Stock Importer (Section) -> Gmail Importer Access Token</p>
        <p style="width: 375px;line-break: normal; border: 1px solid #000;padding: 10px; word-break: break-all">
            <?php print_r($vendor_accesstoken); ?>
        </p>
        <hr>
        <h3>Refresh Token</h3>
        <p>Copy this access token and paste in the vendor->preference->Email Stock Importer (Section) -> Gmail Importer Refresh Token</p>
        <p style="width: 375px;line-break: normal; border: 1px solid #000;padding: 10px; word-break: break-all">
            <?php echo $vendor_refresh_token; ?>
        </p>
        <?php
        return;
    }
}
