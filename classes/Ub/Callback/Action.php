<?php

interface UbCallbackAction {
	function execute($userId, $object, $userbot, $message);
}