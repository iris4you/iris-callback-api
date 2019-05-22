<?php
##### ВНИМАНИЕ
// Этот файл для того, чтобы можно было в форме заносить пользователей с токенами
// Спрячьте этот файл в папку, где требуется особый доступ, а затем уберите блокирующий "return" (он строчкой ниже)
//return;
ini_set("display_errors" , 1);
error_reporting(E_ALL);
ini_set('display_startup_errors', 1);

if (isset($_POST['token'])) {
	require_once("classes/base.php");
	require_once(CLASSES_PATH . "Ub/DbUtil.php");
	require_once(CLASSES_PATH . 'Ub/VkApi.php');
	$token = @$_POST['token'];
	$secret = @$_POST['secret'];
	$vk = new UbVkApi($token);
	$me = $vk->usersGet();
	if (isset($me['error'])) {
		echo '<h1>Ошибище</h1>';
		echo '<p>' . $me['error']['error_msg'] . ' (' . $me['error']['error_code'] . ')</p>';
		return;
	}
	$userId = $me['response'][0]['id'];
	UbDbUtil::query('INSERT INTO userbot_data SET id_user = ' . UbDbUtil::intVal($userId) . ', token = ' . UbDbUtil::stringVal($token)
		 . ', secret = ' . UbDbUtil::stringVal($_POST['secret'])
		 . ' ON DUPLICATE KEY UPDATE token = VALUES(token)'
			. ', secret = VALUES(secret)'
	);
	$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/callback.php";

	echo 'Добавлено<br>'
	. 'Теперь в лс бота введите "+api ' . htmlspecialchars($_POST['secret']) . ' ' . $actual_link . '"'
	;
	return;
}
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>
<body>
<form action="" method="post">
<table>
<!--<tr>
	<td>ID игрока</td>
	<td><input type="text" name="uid" value="" placeholder="ID игрока" style="width: 400px"></td>
</tr>-->
<tr>
	<td>Токен</td>
	<td><input type="text" name="token" value="" placeholder="Токен" style="width: 400px"></td>
</tr>
<tr>
	<td>Секретка</td>
	<td><input type="text" name="secret" value="" placeholder="Секретная фраза" style="width: 400px"></td>
</tr>
<tr>
	<td></td>
	<td><input type="submit" value="Добавить"></td>
</tr>
</table>
</form>
</body></html>