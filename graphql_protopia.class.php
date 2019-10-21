<?php

require_once __DIR__ . '/vendor/autoload.php';

use Jose\Object\JWK;
use Jose\Factory\JWSFactory;

class graphql_protopia 
{

	var $ecosystem_token = "";
	var $ecosystem_user_token = "";
	var $ecosystem_client_id;
	var $ecosystem_client_url;
	var $ecosystem_client_secret;
	var $external_user_id;
	var $external_system;
	var $ecosystem_addr;
	var $ecosystem_client_auth;
	var $assertion_jwt;
	var $log = "";
	
	function __construct($ecosystem_addr, $ecosystem_client_id, $ecosystem_client_url, $ecosystem_client_secret, $external_system = null, $external_user_id = null)
	{
		$this->ecosystem_addr = $ecosystem_addr;
		$this->ecosystem_client_id = $ecosystem_client_id;
		$this->ecosystem_client_secret = $ecosystem_client_secret;
		$this->ecosystem_client_url = $ecosystem_client_url;
		$this->external_system = $external_system;
		$this->external_user_id = $external_user_id;
		
		$this->protopia_auth();
	}
	
	function enable_client_auth()
	{
		$this->ecosystem_client_auth = true;
	}
	
	function disable_client_auth()
	{
		$this->ecosystem_client_auth = false;
	}

	function protopia_query($method, $results, $params_variables = null)
	{
		$variables = [];
		$results = $results ? "{" . $results . "}" : $results;
		if ($params_variables)
		{
			foreach ($params_variables as $key => $value)
			{
				$key = trim($key);
				$key = explode(":", $key);
				$key[0] = trim($key[0]);
				$key[1] = trim($key[1]);
				$variables[$key[0]] = $value;
				$params1[] = "$" . $key[0] . ":" . $key[1];
				$params2[] = $key[0] . ": $" . $key[0];
			}
			$params1 = implode(", ", $params1);
			$params2 = implode(", ", $params2);
		
			$query = <<<EOF
	query ({$params1}) {
		{$method} ({$params2}) 
			{$results}
	}
EOF;
		}
		else
		{
			$query = <<<EOF
	query {
		{$method} 
			{$results}
	}
EOF;
		}
		//echo $query;
		return $this->protopia_graphql($query, $variables);
	}

	function protopia_mutation($method, $results, $params_variables = null)
	{
		$variables = [];
		foreach ($params_variables as $key => $value)
		{
			$key = trim($key);
			$key = explode(":", $key);
			$key[0] = trim($key[0]);
			$key[1] = trim($key[1]);
			$variables[$key[0]] = $value;
			$params1[] = "$" . $key[0] . ":" . $key[1];
			$params2[] = $key[0] . ": $" . $key[0];
		}
		$params1 = implode(", ", $params1);
		$params2 = implode(", ", $params2);
		
		$results = $results ? "{" . $results . "}" : $results;
		
		$query = <<<EOF
	mutation ({$params1}) {
		{$method} ({$params2}) 
			{$results}
	}
EOF;
		return $this->protopia_graphql($query, $variables);
	}

	function protopia_auth()
	{
		$assertion_token = array(
			"sub" => $this->ecosystem_client_id,
			"aud" => [$this->ecosystem_client_url, $this->ecosystem_client_url],
			"iss" => $this->ecosystem_client_url,
			"iat" => time(),
			"exp" => time() + 3600,
		);

		$key = new JWK([
			'kty' => 'oct',
			'k'   => $this->ecosystem_client_secret,
		]);

		$this->assertion_jwt = JWSFactory::createJWSToCompactJSON(
			$assertion_token,                      // The payload or claims to sign
			$key,                         // The key used to sign
			['alg' => 'HS256', 
			"kid" => $this->ecosystem_client_id
			]
		);

		$token_result = $this->protopia_mutation("token", "access_token", ["input: TokenInput!" => 
			array(
				"grant_type" => "jwt-bearer",
				"assertion" => $this->assertion_jwt,
			)
		]);
		$this->ecosystem_token = $token_result["access_token"];

		if ($this->external_user_id)
		{
			$key2 = new JWK([
				'kty' => 'oct',
				'k'   => $this->ecosystem_client_secret,
			]);
			$id_token = array(
				"sub" => $this->external_user_id,
				"aud" => [$this->ecosystem_client_url, $this->ecosystem_client_url],
				"iss" => $this->ecosystem_client_url,
				"iat" => time(),
				"exp" => time() + 3600,
				"acr" => $this->external_system,
			);
			
			$id_jwt = JWSFactory::createJWSToCompactJSON(
				$id_token,                      // The payload or claims to sign
				$key2,                         // The key used to sign
				['alg' => 'HS256', "kid" => $this->ecosystem_client_id]
			);
			
			$auth_result = $this->protopia_mutation("authorize", "auth_req_id", ["input: AuthorizeInput!" => 
				array(
					"scope" => ["user"],
					"id_token_hint" => $id_jwt,
					"assertion" => $this->assertion_jwt,
				)
			]);
			
			$token_user_result = $this->protopia_mutation("token", "access_token", ["input: TokenInput!" => 
				array(
					"grant_type" => "ciba",
					"auth_req_id" => $auth_result["auth_req_id"],
					"assertion" => $this->assertion_jwt,
				)
			]);

			$this->ecosystem_user_token = $token_user_result["access_token"];
		}		
	}

	function protopia_graphql($query, $variables = [])
	{
		return $this->graphql_query($this->ecosystem_addr, $query, $variables, $this->ecosystem_client_auth || !$this->ecosystem_user_token ? $this->ecosystem_token : $this->ecosystem_user_token);
	}

	function graphql_query($endpoint, $query, $variables = [], $token = null)
	{
		$headers = ['Content-Type: application/json', 'User-Agent: Dunglas\'s minimal GraphQL client'];
		if ($token) {
			$headers[] = "Authorization: Bearer $token";
		}
		else {
			//$headers[] = "Authorization: Basic {$ecosystem_client_secret}";
		}
		//answer(print_r($headers, true));
		//answer(print_r(['query' => $query, 'variables' => $variables], true));
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$endpoint);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['query' => $query, 'variables' => $variables]));
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$graphql_data = curl_exec($ch);
		curl_close ($ch);
		$result = json_decode($graphql_data, true);
		
		if (isset($result["errors"]) || !isset($result["data"])) {
			if ($result["errors"][0]["extensions"]["code"] != "INTERNAL_SERVER_ERROR")
			{
				//throw new Exception($result);
			}
			echo "<pre>";
			$error_text = "";
			foreach ($result["errors"] as $error)
			{
				$error_text .= $error["message"] . "\n";
			}
			$error_text .= print_r($result, true);
			$this->log($error_text);
			$this->log(json_encode(['query' => $query, 'variables' => $variables]));
			echo "</pre>";
			file_put_contents(__DIR__ . "/log.txt", print_r(json_encode(['query' => $query, 'variables' => $variables]) . "\n\n", true), FILE_APPEND);
			file_put_contents(__DIR__ . "/log.txt", print_r($error_text, true) . "\n\n", FILE_APPEND);
			file_put_contents(__DIR__ . "/log.txt", "Authorization: Bearer $token" . "\n\n", FILE_APPEND);
		}
		$result = $result["data"];
		return reset($result);
	}
	
	function log($text)
	{
		$this->log .= "{$text}\n";
	}
	
	function get_log()
	{
		return $this->log;
	}
}