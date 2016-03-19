<?php

namespace Bixie\IdealClient\Utils;


class Utils {

	public static function getRandomCode ($iLength = 64) {
		$aCharacters = array('a', 'b', 'c', 'd', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
		$sResult = '';
		for ($i = 0; $i < $iLength; $i++) {
			$sResult .= $aCharacters[rand(0, sizeof($aCharacters) - 1)];
		}
		return $sResult;
	}

	public static function getRootUrl ($iParent = 0) {

		if (empty($_REQUEST['ROOT_URL'])) {

			// Detect installation directory based on current URL
			$sRootUrl = '';

			// Detect scheme
			if (isset($_SERVER['HTTPS']) && (strcasecmp($_SERVER['HTTPS'], 'ON') === 0)) {
				$sRootUrl .= 'https://';
			} else {
				$sRootUrl .= 'http://';
			}

			// Detect domain
			$sRootUrl .= $_SERVER['HTTP_HOST'];

			// Detect port
			if ((strpos($_SERVER['HTTP_HOST'], ':') === false) && isset($_SERVER['SERVER_PORT'])) {
				if (isset($_SERVER['HTTPS']) && (strcasecmp($_SERVER['HTTPS'], 'ON') === 0)) {
					if ((strcmp($_SERVER['SERVER_PORT'], '443') !== 0) && (strcmp($_SERVER['SERVER_PORT'], '80') !== 0)) {
						$sRootUrl .= ':' . $_SERVER['SERVER_PORT'];
					}
				} elseif (strcmp($_SERVER['SERVER_PORT'], '80') !== 0) {
					$sRootUrl .= ':' . $_SERVER['SERVER_PORT'];
				}
			}

			$sRootUrl .= '/';

			// Detect path
			if (isset($_SERVER['SCRIPT_NAME'])) {
				$a = explode('/', substr($_SERVER['SCRIPT_NAME'], 1));

				while (sizeof($a) > ($iParent + 1)) {
					$sRootUrl .= $a[0] . '/';
					array_shift($a);
				}
			}

			$_REQUEST['ROOT_URL'] = $sRootUrl;
		}

		return $_REQUEST['ROOT_URL'];
	}


}