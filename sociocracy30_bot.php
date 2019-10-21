<?php

echo "a"; ob_flush(); flush();

require_once __DIR__ . '/vendor/autoload.php';

include(__DIR__ . "/config.php");
require_once("telegram_functions.php");
require_once(__DIR__ . "/graphql_protopia.class.php");

use Jose\Object\JWK;
use Jose\Factory\JWSFactory;

session_id("sociocracy30bot" . $data["message"]["from"]["id"]);
session_start();

$graphql_protopia = new graphql_protopia($ecosystem_addr, $ecosystem_client_id, $ecosystem_client_url, $ecosystem_client_secret, "telegram", $data["message"]["from"]["id"]);
if(!$graphql_protopia->ecosystem_user_token)
{
	$user = $graphql_protopia->protopia_mutation("registerUser", "_id", ["input: UserInput" => [
		"name" => $data["message"]["from"]["first_name"],
		"family_name" => $data["message"]["from"]["last_name"],
		"telegram_id" => $data["message"]["from"]["id"],
	]]);
	$graphql_protopia = new graphql_protopia($ecosystem_addr, $ecosystem_client_id, $ecosystem_client_url, $ecosystem_client_secret, "telegram", $data["message"]["from"]["id"]);
}

$chat_object = $graphql_protopia->protopia_query("getChatByExternal", "_id", ["input:ExternalInput" => [
	"external_id" => $data["message"]["chat"]["id"],
	"external_system" => "telegram",
	"external_type" => $data["message"]["chat"]["id"] == $data["message"]["from"]["id"] ? "personal_chat" : "group_chat",
]]);
if (!$chat_object)
{
	$chat_object = $graphql_protopia->protopia_mutation("changeChat", "_id", ["input: ChatInput" => [
		"title" => $data["message"]["chat"]["title"]
		"external_id" => $data["message"]["chat"]["id"],
		"external_system" => "telegram",
		"external_type" => $data["message"]["chat"]["id"] == $data["message"]["from"]["id"] ? "personal_chat" : "group_chat",
	]]);
}

if (preg_match("#^\/add_proposal (.*)$#s", $data["message"]["text"], $matches))
{
	
	
	$proposal = $graphql_protopia->protopia_mutation("changeMyProposal", "_id", ["input: ProposalInput" => [
		"title" => $matches[1],
		"chat_id" => $chat_object["_id"],
	]]);
	
    answer("Предложение добавлено!");
}
elseif (preg_match("#^\/token$#s", $data["message"]["text"], $matches))
{


    answer($graphql_protopia->ecosystem_user_token);
}
elseif (preg_match("#^\/vote_menu$#s", $data["message"]["text"], $matches))
{
	$_SESSION = [];
	
	$proposals = $graphql_protopia->protopia_query("getProposals", "_id title");
	foreach($proposals as &$proposal)
	{
		$proposal["_id"] = "vote_type|" . $proposal["_id"];
	}

	answer_one("Укажите предложение", null, null, generate_lister($proposals));
}
elseif (preg_match("#^vote_type\|(.*)$#s", $data["callback_query"]["data"], $matches))
{
	
	
	$proposal = $graphql_protopia->protopia_query("getProposal", "_id
		title
		author {name telegram_id}
		votes {date type author {name telegram_id}}
	", ["id:ID!" => $matches[1]]);
	$text = "";
	$text .= "{$proposal["title"]} ([{$proposal["author"]["name"]}](tg://user?id={$proposal["author"]["telegram_id"]}))\n";
	foreach($proposal["votes"] as $vote)
	{
		$vote["date"] = date("Y-m-d H:i:s", strtotime($vote["date"]));
		$text .= "----[{$vote["author"]["name"]}](tg://user?id={$vote["author"]["telegram_id"]}): {$vote["type"]} ({$vote["date"]})\n";
	}
	$text .= "\n";
	
	edit_message_text("Выберите ответ\n" . $text, generate_lister([
		["_id" => "vote_send|{$matches[1]}|yes", "title" => "Да"],
		["_id" => "vote_send|{$matches[1]}|no", "title" => "Нет"],
		["_id" => "vote_send|{$matches[1]}|doubt", "title" => "Сомнение"],
		["_id" => "vote_menu", "title" => "Другое предложение"],
	]), "markdown");
}
elseif (preg_match("#^vote_send\|(.*)\|(.*)$#s", $data["callback_query"]["data"], $matches))
{
	
	
	$graphql_protopia->protopia_mutation("voteProposal", "_id", [
		"proposal_id:ID" => $matches[1],
		"type:String" => $matches[2],
	]);

	$proposal = $graphql_protopia->protopia_query("getProposal", "_id
		title
		author {name telegram_id}
		votes {date type author {name telegram_id}}
	", ["id:ID!" => $matches[1]]);
	$text = "";
	$text .= "{$proposal["title"]} ([{$proposal["author"]["name"]}](tg://user?id={$proposal["author"]["telegram_id"]}))\n";
	foreach($proposal["votes"] as $vote)
	{
		$vote["date"] = date("Y-m-d H:i:s", strtotime($vote["date"]));
		$text .= "----[{$vote["author"]["name"]}](tg://user?id={$vote["author"]["telegram_id"]}): {$vote["type"]} ({$vote["date"]})\n";
	}
	$text .= "\n";
	edit_message_text("Голос учтен!\nВыберите ответ\n" . $text, generate_lister([
		["_id" => "vote_send|{$matches[1]}|yes", "title" => "Да"],
		["_id" => "vote_send|{$matches[1]}|no", "title" => "Нет"],
		["_id" => "vote_send|{$matches[1]}|doubt", "title" => "Сомнение"],
		["_id" => "vote_menu", "title" => "Другое предложение"],
	]), "markdown");
	
	if ($matches[2] == "doubt")
	{
		answer("[{$data["message"]["from"]["first_name"]} {$data["message"]["from"]["last_name"]}](tg://user?id={$data["message"]["from"]["id"]}), не забудьте написать, в чем ваше сомнение", null, "markdown");
	}
	if ($matches[2] == "no")
	{
		answer("[{$data["message"]["from"]["first_name"]} {$data["message"]["from"]["last_name"]}](tg://user?id={$data["message"]["from"]["id"]}), не забудьте написать, в чем ваше возражение", null, "markdown");
	}
}
elseif (preg_match("#^vote_menu$#s", $data["callback_query"]["data"], $matches))
{
	$_SESSION = [];
	
	$proposals = $graphql_protopia->protopia_query("getProposalsForChat", "_id title", ["chat_id:ID!" => $chat_object["_id"]]);
	foreach($proposals as &$proposal)
	{
		$proposal["_id"] = "vote_type|" . $proposal["_id"];
	}

	edit_message_text("Укажите предложение", generate_lister($proposals));
}

function generate_lister ($total) {
    $list = array();
    for ($i=0;$i<count($total); $i++) {
    $list["inline_keyboard"][$i][0] = array("text"=>"{$total[$i]["title"]}", "callback_data"=>"{$total[$i]["_id"]}");
    }
	return $list;
}

function edit_message_buttons ($reply_markup = null, $chat_id = null, $message_id = null) {
	global $data;
	if (!$chat_id)
	{
			if (!$data["message"]["chat"]["id"]) {
			$chat_id = $data["callback_query"]["message"]["chat"]["id"];
			} else {
			$chat_id = $data["message"]["chat"]["id"];
			}
	}
		if (!$message_id) 
		{
			if (!$data["message"]["message_id"]) {
			$message_id = $data["callback_query"]["message"]["message_id"];
			} else {
			$chat_id = $data["message"]["message_id"];
			}
		}
	$answer_data = array(
	"chat_id" => $chat_id,
	"message_id" => $message_id,
	);
	if ($reply_markup)
	{
	$answer_data["reply_markup"] = json_encode($reply_markup);
	}
	$ch = curl_init();
		global $bot_token;
	curl_setopt($ch, CURLOPT_URL,"https://api.telegram.org/bot{$bot_token}/editMessageReplyMarkup");
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS,
		http_build_query($answer_data)
	  );
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$server_output = curl_exec ($ch);
	curl_close ($ch);
}