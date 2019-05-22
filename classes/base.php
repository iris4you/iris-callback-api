<?php



if (!defined('ROOT_DIR'))
	define("ROOT_DIR", dirname(dirname(__FILE__)) . "/");
if (!defined('CLASSES_PATH'))
	define("CLASSES_PATH", ROOT_DIR . "/classes/");

require_once(CLASSES_PATH . "constants.php");
require_once(CLASSES_PATH . "db.php");

define("SITE_ENCODING", "utf-8");

date_default_timezone_set("Europe/Moscow");

function ppc_strlen($str, $encoding = "UTF-8") {
	return mb_strlen($str, $encoding);
}

function ppc_substr($str, $start, $length = null, $encoding = "UTF-8") {
	if ($length === null)
		$length = ppc_strlen($str);
	return mb_substr($str, $start, $length, $encoding);
}

function ppc_strpos($haystack, $needle, $offset = NULL, $encoding = "UTF-8") {
	return mb_strpos($haystack, $needle, $offset, $encoding);
}

function ppc_strtolower($str, $encoding = "UTF-8") {
	return mb_strtolower($str, $encoding);
}

function ppc_strtoupper($str, $encoding = "UTF-8") {
	return mb_strtoupper($str, $encoding);
}

function ppc_strstr($haystack, $needle, $part, $encoding = "UTF-8") {
	return mb_strstr($haystack, $needle, $part, $encoding);
}

function ppc_charAt($text, $i) {
	if (ppc_strlen($text) <= $i)
		return NULL;
	return ppc_substr($text, $i, 1);
}

function startsWith($text, $part) {
	return (strpos($text, $part) === 0);
}