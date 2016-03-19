<?php
/*
	Class to manage your iDEAL Requests

	Version:     0.1
	Date:        01-12-2012
	PHP:         PHP 5

	Suitable for:
	Rabobank     iDEAL Professional - iDEAL v3.3.1
	ING BANK     iDEAL Advanced - iDEAL v3
	ABN AMRO     iDEAL Zelfbouw - iDEAL v3

	See also:
	www.ideal-simulator.nl


	Author:      Martijn Wieringa
	Company:     iDEAL Checkout
	Email:       info@ideal-checkout.nl
	Website:     https://www.ideal-checkout.nl
*/


namespace Bixie\IdealClient\Gateway\ProfessionalV3;

use Bixie\IdealClient\Exception\IdealClientException;
use Bixie\IdealClient\Exception\IdealClientResultException;
use Bixie\IdealClient\Utils\Http;

class IdealRequest {

	protected $aErrors = array();

	// Security settings
	protected $sSecurePath;
	protected $sCachePath;
	protected $sPrivateKeyPass;
	protected $sPrivateKeyFile;
	protected $sPrivateCertificateFile;
	protected $sPublicCertificateFile;

	// Account settings
	protected $bABNAMRO = false; // ABN has some issues
	protected $sAquirerName;
	protected $sAquirerUrl;
	protected $bTestMode = false;
	protected $sMerchantId;
	protected $sSubId;

	// Constants
	protected $LF = "\n";
	protected $CRLF = "\r\n";

	public function __construct () {
		$this->sPrivateKeyFile = 'private.key';
		$this->sPrivateCertificateFile = 'private.cer';
	}


	// Should point to directory with .cer and .key files
	public function setSecurePath ($sPath) {
		$this->sSecurePath = $sPath;
	}

	// Should point to directory where cache is strored
	public function setCachePath ($sPath = false) {
		$this->sCachePath = $sPath;
	}

	// Set password to generate signatures
	public function setPrivateKey ($sPrivateKeyPass, $sPrivateKeyFile = false, $sPrivateCertificateFile = false) {
		$this->sPrivateKeyPass = $sPrivateKeyPass;

		if ($sPrivateKeyFile) {
			$this->sPrivateKeyFile = $sPrivateKeyFile;
		}

		if ($sPrivateCertificateFile) {
			$this->sPrivateCertificateFile = $sPrivateCertificateFile;
		}
	}

	// Set MerchantID id and SubID
	public function setMerchant ($sMerchantId, $sSubId = 0) {
		$this->sMerchantId = $sMerchantId;
		$this->sSubId = $sSubId;
	}

	// Set aquirer (Use: Rabobank, ING Bank, ABN Amro, Frieslandbank, Simulator or Mollie)
	public function setAquirer ($sAquirerName, $bTestMode = false) {
		$this->sAquirerName = $sAquirerName;
		$this->bTestMode = $bTestMode;

		$sAquirerName = strtolower($sAquirerName);

		if (strpos($sAquirerName, 'abn') !== false) // ABN AMRO
		{
			$this->sPublicCertificateFile = 'abnamro.cer';
			$this->sAquirerUrl = 'ssl://abnamro' . ($bTestMode ? '-test' : '') . '.ideal-payment.de:443/ideal/iDEALv3';
		} elseif (strpos($sAquirerName, 'deu') !== false) // Deutsche Bank
		{
			$this->sPublicCertificateFile = 'deutschebank.cer';
			$this->sAquirerUrl = 'ssl://ideal' . ($this->bTestMode ? 'test' : '') . '.db.com:443/ideal/iDEALv3';
		} elseif (strpos($sAquirerName, 'fries') !== false) // Frieslandbank
		{
			$this->sPublicCertificateFile = 'frieslandbank.cer';
			$this->sAquirerUrl = 'ssl://' . ($bTestMode ? 'test' : '') . 'idealkassa.frieslandbank.nl:443/ideal/iDEALv3';
		} elseif (strpos($sAquirerName, 'ing') !== false) // ING Bank
		{
			$this->sPublicCertificateFile = 'ingbank.cer';
			$this->sAquirerUrl = 'ssl://ideal' . ($bTestMode ? 'test' : '') . '.secure-ing.com:443/ideal/iDEALv3';
		} elseif (strpos($sAquirerName, 'rabo') !== false) // Rabobank
		{
			$this->sPublicCertificateFile = 'rabobank.cer';
			$this->sAquirerUrl = 'ssl://ideal' . ($bTestMode ? 'test' : '') . '.rabobank.nl:443/ideal/iDEALv3';
		} elseif (strpos($sAquirerName, 'sim') !== false) // IDEAL SIMULATOR
		{
			$this->sPublicCertificateFile = 'idealcheckout.cer';
			$this->sAquirerUrl = 'ssl://www.ideal-checkout.nl:443/simulator/';
			$this->bTestMode = true; // Always in TEST MODE
		} else // Unknown issuer
		{
			$this->setError('Unknown aquirer. Please use "Rabobank", "ING Bank", "ABN Amro", or "Simulator".', false, __FILE__, __LINE__);
			return false;
		}
	}


	// Error functions
	protected function setError ($sDesc, $sCode = false, $sFile = 0, $sLine = 0) {
		$this->aErrors[] = array('desc' => $sDesc, 'code' => $sCode, 'file' => $sFile, 'line' => $sLine);
	}

	public function getErrors () {
		return $this->aErrors;
	}

	public function hasErrors () {
		return (sizeof($this->aErrors) ? true : false);
	}


	// Validate configuration
	protected function checkConfiguration ($aSettings = array('sSecurePath', 'sPrivateKeyPass', 'sPrivateKeyFile', 'sPrivateCertificateFile', 'sPublicCertificateFile', 'sAquirerUrl', 'sMerchantId')) {
		$bOk = true;

		for ($i = 0; $i < sizeof($aSettings); $i++) {
			// echo $aSettings[$i] . ' = ' . $this->$aSettings[$i] . '<br>';

			if (isset($aSettings[$i]) == false) {
				$bOk = false;
				$this->setError('Setting ' . $aSettings[$i] . ' was not configurated.', false, __FILE__, __LINE__);
			}
		}

		return $bOk;
	}


	// Send GET/POST data through sockets
	protected function postToHost ($sUrl, $aData, $iTimeout = 30) {
		$sResponse = Http\idealcheckout_doHttpRequest($sUrl, $aData, false, $iTimeout, false, false);

		if (empty($sResponse)) {
			throw new IdealClientException('Error while connecting to: ' . $sUrl);
		} else {
			return $sResponse;
		}
	}


	// Get value within given XML tag
	protected function parseFromXml ($key, $xml) {
		$begin = 0;
		$end = 0;
		$begin = strpos($xml, '<' . $key . '>');

		if ($begin === false) {
			return false;
		}

		$begin += strlen($key) + 2;
		$end = strpos($xml, '</' . $key . '>');

		if ($end === false) {
			return false;
		}

		$result = substr($xml, $begin, $end - $begin);
		return $this->unescapeXml($result);
	}

	// Remove space characters from string
	protected function removeSpaceCharacters ($string) {
		return preg_replace('/\s/', '', $string);
	}

	// Escape (replace/remove) special characters in string
	protected function escapeSpecialChars ($string) {
		$string = str_replace(array('�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�', '�'), array('a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'ed', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 's', 'u', 'u', 'u', 'u', 'y', 'y', 'A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'EUR', 'ED', 'I', 'I', 'I', 'I', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'S', 'U', 'U', 'U', 'U', 'Y', 'Y'), $string);
		$string = preg_replace('/[^a-zA-Z0-9\-\.\,\(\)_]+/', ' ', $string);
		$string = preg_replace('/[\s]+/', ' ', $string);

		return $string;
	}

	// Escape special XML characters
	protected function escapeXml ($string) {
		return utf8_encode(str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $string));
	}

	// Unescape special XML characters
	protected function unescapeXml ($string) {
		return str_replace(array('&lt;', '&gt;', '&quot;', '&amp;'), array('<', '>', '"', '&'), utf8_decode($string));
	}


	protected function getMessageDigest ($sMessage) {
		return base64_encode(hash('sha256', $sMessage, true));
	}


	protected function getSignature ($sMessage, $sKeyName, $sKeyPassword = false) {

		if (!$sKeyData = file_get_contents($this->sSecurePath . '/' . $sKeyName) or empty($sKeyData)) {
			throw new IdealClientResultException('File "' . $sKeyName . '" is empty or does not exist.');
		}

		if ($sKeyPassword === false) {
			$oKeyData = openssl_get_publickey($sKeyData);

			if (empty($oKeyData)) {
				throw new IdealClientResultException('File "' . $sKeyName . '" is an invalid publickey file.');
			}
		} else {
			$oKeyData = openssl_get_privatekey($sKeyData, $sKeyPassword);

			if (empty($oKeyData)) {
				throw new IdealClientResultException('File "' . $sKeyName . '" is an invalid privatekey file, or privatekey file doesn\'t match private keypass.');
			}
		}

		if (!openssl_sign($sMessage, $sSignature, $oKeyData, 'SHA256')) {
			throw new IdealClientResultException('Cannot sign message');
		}

		$sSignature = base64_encode($sSignature);

		return $sSignature;
	}

	protected function verifySignature ($sMessage, $sSignature, $sCertificatePath) {
		$sCertificateData = file_get_contents($sCertificatePath);
		$oCertificateData = openssl_get_publickey($sCertificateData);

		// Replace self-closing-tags
		$sMessage = str_replace(array('/><SignatureMethod', '/><Reference', '/></Transforms', '/><DigestValue'), array('></CanonicalizationMethod><SignatureMethod', '></SignatureMethod><Reference', '></Transform></Transforms', '></DigestMethod><DigestValue'), $sMessage);

		// Decode signature
		$sSignature = base64_decode($sSignature);

		if (version_compare(PHP_VERSION, '5.3.0') < 0) {
			return self::openssl_verify_alternative($sMessage, $sSignature, $oCertificateData);
		} else {
			return openssl_verify($sMessage, $sSignature, $oCertificateData, 'SHA256');
		}
	}

	protected function verifyDigest ($sMessage, $sDigest) {
		return (strcmp($this->getMessageDigest($sMessage), $sDigest) === 0);
	}

	protected function getCertificateFingerprint ($sCertificateName) {

		if (!is_file($this->sSecurePath . '/' . $sCertificateName)) {

			throw new IdealClientResultException('Invalid certificate file: ' . $sCertificateName);
		}

		$sData = file_get_contents($this->sSecurePath . '/' . $sCertificateName);
		if (empty($sData)) {

			throw new IdealClientResultException('Invalid certificate file: ' . $sCertificateName);

		}

		$oData = openssl_x509_read($sData);

		if ($oData == false) {

			throw new IdealClientResultException('Invalid certificate file: ' . $sCertificateName);

		} elseif (!openssl_x509_export($oData, $sData)) {

			throw new IdealClientResultException('Invalid certificate file: ' . $sCertificateName);
		}

		// Remove any ASCII armor
		$sData = str_replace('-----BEGIN CERTIFICATE-----', '', $sData);
		$sData = str_replace('-----END CERTIFICATE-----', '', $sData);

		$sData = base64_decode($sData);
		$sFingerprint = sha1($sData);
		$sFingerprint = strtoupper($sFingerprint);

		return $sFingerprint;
	}

	protected function getPublicCertificateFile ($sCertificateFingerprint) {
		$aCertificateFiles = array();

		if (file_exists($this->sSecurePath . $this->sPublicCertificateFile)) {
			$aCertificateFiles[] = $this->sPublicCertificateFile;
		}


		// Upto 10 public certificates by acquirer; eg: rabobank-0.cer, rabobank-1.cer, rabobank-2.cer, etc.
		for ($i = 0; $i < 10; $i++) {
			$sCertificateFile = substr($this->sPublicCertificateFile, 0, -4) . '-' . $i . '.cer';

			if (file_exists($this->sSecurePath . $sCertificateFile)) {
				$aCertificateFiles[] = $sCertificateFile;
			}
		}


		// Find generic certificates
		if (file_exists($this->sSecurePath . 'ideal.cer')) {
			$aCertificateFiles[] = 'ideal.cer';
		}


		// Upto 10 public certificates; eg: ideal-0.cer, ideal-1.cer, ideal-2.cer, etc.
		for ($i = 0; $i < 10; $i++) {
			$sCertificateFile = 'ideal-' . $i . '.cer';

			if (file_exists($this->sSecurePath . $sCertificateFile)) {
				$aCertificateFiles[] = $sCertificateFile;
			}
		}

		// Test each certificate with given fingerprint
		foreach ($aCertificateFiles as $sCertificateFile) {
			$sFingerprint = $this->getCertificateFingerprint($sCertificateFile);

			if (strcmp($sFingerprint, $sCertificateFingerprint) === 0) {
				return $this->sSecurePath . $sCertificateFile;
			}
		}

		return false;
	}

	// Verify response message (<DigestValue>, <SignatureValue>)
	protected function verifyResponse ($sXmlData, $sResponseType) {
		$sCertificateFingerprint = $this->parseFromXml('KeyName', $sXmlData);
		$sDigestValue = $this->parseFromXml('DigestValue', $sXmlData);
		$sSignatureValue = str_replace(array("\r", "\n"), '', $this->parseFromXml('SignatureValue', $sXmlData));

		$sDigestData = '';

		if ($this->parseFromXml('errorCode', $sXmlData)) // Error found
		{
			// Add error to error-list
			$this->setError($this->parseFromXml('errorMessage', $sXmlData) . ' - ' . $this->parseFromXml('errorDetail', $sXmlData), $this->parseFromXml('errorCode', $sXmlData), __FILE__, __LINE__);
		} elseif (strpos($sXmlData, '</' . $sResponseType . '>') !== false) // Directory Response
		{
			// Strip <Signature>
			$iStart = strpos($sXmlData, '<' . $sResponseType);
			$iEnd = strpos($sXmlData, '<Signature');
			$sDigestData = substr($sXmlData, $iStart, $iEnd - $iStart) . '</' . $sResponseType . '>';
		}

		if (!empty($sDigestData)) {
			// Recalculate & compare DigestValue
			if ($this->verifyDigest($sDigestData, $sDigestValue)) {
				// Find <SignedInfo>, and add ' xmlns="http://www.w3.org/2000/09/xmldsig#"'
				$iStart = strpos($sXmlData, '<SignedInfo>');
				$iEnd = strpos($sXmlData, '</SignedInfo>');
				$sSignatureData = '<SignedInfo xmlns="http://www.w3.org/2000/09/xmldsig#">' . substr($sXmlData, $iStart + 12, $iEnd - ($iStart + 12)) . '</SignedInfo>';

				if (!empty($sSignatureData)) {
					// Detect used public certificate by given fingerprint
					if ($sPublicCertificateFile = $this->getPublicCertificateFile($sCertificateFingerprint)) {
						// Recalculate & compare SignatureValue
						if ($this->verifySignature($sSignatureData, $sSignatureValue, $sPublicCertificateFile)) {
							return true;
						} else {
							$this->setError('Invalid signature value in XML response.', '', __FILE__, __LINE__);
						}
					} else {
						$this->setError('Cannot find public certificate file with fingerprint: ' . $sCertificateFingerprint, '', __FILE__, __LINE__);
					}
				} else {
					$this->setError('Cannot find <SignedInfo> in XML response.', '', __FILE__, __LINE__);
				}
			} else {
				$this->setError('Invalid digest value in XML response.', '', __FILE__, __LINE__);
			}
		} else {
			$this->setError('Cannot find <' . $sResponseType . '> in XML response.', '', __FILE__, __LINE__);
		}

		return false;
	}

	// PHP 5.2 alternative for SHA256 signing
	public static function openssl_sign_alternative ($sMessage, &$sSignature, $oKeyData) {
		$aPrivateKey = openssl_pkey_get_details($oKeyData);

		$sSha256 = '3031300d060960864801650304020105000420';
		$sHash = $sSha256 . hash('sha256', $sMessage);

		$iLength = ($aPrivateKey['bits'] / 8) - ((strlen($sHash) / 2) + 3);

		$sData = '0001' . str_repeat('FF', $iLength) . '00' . $sHash;
		$sData = pack('H*', $sData);

		return openssl_private_encrypt($sData, $sSignature, $oKeyData, OPENSSL_NO_PADDING);
	}

	// PHP 5.2 alternative for SHA256 validation
	public static function openssl_verify_alternative ($sMessage, &$sSignature, $oKeyData) {
		$aPrivateKey = openssl_pkey_get_details($oKeyData);

		$sSha256 = '3031300d060960864801650304020105000420';
		$sHash = $sSha256 . hash('sha256', $sMessage);

		$iLength = ($aPrivateKey['bits'] / 8) - ((strlen($sHash) / 2) + 3);

		$sData = '0001' . str_repeat('FF', $iLength) . '00' . $sHash;
		$sData = pack('H*', $sData);

		return openssl_public_decrypt($sData, $sSignature, $oKeyData, OPENSSL_NO_PADDING);
	}
}
