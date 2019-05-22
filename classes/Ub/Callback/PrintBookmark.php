<?php
class UbCallbackPrintBookmark implements UbCallbackAction {
	function execute($userId, $object, $userbot, $message) {

		$chatId = UbUtil::getChatId($userId, $object, $userbot, $message);
		if (!$chatId) {
			UbUtil::echoError('no chat bind', UB_ERROR_NO_CHAT);
			return;
		}

		$peerId = UbVkApi::chat2PeerId($chatId);
		$vk = new UbVkApi($userbot['token']);
		$message = $vk->messagesGetByConversationMessageId($peerId, [$object['conversation_message_id']]);
		if (isset($message['error'])) {
			$e = $message['error'];
			$res = $vk->messagesSend($peerId, UB_ICON_WARN . ' Ошибка ВК: ' . $e['error_msg'] . ' (' . $e['error_code'] . ')');
			return;
		}
		$messages = $message['response']['items'];

		if (sizeof($messages)) {
			$resMessage = '🔼 Перейти к закладке «' . $object['description'] . '»';
			$message = $vk->messagesSend($peerId, $resMessage, ['reply_to' => $messages[0]['id']]);
			if (isset($message['error'])) { // ошибка при отправлении в ВК
				$e = $message['error'];

				$msg = UB_ICON_WARN . " Закладка недоступна. Удаляю.";
				switch ($e['error_code']) {
					case 100 : $msg .= "\n Скорее всего сменился юзербот (100)"; break;
					default : $msg .= "\nОшибка ВК: " . $e['error_msg'] . ' (' . $e['error_code'] . ')';
				}
				$res = $vk->messagesSend($peerId, $msg);
			}
		}
		echo 'ok';
	}

}