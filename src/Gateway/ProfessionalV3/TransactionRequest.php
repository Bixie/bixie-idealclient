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

class TransactionRequest extends IdealRequest {
	protected $sOrderId;
	protected $sOrderDescription;
	protected $fOrderAmount;
	protected $sReturnUrl;
	protected $sIssuerId;
	protected $sEntranceCode;

	// Transaction info
	protected $sTransactionId;
	protected $sTransactionUrl;

	public function __construct () {
		parent::__construct();

		if (defined('IDEAL_RETURN_URL')) {
			$this->setReturnUrl(IDEAL_RETURN_URL);
		}

		// Random EntranceCode
		$this->sEntranceCode = sha1(rand(1000000, 9999999));
	}

	public function setOrderId ($sOrderId) {
		$this->sOrderId = substr($sOrderId, 0, 16);
	}

	public function setOrderDescription ($sOrderDescription) {
		$this->sOrderDescription = trim(substr($this->escapeSpecialChars($sOrderDescription), 0, 32));
	}

	public function setOrderAmount ($fOrderAmount) {
		$this->fOrderAmount = round($fOrderAmount, 2);
	}

	public function setReturnUrl ($sReturnUrl) {
		// Fix for ING Bank, urlescape [ and ]
		$sReturnUrl = str_replace('[', '%5B', $sReturnUrl);
		$sReturnUrl = str_replace(']', '%5D', $sReturnUrl);

		$this->sReturnUrl = substr($sReturnUrl, 0, 512);
	}

	// ID of the selected bank
	public function setIssuerId ($sIssuerId) {
		$sIssuerId = preg_replace('/[^a-zA-Z0-9]/', '', $sIssuerId);
		$this->sIssuerId = $sIssuerId;
	}

	// A random generated entrance code
	public function setEntranceCode ($sEntranceCode) {
		$this->sEntranceCode = substr($sEntranceCode, 0, 40);
	}

	// Retrieve the transaction URL recieved in the XML response of de IDEAL SERVER
	public function getTransactionUrl () {
		return $this->sTransactionUrl;
	}

	// Execute request (Setup transaction)
	public function doRequest () {
		if ($this->checkConfiguration() && $this->checkConfiguration(array('sOrderId', 'sOrderDescription', 'fOrderAmount', 'sReturnUrl', 'sReturnUrl', 'sIssuerId', 'sEntranceCode'))) {
			$sTimestamp = gmdate('Y-m-d\TH:i:s.000\Z');
			$sCertificateFingerprint = $this->getCertificateFingerprint($this->sPrivateCertificateFile);

			$sXml = '<AcquirerTrxReq xmlns="http://www.idealdesk.com/ideal/messages/mer-acq/3.3.1" version="3.3.1">';
			$sXml .= '<createDateTimestamp>' . $sTimestamp . '</createDateTimestamp>';
			$sXml .= '<Issuer>';
			$sXml .= '<issuerID>' . $this->sIssuerId . '</issuerID>';
			$sXml .= '</Issuer>';
			$sXml .= '<Merchant>';
			$sXml .= '<merchantID>' . $this->sMerchantId . '</merchantID>';
			$sXml .= '<subID>' . $this->sSubId . '</subID>';
			$sXml .= '<merchantReturnURL>' . $this->sReturnUrl . '</merchantReturnURL>';
			$sXml .= '</Merchant>';
			$sXml .= '<Transaction>';
			$sXml .= '<purchaseID>' . $this->sOrderId . '</purchaseID>';
			$sXml .= '<amount>' . number_format($this->fOrderAmount, 2, '.', '') . '</amount>';
			$sXml .= '<currency>EUR</currency>';
			$sXml .= '<expirationPeriod>PT1H</expirationPeriod>';
			$sXml .= '<language>nl</language>';
			$sXml .= '<description>' . $this->sOrderDescription . '</description>';
			$sXml .= '<entranceCode>' . $this->sEntranceCode . '</entranceCode>';
			$sXml .= '</Transaction>';
			$sXml .= '</AcquirerTrxReq>';

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
			$sXml .= '<AcquirerTrxReq xmlns="http://www.idealdesk.com/ideal/messages/mer-acq/3.3.1" version="3.3.1">';
			$sXml .= '<createDateTimestamp>' . $sTimestamp . '</createDateTimestamp>';
			$sXml .= '<Issuer>';
			$sXml .= '<issuerID>' . $this->sIssuerId . '</issuerID>';
			$sXml .= '</Issuer>';
			$sXml .= '<Merchant>';
			$sXml .= '<merchantID>' . $this->sMerchantId . '</merchantID>';
			$sXml .= '<subID>' . $this->sSubId . '</subID>';
			$sXml .= '<merchantReturnURL>' . $this->sReturnUrl . '</merchantReturnURL>';
			$sXml .= '</Merchant>';
			$sXml .= '<Transaction>';
			$sXml .= '<purchaseID>' . $this->sOrderId . '</purchaseID>';
			$sXml .= '<amount>' . number_format($this->fOrderAmount, 2, '.', '') . '</amount>';
			$sXml .= '<currency>EUR</currency>';
			$sXml .= '<expirationPeriod>PT1H</expirationPeriod>';
			$sXml .= '<language>nl</language>';
			$sXml .= '<description>' . $this->sOrderDescription . '</description>';
			$sXml .= '<entranceCode>' . $this->sEntranceCode . '</entranceCode>';
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
			$sXml .= '</AcquirerTrxReq>';

			$sXmlReply = $this->postToHost($this->sAquirerUrl, $sXml, 10);

			if ($sXmlReply) {
				if ($this->verifyResponse($sXmlReply, 'AcquirerTrxRes')) {
					$this->sTransactionId = $this->parseFromXml('transactionID', $sXmlReply);
					$this->sTransactionUrl = html_entity_decode($this->parseFromXml('issuerAuthenticationURL', $sXmlReply));

					return $this->sTransactionId;
				}
			}
		}

		return false;
	}

	// Start transaction
	public function doTransaction () {
		if ((sizeof($this->aErrors) == 0) && $this->sTransactionId && $this->sTransactionUrl) {
			header('Location: ' . $this->sTransactionUrl);
			exit;
		}

		$this->setError('Please setup a valid transaction request first.', false, __FILE__, __LINE__);
		return false;
	}
}
