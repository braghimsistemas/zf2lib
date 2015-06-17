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
	
	/**
	 * Retorna as configurações específicas de cada projeto ajustadas
	 * no arquivo config/autoload/global.php do projeto.
	 * 
	 * @param type $index
	 * @param type $module
	 * @return type
	 * @throws Exception
	 */
	public static function getZf2libConfig($index, $module = null)
	{
		$global = require self::$file;
		if (!isset($global['zf2lib-config'])) {
			throw new Exception("Para trabalhar com a biblioteca zf2lib é necessário um índice 'zf2lib-config' no arquivo '" . self::$file . "'");
		}
		
		// Podemos usar uma configuração diferente para cada modulo.
		if ($module) {
			$module = strtolower($module);
			$configValue = isset($global['zf2lib-config'][$module][$index]) ? $global['zf2lib-config'][$module][$index] : false;
		} else {
			$configValue = isset($global['zf2lib-config'][$index]) ? $global['zf2lib-config'][$index] : false;
		}
		if (!$configValue) {
			throw new Exception("Defina o indice $index para este módulo no arquivo " . self::$file . ", no indice 'zf2lib-config'");
		}
		return $configValue;
	}
}
