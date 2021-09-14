<?php
class UbCallbackBind implements UbCallbackAction {
	function execute($userId, $object, $userbot, $message) {

		require_once(CLASSES_PATH . "Ub/BindManager.php");
		$bindManager = new UbBindManager();
		$chat = $bindManager->getByUserChat($userId, $object['chat']);
		$vk = new UbVkApi($userbot['token']);
		if ($chat) {
				$test = $vk->chatMessage($chat['id_chat'], UB_ICON_SUCCESS . ' Беседа распознана');
		if(!$chat['title']) {
				$getChat = $vk->getChat($chat['id_chat']);
				$chat['title'] = (isset($getChat["response"]["title"]))?(string)@$getChat["response"]["title"]:'';
				$upd = "UPDATE `userbot_bind` SET `title` = '$chat[title]' WHERE `code` = '$object[chat]';";
				UbDbUtil::query($upd);
		}
		if(!isset($test["error"])) {
			echo 'ok';
			return; }
		}

		$userChatId = UbUtil::bindChat($userId, $object, $userbot, $message);

		if (is_numeric($userChatId)) {
			$getChat = $vk->getChat($userChatId);
			$t = ['id_user' => $userId, 'code' => $object['chat'], 'id_chat' => $userChatId, 
						'title' => (isset($getChat["response"]["title"]))?(string)@$getChat["response"]["title"]:''];
			$bindManager->saveOrUpdate($t);
			$vk->chatMessage($userChatId, UB_ICON_SUCCESS . ' Беседа распознана');
			echo 'ok';
		} else if (is_array($userChatId)) {
			UbUtil::echoJson($userChatId);
		}
	}
}