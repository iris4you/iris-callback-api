<?php
ini_set("display_errors" , 1);
error_reporting(E_ALL);
ini_set('display_startup_errors', 1);

require_once("classes/base.php");
require_once(CLASSES_PATH . "Ub/Callback/Callback.php");
$callback = new UbCallbackCallback();
$callback->run();
