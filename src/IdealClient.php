<?php

namespace Bixie\IdealClient;


use Bixie\IdealClient\Exception\IdealClientException;
use Bixie\IdealClient\Gateway\Gateway;
use Bixie\IdealClient\Utils\Utils;

class IdealClient {
	/**
	 * @var Gateway
	 */
	protected $gateway;
	/**
	 * @var IdealClientDatabase
	 */
	protected $database;
	/**
	 * @var array
	 */
	protected $config;
	/**
	 * @var \Closure
	 */
	protected $updateCallback;

	/**
	 * IdealClient constructor.
	 * @param $config
	 */
	public function __construct (array $config) {
		$this->config = $config;
		$this->database = new IdealClientDatabase($config['database']);
		//temp load idealcheckout http helpers
		if (!function_exists('Bixie\IdealClient\Utils\Http\idealcheckout_doHttpRequest')) {
			require __DIR__ . '/Utils/Http.php';
		}
	}

	/**
	 * @param \Closure $updateCallback
	 * @return IdealClient
	 */
	public function setUpdateCallback ($updateCallback) {
		$this->updateCallback = $updateCallback;
		return $this;
	}

	/**
	 * @param $data
	 * @return IdealClientTransaction
	 */
	public function createTransaction ($data) {
		$transaction = new IdealClientTransaction(array_merge([
			'order_code' => Utils::getRandomCode(32),
			'gateway_code' => 'ideal',
			'country_code' => 'NL',
			'currency_code' => 'EUR',
			'transaction_id' => Utils::getRandomCode(32),
			'transaction_code' => Utils::getRandomCode(32),
			'transaction_date' => time()
		], $data));

		return $this->database->saveTransaction($transaction);
	}

	/**
	 * @param $order_id
	 * @param $order_code
	 * @return IdealClientTransaction|bool
	 */
	public function findByOrder ($order_id, $order_code) {
		if ($transaction = $this->database->findByOrder($order_id, $order_code)) {
			return $transaction;
		}
		throw new IdealClientException(sprintf("Transaction %s, %s not found", $order_id, $order_code));

	}

	/**
	 * @param $transaction_id
	 * @param $transaction_code
	 * @return IdealClientTransaction|bool
	 */
	public function findByTransaction ($transaction_id, $transaction_code) {
		if ($transaction = $this->database->findByTransaction($transaction_id, $transaction_code)) {
			return $transaction;
		}
		throw new IdealClientException(sprintf("Transaction %s, %s not found", $transaction_id, $transaction_code));

	}

	/**
	 * @param IdealClientTransaction $transaction
	 * @return IdealClientTransaction
	 */
	public function saveTransaction (IdealClientTransaction $transaction) {
		return $this->database->saveTransaction($transaction);
	}

	/**
	 * called when transaction is updated
	 * @param IdealClientTransaction $transaction
	 */
	public function updateStatus (IdealClientTransaction $transaction) {
		if (is_callable($this->updateCallback)) {
			call_user_func($this->updateCallback, $transaction);
		}
	}

	/**
	 * @return Gateway
	 */
	public function getGateway () {

		$classname = sprintf('Bixie\IdealClient\Gateway\%s\Gateway', $this->config['gateway']['name']);

		if (!class_exists($classname)) {
			throw new IdealClientException(sprintf('Gateway %s not found', $this->config['gateway']['name']));
		}

		$this->gateway = new $classname($this, $this->config['gateway']);

		return $this->gateway;
	}

	/**
	 * @param IdealClientTransaction $transaction
	 * @return IdealClientResult
	 */
	public function doSetup (IdealClientTransaction $transaction) {
		return $this->getGateway()->doSetup($transaction);
	}

	/**
	 * @return IdealClientResult
	 */
	public function doTransaction () {
		return $this->getGateway()->doTransaction();
	}

	/**
	 * @return IdealClientResult
	 */
	public function doReturn () {
		return $this->getGateway()->doReturn();
	}

}