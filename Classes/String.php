<?php

namespace BraghimSistemas\Util;

class String {

	/**
	 * Identifica o formato da data e retorna o inverso.
	 * 
	 * @param type $date
	 * @return type
	 */
	public static function dateReverse($date) {
		$result = false;

		// formato 30/12/14 ou 30/12/2014 -para-> 14-12-30 ou 2014-12-30
		if (preg_match("/[0-9]{2}\/[0-9]{2}\/[0-9]+/", $date)) {
			$result = implode("-", array_reverse(explode("/", $date)));

			// formato 14-12-30 ou 2014-12-30 -para-> 30/12/14 ou 30/12/2014
		} else if (preg_match("/[0-9]+\-[0-9]{2}\-[0-9]{2}/", $date)) {
			$result = implode("/", array_reverse(explode("-", $date)));
		}
		return $result;
	}

	/**
	 * Retorna true caso o arquivo for uma imagem.
	 * 
	 * @param type $file
	 * @return type
	 */
	public static function isImage($file) {
		return (bool) @getimagesize($file);
	}

	/**
	 * Transforma bytes em outras unidades.
	 * 
	 * @param type $bytes
	 * @param type $precision
	 * @return type
	 */
	public static function formatBytes($bytes, $precision = 2) {
		$units = array('B', 'KB', 'MB', 'GB', 'TB');

		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($units) - 1);

		// Uncomment one of the following alternatives
		$bytes /= pow(1024, $pow);
		// $bytes /= (1 << (10 * $pow)); 

//		return round($bytes, $precision) . ' ' . $units[$pow];
		return round($bytes, $precision);
	}
}
