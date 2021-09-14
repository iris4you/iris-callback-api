<?php

define('VK_API_VERSION', '5.131');

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

	private $token;
	private $login;
	private $passw;
	private $AppID=0;
	private $proxy=False;
	private $agent="Dalvik/2.1.0 (Linux; U; Android 8.1.0; SDK 27; armeabi-v7a; unknown Android SDK built for armeabi-v7a; en)";

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
		$this->proxy=True;
		$res = $this->curl("https://api.vk.com/method/".$method,$body);
		return $res;
	}

	public function messagesSearch($q, $peerId = null, $count = 10, $offset = 0) {
		$params = ['q' => $q, 'count' => (int)$count, 'offset' => (int)$offset];
		if ($peerId)
			$params['peer_id'] = (int)$peerId;

		return $this->vkRequest('messages.search', http_build_query($params));
	}

	public function messagesAddChatUser($userId, $chatId, $bp = false) {
			if ($userId < 0){ return $this->addBotToChat($userId, $chatId, $bp); }
		#$add = $this->AddFriendsById($userId); // пытаться дружить с приглашаемым (потом верну. мб.);
		return $this->vkRequest('messages.addChatUser', 'chat_id=' . (int)$chatId . '&user_id=' . (int)$userId);
	}

	public function messagesRemoveChatUser($chatId, $userId) {
		return $this->vkRequest('messages.removeChatUser', 'chat_id=' . (int)$chatId . '&member_id=' . $userId);
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
		$options = ['message_ids' => ((is_array($messageIds))? implode(',', $messageIds):$messageIds)];
		if ($deleteForAll)
			$options['delete_for_all'] = $deleteForAll;
		if ($isSpam)
			$options['spam'] = 1;

		return $this->vkRequest('messages.delete', http_build_query($options));
	}

	public function messagesPin($peerId, $message_id) {
		if ($peerId < 2000000000) $peerId+=2000000000;
		$res = $this->vkRequest('messages.pin', 'peer_id=' . (int) $peerId . "&message_id=" . (int) $message_id);
		return $res;
	}

	public function messagesUnPin($peerId) {
		if ($peerId < 2000000000) $peerId+=2000000000;
		$res = $this->vkRequest('messages.unpin', 'peer_id=' . (int) $peerId);
		return $res;
	}

	function messagesSetMemberRole($peerId, $member_id, $role = 'member') {
		if ($peerId < 2000000000) $peerId+=2000000000;
		$res = $this->vkRequest('messages.setMemberRole', 'peer_id=' . (int) $peerId . "&member_id=" . (int) $member_id . "&role=" . (string) $role);
		return $res;
	}

	function messagesGetConversations($amount = 200, $filter = 'all') {
		return $this->vkRequest('messages.getConversations', ['count' => intval($amount), 'filter' => $filter]);
	}

	public function messagesGetHistory($peerId, $offset, $count, $options = []) {
		if (is_array($options))
			$options = http_build_query($options);
		return $this->vkRequest('messages.getHistory', 'peer_id=' . $peerId . '&offset=' . $offset . '&count=' . $count . '&' . $options);
	}

	public function messagesGetInviteLink($peerId) {
		if ($peerId < 2000000000) $peerId+=2000000000;
		$res = $this->vkRequest('messages.getInviteLink', 'peer_id=' . $peerId);
		if (isset($res["response"]["link"])) return $res["response"]["link"];
		if (isset($res["error"]["error_msg"])) return $res["error"]["error_msg"];
		return '';
	}

	public function joinChatByInviteLink($link) {
		$res = $this->vkRequest('messages.joinChatByInviteLink', 'link=' . $link);
		if (isset($res["response"]["chat_id"])) return $res["response"]["chat_id"];
		if (isset($res["error"]["error_msg"])) return $res["error"]["error_msg"];
		return $res;
	}

	public function getChat($chatId, $fields = null) {
		$options = [];
			$options[] = (is_array($chatId))? 'chat_ids=' . implode(',', $chatId) : 'chat_id=' . (int)$chatId;
		if ($fields)
			$options[] = 'fields=' . $fields;
		return $this->vkRequest('messages.getChat', implode('&', $options));
	}

	public function getTime() {
			if (!$this->token) { return time(); }
			$getTime = $this->vkRequest('utils.getServerTime','');
			$time = (isset($getTime["response"])) ? $getTime["response"]:time();
		return $time;
	}

	public function usersGet($users = null, $fields = null) {
		$options = [];
		if ($users) {
			$options[] = 'user_ids=' . ((is_array($users)) ? implode(',', $users) : $users); }
		if ($fields)
			$options[] = 'fields=' . $fields;
		return $this->vkRequest('users.get', implode('&', $options));
	}

	function wallCreateComment($owner_id, $post_id, $message, $options = []) {
		$add = '';
		if ($options)
			foreach ($options as $k => $val)
				$add .= '&' . urlencode($k) . '=' . urlencode($val);

		$res = $this->vkRequest('wall.createComment', 'guid=' . mt_rand(0, 2000000000) . '&owner_id=' . urlencode($owner_id) . '&post_id=' . urlencode($post_id) . "&message=".urlencode($message) . $add);
		return $res;
	}

	function wallDeleteComment($owner_id = 0, $comment_id = 0) {
		$owner_id = (int) $owner_id;
		$comment_id = (int) $comment_id;

		if ($comment_id == 0 || $owner_id == 0) {
			return 0;
		}

		$res = $this->vkRequest('wall.deleteComment', 'guid=' . mt_rand(0, 2000000000) . '&owner_id=' . $owner_id . '&comment_id=' . $comment_id);
		return $res;
	}

	public function setCovidStatus($setCovidStatus, $ct = false) {
		if (!$ct) $ct = $this->token;
		$method = 'users.setCovidStatus';
		$body['v'] = '5.103';
		$body['access_token'] = $ct;
		$body['status_id'] = (int) $setCovidStatus;
		$this->proxy=True;
		$res = $this->curl("https://api.vk.com/method/".$method,$body);
		return $res;
	}

	public function onlinePrivacy($status, $mt = false) {
		//$status - nobody(оффлайн для всех), all(Отключения оффлайна), friends(оффлайн для всех, кроме друзей)
		if(!$mt) $mt = $this->token;
		$method = 'account.setPrivacy';
		$body = array(
		    'key' => 'online',
		    'value' => $status,
		    'access_token' => $mt,
		    'v'=> 5.103
		);
		$this->agent="VKAndroidApp/5.52-4543 (Android 8.1.0; SDK 27; armeabi-v7a; unknown Android SDK built for armeabi-v7a; en)";
		#$res = $this->curl_ME("https://api.vk.com/method/".$method,$body);
		$res = $this->curl("https://api.vk.com/method/".$method,$body);
		return $res;
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

	function curl_proxy($url, $data = null, $headers = null, $proxy = true) {
		$this->proxy=True;
		$response = $this->curl2($url, $data, $headers);
		return json_decode($response, true);
	}

	function curl2($url, $data = null, $headers = null) {
		$cUrl = curl_init( $url );
		curl_setopt($cUrl, CURLOPT_URL, $url);
		curl_setopt($cUrl, CURLOPT_TIMEOUT, 2);
		curl_setopt($cUrl, CURLOPT_FAILONERROR, true); 
		curl_setopt($cUrl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($cUrl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($cUrl, CURLOPT_SSL_VERIFYHOST, 0);
#		curl_setopt($cUrl,CURLOPT_FOLLOWLOCATION, true);

		/* тут можно задать прокси, тип */
#		curl_setopt($cUrl,CURLOPT_PROXY, "тут_прокси_и_:порт"); 
#		curl_setopt($cUrl,CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5); 

		if ($data) {
			curl_setopt($cUrl, CURLOPT_POST, 1);
			curl_setopt($cUrl, CURLOPT_POSTFIELDS, $data);
		}

		if ($headers) {
			curl_setopt($cUrl, CURLOPT_HEADER, 1);
			curl_setopt($cUrl, CURLOPT_HTTPHEADER, $headers);
		}

				$AGENT = False;
		if ($this->agent) { $AGENT = $this->agent; }
		if ($AGENT && (bool)@$AGENT != False && (string)@$AGENT!='') {
			curl_setopt($cUrl, CURLOPT_USERAGENT, (string)@$AGENT);
		}

		$response = curl_exec( $cUrl );
		curl_close( $cUrl );

		return $response;
	}

	function curl_ME($url, $data = null) {
		$ua = "VKAndroidApp/5.52-4543 (Android 8.1.0; SDK 27; armeabi-v7a; unknown Android SDK built for armeabi-v7a; en)";
		$this->agent = $ua;
		#response = $this->curl2($url, $data, $headers, $proxy, $ua);
		$response = $this->curl2($url, $data, $headers);
		return json_decode($response, true);
	}

	function passgen($len = 32) {
	$password = '';
	$small = 'abcdefghijklmnopqrstuvwxyz';
	$large = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$numbers = '1234567890';
		for ($i = 0; $i < $len; $i++) {
        switch (mt_rand(1, 3)) {
            case 3 :
                $password .= $large [mt_rand(0, 25)];
                break;
            case 2 :
                $password .= $small [mt_rand(0, 25)];
                break;
            case 1 :
                $password .= $numbers [mt_rand(0, 9)];
                break;
        }
	}
	return $password;
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

