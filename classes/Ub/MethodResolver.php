<?php

class UbMethodResolver {

	var $map;

	public function __construct() {
		$this->map = $this->buildMap();
	}

	function getMethodHandler($method) {
		$actionName = @$this->map[$method];
		if (!$actionName)
			return null;

		return $this->getClass($actionName);
	}

	private function buildMap() {
		$methods = file(CLASSES_PATH . 'Ub/methods.txt');
		$res = array();
		foreach ($methods as $item) {
			if (strpos($item, "#") === 0) continue;
			$data = self::extractItem(str_replace(array("\r", "\n"), array("", ""), $item));
			if ($data)
				$res[$data[0]] = $data[1];
		}
		return $res;
	}

	static function extractItem($text) {
		$parts = preg_split("/[\t]+/", $text);
		$res = [];
		foreach ($parts as $part) {
			$part = trim($part);
			if ($part)
				$res[] = $part;
		}
		if (sizeof($res) < 2)
			return null;
		$method = $res[0];
		$action = $res[1];
		return [$method, $action];

	}

	function getClass($pathClass) {
		$classPath = str_replace(".", "/", $pathClass) . ".php";
		$className = str_replace(".", "", $pathClass);
		require_once(CLASSES_PATH . $classPath);
		return new $className();
	}
}