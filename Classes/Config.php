<?php
namespace BraghimSistemas\Zf2lib\Classes;

use Exception;

/**
 * Description of Config
 *
 * @author pedepano
 */
class Config
{
	public static $file = 'config/autoload/global.php';
	
	public static function getZf2libConfig($index)
	{
		$global = require self::$file;
		if (!isset($global['zf2lib-config'])) {
			throw new Exception("Para trabalhar com a biblioteca zf2lib é necessário um índice 'zf2lib-config' no arquivo '" . self::$file . "'");
		}
		
		$configValue = isset($global['zf2lib-config'][$index]) ? $global['zf2lib-config'][$index] : false;
		if (!$configValue) {
			throw new Exception("Defina o indice $index no arquivo " . self::$file . ", no indice 'zf2lib-config'");
		}
		return $configValue;
	}
}
