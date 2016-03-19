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

class StatusRequest extends IdealRequest {
	// Account info
	protected $sAccountCity;
	protected $sAccountName;
	protected $sAccountNumber;

	// Transaction info
	protected $sTransactionId;
	protected $sTransactionStatus;

	public function __construct () {
		parent::__construct();
	}

	// Set transaction id
	public function setTransactionId ($sTransactionId) {
		$this->sTransactionId = $sTransactionId;
	}

	// Get account city
	public function getAccountCity () {
		if (!empty($this->sAccountCity)) {
			return $this->sAccountCity;
		}

		return '';
	}

	// Get account name
	public function getAccountName () {
		if (!empty($this->sAccountName)) {
			return $this->sAccountName;
		}

		return '';
	}

	// Get account number
	public function getAccountNumber () {
		if (!empty($this->sAccountNumber)) {
			return $this->sAccountNumber;
		}

		return '';
	}

	// Execute request
	public function doRequest () {
		if ($this->checkConfiguration() && $this->checkConfiguration(array('sTransactionId'))) {
			$sTimestamp = gmdate('Y-m-d\TH:i:s.000\Z');
			$sCertificateFingerprint = $this->getCertificateFingerprint($this->sPrivateCertificateFile);

			$sXml = '<AcquirerStatusReq xmlns="http://www.idealdesk.com/ideal/messages/mer-acq/3.3.1" version="3.3.1">';
			$sXml .= '<createDateTimestamp>' . $sTimestamp . '</createDateTimestamp>';
			$sXml .= '<Merchant>';
			$sXml .= '<merchantID>' . $this->sMerchantId . '</merchantID>';
			$sXml .= '<subID>' . $this->sSubId . '</subID>';
			$sXml .= '</Merchant>';
			$sXml .= '<Transaction>';
			$sXml .= '<transactionID>' . $this->sTransactionId . '</transactionID>';
			$sXml .= '</Transaction>';
			$sXml .= '</AcquirerStatusReq>';

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
			$sXml .= '<AcquirerStatusReq xmlns="http://www.idealdesk.com/ideal/messages/mer-acq/3.3.1" version="3.3.1">';
			$sXml .= '<createDateTimestamp>' . $sTimestamp . '</createDateTimestamp>';
			$sXml .= '<Merchant>';
			$sXml .= '<merchantID>' . $this->sMerchantId . '</merchantID>';
			$sXml .= '<subID>' . $this->sSubId . '</subID>';
			$sXml .= '</Merchant>';
			$sXml .= '<Transaction>';
			$sXml .= '<transactionID>' . $this->sTransactionId . '</transactionID>';
			$sXml .= '</Transaction>';
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
			$sXml .= '</AcquirerStatusReq>';

			$sXmlReply = $this->postToHost($this->sAquirerUrl, $sXml, 10);

			if ($sXmlReply) {
				// Verify message (DigestValue & SignatureValue)
				if ($this->verifyResponse($sXmlReply, 'AcquirerStatusRes')) {
					$sTimestamp = $this->parseFromXml('createDateTimeStamp', $sXmlReply);
					$sTransactionId = $this->parseFromXml('transactionID', $sXmlReply);
					$sTransactionStatus = $this->parseFromXml('status', $sXmlReply);

					// $sAccountNumber = $this->parseFromXml('consumerAccountNumber', $sXmlReply);
					// $sAccountName = $this->parseFromXml('consumerName', $sXmlReply);
					// $sAccountCity = $this->parseFromXml('consumerCity', $sXmlReply);

					// Try to keep field compatible where possible
					$sAccountNumber = $this->parseFromXml('consumerIBAN', $sXmlReply) . ' | ' . $this->parseFromXml('consumerBIC', $sXmlReply);
					$sAccountName = $this->parseFromXml('consumerName', $sXmlReply);
					$sAccountCity = '-';

					// $this->sTransactionId = $sTransactionId;
					$this->sTransactionStatus = strtoupper($sTransactionStatus);

					$this->sAccountCity = $sAccountCity;
					$this->sAccountName = $sAccountName;
					$this->sAccountNumber = $sAccountNumber;

					return $this->sTransactionStatus;
				}
			}
		}

		return false;
	}
}
