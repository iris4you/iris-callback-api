<?php


class UbDbUtil {
	/** @return mysqli */
	static function singleton() {
		static $db;
		if (!$db) {
			$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_DATABASE);
		}
		return $db;
	}

	static function select($query) {
		$db = self::singleton();
		$items = $db->query($query);
		return $items->fetch_all(MYSQLI_ASSOC);
	}

	static function selectOne($query) {
		$items = self::select($query);
		if (count($items) > 0)
			return $items[0];
		return null;
	}

	static function query($query) {
		$db = self::singleton();
		$db->query($query);
		return $db->affected_rows;
	}

	static function stringVal($val) {
		return '"' . self::stringValStripped($val) . '"';
	}

	static function stringValStrippedLike($val) {
		return str_replace(array('%', '_'), array('\%', '\_'), self::stringValStripped($val));
	}

	static function stringValStripped($val) {
		$db = self::singleton();
		return $db->escape_string($val);
	}

	static function intVal($val) {
		return intval($val);
	}
}