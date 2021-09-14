<?php
class UbCallbackAddUser implements UbCallbackAction {
	function execute($userId, $object, $userbot, $message) {

		$chatId = UbUtil::getChatId($userId, $object, $userbot, $message);

		if (!$chatId) {
			UbUtil::echoJson(UbUtil::buildErrorResponse('error', 'no chat bind', UB_ERROR_NO_CHAT));
			return;
		}

		require_once(CLASSES_PATH . "Ub/VkApi.php");
		$vk = new UbVkApi($userbot['token']);
		$res = $vk->messagesAddChatUser($object['user_id'], $chatId, @$userbot['btoken']);
		$silent = (isset($object["silent"]))?(bool)$object["silent"]:false;

		if(!isset($res['error'])) {
			echo 'ok';
			return;
		}

		if (isset($res['error'])) {
			$peerId = UbVkApi::chat2PeerId($chatId);
			$error = UbUtil::getVkErrorText($res['error']);

			if ($error == 'Пользователь уже в беседе') {
			if(!$silent) {
			    $vk->messagesSend($peerId, UB_ICON_WARN . ' ' . $error); 
			}
			echo 'ok';
			return;
			}

			if(!$silent) {
			    $vk->messagesSend($peerId, UB_ICON_WARN . ' ' . $error); 
			}
			UbUtil::echoErrorVkResponse($res['error']);
			return;
		}
	}
}