<?php

namespace Bixie\IdealClient\Gateway\ProfessionalV3;

use Bixie\IdealClient\Exception\IdealClientResultException;
use Bixie\IdealClient\Gateway\Gateway as GatewayBase;
use Bixie\IdealClient\IdealClient;
use Bixie\IdealClient\IdealClientResult;
use Bixie\IdealClient\IdealClientTransaction;
use Bixie\IdealClient\Utils\Utils;

class Gateway extends GatewayBase {
	/**
	 * Gateway constructor.
	 * @param IdealClient $client
	 * @param array       $config
	 */
	public function __construct (IdealClient $client, array $config) {
		parent::__construct($client, $config);
	}

	/**
	 * @param  array $config
	 */
	protected function setConfig ($config) {
		parent::setConfig($config);
		// Merchant ID
		$this->config['MERCHANT_ID'] = $config['MERCHANT_ID'];
		// Your iDEAL Sub ID
		$this->config['SUB_ID'] = $config['SUB_ID'];
		// Use TEST/LIVE mode; true=TEST, false=LIVE
		$this->config['TEST_MODE'] = $config['TEST_MODE'];
		// Password used to generate private key file
		$this->config['PRIVATE_KEY_PASS'] = $config['PRIVATE_KEY_PASS'];
		// Name of your PRIVATE-KEY-FILE (should be located in /idealcheckout/certificates/)
		$this->config['PRIVATE_KEY_FILE'] = $config['PRIVATE_KEY_FILE'];
		// Name of your PRIVATE-CERTIFICATE-FILE (should be located in /idealcheckout/certificates/)
		$this->config['PRIVATE_CERTIFICATE_FILE'] = $config['PRIVATE_CERTIFICATE_FILE'];

		// ING gateway settings
		$this->config['GATEWAY_NAME'] = 'ING Bank - iDEAL Advanced';
		$this->config['GATEWAY_WEBSITE'] = 'http://www.ingbank.nl/';
		$this->config['GATEWAY_METHOD'] = 'ideal-professional-v3';

	}

	/**
	 * @param IdealClientTransaction $transaction
	 * @return IdealClientResult
	 */
	public function doSetup (IdealClientTransaction $transaction) {

		$result = new IdealClientResult();
		$result['title'] = 'Pay directly online via your own bank';


		if (strcmp($transaction->getTransactionStatus(), 'SUCCESS') === 0) {
			$result['messages'] = 'Transaction already completed.';
		} elseif ((strcmp($transaction->getTransactionStatus(), 'OPEN') === 0) && !empty($transaction->getTransactionUrl())) {
			header('Location: ' . $transaction->getTransactionUrl());
			exit;
		} else {

			$issuerRequest = new IssuerRequest();
			$issuerRequest->setSecurePath($this->config['CERTIFICATE_PATH']);
			$issuerRequest->setCachePath($this->config['TEMP_PATH']);
			$issuerRequest->setPrivateKey($this->config['PRIVATE_KEY_PASS'], $this->config['PRIVATE_KEY_FILE'], $this->config['PRIVATE_CERTIFICATE_FILE']);
			$issuerRequest->setMerchant($this->config['MERCHANT_ID'], $this->config['SUB_ID']);
			$issuerRequest->setAquirer($this->config['GATEWAY_NAME'], $this->config['TEST_MODE']);


			$issuerList = $issuerRequest->doRequest();

			if ($issuerRequest->hasErrors()) {
				if ($this->config['TEST_MODE']) {

					$result['html'] = '<code>' . var_export($issuerRequest->getErrors(), true) . '</code>';
					return $result;

				} else {

					$transaction->setTransactionStatus('FAILURE');

					$transaction->addLogEntry(sprintf('Executing IssuerRequest on %s. Recieved: ERROR%s %s.',
						date('Y-m-d, H:i:s'),
						"\n\n",
						var_export($issuerRequest->getErrors(), true)));

					$this->client->saveTransaction($transaction);

					$result['messages'][] = 'Due to technical problems, no payments are possible at the moment.';

					if ($transaction->getTransactionPaymentUrl()) {
						$result['html'] .= '<p><a href="' . htmlentities($transaction->getTransactionPaymentUrl()) . '">kies een andere betaalmethode</a></p>';
					} elseif ($transaction->getTransactionFailureUrl()) {
						$result['html'] .= '<p><a href="' . htmlentities($transaction->getTransactionFailureUrl()) . '">terug naar de website</a></p>';
					}

					return $result;
				}
			}

			$transaction->addLogEntry(sprintf('Executing IssuerRequest on %s.', date('Y-m-d, H:i:s')));

			$this->client->saveTransaction($transaction);

			$result['issuerlist'] = [
				'action' => Utils::getRootUrl() . 'handler.php?task=transaction&order_id=' . $transaction->getOrderId() . '&order_code=' . $transaction->getOrderCode(),
				'issuers' => $issuerList,
			];

		}

		return $result;
	}

	/**
	 * @return IdealClientResult
	 */
	public function doTransaction () {
		$result = new IdealClientResult();

		if (empty($_POST['issuer_id']) && !empty($_GET['issuer_id'])) {
				$_POST['issuer_id'] = $_GET['issuer_id'];
		}

		// Look for proper GET's en POST's
		if (empty($_POST['issuer_id']) || empty($_GET['order_id']) || empty($_GET['order_code'])) {

			throw new IdealClientResultException('Invalid transaction request.');

		}
		$issuer_id = $_POST['issuer_id'];


		// Lookup transaction
		if (!$transaction = $this->client->findByOrder($_GET['order_id'], $_GET['order_code'])) {

			throw new IdealClientResultException('Transaction not found.');

		}

		if (strcmp($transaction->getTransactionStatus(), 'SUCCESS') === 0) {

			$result['messages'] = 'Transaction already completed.';

		} elseif ((strcmp($transaction->getTransactionStatus(), 'OPEN') === 0) && !empty($transaction->getTransactionUrl())) {
			header('Location: ' . $transaction->getTransactionUrl());
			exit;
		} else {
			$transactionRequest = new TransactionRequest();
			$transactionRequest->setSecurePath($this->config['CERTIFICATE_PATH']);
			$transactionRequest->setCachePath($this->config['TEMP_PATH']);
			$transactionRequest->setPrivateKey($this->config['PRIVATE_KEY_PASS'], $this->config['PRIVATE_KEY_FILE'], $this->config['PRIVATE_CERTIFICATE_FILE']);
			$transactionRequest->setMerchant($this->config['MERCHANT_ID'], $this->config['SUB_ID']);
			$transactionRequest->setAquirer($this->config['GATEWAY_NAME'], $this->config['TEST_MODE']);

			$transactionRequest->setOrderId($transaction->getOrderId());
			$transactionRequest->setOrderDescription($transaction->getTransactionDescription());
			$transactionRequest->setOrderAmount($transaction->getTransactionAmount());

			$transactionRequest->setIssuerId($issuer_id);
			$transactionRequest->setEntranceCode($transaction->getTransactionCode());
			$transactionRequest->setReturnUrl(Utils::getRootUrl() . 'handler.php?task=return');


			// Find TransactionID
			$sTransactionId = $transactionRequest->doRequest();

			if ($transactionRequest->hasErrors()) {

				if ($this->config['TEST_MODE']) {

					$result['html'] = '<code>' . var_export($transactionRequest->getErrors(), true) . '</code>';
					return $result;

				} else {

					$transaction->setTransactionStatus('FAILURE');

					$transaction->addLogEntry(sprintf('Executing TransactionRequest on %s. Recieved: ERROR%s %s.',
						date('Y-m-d, H:i:s'),
						"\n\n",
						var_export($transactionRequest->getErrors(), true)));

					$this->client->saveTransaction($transaction);

					$result['messages'] = 'Due to technical problems, no payments are possible at the moment.';

					if ($transaction->getTransactionPaymentUrl()) {
						$result['html'] .= '<p><a href="' . htmlentities($transaction->getTransactionPaymentUrl()) . '">kies een andere betaalmethode</a></p>';
					} elseif ($transaction->getTransactionFailureUrl()) {
						$result['html'] .= '<p><a href="' . htmlentities($transaction->getTransactionFailureUrl()) . '">terug naar de website</a></p>';
					}

					$result['html'] .= '<!--

' . var_export($transactionRequest->getErrors(), true) . '

-->';

					return $result;
				}
			}

			$sTransactionUrl = $transactionRequest->getTransactionUrl();

			$transaction->addLogEntry(sprintf('Executing TransactionRequest on %s. Recieved: %s.',
				date('Y-m-d, H:i:s'),
				$sTransactionId));

			$transaction->setTransactionId($sTransactionId);
			$transaction->setTransactionUrl($sTransactionUrl);
			$transaction->setTransactionStatus('OPEN');
			$transaction->setTransactionDate(time());

			$this->client->saveTransaction($transaction);

			$transactionRequest->doTransaction();
		}

		return $result;
	}

	/**
	 * @return IdealClientResult
	 */
	public function doReturn () {
		$result = new IdealClientResult();

		if (empty($_GET['trxid']) || empty($_GET['ec'])) {

			throw new IdealClientResultException('Invalid return request.');

		}

		if (!$transaction = $this->client->findByTransaction($_GET['trxid'], $_GET['ec'])) {

			throw new IdealClientResultException('Transaction not found.');

		}

		// Transaction already finished
		if (strcasecmp($transaction->getTransactionStatus(), 'SUCCESS') === 0) {
			
			if (!empty($transaction->getTransactionSuccessUrl())) {
				header('Location: ' . $transaction->getTransactionSuccessUrl());
				exit;
			}
			$result['messages'] = 'Payment succesfully received.';
			$result['html'] .= '<a class="uk-button uk-margin" href="' . htmlspecialchars(Utils::getRootUrl()) . '">Back to main site</a>';

		} else {
			// Check status
			$statusRequest = new StatusRequest();
			$statusRequest->setSecurePath($this->config['CERTIFICATE_PATH']);
			$statusRequest->setCachePath($this->config['TEMP_PATH']);
			$statusRequest->setPrivateKey($this->config['PRIVATE_KEY_PASS'], $this->config['PRIVATE_KEY_FILE'], $this->config['PRIVATE_CERTIFICATE_FILE']);
			$statusRequest->setMerchant($this->config['MERCHANT_ID'], $this->config['SUB_ID']);
			$statusRequest->setAquirer($this->config['GATEWAY_NAME'], $this->config['TEST_MODE']);

			$statusRequest->setTransactionId($transaction->getTransactionId());

			$transaction->setTransactionStatus($statusRequest->doRequest());

			if ($statusRequest->hasErrors()) {
				if ($this->config['TEST_MODE']) {
					
					$result['html'] = '<code>' . var_export($statusRequest->getErrors(), true) . '</code>';
					return $result;
					
				} else {

					$transaction->setTransactionStatus('FAILURE');

					$transaction->addLogEntry(sprintf('Executing StatusRequest on %s. Recieved: ERROR%s %s.',
						date('Y-m-d, H:i:s'),
						"\n\n",
						var_export($statusRequest->getErrors(), true)));

					$this->client->saveTransaction($transaction);

					$result['messages'] = 'Due to technical problems, no payments are possible at the moment.';

					if ($transaction->getTransactionPaymentUrl()) {
						$result['html'] .= '<p><a href="' . htmlentities($transaction->getTransactionPaymentUrl()) . '">kies een andere betaalmethode</a></p>';
					} elseif ($transaction->getTransactionFailureUrl()) {
						$result['html'] .= '<p><a href="' . htmlentities($transaction->getTransactionFailureUrl()) . '">terug naar de website</a></p>';
					}

					$result['html'] .= '<!--

' . var_export($statusRequest->getErrors(), true) . '

-->';

					return $result;
				}
			}

			$transaction->addLogEntry(sprintf('Executing StatusRequest for #%s on %s. Recieved: %s.',
				date('Y-m-d, H:i:s'),
				$transaction->getTransactionId(),
				$transaction->getTransactionStatus()));

			$this->client->saveTransaction($transaction);

			$this->client->updateStatus($transaction);

			// Set status message
			if (strcmp($transaction->getTransactionStatus(), 'SUCCESS') === 0) {

				$result['messages'] = 'Payment succesfully received.';
				$result['html'] .= '<a class="uk-button uk-margin" href="' . htmlspecialchars(Utils::getRootUrl()) . '">Back to main site</a>';

			} elseif ((strcmp($transaction->getTransactionStatus(), 'OPEN') === 0) && !empty($transaction->getTransactionUrl())) {
				$result['messages'] = 'Payment not finished.';

				if ($transaction->getTransactionUrl()) {
					$result['html'] .= '<a class="uk-button uk-margin" href="' . htmlspecialchars($transaction->getTransactionUrl()) . '">Continue<i class="uk-icon-angle-double-right uk-margin-small-left"></i></a>';
				}
			} else {
				if (strcasecmp($transaction->getTransactionStatus(), 'CANCELLED') === 0) {

					$result['messages'] = 'Payment is cancelled.';
					$result['html'] .= '<a class="uk-button uk-margin" href="' . htmlspecialchars(Utils::getRootUrl()) . '">Back to main site</a>';

				} elseif (strcasecmp($transaction->getTransactionStatus(), 'EXPIRED') === 0) {

					$result['messages'] = 'Payment has expired.';
					$result['html'] .= '<a class="uk-button uk-margin" href="' . htmlspecialchars(Utils::getRootUrl()) . '">Back to main site</a>';

				} else {
					$result['messages'] = 'Payment has failed.';
					$result['html'] .= '<a class="uk-button uk-margin" href="' . htmlspecialchars(Utils::getRootUrl()) . '">Back to main site</a>';
				}

				if ($transaction->getTransactionPaymentUrl()) {
					$result['html'] .= '<p><a href="' . htmlentities($transaction->getTransactionPaymentUrl()) . '">kies een andere betaalmethode</a></p>';
				} elseif ($transaction->getTransactionFailureUrl()) {
					$result['html'] .= '<p><a href="' . htmlentities($transaction->getTransactionFailureUrl()) . '">terug naar de website</a></p>';
				}
			}


			if ($transaction->getTransactionSuccessUrl() && (strcasecmp($transaction->getTransactionStatus(), 'SUCCESS') === 0)) {
				header('Location: ' . $transaction->getTransactionSuccessUrl());
				exit;
			} elseif ($transaction->getTransactionPaymentUrl() && !in_array($transaction->getTransactionStatus(), array('SUCCESS', 'PENDING'))) {
				header('Location: ' . $transaction->getTransactionPaymentUrl());
				exit;
			} elseif ($transaction->getTransactionFailureUrl() && !in_array($transaction->getTransactionStatus(), array('SUCCESS', 'PENDING'))) {
				header('Location: ' . $transaction->getTransactionFailureUrl());
				exit;
			}
		}

		return $result;
	}

}