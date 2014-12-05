<?php
class mobileNotifications {
	public function __construct(){
		$this->conf = (object)array('ios'=>array(), 'android'=>array(), 'blackberry'=>array());
		
		// ios specific configuration
		$this->conf->ios['badge'] = 1;
		$this->conf->ios['sound'] = 'default';
		$this->conf->ios['development'] = false;
		$this->conf->ios['apns_url'] = NULL;
		$this->conf->ios['apns_cert'] = NULL;
		$this->conf->ios['apns_port'] = 2195;		
		$this->conf->ios['development'] = false;
		if($this->conf->ios['development']){
			$this->conf->ios['apns_url'] = 'gateway.sandbox.push.apple.com';
			$this->conf->ios['apns_cert'] = '....pem';
		}
		else{
			$this->conf->ios['apns_url'] = 'gateway.push.apple.com';
			$this->conf->ios['apns_cert'] = '....pem';
		}
		
		// android specific configuration
		$this->conf->android['apikey'] = 'A...';
		$this->conf->android['contentTitle']	= 'Inovi';
		$this->conf->android['subtitle']	= '';
		$this->conf->android['tickerText']	= 'Inovi';
		$this->conf->android['vibrate']	= 1;
		$this->conf->android['sound']	= 1;
		$this->conf->android['largeIcon']	= 'large_icon';
		$this->conf->android['smallIcon']	= 'small_icon';
		$this->conf->android['url']='https://android.googleapis.com/gcm/send';

		// black berry specific configuration
		$this->conf->blackberry['appid'] = '...111';
		$this->conf->blackberry['password'] = '***';
		$this->conf->blackberry['development'] = true;
		if($this->conf->blackberry['development']){
			$this->conf->blackberry['url'] = 'https://cp5097.pushapi.eval.blackberry.com/mss/PD_pushRequest';
		}
		else{
			$this->conf->blackberry['url'] = '';
		}
	}
	
	public $conf ;
	
	/**
	* returns array of valid ios tokens based on preg match
	*
	* @param string semi-colon(;) separated list of tokens
	* @return array of valid ios tokens. 
	*/
	public function getIosTokens($sTokens){
		$device_tokens = array();	
		if (preg_match_all ('/[A-F0-9]{64}/si', $sTokens, $matches)){// iOS		
			foreach($matches[0] as $k => $match){
				$device_tokens[]=$match;
			}	
		}				
		return $device_tokens;	
	}
	
	/**
	* returns array of valid android tokens based on preg match
	*
	* @param string semi-colon(;) separated list of tokens
	* @return array of valid android tokens. 
	*/
	public function getAndroidTokens($sTokens){
		$device_tokens = array();
		if (preg_match_all ('/[^;]{100,}/si', $sTokens, $matches)){// And		
			foreach($matches[0] as $k => $match){
				$device_tokens[]=$match;
			}	
		}				
		return $device_tokens;
	}					

	/**
	* returns array of valid blackberry tokens based on preg match
	*
	* @param string semi-colon(;) separated list of tokens
	* @return array of valid blackberry tokens. 
	*/
	public function getBlackberryTokens($sTokens){
		$device_tokens = array();		
		if (preg_match_all ('/bb\-[0-9A-F]{8,}/si', $sTokens, $matches)){// BB		
			foreach($matches[0] as $k => $match){
				$device_tokens[]=$match;
			}	
		}			
		return $device_tokens;
	}	
	
	/**
	* sends ios notifications 
	*
	* @param array list of valid ios tokens
	* @param string notification text content
	* @return 
	*/
	public function sendIos($device_tokens, $sNotif){	
		echo $sNotif.'<br />';
		$payload = array();
		$payload['aps'] = array('alert' => $sNotif, 'badge' => intval($this->conf->ios['badge']), 'sound' => $this->conf->ios['sound']);
		$payload = json_encode($payload);		
		
		$stream_context = stream_context_create();
		stream_context_set_option($stream_context, 'ssl', 'local_cert', $this->conf->ios['apns_cert']);		
		$apns = stream_socket_client('ssl://' . $this->conf->ios['apns_url'] . ':' . $this->conf->ios['apns_port'], $error, $error_string, 2, STREAM_CLIENT_CONNECT, $stream_context);
		
		foreach($device_tokens as $device_token){
			$apns_message = chr(0) . chr(0) . chr(32) . pack('H*', str_replace(' ', '', $device_token)) . chr(0) . chr(strlen($payload)) . $payload;
			fwrite($apns, $apns_message);
		}
		
		var_dump($payload);
		
		@socket_close($apns);
		@fclose($apns);
		
		var_dump($error);
		var_dump($error_string);
	}

	/**
	* sends android notifications 
	*
	* @param array list of valid android tokens
	* @param string notification text content
	* @return 
	*/
	public function sendAndroid($device_tokens, $sNotif){
		echo $sNotif.'<br />';
		$msg = array(
		'contentText' => $sNotif,
		'contentTitle'	=> $this->conf->android['contentTitle'],
		'subtitle'	=> $this->conf->android['subtitle'],
		'tickerText'	=> $sNotif,
		'vibrate'	=> $this->conf->android['vibrate'],
		'sound'	=> $this->conf->android['sound'],
		'largeIcon'	=> $this->conf->android['largeIcon'],
		'smallIcon'	=> $this->conf->android['smallIcon']);
		$fields = array(
		'registration_ids' => $device_tokens,
		'data'	=> $msg		);
		$headers = array(
		'Authorization: key=' . $this->conf->android['apikey'],
		'Content-Type: application/json');
		
		$ch = curl_init();
		curl_setopt( $ch,CURLOPT_URL, 'https://android.googleapis.com/gcm/send' );
		curl_setopt( $ch,CURLOPT_POST, true );
		curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
		$result = curl_exec($ch );
		curl_close( $ch );
		echo $result; 		
		
		echo json_encode( $fields );
	}
	
	/**
	* sends blackberry notifications 
	*
	* @param array list of valid blackberry tokens
	* @param string notification text content
	* @return 
	*/
	public function sendBlackberry($device_tokens, $sNotif){	
		echo $sNotif.'<br />';
		$addresses = '';
		foreach ($device_tokens as $value) {
			$addresses .= '<address address-value="' . str_replace('bb-', '', $value) . '"/>';
		}
		
		$appid = $this->conf->blackberry['appid'];
		$password = $this->conf->blackberry['password'];		
		$messageid = microtime(true);
		$deliverbefore = gmdate('Y-m-d\TH:i:s\Z', strtotime('+5 minutes'));		
		
		// prep the bundle		
		$data = '--mPsbVQo0a68eIL3OAxnm'. "\r\n" .
		'Content-Type: application/xml; charset=UTF-8' . "\r\n\r\n" .
		'<?xml version="1.0"?>
		<!DOCTYPE pap PUBLIC "-//WAPFORUM//DTD PAP 2.1//EN" "http://www.openmobilealliance.org/tech/DTD/pap_2.1.dtd">
		<pap>
		<push-message push-id="' . $messageid . '" deliver-before-timestamp="' . $deliverbefore . '" source-reference="' . $appid . '">'
		. $addresses .
		'<quality-of-service delivery-method="unconfirmed"/>
		</push-message>
		</pap>' . "\r\n" .
		'--mPsbVQo0a68eIL3OAxnm' . "\r\n" .
		'Content-Type: text/plain' . "\r\n" .
		'Push-Message-ID: ' . $messageid . "\r\n\r\n" .
		stripslashes($sNotif) . "\r\n" .
		'--mPsbVQo0a68eIL3OAxnm--' . "\n\r";
		
		echo htmlentities($data);
		
		$headers = array("Content-Type: multipart/related; boundary=mPsbVQo0a68eIL3OAxnm; type=application/xml", "Accept: text/html, image/gif, image/jpeg, *; q=.2, */*; q=.2", "Connection: keep-alive");
		
		$ch = curl_init();
		curl_setopt( $ch,CURLOPT_URL, $this->conf->blackberry['url']);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt( $ch,CURLOPT_POST, true );
		curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
		curl_setopt($ch, CURLOPT_USERAGENT, "SAA push application");
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, $appid.':'.$password);
		curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt( $ch,CURLOPT_POSTFIELDS, $data);
		$result = curl_exec($ch );
		curl_close( $ch );
		var_dump($result); 
	}	
	
	/**
	* send device independant notifications 
	*
	* @param string semi-colon(;) separated list of tokens
	* @param string notification text content
	* @return 
	*/
	public function send($device_tokens, $sNotif){	
		// blackberry
		$aBlackberryTokens = $this->getBlackberryTokens($device_tokens);
		$this->sendBlackberry($aBlackberryTokens, $sNotif);
		
		// ios
		$aIosTokens = $this->getIosTokens($device_tokens);
		$this->sendIos($aIosTokens, $sNotif);
		
		// android
		$aAndroidTokens = $this->getAndroidTokens($device_tokens);
		$this->sendAndroid($aAndroidTokens, $sNotif);
	}
}
?>
