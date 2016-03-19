<?php

namespace Bixie\IdealClient\Utils\Http;

use Bixie\IdealClient\Exception\IdealClientException;

// Load data from an URL
function idealcheckout_doHttpRequest ($sUrl, $sPostData = false, $bRemoveHeaders = false, $iTimeout = 30, $bDebug = false, $aAdditionalHeaders = false) {
	if (!empty($sUrl)) {

		if (in_array('curl', get_loaded_extensions()) && function_exists('curl_init')) {
			return idealcheckout_doHttpRequest_curl($sUrl, $sPostData, $bRemoveHeaders, $iTimeout, $bDebug, $aAdditionalHeaders);
		} else {
			throw new IdealClientException('idealcheckout_doHttpRequest: Cannot detect cURL');
		}
	}

	return '';
}

function idealcheckout_getDebugMode () {
	return false;
}

function idealcheckout_log ($sText, $sFile = false, $iLine = false, $bDebugCheck = true) {
	return false;
}

// doHttpRequest (Uses curl-library)
function idealcheckout_doHttpRequest_curl ($sUrl, $sPostData = false, $bRemoveHeaders = false, $iTimeout = 30, $bDebug = false, $aAdditionalHeaders = false) {
	global $bIdealcheckoutCurlVerificationError;

	if (!isset($bIdealcheckoutCurlVerificationError)) {
		$bIdealcheckoutCurlVerificationError = false;
	}

	$aUrl = parse_url($sUrl);

	$bHttps = false;
	$sRequestUrl = '';

	if (in_array($aUrl['scheme'], array('ssl', 'https'))) {
		$sRequestUrl .= 'https://';
		$bHttps = true;

		if (empty($aUrl['port'])) {
			$aUrl['port'] = 443;
		}
	} else {
		$sRequestUrl .= 'http://';

		if (empty($aUrl['port'])) {
			$aUrl['port'] = 80;
		}
	}

	$sRequestUrl .= $aUrl['host'] . (empty($aUrl['path']) ? '/' : $aUrl['path']) . (empty($aUrl['query']) ? '' : '?' . $aUrl['query']);

	if (is_array($sPostData)) {
		$sPostData = str_replace(array('%5B', '%5D'), array('[', ']'), http_build_query($sPostData));
	}


	if ($bDebug === true) {
		$sRequest = 'Requested URL: ' . $sRequestUrl . "\r\n";
		$sRequest .= 'Portnumber: ' . $aUrl['port'] . "\r\n";

		if ($sPostData) {
			$sRequest .= 'Posted data: ' . $sPostData . "\r\n";
		}

		echo "\r\n" . "\r\n" . '<h1>SEND DATA:</h1>' . "\r\n" . '<code style="display: block; background: #E0E0E0; border: #000000 solid 1px; padding: 10px;">' . str_replace(array("\n", "\r"), array('<br>' . "\r\n", ''), htmlspecialchars($sRequest)) . '</code>' . "\r\n" . "\r\n";
	}


	$oCurl = curl_init();
	$oCertInfo = false;

	if ($bHttps && idealcheckout_getDebugMode()) {
		$oCertInfo = tmpfile();

		$sHostName = ($bHttps ? 'https://' : 'http://') . $aUrl['host'] . (empty($aUrl['port']) ? '' : ':' . $aUrl['port']);
		idealcheckout_getUrlCertificate($sHostName);
	}

	curl_setopt($oCurl, CURLOPT_URL, $sRequestUrl);
	curl_setopt($oCurl, CURLOPT_PORT, $aUrl['port']);

	if ($bHttps && ($bIdealcheckoutCurlVerificationError == false)) {
		curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, 2);

		if ($oCertInfo) {
			curl_setopt($oCurl, CURLOPT_STDERR, $oCertInfo);
			curl_setopt($oCurl, CURLOPT_VERBOSE, true);
			curl_setopt($oCurl, CURLOPT_CERTINFO, true);
		}
	}

	curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($oCurl, CURLOPT_TIMEOUT, $iTimeout);
	curl_setopt($oCurl, CURLOPT_HEADER, $bRemoveHeaders == false);


	if (substr($sPostData, 0, 1) == '{') // JSON string
	{
		if (!is_array($aAdditionalHeaders)) {
			$aAdditionalHeaders = array();
		}

		$aAdditionalHeaders[] = 'Content-Type: application/json';
	}


	if (is_array($aAdditionalHeaders) && sizeof($aAdditionalHeaders)) {
		curl_setopt($oCurl, CURLOPT_HTTPHEADER, $aAdditionalHeaders);
	}


	if ($sPostData != false) {
		curl_setopt($oCurl, CURLOPT_POST, true);
		curl_setopt($oCurl, CURLOPT_POSTFIELDS, $sPostData);
	}

	$sResponse = curl_exec($oCurl);


	// Capture certificate info
	if ($bHttps && $oCertInfo) {
		fseek($oCertInfo, 0);

		$sCertInfo = '';

		while ($s = fread($oCertInfo, 8192)) {
			$sCertInfo .= $s;
		}

		fclose($oCertInfo);

		idealcheckout_log('cURL Retrieved SSL Certificate:' . "\r\n" . $sCertInfo, __FILE__, __LINE__);
	}

	if (idealcheckout_getDebugMode()) {
		if (curl_errno($oCurl) && (strpos(curl_error($oCurl), 'self signed certificate') !== false)) {
			idealcheckout_log('cURL error #' . curl_errno($oCurl) . ': ' . curl_error($oCurl), __FILE__, __LINE__);
			idealcheckout_log(curl_getinfo($oCurl), __FILE__, __LINE__);
			$bIdealcheckoutCurlVerificationError = true;

			curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($oCurl, CURLOPT_VERBOSE, false);
			curl_setopt($oCurl, CURLOPT_CERTINFO, false);

			// cURL Retry
			$sResponse = curl_exec($oCurl);
		}

		if (curl_errno($oCurl) == CURLE_SSL_CACERT) {
			idealcheckout_log('cURL error #' . curl_errno($oCurl) . ': ' . curl_error($oCurl), __FILE__, __LINE__);
			idealcheckout_log('ca-bundle.crt not installed?!', __FILE__, __LINE__);
			idealcheckout_log(curl_getinfo($oCurl), __FILE__, __LINE__);

			$sBundlePath = dirname(dirname(__FILE__)) . '/certificates/ca-bundle.crt';

			if (is_file($sBundlePath)) {
				curl_setopt($oCurl, CURLOPT_CAINFO, $sBundlePath);

				// cURL Retry
				$sResponse = curl_exec($oCurl);
			}
		}

		if ((curl_errno($oCurl) == CURLE_SSL_PEER_CERTIFICATE) || (curl_errno($oCurl) == 77)) {
			idealcheckout_log('cURL error: ' . curl_error($oCurl), __FILE__, __LINE__);
			idealcheckout_log(curl_getinfo($oCurl), __FILE__, __LINE__);
			curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, false);

			// cURL Retry
			$sResponse = curl_exec($oCurl);
		}

		if (curl_errno($oCurl) && (strpos(curl_error($oCurl), 'error setting certificate verify locations') !== false)) {
			idealcheckout_log('cURL error: ' . curl_error($oCurl), __FILE__, __LINE__);
			idealcheckout_log(curl_getinfo($oCurl), __FILE__, __LINE__);
			curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, false);

			// cURL Retry
			$sResponse = curl_exec($oCurl);
		}

		if (curl_errno($oCurl) && (strpos(curl_error($oCurl), 'certificate subject name ') !== false) && (strpos(curl_error($oCurl), ' does not match target host') !== false)) {
			idealcheckout_log('cURL error: ' . curl_error($oCurl), __FILE__, __LINE__);
			idealcheckout_log(curl_getinfo($oCurl), __FILE__, __LINE__);
			curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, 0);

			// cURL Retry
			$sResponse = curl_exec($oCurl);
		}
	}

	if (curl_errno($oCurl)) {
		idealcheckout_log('cURL cannot rely on SSL verification. All SSL verification is disabled from this point.', __FILE__, __LINE__);
		idealcheckout_log(curl_getinfo($oCurl), __FILE__, __LINE__);
		$bIdealcheckoutCurlVerificationError = true;

		curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($oCurl, CURLOPT_VERBOSE, false);
		curl_setopt($oCurl, CURLOPT_CERTINFO, false);

		// cURL Retry
		$sResponse = curl_exec($oCurl);
	}

	if (curl_errno($oCurl)) {
		// cURL Failed
		idealcheckout_log('cURL error: ' . curl_error($oCurl), __FILE__, __LINE__);
		idealcheckout_log(curl_getinfo($oCurl), __FILE__, __LINE__);
		throw new IdealClientException('Error while calling url: ' . $sRequestUrl);
	}

	curl_close($oCurl);


	if ($bDebug === true) {
		echo "\r\n" . "\r\n" . '<h1>RECIEVED DATA:</h1>' . "\r\n" . '<code style="display: block; background: #E0E0E0; border: #000000 solid 1px; padding: 10px;">' . str_replace(array("\n", "\r"), array('<br>' . "\r\n", ''), htmlspecialchars($sResponse)) . '</code>' . "\r\n" . "\r\n";
	}


	if (empty($sResponse)) {
		return '';
	}

	return $sResponse;
}

function idealcheckout_getUrlCertificate ($sUrl, $bDebug = false) {
	if ($bDebug || idealcheckout_getDebugMode()) {
		if (version_compare(PHP_VERSION, '5.3.0') < 0) {
			idealcheckout_log('PHP version is to low for retrieving certificates.', __FILE__, __LINE__);
		} else {
			if ($oStream = @stream_context_create(array('ssl' => array('capture_peer_cert' => true)))) {
				idealcheckout_log('Fetching peer certificate for: ' . $sUrl, __FILE__, __LINE__);

				if ($oHandle = @fopen($sUrl, 'rb', false, $oStream)) {
					if (function_exists('stream_context_get_params')) {
						$aParams = stream_context_get_params($oHandle);

						if (isset($aParams['options'], $aParams['options']['ssl'], $aParams['options']['ssl']['peer_certificate'])) {
							$oPeerCertificate = $aParams['options']['ssl']['peer_certificate'];

							$sTempPath = dirname(__DIR__) . '/temp';

							// Save certificate
							if (@openssl_x509_export_to_file($oPeerCertificate, $sTempPath . '/peer.' . time() . '.crt')) {
								return true;
							}
						} else {
							return false;
						}
					} else {
						idealcheckout_log('Stream function does not exist on this PHP version.', __FILE__, __LINE__);
					}
				}

				idealcheckout_log('Peer certificate capture failed for: ' . $sUrl, __FILE__, __LINE__);
			}
		}
	}

	return false;
}

