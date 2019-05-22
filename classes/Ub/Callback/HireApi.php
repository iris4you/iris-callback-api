<?php
class UbCallbackHireApi implements UbCallbackAction {
	function execute($userId, $object, $userbot, $message) {
		$chatId = UbUtil::getChatId($userId, $object, $userbot, $message);

		if (!$chatId) {
			UbUtil::echoError('no chat bind', UB_ERROR_NO_CHAT);
			return;
		}

		$price = $object['price'];
		if ($price < 5) {
			UbUtil::echoError('not enougth money', 5);
		}

		echo json_encode(['response' => 'ok', 'days' => intval(7*$price/5.)], JSON_UNESCAPED_UNICODE);
	}

}