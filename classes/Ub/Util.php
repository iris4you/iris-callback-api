<?php

define('UB_ICON_WARN', "⚠️");
define('UB_ICON_SUCCESS', "✅");
define('UB_ICON_SUCCESS_OFF', "❎");
define('UB_ICON_NOTICE', "📝");
define('UB_ICON_INFO', "🗓");
define('UB_ICON_DANGER', "📛");
define('UB_ICON_COMMENT', "💬");
define('UB_ICON_CONFIG', "⚙️");
define('UB_ICON_CATALOG', "🗂");
define('UB_ICON_STATS', "📊");

class UbUtil {

	public static function json(array $array) {
		return json_encode($array, JSON_UNESCAPED_UNICODE);
	}

	public static function echoJson(array $array) {
		echo json_encode($array, JSON_UNESCAPED_UNICODE);
	}

	public static function errorVkResponse(array $error) {
		return self::buildErrorResponse('vk_error', $error['error_msg'], $error['error_code']);
	}

	public static function echoErrorVkResponse($error) {
		self::echoJson(self::errorVkResponse($error));
	}

	public static function buildErrorResponse($type, $message, $code) {
		return ['response' => $type, 'error_message' => $message, 'error_code' => $code];
	}

	public static function echoError($message, $code = -1) {
		echo json_encode(self::buildErrorResponse('error', $message, $code), JSON_UNESCAPED_UNICODE);
	}

	public static function error($message, $code = -1) {
		return json_encode(self::buildErrorResponse('error', $message, $code), JSON_UNESCAPED_UNICODE);
	}

	public static function getVkErrorText($error) {
		$errorCode = $error['error_code'];
		$eMessage = $error['error_msg'];
		$errorMessage = null;
		switch ($errorCode) {
			case VK_BOT_ERROR_ACCESS_DENIED :
				if (strpos($eMessage, 'already in') !== false)
					$errorMessage = 'Пользователь уже в беседе';
				else if (strpos($eMessage, 'can\'t add this') !== false)
					$errorMessage = 'Не могу добавить. Скорее всего пользователь не в моих друзьях.';
				else if (strpos($eMessage, 'user already left') !== false)
					$errorMessage = 'Не могу добавить. Пользователь сам вышел.';
				else
					$errorMessage = ' Ошибка ВК: ' . $eMessage . ' (' . $errorCode . ')';
			break;
			case VK_BOT_ERROR_CANT_DELETE_FOR_ALL_USERS :
				$errorMessage = 'Невозможно удалить для всех пользователей.' . PHP_EOL . 'Возможно удаляющий не имеет прав администратора или удаляемые сообщения принадлежат администратору.'
				;
			break;
			default : $errorMessage = ' Ошибка ВК: ' . $eMessage . ' (' . $errorCode . ')'; break;
		}
		return $errorMessage;
	}


	public static function getChatId($userId, $object, $userbot, $message) {
		require_once(CLASSES_PATH . "Ub/BindManager.php");
		$bManager = new UbBindManager();
		$chat = $bManager->getByUserChat($userId, $object['chat']);

		if (!$chat) {
			if ($message) {
				$res = UbUtil::bindChat($userId, $object, $userbot, $message);
				if (is_numeric($res))
					return $res;
			}
		} else
			return $chat['id_chat'];
		return null;
	}

	public static function bindChat($userId, $object, $userbot, $message) {
		if (!$message)
			$message = $object;
		require_once(CLASSES_PATH . "Ub/BindManager.php");
		$bindManager = new UbBindManager();
		$chat = $bindManager->getByUserChat($userId, $object['chat']);
		if ($chat) {
			return $chat['id_chat'];
		}
		$vk = new UbVkApi($userbot['token']);
		$result = $vk->messagesGetConversations();
		if (isset($result['error'])) {
			return UbUtil::errorVkResponse($result['error']);
		}
		$result = $result['response'];
		$goodChats = self::findChats($result['items'], $message);
		$userChatId = 0;
		if ($goodChats['sure']) {
			$userChatId = UbVkApi::peer2ChatId($goodChats['items'][0]['peer_id']);
		} else {
			foreach ($goodChats['items'] as $chat) {
				$result = $vk->messagesGetHistory($chat['peer_id'], 0, 100);
				if (isset($result['error'])) {
					return UbUtil::errorVkResponse($result['error']);
				}
				foreach ($result['response']['items'] as $item) {
					if (self::isMessagesEqual($item, $message)) {
						$userChatId = UbVkApi::peer2ChatId($item['peer_id']);
					}
				}
				if ($userChatId)
					break;
			}
		}

		if ($userChatId) {
			$t = ['id_user' => $userId, 'code' => $object['chat'], 'id_chat' => $userChatId];
			$bindManager->saveOrUpdate($t);
			return $userChatId;
		} else {
			return UbUtil::error('no chat id', UB_ERROR_CANT_BIND_CHAT);
		}
	}

	private static function findChats($items, $vkMessage) {
		$goodChats = [];
		foreach ($items as $item) {
			$lm = $item['last_message'];
			$sLocal = $lm['conversation_message_id'];
			if ($sLocal > $vkMessage['conversation_message_id'] - 300 && $sLocal < $vkMessage['conversation_message_id'] + 300) {
				if (self::isMessagesEqual($vkMessage, $lm)/*$vkMessage['from_id'] == $lm['from_id'] && $vkMessage['conversation_message_id'] == $sLocal*//* && $lm['text'] == $vkMessage['text']*/)
					return ['sure' => 1, 'items' => [$item['last_message']]];
				$goodChats[] = $item['last_message'];
			}
		}
		return ['sure' => 0, 'items' => $goodChats];
	}

	private static function isMessagesEqual($m1, $m2) {
		return ($m1['from_id'] == $m2['from_id'] && $m1['conversation_message_id'] == $m2['conversation_message_id']/* && $m1['text'] == $m2['text']*/);
	}
}