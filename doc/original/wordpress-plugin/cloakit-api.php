<?php

class CloakitApi {
	protected $host = 'http://api.clofilter.com/';
	protected $version = 'v1';

	public function check($data = [])
	{
		return $this->request('/check', $data, 'post');
	}

	public function request($path, $data = [], $method = 'get')
	{
		$url = $this->host . $this->version . $path;

		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($method));
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, [
			'Content-Type: application/json',
			'Content-Length: ' . strlen(json_encode($data))
		]);

		$api = json_decode(curl_exec($curl));
		curl_close($curl);

		return $api;
	}
}
