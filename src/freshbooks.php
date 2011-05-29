<?php
/**
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	This is class written to work with Freshbooks OAuth methods. It 
 *	authorizes a user and will let you make requests to Freshbooks API 
 *	methods with OAuth headers.
 *
 * 	Last Edit: Oct. 1st, 2010
 * 	@author Mike Helmick (mikeh@ydekproductions.com)
 * 	@version 2.0
 *
**/

class Freshbooks
{
	protected $oauth_consumer_key;
	protected $oauth_consumer_secret;
	protected $oauth_callback;
	protected $oauth_token;
	protected $oauth_token_secret;
	
	// $subdomain is the subdomain of the users Freshbooks account
	// (i.e. http://example.freshbooks.com; "example" being the subdomain).
	protected $subdomain;
	
	function __construct($oauth_consumer_key, $oauth_consumer_secret, $oauth_callback = NULL, $subdomain = NULL, $oauth_token = NULL, $oauth_token_secret = NULL)
	{
		$this->oauth_consumer_key = $oauth_consumer_key;
		$this->oauth_consumer_secret = $oauth_consumer_secret;
		
		// If a subdomain is not supplied, it will be set to the user who owns the application's subdomain,
		// This would be good if you are only authenticating yourself, and not other users.
		if($subdomain === NULL)
			$this->subdomain = $this->oauth_consumer_key;
		else
			$this->subdomain = $subdomain;
		
		if($oauth_callback)		$this->oauth_callback = $oauth_callback;
		if($oauth_token)		$this->oauth_token = $oauth_token;
		if($oauth_token_secret) $this->oauth_token_secret = $oauth_token_secret;
	}
	
	
	public function apiUrl() { return 'https://'.$this->subdomain.'.freshbooks.com/api/2.1/xml-in'; }
	public function accessTokenUrl()  { return 'https://'.$this->subdomain.'.freshbooks.com/oauth/oauth_access.php'; }
	public function authorizeUrl()    { return 'https://'.$this->subdomain.'.freshbooks.com/oauth/oauth_authorize.php'; }
	public function requestTokenUrl() { return 'https://'.$this->subdomain.'.freshbooks.com/oauth/oauth_request.php'; }
	
	
	public function createNonce($length)
	{
		$characters = '0123456789abcdefghijklmnopqrstuvwxyz';
		$nonce = '';    
		for ($p = 0; $p < $length; $p++) {
		    $nonce .= $characters[mt_rand(0, strlen($characters)-1)];
		}
		return $nonce;
	}
	
	
	private function urlEncodeParams($params)
	{
		$postdata ='';
		foreach($params as $key => $value)
		{
		    if(!empty($postdata)) $postdata .= '&';
		    $postdata .= $key.'=';
		    $postdata .= urlencode($value);
		}
		return $postdata;
	}
	
	
	private function getRequestToken()
	{
		$params = array(
			'oauth_consumer_key' => $this->oauth_consumer_key,
			'oauth_callback' => $this->oauth_callback,
			'oauth_signature' => $this->oauth_consumer_secret. '&',
			'oauth_signature_method' => 'PLAINTEXT',
			'oauth_version' => '1.0',
			'oauth_timestamp' => time(),
			'oauth_nonce' => $this->createNonce(20),
		);
		
		return $this->OAuthRequest($this->requestTokenUrl(), $params);
	}
	
	
	public function getLoginUrl()
	{
		$token = $this->getRequestToken();
		$loginUrl = $this->authorizeUrl().'?oauth_token='.$token['oauth_token'];
		return $loginUrl;
	}
	
	
	public function getAccessToken($token, $verifier)
	{
		$params = array(
			'oauth_consumer_key' => $this->oauth_consumer_key,
			'oauth_token' => $token,
			'oauth_verifier' => $verifier,
			'oauth_signature' => $this->oauth_consumer_secret. '&',
			'oauth_signature_method' => 'PLAINTEXT',
			'oauth_version' => '1.0',
			'oauth_timestamp' => time(),
			'oauth_nonce' => $this->createNonce(20),
		);
		
		return $this->OAuthRequest($this->accessTokenUrl(), $params);
	}
	
	
	private function OAuthRequest($url, $params = array())
	{
		// URL encode our params
		$params = $this->urlEncodeParams($params);

		// send the request to FreshBooks
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$res= curl_exec($ch);

		// parse the request
		$r = array();
		parse_str($res, $r);
		
		return $r;
	}
	
	
	private function buildAuthHeader()
	{
		$params = array(
			'oauth_version' => '1.0',
			'oauth_consumer_key' => $this->oauth_consumer_key,
			'oauth_token' => $this->oauth_token,
			'oauth_timestamp' => time(),
			'oauth_nonce' => $this->createNonce(20),
			'oauth_signature_method' => 'PLAINTEXT',
			'oauth_signature' => $this->oauth_consumer_secret. '&' .$this->oauth_token_secret
		);

		$auth = 'OAuth realm=""';
		foreach($params as $kk => $vv)
		{
			$auth .= ','.$kk . '="' . urlencode($vv) . '"';
		}
		
		return $auth;
	}
	
	
	public function post($request)
	{
		$headers = array(
		            'Authorization: '.$this->buildAuthHeader().'',
		            'Content-Type: application/xml; charset=UTF-8',
		            'Accept: application/xml; charset=UTF-8',
		            'User-Agent: My-Freshbooks-App-1.0');

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->apiUrl());
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $request);

		$response = curl_exec($ch);
		curl_close($ch);
		$response = new SimpleXMLElement($response);
		
		if($response->attributes()->status == 'ok')
			return $response;
		elseif($response->attributes()->status == 'fail' || $response->error)
			throw new FreshbooksAPIError($response->error);
		else
			throw new FreshbooksError('Oops, something went wrong. :(');
	}
	
}

class FreshbooksError extends Exception {}
class FreshbooksAPIError extends FreshbooksError {}