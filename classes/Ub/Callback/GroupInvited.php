<?php
class UbCallbackGroupInvited implements UbCallbackAction {

	function closeConnection() {
		@ob_end_clean();
		@header("Connection: close");
		@ignore_user_abort(true);
		@ob_start();
		echo 'ok';
		$size = ob_get_length();
		@header("Content-Length: $size");
		@ob_end_flush(); // All output buffers must be flushed here
		@flush(); // Force output to client
	}

	function execute($userId, $object, $userbot, $message) {
		$vk = new UbVkApi($userbot['token']);
		$group_id = (int) $object["group_id"];
		$bot_id = ($group_id > 0) ? "-$group_id" : $group_id;
		#result = $vk->messagesGetConversations();
		$text = "Меня добавили";
		$result = $vk->messagesSearch($text, $peerId = null, $count = 10);
		if (isset($result['error'])) {
			return UbUtil::errorVkResponse($result['error']);
		}
		$userChatId = 0;
				foreach ($result['response']['items'] as $item) {
				$sLocal = $item['conversation_message_id'];
					if ($item['from_id'] == $bot_id && $sLocal > $message['conversation_message_id'] - 300 && $sLocal < $message['conversation_message_id'] + 300) {
						$userChatId = UbVkApi::peer2ChatId($item['peer_id']);
					}
				}

		if ($userChatId) {
			self::closeConnection(); // echo 'ok';
			$t = $vk->messagesSetMemberRole($userChatId, $bot_id, $role = 'admin');
			$vk->chatMessage($userChatId, '!связать');
			return $userChatId;
		} else {
			UbUtil::echoJson(UbUtil::buildErrorResponse('error', 'БЕДЫ С API', 0));
			return;
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
