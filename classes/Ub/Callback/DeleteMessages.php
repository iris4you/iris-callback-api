<?php
class UbCallbackDeleteMessages implements UbCallbackAction {
	function execute($userId, $object, $userbot, $message) {

		$chatId = UbUtil::getChatId($userId, $object, $userbot, $message);
		$localIds = $object['local_ids'];
		$vk = new UbVkApi($userbot['token']);

		if (!count($localIds)) {
			$vk->chatMessage($chatId, UB_ICON_WARN . ' Не выбраны сообщения для удаления');
			echo 'ok';
			return;
		}

		$messages = $vk->messagesGetByConversationMessageId(UbVkApi::chat2PeerId($chatId), $localIds);

		if (isset($messages['error'])) {
			$error = UbUtil::getVkErrorText($messages['error']);
			$res = $vk->chatMessage($chatId, UB_ICON_WARN . ' ' . $error);
			if (isset($res['error']))
				UbUtil::echoErrorVkResponse($res['error']);
			else
				echo 'ok';
			return;
		}

		$messages = $messages['response']['items'];
		$ids = [];
		foreach ($messages as $m)
			$ids[] = $m['id'];

		if (!count($ids)) {
			$vk->chatMessage($chatId, UB_ICON_WARN . ' Не нашёл сообщений для удаления');
			echo 'ok';
			return;
		}

		$res = $vk->messagesDelete($ids, true);

		if (isset($res['error'])) {
			$error = UbUtil::getVkErrorText($res['error']);
			$vk->chatMessage($chatId, UB_ICON_WARN . ' ' . $error);
			echo 'ok';
			return;
		}
		$vk->chatMessage($chatId, UB_ICON_SUCCESS . ' Сообщения удалены');
		echo 'ok';
	}
}