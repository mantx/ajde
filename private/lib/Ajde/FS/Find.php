<?php

class Ajde_FS_Find extends Ajde_Object_Static
{
	public static function findFile($dir, $pattern)
	{
		$search = Config::get("local_root") . DIRECTORY_SEPARATOR . $dir . $pattern;
		$result = glob($search);
		if ($result === false) {
			return false;
		}
		foreach (glob($search) as $filename) {
			return $filename;
		}
		return false;
	}
	
	public static function findFiles($dir, $pattern, $flags = 0)
	{
		$search = Config::get("local_root") . DIRECTORY_SEPARATOR . $dir . $pattern;
		$return = array();
		$files = (array) glob($search, $flags);
		foreach ($files as $filename) {
			$return[] = $filename;
		}
		return $return;
	}
	
	public static function findFilenames($dir, $pattern, $flags = 0)
	{
	    $files = self::findFiles($dir, $pattern, $flags);
	    $return = array();
	    foreach ($files as $file) {
	        $return[] = basename($file);
	    }
	    return $return;
	}
	
	/**
	 *
	 * @param array $array 
	 * @return array
	 */
	public static function mkglobi($array) {
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			return $array;
		}
		$ret = array();
		foreach($array as $extension) {
			$item = '';
			for($i = 0; $i < strlen($extension); $i++) {
				$letter = substr($extension, $i, 1);
				$item = $item . '[' . strtolower($letter) . strtoupper($letter) . ']';
			}
			$ret[] = $item;
		}
		return $ret;
	}
}