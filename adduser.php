<?php
##### ВНИМАНИЕ
// Этот файл для того, чтобы можно было в форме заносить пользователей с токенами
// Спрячьте этот файл в папку, где требуется особый доступ, а затем уберите блокирующий "return" (он строчкой ниже)
//return;
ini_set("display_errors" , 1);
error_reporting(E_ALL);
ini_set('display_startup_errors', 1);
header('Cache-Control: no-store, no-cache, must-revalidate', true);
header('Content-Type: text/html; charset=utf-8', true);
header('X-UA-Compatible: IE=edge', true); /* 4 MSIE */
echo '<?xml version="1.0" encoding="utf-8"?><!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="ru"><head>
<meta http-equiv="X-UA-Compatible" content="IE=edge;chrome=1" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="initial-scale=1,width=device-width" />
<style type="text/css">
	html, body, table, * { margin: 0 auto; }
	body, table { background: transparent; margin: 0 auto; max-width: 800px; }
	html { background: transparent; margin: 0; padding: 0; text-align: center; }
</style></head><body style="margin:0px auto;max-width:800px;min-widht:100px;">
';
if (isset($_POST['token'])) {
	require_once("classes/base.php");
	require_once(CLASSES_PATH . "Ub/DbUtil.php");
	require_once(CLASSES_PATH . 'Ub/VkApi.php');
	$token = @$_POST['token'];
	$btoken = (isset($_POST['btoken'])?@$_POST['btoken']:@$_POST['token']);
	$secret = @$_POST['secret'];
	$bptime = (int)time();
	$vk = new UbVkApi($token);
	$me = $vk->usersGet();
	if (isset($me['error'])) {
		echo '<h1>Ошибище</h1>';
		echo '<p>' . $me['error']['error_msg'] . ' (' . $me['error']['error_code'] . ')</p>';
		return;
	}
	$userId = $me['response'][0]['id'];
	UbDbUtil::query('INSERT INTO userbot_data SET id_user = ' . UbDbUtil::intVal($userId) . ', token = ' . UbDbUtil::stringVal($token)
		 . ', btoken = ' . UbDbUtil::stringVal($btoken)
		 . ', bptime = ' . UbDbUtil::intVal($bptime)
		 . ', secret = ' . UbDbUtil::stringVal($_POST['secret'])
		 . ' ON DUPLICATE KEY UPDATE token = VALUES(token)'
			. ', btoken = VALUES(btoken)'
			. ', bptime = VALUES(bptime)'
			. ', secret = VALUES(secret)'
	);
	$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/callback.php";

	echo 'Добавлено<br />'
	. 'Теперь в лс бота введите "+api ' . htmlspecialchars($_POST['secret']) . ' ' . $actual_link . '"'
	;
	return;
}
?>
<form action="" method="post">
<table>
<tr>
	<td>Токен</td>
	<td><input type="text" name="token" value="" placeholder="Токен" style="max-width:200px">
	<a href="https://oauth.vk.com/authorize?client_id=2685278&display=mobile&scope=notify,friends,photos,audio,video,docs,status,notes,pages,wall,groups,messages,offline,notifications,email&redirect_uri=https://api.vk.com/blank.html&response_type=token&v=5.78" 
	  target="_blank" rel="external">»</a>
	</td>
</tr>
<tr>
	<td>БП Токен</td>
	<td><input type="text" name="btoken" value="" placeholder="Токен" style="max-width:200px">
	<a href="https://oauth.vk.com/authorize?client_id=6441755&redirect_uri=https://api.vk.com/blank.html&display=mobile&response_type=token&revoke=1"
	  target="_blank" rel="external">»</a>
	</td>
</tr>
<tr>
	<td>Секретка</td>
	<td><input type="text" name="secret" value="" placeholder="Секретная фраза" style="max-width:200px">
	<u title="Придумай сам(а)...">?</u>
	</td>
</tr>
<tr>
	<td></td>
	<td><input type="submit" value="Добавить"></td>
</tr>
</table>
</form>
</body></html>
