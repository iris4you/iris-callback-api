<?php
set_time_limit(600000);
$last =(int) filemtime(__FILE__);
$time=time(); // поточний системний час;
$ago = $time - $last;

/*
 * это надо выполнить один раз. 
 * можно и вручную:
ALTER TABLE `userbot_data` ADD `a_add` ENUM( '0', '1' ) NOT NULL DEFAULT '0' AFTER `id_user` ,
ADD `a_del` ENUM( '0', '1' ) NOT NULL DEFAULT '0' AFTER `a_add`;
 * после чего можно ставить этот файл на крон.
 */
if ($ago >= 3600) {
require_once("classes/base.php");
require_once("classes/Ub/VkApi.php");
require_once("classes/Ub/DbUtil.php");

$query = "SELECT * FROM `userbot_data` WHERE `a_add` = '1' OR `a_del` = '1';";
$users = UbDbUtil::select($query);

foreach ($users as $user) {
		$vk = new UbVkApi($user['token']);
		$add=0;
		$del=0;

		if ($user['a_add']) { $add = $vk->confirmAllFriends(); }
		if ($user['a_del']) { $del = $vk->cancelAllRequests(); }

		echo "+$add; -$del<br />";
		
		if ($add > 0 || $del > 0) {
		touch(__FILE__,$time); }
		unset($user);
		unset($vk);
		}

		unset($users);
} else exit("//ok: $ago sec ago");