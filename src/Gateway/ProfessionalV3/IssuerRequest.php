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

use Bixie\IdealClient\Exception\IdealClientResultException;
use Bixie\IdealClient\Utils\Http;

class IssuerRequest extends IdealRequest {
	public function __construct () {
		parent::__construct();
	}

	// Execute request (Lookup issuer list)
	public function doRequest () {
		if ($this->checkConfiguration()) {
			$sCacheFile = false;

			// Used cached issuers?
			if (($this->bTestMode == false) && $this->sCachePath) {
				$sCacheFile = $this->sCachePath . 'issuers.cache';
				$bFileCreated = false;

				if (file_exists($sCacheFile) == false) {
					$bFileCreated = true;

					// Attempt to create cache file
					if (@touch($sCacheFile)) {
						@chmod($sCacheFile, 0777);
					}
				}

				if (file_exists($sCacheFile) && is_readable($sCacheFile) && is_writable($sCacheFile)) {
					if ($bFileCreated || (filemtime($sCacheFile) > strtotime('-24 Hours'))) {
						// Read data from cache file
						if ($sData = file_get_contents($sCacheFile)) {
							return unserialize($sData);
						}
					}
				} else {
					$sCacheFile = false;
				}
			}


			$sTimestamp = gmdate('Y-m-d\TH:i:s.000\Z');
			$sCertificateFingerprint = $this->getCertificateFingerprint($this->sPrivateCertificateFile);

			$sXml = '<DirectoryReq xmlns="http://www.idealdesk.com/ideal/messages/mer-acq/3.3.1" version="3.3.1">';
			$sXml .= '<createDateTimestamp>' . $sTimestamp . '</createDateTimestamp>';
			$sXml .= '<Merchant>';
			$sXml .= '<merchantID>' . $this->sMerchantId . '</merchantID>';
			$sXml .= '<subID>' . $this->sSubId . '</subID>';
			$sXml .= '</Merchant>';
			$sXml .= '</DirectoryReq>';

			// Calculate <DigestValue>
			$sDigestValue = $this->getMessageDigest($sXml);

			$sXml = '<SignedInfo xmlns="http://www.w3.org/2000/09/xmldsig#">';
			$sXml .= '<CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"></CanonicalizationMethod>';
			$sXml .= '<SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"></SignatureMethod>';
			$sXml .= '<Reference URI="">';
			$sXml .= '<Transforms>';
			$sXml .= '<Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"></Transform>';
			$sXml .= '</Transforms>';
			$sXml .= '<DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"></DigestMethod>';
			$sXml .= '<DigestValue>' . $sDigestValue . '</DigestValue>';
			$sXml .= '</Reference>';
			$sXml .= '</SignedInfo>';

			// Calculate <SignatureValue>
			$sSignatureValue = $this->getSignature($sXml, $this->sPrivateKeyFile, $this->sPrivateKeyPass);

			$sXml = '<' . '?' . 'xml version="1.0" encoding="UTF-8"' . '?' . '>' . "\n";
			$sXml .= '<DirectoryReq xmlns="http://www.idealdesk.com/ideal/messages/mer-acq/3.3.1" version="3.3.1">';
			$sXml .= '<createDateTimestamp>' . $sTimestamp . '</createDateTimestamp>';
			$sXml .= '<Merchant>';
			$sXml .= '<merchantID>' . $this->sMerchantId . '</merchantID>';
			$sXml .= '<subID>' . $this->sSubId . '</subID>';
			$sXml .= '</Merchant>';
			$sXml .= '<Signature xmlns="http://www.w3.org/2000/09/xmldsig#">';
			$sXml .= '<SignedInfo>';
			$sXml .= '<CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"></CanonicalizationMethod>';
			$sXml .= '<SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"></SignatureMethod>';
			$sXml .= '<Reference URI="">';
			$sXml .= '<Transforms>';
			$sXml .= '<Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"></Transform>';
			$sXml .= '</Transforms>';
			$sXml .= '<DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"></DigestMethod>';
			$sXml .= '<DigestValue>' . $sDigestValue . '</DigestValue>';
			$sXml .= '</Reference>';
			$sXml .= '</SignedInfo>';
			$sXml .= '<SignatureValue>' . $sSignatureValue . '</SignatureValue>';
			$sXml .= '<KeyInfo>';
			$sXml .= '<KeyName>' . $sCertificateFingerprint . '</KeyName>';
			$sXml .= '</KeyInfo>';
			$sXml .= '</Signature>';
			$sXml .= '</DirectoryReq>';

			$sXmlReply = $this->postToHost($this->sAquirerUrl, $sXml, 10);

			if ($sXmlReply) {
				if ($this->verifyResponse($sXmlReply, 'DirectoryRes')) {
					$aIssuerList = array();

					while (strpos($sXmlReply, '<issuerID>')) {
						$sIssuerId = $this->parseFromXml('issuerID', $sXmlReply);
						$sIssuerName = $this->parseFromXml('issuerName', $sXmlReply);

						$aIssuerList[$sIssuerId] = $sIssuerName;

						$sXmlReply = substr($sXmlReply, strpos($sXmlReply, '</Issuer>') + 9);
					}

					// Save data in cache?
					if ($sCacheFile) {
						file_put_contents($sCacheFile, serialize($aIssuerList));
					}

					return $aIssuerList;
				}
			}
		}

		return false;
	}
}
