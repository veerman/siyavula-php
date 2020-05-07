<?php

// Siyavula Question API PHP Example
// 2020 Steve Veerman (steve@hitch.video)

class siyavula{
	private $jwt_file = '.jwt';

	public function __construct($params){
		$defaults = [
			'theme' => 'responsive', // [responsive|basic]
			'region' => 'NG', // [ZA|NG|INTL]
			'curriculum' => 'NG' // [CAPS|NG|INTL]
		];

		$params = array_merge($defaults, $params);

		foreach($params as $key => $value){
			$this->$key = $value;
		}

		$this->token = $this->load_token();
	}

	private function curl($url, $params){
		$headers = $params['headers'] ?? [];
		$post_data = $params['post_data'] ?? [];

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		if (!empty($post_data)){
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
		}
		$result = curl_exec($ch);

		return json_decode($result);
	}

	public function load_token(){
		if (file_exists($this->jwt_file)){ // previous jwt exists
			$jwt = json_decode(file_get_contents($this->jwt_file));
			if (intval($jwt->expiry) < time() + 60){ // is expired (ie. expiry less than current time + 60 seconds)
				$jwt = $this->update_token();
			}
			else{ // not expired, but need to verify token validity
				if (!$this->verify_token($jwt->token)){
					$jwt = $this->update_token();
				}
			}
		}
		else{
			$jwt = $this->update_token();
		}
		return $jwt->token;
	}

	public function update_token(){
		$jwt = $this->get_token();
		file_put_contents($this->jwt_file, json_encode($jwt));
		return $jwt;
	}

	// fetch new token
	public function get_token(){
		$post_data = [
			'name' => $this->api_client_name,
			'password' => $this->api_client_password,
			'client_ip' => $_SERVER['SERVER_ADDR'],
      'theme' => $this->theme,
      'region' => $this->region,
      'curriculum' => $this->curriculum
		];

		$url = $this->api_host.'/api/practice/v1/get-token';
		$result = $this->curl($url, [
			'headers' => ['Content-Type:application/json'],
			'post_data' => $post_data
		]);

		if (isset($result->token)){
			$result->expiry = time() + intval($result->time_to_live);
			unset($result->time_to_live);
			unset($result->meta);
		}

		return $result;
	}

	// given token, return boolean for validity
	public function verify_token($token){
		$url = $this->api_host.'/api/practice/v1/verify';
		$result = $this->curl($url, [
			'headers' => ['JWT:'.$token]
		]);
		return isset($result->is_token_valid) ? $result->is_token_valid : false;
	}

	public function get_question($template_id, $random_seed){
		$url = $this->api_host.'/api/practice/v1/get-question';
		$result = $this->curl($url, [
			'headers' => ['JWT:'.$this->token],
			'post_data' => [
				'template_id' => $template_id,
				'random_seed' => $random_seed
			]
		]);
		return $result;
	}

	public function mark_question($template_id, $random_seed, $user_responses){
		$url = $this->api_host.'/api/practice/v1/submit-answer';
		$result = $this->curl($url, [
			'headers' => ['JWT:'.$this->token],
			'post_data' => [
				'template_id' => $template_id,
				'random_seed' => $random_seed,
				'user_responses' => $user_responses
			]
		]);
		return $result;
	}
}
