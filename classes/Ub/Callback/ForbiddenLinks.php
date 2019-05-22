<?php
class UbCallbackForbiddenLinks implements UbCallbackAction {
	function execute($userId, $object, $userbot, $message) {

		$chatId = UbUtil::getChatId($userId, $object, $userbot, $message);

		if (!$chatId) {
			UbUtil::echoJson(UbUtil::buildErrorResponse('error', 'no chat bind', UB_ERROR_NO_CHAT));
			return;
		}

		$localIds = $object['local_ids'];

		if (!count($localIds)) {
			echo 'ok';
			return;
		}

		$vk = new UbVkApi($userbot['token']);
		$messages = $vk->messagesGetByConversationMessageId(UbVkApi::chat2PeerId($chatId), $localIds);
		if (isset($messages['error'])) {
			if ($message['error']['error_code'] == VK_BOT_ERROR_ACCESS_DENIED && strpos($message['error']['error_msg'], 'can\'t add this') !== false) {
				$vk->chatMessage($chatId, UB_ICON_WARN . ' Не могу добавить пользователя @id' . $object['user_id'] . '. Вероятнее всего он не в друзьях.');
			} else {
				$error = UbUtil::getVkErrorText($messages['error']);
				$vk->chatMessage($chatId, UB_ICON_WARN . ' ' . $error);
			}
			echo 'ok';
			//UbUtil::echoErrorVkResponse($messages['error']);
			return;
		}

		$messages = $messages['response']['items'];
		$ids = [];
		foreach ($messages as $m)
			$ids[] = $m['id'];

		if (!count($ids)) {
			//UbUtil::echoError('nothing to delete', 11);
			return;
		}

		$res = $vk->messagesDelete($ids, true);
		if (isset($res['error'])) {
			$error = UbUtil::getVkErrorText($res['error']);
			$vk->chatMessage($chatId, UB_ICON_WARN . ' ' . $error);
			echo 'ok';
			//UbUtil::echoErrorVkResponse($res['error']);
			return;
		}
		echo 'ok';
	}

}