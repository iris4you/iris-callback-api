<?php
class UbCallbackPing implements UbCallbackAction {
	function execute($userId, $object, $userbot, $message) {
		@header('Content-type: application/json; charset=utf-8', true);
		echo json_encode(['response' => 'ok'], JSON_UNESCAPED_UNICODE);
	}

}