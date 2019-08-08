<?php
ini_set('display_errors',1); error_reporting(E_ALL);
require_once(Mage::getBaseDir('lib') . '/google-client-api/autoload.php');

class Mowdirect_Emailimporter_Helper_GmailConnect extends Mage_Core_Helper_Abstract {
    private $token;
    private $client;
    private $redirectUri = 'http://127.0.0.1/index.php/email_importer/index/gmailredirect/';
    //private $auth_config = __DIR__.'/gmail_client_credentials.json';
    private $client_secret = '';
    private $client_id = '';
    private $app_name = 'Import Inventry Gmail API';
    private $scope = Google_Service_Gmail::GMAIL_READONLY;
    private $download_path = '';
    private $refresh_token = '';
      
    public function set_config( $args = [] ){

        if( !empty($args['client_id']) ){
            $this->client_id = $args['client_id'];
        }else{
            $this->client_id = Mage::getStoreConfig('emailimporter/vendor_email/gmail_client_id');
        }

        if( !empty($args['client_secret']) ){
            $this->client_secret = $args['client_secret'];
        }else{
            $this->client_secret = Mage::getStoreConfig('emailimporter/vendor_email/gmail_client_secret');
        }

        if( !empty($args['redirect_uri']) ){
            $this->redirectUri = $args['redirect_uri'];
        }else{
            $this->redirectUri = Mage::app()->getStore()->getUrl('email_importer/index/gmailredirect');
        }

        if( !empty($args['app_name']) ){
            $this->app_name= $args['app_name'];
        }

        // if( !empty($args['auth_config']) && file_exists($args['auth_config']) ){
        //     $this->auth_config = $args['auth_config'];
        // }else{
        //     $this->auth_config = Mage::getStoreConfig('emailimporter/vendor_email/gmail_service_email_service_account');
        // }

        if( !empty($args['scope']) ){
            $this->scope = $args['scope'];
        }

        if( !empty($args['refresh_token']) ){
            $this->refresh_token = $args['refresh_token'];
        }

        $client = new Google_Client();
        $client->setApplicationName($this->app_name);
    	//$client->setAuthConfig($this->auth_config);
        $client->setScopes($this->scope);
        $client->setRedirectUri($this->redirectUri);
        $client->setClientSecret($this->client_secret);
        $client->setClientId($this->client_id);
        $this->client = $client;
        return true;
    }
    
    public function get_token(){
        return $this->token;
    }
    
    public function create_auth_url(){
        return $this->client->createAuthUrl();
    }

    public function get_refresh_token(){
        return $this->refresh_token;
    }

    public function get_token_by_code($code){
        if(!empty($code)){
            return $this->client->fetchAccessTokenWithAuthCode($code);
        }
        return false;
    }
    
    public function authenticate( $args = [] ){
        if( !empty($args['code']) ){
            $this->token = $this->client->fetchAccessTokenWithAuthCode($args['code']);
        }elseif( !empty($args['token']) ){
            $this->token = $args['token'];
        }else{
            return false;
        }
        
        if( $this->refresh_token ){
            $this->client->getOAuth2Service()->setRefreshToken( $this->refresh_token );
        }
        
        $this->client->setAccessToken($this->token);
        if( $this->client->isAccessTokenExpired() ){
            if ($this->client->getRefreshToken()) {
                $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
            }else{
                return false;
            }
        }
        $this->refresh_token = $this->client->getRefreshToken();
        return true;
    }
    
    private function get_gmail_service(){
        return new Google_Service_Gmail($this->client);
    }
    
    public function set_dowload_path( $path ){
        if( is_dir($path) ){
            $this->download_path = $path;
        }
    }
    
    public function download_csv_files_from_message( $args = [] ){
        $download_path = $this->download_path;
        if( !empty($args['download_path']) && is_dir($args['download_path']) ){
            $download_path = $args['download_path'];
        }
        $count = 10;
        if( !empty($args['count']) ){
            $count = $args['count'];
        }
        
        
        $service = $this->get_gmail_service();
        $messages = $service->users_messages->listUsersMessages('me', ['maxResults'=>$count]);
        foreach($messages as $data){
    	    $message_id = $data->getId();
            $message = $service->users_messages->get('me',$message_id);
    	    $parts = $message->getPayload()->getParts();
    	    
    	    foreach($parts as $part){
    	        if( empty($part->getBody()->getAttachmentId()) ){
    	            continue;
    	        }
    	        
	            if( empty($part->filename) ){
	                continue;
	            }
	            $info = pathinfo($part->filename);
	            if(empty($info['extension']) || 'csv' != $info['extension'] ){
	                continue;
	            }
	            
	            $attachment_id = $part->getBody()->getAttachmentId();
	            $encoded_string = $service->users_messages_attachments->get('me', $message_id, $attachment_id)->getData();
	            $encoded_string = strtr($encoded_string, array('-' => '+', '_' => '/'));
                $csvdata = base64_decode($encoded_string);
                $f = finfo_open();
                if('text/plain' == finfo_buffer($f, $csvdata, FILEINFO_MIME_TYPE)){
                    $file_path = $download_path . '/' . $part->filename;
                    var_dump($file_path);
                    $file = fopen($file_path, "w+");
                    fwrite($file, $csvdata);
                    fclose($file);
                }
    	    }
    	    
    	}
    	
    	return true;
    }

    
}
// $gmail = new Gmail_Api();
// if(empty($_GET['code'])){
//     header("Location: ".$gmail->create_auth_url());
//     die;
// }
// $gmail->authenticate(['code'=>$_GET['code']]);
// $gmail->download_csv_files_from_message();