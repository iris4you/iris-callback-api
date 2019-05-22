<?php

define('VK_API_VERSION', '5.92');

define('VK_BOT_ERROR_UNKNOWN', 1);
define('VK_BOT_ERROR_APP_IS_OFF', 2);
define('VK_BOT_ERROR_UNKNOWN_METHOD', 3);
define('VK_BOT_ERROR_WRONG_TOKEN', 4);
define('VK_BOT_ERROR_AUTH_FAILED', 5);
define('VK_BOT_ERROR_TOO_MANY_REQUESTS', 6);
define('VK_BOT_ERROR_NO_RIGHTS_FOR_ACTION', 7);
define('VK_BOT_ERROR_WRONG_REQUEST', 8);
define('VK_BOT_ERROR_ONE_TYPE_ACTIONS', 9);
define('VK_BOT_ERROR_INTERNAL', 10);
define('VK_BOT_ERROR_TEST_MODE_APP_MUST_BE_OFF', 11);
define('VK_BOT_ERROR_CAPTCHA', 14);
define('VK_BOT_ERROR_ACCESS_DENIED', 15);
define('VK_BOT_ERROR_HTTPS_REQUIRED', 16);
define('VK_BOT_ERROR_VALIDATION_REQUIRED', 17);
define('VK_BOT_ERROR_PAGE_DELETED', 18);
define('VK_BOT_ERROR_ACTION_DENIED_FOR_STANDALONE', 20);
define('VK_BOT_ERROR_ACTION_ALLOWED_ONLY_FOR_STANDALONE', 21);
define('VK_BOT_ERROR_METHOD_IS_OFF', 23);
define('VK_BOT_ERROR_USER_CONFIRMATION_REQUIRED', 24);
define('VK_BOT_ERROR_GROUP_TOKEN_IS_INVALID', 27);
define('VK_BOT_ERROR_APP_TOKEN_IS_INVALID', 28);
define('VK_BOT_ERROR_DATA_REQUEST_LIMIT', 29);
define('VK_BOT_ERROR_PROFILE_IS_PRIVATE', 30);
define('VK_BOT_ERROR_ONE_OF_PARAMETERS_IS_WRONG', 100);
define('VK_BOT_ERROR_WRONG_APP_API', 101);
define('VK_BOT_ERROR_WRONG_USER_ID', 113);
define('VK_BOT_ERROR_WRONG_TIMESTAMP', 150);
define('VK_BOT_ERROR_USER_NOT_FOUND', 177);
define('VK_BOT_ERROR_ALBUM_ACCESS_DENIED', 200);
define('VK_BOT_ERROR_AUDIO_ACCESS_DENIED', 201);
define('VK_BOT_ERROR_GROUP_ACCESS_DENIED', 203);
define('VK_BOT_ERROR_ALBUM_IS_FULL', 300);
define('VK_BOT_ERROR_ACTION_IS_DENIED', 500);
define('VK_BOT_ERROR_NO_RIGHTS_FOR_ADV_CABINET', 600);
define('VK_BOT_ERROR_IN_ADV_CABINET', 603);

define('VK_BOT_ERROR_CANT_SEND_TO_USER_IN_BLACKLIST', 900);
define('VK_BOT_ERROR_CANT_SEND_WITHOUT_PERMISSION', 901);
define('VK_BOT_ERROR_CANT_SEND_TO_USER_PRIVACY_SETTINGS', 902);
define('VK_BOT_ERROR_KEYBOARD_FORMAT_IS_INVALID', 911);
define('VK_BOT_ERROR_THIS_IS_CHATBOT_FEATURE', 912);
define('VK_BOT_ERROR_TOO_MANY_FORWARDED_MESSAGES', 913);
define('VK_BOT_ERROR_MESSAGE_IS_TOO_LONG', 914);
define('VK_BOT_ERROR_NO_ACCESS_TO_THIS_CHAT', 917);
define('VK_BOT_ERROR_CANT_FORWARD_SELECTED_MESSAGES', 921);
define('VK_BOT_ERROR_CANT_DELETE_FOR_ALL_USERS', 924);
define('VK_BOT_ERROR_USER_NOT_FOUND_IN_CHAT', 935);
define('VK_BOT_ERROR_CONTACT_NOT_FOUND', 936);

class UbVkApi {

	var $token;

	public function __construct($token) {
		$this->token = $token;
	}

	public function messagesSearch($q, $peerId = null, $count = 10) {
		$params = ['q' => $q, 'count' => $count];
		if ($peerId)
			$params['peer_id'] = $peerId;

		return $this->vkRequest('messages.search', http_build_query($params));
	}

	public function messagesAddChatUser($userId, $chatId) {
		return $this->vkRequest('messages.addChatUser', 'chat_id=' . $chatId . '&user_id=' . $userId);
	}

	function chatMessage($chatId, $message, $options = []) {
		return $this->messagesSend(self::chat2PeerId($chatId), $message, $options);
	}

	function messagesSend($peerId, $message, $options = []) {
		$add = '';
		if ($options)
			foreach ($options as $k => $val)
				$add .= '&' . urlencode($k) . '=' . urlencode($val);

		$res = $this->vkRequest('messages.send', 'random_id=' . mt_rand(0, 2000000000) . '&peer_id=' . urlencode($peerId) . "&message=".urlencode($message) . $add);
		return $res;
	}

	function messagesGetByConversationMessageId($peerId, $conversationMessageIds) {
		if (is_array($conversationMessageIds))
			$conversationMessageIds = implode(',', $conversationMessageIds);
		$options = ['peer_id' => intval($peerId), 'conversation_message_ids' => $conversationMessageIds];
		return $this->vkRequest('messages.getByConversationMessageId', $options);
	}

	function messagesDelete($messageIds, $deleteForAll = false, $isSpam = false) {
		$options = ['message_ids' => implode(',', $messageIds)];
		if ($deleteForAll)
			$options['delete_for_all'] = $deleteForAll;
		if ($isSpam)
			$options['spam'] = 1;

		return $this->vkRequest('messages.delete', http_build_query($options));
	}


	function messagesGetConversations($amount = 200, $filter = 'all') {
		return $this->vkRequest('messages.getConversations', ['count' => intval($amount), 'filter' => $filter]);
	}

	public function messagesGetHistory($peerId, $offset, $count, $options = []) {
		if (is_array($options))
			$options = http_build_query($options);
		return $this->vkRequest('messages.getHistory', 'peer_id=' . $peerId . '&offset=' . $offset . '&count=' . $count . '&' . $options);
	}


	public function usersGet($users = null, $fields = null) {
		$options = [];
		if ($users && count($users))
			$options[] = 'user_ids=' . implode(',', $users);
		if ($fields)
			$options[] = 'fields=' . $fields;
		return $this->vkRequest('users.get', implode('&', $options));
	}






	public function vkRequest($method, $body) {
		if (is_array($body)) {
			$body['v'] = VK_API_VERSION;
			$body['access_token'] = $this->token;
		} else {
			$body .= "&v=" . VK_API_VERSION . "&access_token=" . $this->token;
		}
		$res = $this->curl("https://api.vk.com/method/" . $method, $body);
		return $res;
	}

	function curl($url, $data = null, $headers = null) {
		$response = $this->curl2($url, $data, $headers);
		return json_decode($response, true);
	}

	function curl2($url, $data = null, $headers = null) {
		$cUrl = curl_init( $url );
		curl_setopt($cUrl, CURLOPT_URL, $url);
		curl_setopt($cUrl,CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($cUrl,CURLOPT_TIMEOUT, 2);
		curl_setopt($cUrl,CURLOPT_FOLLOWLOCATION, true);
		if ($data) {
			curl_setopt($cUrl, CURLOPT_POST, 1);
			curl_setopt($cUrl, CURLOPT_POSTFIELDS, $data);
		}

		if ($headers) {
			curl_setopt($cUrl, CURLOPT_HTTPHEADER, $headers);
		}

		$response = curl_exec( $cUrl );
		curl_close( $cUrl );

		return $response;
	}

	static function chat2PeerId($chatId) {
		return 2000000000 + $chatId;
	}

	static function peer2ChatId($peerId) {
		return $peerId - 2000000000;
	}

	static function isChat($peerId) {
		return $peerId >= 2000000000;
	}

	static function isGroup($peerId) {
		return $peerId < 0;
	}

	public static function isUser($peerId) {
		return $peerId > 0 && $peerId < 2000000000;
	}

	static function group2PeerId($groupId) {
		return -$groupId;
	}

	static function peer2GroupId($peerId) {
		return -$peerId;
	}

	static function user2PeerId($id) {
		return $id;
	}

	static function peerId2User($id) {
		return $id;
	}




}

