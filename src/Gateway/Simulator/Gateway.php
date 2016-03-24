<?php

namespace Bixie\IdealClient\Gateway\Simulator;

use Bixie\IdealClient\Exception\IdealClientResultException;
use Bixie\IdealClient\Gateway\Gateway as GatewayBase;
use Bixie\IdealClient\IdealClientResult;
use Bixie\IdealClient\IdealClientTransaction;
use Bixie\IdealClient\Utils\Utils;

class Gateway extends GatewayBase {

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

		// Basic gateway settings simulator
		$this->config['GATEWAY_NAME'] = 'iDEAL Simulator - iDEAL';
		$this->config['GATEWAY_WEBSITE'] = 'http://www.ideal-simulator.nl/';
		$this->config['GATEWAY_METHOD'] = 'ideal-simulator';

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
			$sFormHtml = '
<form action="https://www.ideal-checkout.nl/payment/" method="post">
	<input name="gateway_code" type="hidden" value="ideal">
	<input name="order_id" type="hidden" value="' . htmlspecialchars($transaction->getOrderId()) . '">
	<input name="order_description" type="hidden" value="' . htmlspecialchars($transaction->getTransactionDescription()) . '">
	<input name="order_amount" type="hidden" value="' . htmlspecialchars($transaction->getTransactionAmount()) . '">
	<input name="url_success" type="hidden" value="' . htmlspecialchars(Utils::getRootUrl() . 'handler.php?task=return&transaction_id=' . $transaction->getTransactionId() . '&transaction_code=' . $transaction->getTransactionCode() . '&status=SUCCESS') . '">
	<input name="url_pending" type="hidden" value="' . htmlspecialchars(Utils::getRootUrl() . 'handler.php?task=return&transaction_id=' . $transaction->getTransactionId() . '&transaction_code=' . $transaction->getTransactionCode() . '&status=PENDING') . '">
	<input name="url_cancel" type="hidden" value="' . htmlspecialchars(Utils::getRootUrl() . 'handler.php?task=return&transaction_id=' . $transaction->getTransactionId() . '&transaction_code=' . $transaction->getTransactionCode() . '&status=CANCELLED') . '">
	<input name="url_error" type="hidden" value="' . htmlspecialchars(Utils::getRootUrl() . 'handler.php?task=return&transaction_id=' . $transaction->getTransactionId() . '&transaction_code=' . $transaction->getTransactionCode() . '&status=FAILURE') . '">
	<button type="submit" class="uk-button">Continue<i class="uk-icon-angle-double-right uk-margin-small-left"></i></button>
</form>';

			$result['html'] = $sFormHtml;

			if (($this->config['TEST_MODE'] == false) && !idealcheckout_getDebugMode()) {
				$result['html'] .= '<script type="text/javascript"> function doAutoSubmit() { document.forms[0].submit(); } setTimeout(\'doAutoSubmit()\', 100); </script>';
			}
		}

		return $result;
	}

	/**
	 * @return IdealClientResult
	 */
	public function doReturn () {
		$result = new IdealClientResult();

		if (empty($_GET['transaction_id']) || empty($_GET['transaction_code']) || empty($_GET['status'])) {

			throw new IdealClientResultException('Invalid return request.');
		
		}

		if (!$transaction = $this->client->findByTransaction($_GET['transaction_id'], $_GET['transaction_code'])) {

			throw new IdealClientResultException('Transaction not found.');
			
		}

		$transactionStatus = $_GET['status'];
		$statusChanged = ((strcasecmp($transaction->getTransactionStatus(), $transactionStatus) !== 0) && !in_array($transaction->getTransactionStatus(), ['SUCCESS']));

		if ($statusChanged) {

			$transaction->setTransactionStatus($transactionStatus);

			$transaction->addLogEntry(sprintf('Executing StatusRequest on %s for #%s Recieved: %s.', 
				date('Y-m-d, H:i:s'), 
				$transaction->getTransactionId(), 
				$transaction->getTransactionStatus()));

			$this->client->saveTransaction($transaction);

			$this->client->updateStatus($transaction);

		}

		// Set status message
		if (strcasecmp($transaction->getTransactionStatus(), 'SUCCESS') === 0) {
			if (!empty($transaction->getTransactionSuccessUrl())) {
				header('Location: ' . $transaction->getTransactionSuccessUrl());
				exit;
			}
			$result['messages'] = 'Payment succesfully received.';
			$result['html'] .= '<a class="uk-button uk-margin" href="' . htmlspecialchars(Utils::getRootUrl()) . '">Back to main site</a>';

		} elseif (strcmp($transaction->getTransactionStatus(), 'PENDING') === 0) {
			if (!empty($transaction->getTransactionPendingUrl())) {
				header('Location: ' . $transaction->getTransactionPendingUrl());
				exit;
			}
			$result['messages'] = 'Payment is pending.';
			$result['html'] .= '<a class="uk-button uk-margin" href="' . htmlspecialchars(Utils::getRootUrl()) . '">Back to main site</a>';

		} elseif (strcasecmp($transaction->getTransactionStatus(), 'CANCELLED') === 0) {
			if (!empty($transaction->getTransactionFailureUrl())) {
				header('Location: ' . $transaction->getTransactionFailureUrl());
				exit;
			}
			$result['messages'] = 'Payment is cancelled.';
			$result['html'] .= '<a class="uk-button uk-margin" href="' . htmlspecialchars(Utils::getRootUrl()) . '">Back to main site</a>';

		} else {
			if (!empty($transaction->getTransactionFailureUrl())) {
				header('Location: ' . $transaction->getTransactionFailureUrl());
				exit;
			}
			$result['messages'] = 'Payment has failed.';
			$result['html'] .= '<a class="uk-button uk-margin" href="' . htmlspecialchars(Utils::getRootUrl()) . '">Back to main site</a>';

		}

		return $result;
	}

}