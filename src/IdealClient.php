<?php

namespace Bixie\IdealClient;


use Bixie\IdealClient\Exception\IdealClientException;
use Bixie\IdealClient\Utils\Utils;

class IdealClient {

	protected $gateway;

	protected $database;

	protected $config;

	/**
	 * IdealClient constructor.
	 * @param $config
	 */
	public function __construct (array $config) {
		$this->config = $config;
		$this->database = new IdealClientDatabase($config['database']);

	}

	public function createTransaction ($data) {
		$transaction = new IdealClientTransaction(array_merge([
			'order_code' => Utils::getRandomCode(32),
			'wateway_code' => $this->config['gateway']['name'],
			'country_code' => 'NL',
			'currency_code' => 'EUR',
			'transaction_id' => Utils::getRandomCode(32),
			'transaction_code' => Utils::getRandomCode(32),
			'transaction_date' => time()
		], $data));

		return $this->database->saveTransaction($transaction);
	}

	public function findTransaction ($order_id, $order_code) {
		if ($transaction = $this->database->findByOrderIdAndCode($order_id, $order_code)) {
			return $transaction;
		}
		throw new IdealClientException(sprintf("Transaction %s, %s not found", $order_id, $order_code));

	}

	public function updateStatus ($transaction_id, $transaction_code) {

	}

	public function getGateway () {
		require_once(__DIR__ . '/../../../../idealcheckout/includes/init.php');

		$this->gateway = new \Gateway();

		return $this->gateway;
	}

	public function doSetup ($order_id, $order_code) {
		$this->getGateway()->doSetup($order_id, $order_code);
	}

	public function doReturn () {
		$this->getGateway()->doReturn($this);
	}

}