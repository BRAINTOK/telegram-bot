<?php

function str_split_unicode($str, $l = 0) {
    if ($l > 0) {
        $ret = array();
        $len = mb_strlen($str, "UTF-8");
        for ($i = 0; $i < $len; $i += $l) {
            $ret[] = mb_substr($str, $i, $l, "UTF-8");
        }
        return $ret;
    }
    return preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY);
}

function answer($text, $chat_id = null, $parse_mode = null)
{
	if (mb_strlen($text) > 3000)
	{
		$texts = str_split_unicode($text, 3000);
		foreach ($texts as $text_part)
		{
			answer_one($text_part, $chat_id, $parse_mode);
		}
	}
	else
	{
		answer_one($text, $chat_id, $parse_mode);
	}
}

function answer_reply($text, $chat_id = null, $answer_message_id = null)
{
	global $data;
	if (!$chat_id)
	{
		$chat_id = $data["message"]["chat"]["id"];
	}
	$answer_data = array(
	'chat_id' => $chat_id,
	"text" => $text,
	//"parse_mode" => "Markdown",
	);
	if ($answer_message_id)
	{
		$answer_data["reply_to_message_id"] = $answer_message_id;
	}
	if ($parse_mode)
	{
		$answer_data["parse_mode"] = $parse_mode;
	}
	if (is_array($buttons))
	{
		$answer_data["reply_markup"]["inline_keyboard"][0] = array();
		foreach ($buttons as $command => $title)
		{
			$answer_data["reply_markup"]["inline_keyboard"][0][] = array("text" => $title, "callback_data" => $command);
		}
		$answer_data["reply_markup"] = json_encode($answer_data["reply_markup"]);
		//$answer_data["text"] = $answer_data["reply_markup"];
		//unset($answer_data["reply_markup"]);
	}
	$ch = curl_init();
    global $bot_token;
	curl_setopt($ch, CURLOPT_URL,"https://api.telegram.org/bot{$bot_token}/sendMessage");
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS,
				http_build_query($answer_data)
			);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$server_output = curl_exec ($ch);
	curl_close ($ch);
}

function answer_one($text, $chat_id = null, $buttons = null, $parse_mode = null)
{
	global $data;
	if (!$chat_id)
	{
			if (!$data["message"]["chat"]["id"]) {
			$chat_id = $data["callback_query"]["message"]["chat"]["id"];
			} else {
			$chat_id = $data["message"]["chat"]["id"];
			}
	}
	$answer_data = array(
	"chat_id" => $chat_id,
	"text" => $text,
	//"parse_mode" => "Markdown",
	);
	if ($parse_mode)
	{
	$answer_data["parse_mode"] = $parse_mode;
	}
	if ($buttons)
	{
	$answer_data["reply_markup"] = json_encode($buttons);
	}
	$ch = curl_init();
	global $bot_token;
	curl_setopt($ch, CURLOPT_URL,"https://api.telegram.org/bot{$bot_token}/sendMessage");
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS,
		http_build_query($answer_data)
	  );
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$server_output = curl_exec ($ch);
	curl_close ($ch);
}

function answer_image($url, $chat_id = null, $parse_mode = null, $buttons = null)
{
	global $data;
	if (!$chat_id)
	{
		$chat_id = $data["message"]["chat"]["id"];
	}
	$answer_data = array(
	'chat_id' => $chat_id,
	"photo" => $url,
	//"parse_mode" => "Markdown",
	);
	if ($parse_mode)
	{
		$answer_data["parse_mode"] = $parse_mode;
	}
	if (is_array($buttons))
	{
		$answer_data["reply_markup"]["inline_keyboard"][0] = array();
		foreach ($buttons as $command => $title)
		{
			$answer_data["reply_markup"]["inline_keyboard"][0][] = array("text" => $title, "callback_data" => $command);
		}
		$answer_data["reply_markup"] = json_encode($answer_data["reply_markup"]);
		//$answer_data["text"] = $answer_data["reply_markup"];
		//unset($answer_data["reply_markup"]);
	}
	$ch = curl_init();
    global $bot_token;
	curl_setopt($ch, CURLOPT_URL,"https://api.telegram.org/bot{$bot_token}/sendPhoto");
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS,
				http_build_query($answer_data)
			);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$server_output = curl_exec ($ch);
	curl_close ($ch);
}

function answer_document($url, $chat_id = null, $parse_mode = null, $buttons = null)
{
	global $data;
	if (!$chat_id)
	{
		$chat_id = $data["message"]["chat"]["id"];
	}
	$answer_data = array(
	'chat_id' => $chat_id,
	"document" => $url,
	//"parse_mode" => "Markdown",
	);
	if ($parse_mode)
	{
		$answer_data["parse_mode"] = $parse_mode;
	}
	if (is_array($buttons))
	{
		$answer_data["reply_markup"]["inline_keyboard"][0] = array();
		foreach ($buttons as $command => $title)
		{
			$answer_data["reply_markup"]["inline_keyboard"][0][] = array("text" => $title, "callback_data" => $command);
		}
		$answer_data["reply_markup"] = json_encode($answer_data["reply_markup"]);
		//$answer_data["text"] = $answer_data["reply_markup"];
		//unset($answer_data["reply_markup"]);
	}
	$ch = curl_init();
    global $bot_token;
	curl_setopt($ch, CURLOPT_URL,"https://api.telegram.org/bot{$bot_token}/sendDocument");
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS,
				http_build_query($answer_data)
			);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$server_output = curl_exec ($ch);
//	answer($server_output);
	curl_close ($ch);
}

function answer_voice($file_id, $chat_id = null, $parse_mode = null, $buttons = null)
{
	global $data;
	if (!$chat_id)
	{
		$chat_id = $data["message"]["chat"]["id"];
	}
	$answer_data = array(
	'chat_id' => $chat_id,
	"voice" => $file_id,
	//"parse_mode" => "Markdown",
	);
	if ($parse_mode)
	{
		$answer_data["parse_mode"] = $parse_mode;
	}
	if (is_array($buttons))
	{
		$answer_data["reply_markup"]["inline_keyboard"][0] = array();
		foreach ($buttons as $command => $title)
		{
			$answer_data["reply_markup"]["inline_keyboard"][0][] = array("text" => $title, "callback_data" => $command);
		}
		$answer_data["reply_markup"] = json_encode($answer_data["reply_markup"]);
		//$answer_data["text"] = $answer_data["reply_markup"];
		//unset($answer_data["reply_markup"]);
	}
	$ch = curl_init();
    global $bot_token;
	curl_setopt($ch, CURLOPT_URL,"https://api.telegram.org/bot{$bot_token}/sendVoice");
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS,
				http_build_query($answer_data)
			);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$server_output = curl_exec ($ch);
	curl_close ($ch);
}

function getChatMember($chat_id = null, $user_id = null)
{
	global $data;
	if (!$chat_id)
	{
		$chat_id = $data["message"]["chat"]["id"];
	}
	if (!$user_id)
	{
		$user_id = $data["message"]["from"]["id"];
	}

	$post_data = array(
		'chat_id' => $chat_id,
		"user_id" => $user_id,
	);

	$ch = curl_init();
    global $bot_token;
	curl_setopt($ch, CURLOPT_URL,"https://api.telegram.org/bot{$bot_token}/getChatMember");
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS,
				http_build_query($post_data)
			);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$server_output = curl_exec ($ch);
	curl_close ($ch);
	return json_decode($server_output, true)["result"];
}

function is_admin($chat_id = null, $user_id = null)
{
	$status = getChatMember($chat_id, $user_id)["status"];
	return $status == "creator" || $status == "administrator";
}