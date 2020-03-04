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

	public function addBotToChat($bot_id, $chatId, $bp = false) {
			if (!$bp) { return; }
		$method = 'bot.addBotToChat';
		$body['v'] = VK_API_VERSION;
		$body['access_token'] = $bp;
		$body['peer_id'] = self::chat2PeerId($chatId);
		$body['bot_id'] = $bot_id;
		$res = $this->curl_proxy("https://api.vk.com/method/".$method,$body);
		return $res;
	}

	public function AddFriendsById($id = false) {
                                 $id = (int) $id;
	    if ($id <= 0) {
					return 0;
	    }

				$get = $this->vkRequest('friends.areFriends', 'user_ids='.$id);
				$are = (isset($get['response']))?(int)@$get["response"][0]["friend_status"]:0;
				$user= $this->usersGet($id, 'deactivated'); /* получаем не деактивирован ли */
				$dog = (isset($user["response"]["deactivated"]))?$user["response"]["deactivated"]:0;


	  if ($dog) { /* Если пользователь деактивирован (забанен или удалён) */
	  if ($are) { $del = $vk->vkRequest('friends.delete', 'user_id='.$id); }
	    return 0;
	  } elseif($are == 3 || $are == 1) {
	    return $are;
	  }

				$add = $this->vkRequest('friends.add', 'user_id='.$id);

	    if (isset($add["response"])) {
					return $add["response"];
	    }

	    if (isset($add["error"])) {
					return $add["error"];
	    }

	    return false;
	}

	public function areFriendsById($id = false) {
                                 $id = (int) $id;
	    if ($id <= 0) {
					return 0;
	    }

				$get = $this->vkRequest('friends.areFriends', 'user_ids='.$id);
				$are = (isset($get['response']))?(int)@$get["response"][0]["friend_status"]:0;
				$user= $this->usersGet($id, 'deactivated'); /* получаем не деактивирован ли */
				$dog = (isset($user["response"]["deactivated"]))?$user["response"]["deactivated"]:0;

	  if ($dog) { /* Если пользователь деактивирован (забанен или удалён) */
	  if ($are) { $del = $vk->vkRequest('friends.delete', 'user_id='.$id); }
	    return 0;
	  }

	  if ($are == 2) {
				$add = $this->vkRequest('friends.add', 'user_id='.$id);
	  if ((int)@$add["response"] == 2) { $are = 3; }
	  }

	    return $are;

	}

	public function areFriendsByIds($users) {
	    $ids = (is_array($ids)) ? implode(',', $users) : $users;
	    $res = $this->vkRequest('friends.areFriends', 'user_ids='.$ids);
	    return $res;
	}

	public function cancelAllRequests() {
		$res = $this->vkRequest('friends.getRequests', 'out=1');
		$count = (int)@$res["response"]["count"]; // кол-во
		if ($count == 0) { return 0; } else { $count = 0; }
		$arr = $res['response']['items'];//Выбираем только ID пользователей
	  foreach ($arr as $id) {
		         $del = $this->vkRequest('friends.delete', 'user_id='.$id);
		         $are = $this->areFriendsById($id);
	  if ($are == 0) { $count++; }
		            sleep($count); }
		return $count;
	}

	public function confirmAllFriends() {
		$res = $this->vkRequest('friends.getRequests', 'need_viewed=1');
		$count = (int)@$res["response"]["count"]; // кол-во
		if ($count == 0) { return 0; } else { $count = 0; }
		$arr = $res['response']['items'];//Выбираем только ID пользователей
	  foreach ($arr as $id) {
				$are = $this->AddFriendsById($id);
	  if ($are == 2) { $count++; }
		            sleep($count); }
		return $count;
	}

	public function messagesSearch($q, $peerId = null, $count = 10) {
		$params = ['q' => $q, 'count' => $count];
		if ($peerId)
			$params['peer_id'] = $peerId;

		return $this->vkRequest('messages.search', http_build_query($params));
	}

	public function messagesAddChatUser($userId, $chatId, $bp = false) {
			if ($userId < 0) {
		return $this->addBotToChat($userId, $chatId, $bp); }
		$add = $this->AddFriendsById($userId); // пытаться дружить с приглашаемым
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

	public function GetFwdMessagesByConversationMessageId($peerId = 0, $conversation_message_id = 0) {
				$fwd = Array(); /* массив. всегда. чтоб count($fwd) >= 0 */
		if ($peerId == 0 || $conversation_message_id == 0) { return $fwd; }
		if ($peerId < 2000000000) $peerId+=2000000000;
				$message = $this->messagesGetByConversationMessageId($peerId, $conversation_message_id);
		if (isset($message['error'])) { return $fwd; }

		if ((int)@$message["response"]["count"] == 0) { return $fwd; }

		if (isset($message["response"]["items"][0]["fwd_messages"])) {
				$fwd = $message["response"]["items"][0]["fwd_messages"]; }
				return $fwd;
	}

	public function messagesGetInviteLink($peerId) {
		if ($peerId < 2000000000) $peerId+=2000000000;
		$res = $this->vkRequest('messages.getInviteLink', 'peer_id=' . $peerId);
		if (isset($res["response"]["link"])) return $res["response"]["link"];
		if (isset($res["error"]["error_msg"])) return $res["error"]["error_msg"];
		return $res;
	}

	public function joinChatByInviteLink($link) {
		$res = $this->vkRequest('messages.joinChatByInviteLink', 'link=' . $link);
		if (isset($res["response"]["chat_id"])) return $res["response"]["chat_id"];
		if (isset($res["error"]["error_msg"])) return $res["error"]["error_msg"];
		return $res;
	}

	public function getChat($chatId, $fields = null) {
		$options = [];
		if (is_numeric($chatId)) {
			$options[] = 'chat_id=' . $chatId; } else {
			$options[] = 'chat_ids=' . ((is_array($chatId)) ? implode(',', $chatId) : $chatId); }
		if ($fields)
			$options[] = 'fields=' . $fields;
		return $this->vkRequest('messages.getChat', implode('&', $options));
	}

	public function usersGet($users = null, $fields = null) {
		$options = [];
		if ($users) {
			$options[] = 'user_ids=' . ((is_array($users)) ? implode(',', $users) : $users); }
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

	function curl($url, $data = null, $headers = null, $proxy = null) {
		$response = $this->curl2($url, $data, $headers, $proxy);
		return json_decode($response, true);
	}

	function curl_proxy($url, $data = null, $headers = null, $proxy = true) {
		$response = $this->curl2($url, $data, $headers, $proxy);
		return json_decode($response, true);
	}

	function curl2($url, $data = null, $headers = null, $proxy = null) {
		$cUrl = curl_init( $url );
		curl_setopt($cUrl, CURLOPT_URL, $url);
		curl_setopt($cUrl,CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($cUrl,CURLOPT_TIMEOUT, 2);
		curl_setopt($cUrl,CURLOPT_FOLLOWLOCATION, true);
		if ($proxy) { /* тут можно задать прокси, тип */
#		curl_setopt($cUrl,CURLOPT_PROXY, "тут_прокси_и_:порт"); 
#		curl_setopt($cUrl,CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5); 
		}
		curl_setopt($cUrl,CURLOPT_FAILONERROR, true); 
		curl_setopt($cUrl,CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($cUrl,CURLOPT_SSL_VERIFYHOST, 0);
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

