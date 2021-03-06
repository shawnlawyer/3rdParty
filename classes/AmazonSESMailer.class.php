<?php
require_once('AWSSDKforPHP/sdk.class.php');
require_once('AWSSDKforPHP/services/ses.class.php');
/*
.---------------------------------------------------------------------------.
|  Software: Amazon SES Mailer - PHP email class for Amazon SES             |
|   Version: 1.0                                                            |
|   Contact: dev@geoloqi.com                                                |
|      Info: https://github.com/geoloqi/Amazon-SES-Mailer-PHP               |
| ------------------------------------------------------------------------- |
|     Admin: Aaron Parecki                                                  |
|   Authors: Aaron Parecki aaronpk@geoloqi.com                              |
| Copyright (c) 2011, Geoloqi.com                                           |
| ------------------------------------------------------------------------- |
|   License: Distributed under the Lesser General Public License (LGPL)     |
|            http://www.gnu.org/copyleft/lesser.html                        |
| This program is distributed in the hope that it will be useful - WITHOUT  |
| ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or     |
| FITNESS FOR A PARTICULAR PURPOSE.                                         |
'---------------------------------------------------------------------------'
*/


require_once('PHPMailerLite.class.php');
class AmazonSESMailer extends PHPMailerLite {

	public $AWSAccessKeyId;
	public $AWSSecretKey;
	public $AWSRegion;

	public $Mailer = 'amazonses';

	public function __construct($key, $secret, $region='us-east-1', $exceptions=FALSE) {
		$this->AWSAccessKeyID = $key;
		$this->AWSSecretKey = $secret;
		$this->AWSRegion = $region;
		parent::__construct($exceptions);
	}

	/**
	* Sends mail using Amazon SES.
	* @param string $header The message headers
	* @param string $body The message body
	* @access protected
	* @return bool
	*/
	protected function AmazonSESSend($header, $body) {
		$ses = new AmazonSES(array(
			"key" => $this->AWSAccessKeyID, 
			"secret" => $this->AWSSecretKey,
			"region" => $this->AWSRegion,
			));

		if ($this->SingleTo === true) {
			foreach ($this->SingleToArray as $key => $val) {
				$response = $ses->send_raw_email(array(
					'Data' => base64_encode($header . "\n" . $body)
				), array(
					'Source' => $this->From,
					'Destinations' => $val
				));

				// implement call back function if it exists
				$isSent = ($response->isOK()) ? 1 : 0;
				$this->doCallback($isSent,$val,$this->cc,$this->bcc,$this->Subject,$body);
				if(!$isSent) {
					throw new phpmailerException('Error Sending via Amazon SES [Type: '.$response->body->Error->Type.", Code: ".$response->body->Error->Code.", Message: ".$response->body->Error->Message."]", self::STOP_CRITICAL);
				}
			}
		} else {
			$response = $ses->send_raw_email(array(
				'Data' => base64_encode($header . "\n" . $body)
			), array(
				'Source' => $this->From,
				'Destinations' => $this->to
			));
			// implement call back function if it exists
			$isSent = ($response->isOK()) ? 1 : 0;
			$this->doCallback($isSent,$this->to,$this->cc,$this->bcc,$this->Subject,$body);
			if(!$isSent) {
				throw new phpmailerException('Error Sending via Amazon SES [Type: '.$response->body->Error->Type.", Code: ".$response->body->Error->Code.", Message: ".$response->body->Error->Message."]", self::STOP_CRITICAL);
			}
		}
		return true;
	}

	protected function GetSendFunction($mailer) {
		switch($mailer) {
			case 'amazonses':
				return 'AmazonSESSend';
			default:
				return parent::GetSendFunction($mailer);
		}
	}	

}
