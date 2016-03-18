<?php

namespace Bixie\IdealClient;


class IdealClientTransaction {

	/**
	 * @var integer
	 */
	public $id;
	/**
	 * @var string
	 */
	public $order_id;
	/**
	 * @var string
	 */
	public $order_code;
	/**
	 * @var array
	 */
	public $order_params;
	/**
	 * @var string
	 */
	public $gateway_code;
	/**
	 * @var string
	 */
	public $store_code;
	/**
	 * @var string
	 */
	public $language_code;
	/**
	 * @var string
	 */
	public $country_code;
	/**
	 * @var string
	 */
	public $currency_code;
	/**
	 * @var string
	 */
	public $transaction_id;
	/**
	 * @var string
	 */
	public $transaction_code;
	/**
	 * @var array
	 */
	public $transaction_params;
	/**
	 * @var string
	 */
	public $transaction_date;
	/**
	 * @var float
	 */
	public $transaction_amount;
	/**
	 * @var string
	 */
	public $transaction_description;
	/**
	 * @var string
	 */
	public $transaction_status;
	/**
	 * @var string
	 */
	public $transaction_url;
	/**
	 * @var string
	 */
	public $transaction_payment_url;
	/**
	 * @var string
	 */
	public $transaction_success_url;
	/**
	 * @var string
	 */
	public $transaction_pending_url;
	/**
	 * @var string
	 */
	public $transaction_failure_url;
	/**
	 * @var string
	 */
	public $transaction_log;

	/**
	 * IdealClientTransaction constructor.
	 * @param array $data
	*/
	public function __construct (array $data) {
		foreach (get_object_vars($this) as $key => $default) {
			$this->$key = isset($data[$key]) ? $data[$key] : $default;
		}
	}

	public function toArray () {
		$data = [];
		foreach (get_object_vars($this) as $key => $value) {
			switch ($key) {
				case 'order_params':
					$data[$key] = $this->getOrderParams();
					break;
				case 'transaction_params':
					$data[$key] = $this->getTransactionParams();
					break;
				default:
					$data[$key] = $value;
					break;
			}
		}
		return $data;
	}


	/**
	 * @return int
	 */
	public function getId () {
		return $this->id;
	}

	/**
	 * @param int $id
	 * @return IdealClientTransaction
	 */
	public function setId ($id) {
		$this->id = $id;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getOrderId () {
		return $this->order_id;
	}

	/**
	 * @param string $order_id
	 * @return IdealClientTransaction
	 */
	public function setOrderId ($order_id) {
		$this->order_id = $order_id;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getOrderCode () {
		return $this->order_code;
	}

	/**
	 * @param string $order_code
	 * @return IdealClientTransaction
	 */
	public function setOrderCode ($order_code) {
		$this->order_code = $order_code;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getOrderParams () {
		if (!is_array($this->order_params)) {
			$this->order_params = json_decode($this->order_params, true);
		}
		return $this->order_params;
	}

	/**
	 * @param array $order_params
	 * @return IdealClientTransaction
	 */
	public function setOrderParams ($order_params) {
		$this->order_params = $order_params;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getGatewayCode () {
		return $this->gateway_code;
	}

	/**
	 * @param string $gateway_code
	 * @return IdealClientTransaction
	 */
	public function setGatewayCode ($gateway_code) {
		$this->gateway_code = $gateway_code;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getStoreCode () {
		return $this->store_code;
	}

	/**
	 * @param string $store_code
	 * @return IdealClientTransaction
	 */
	public function setStoreCode ($store_code) {
		$this->store_code = $store_code;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getLanguageCode () {
		return $this->language_code;
	}

	/**
	 * @param string $language_code
	 * @return IdealClientTransaction
	 */
	public function setLanguageCode ($language_code) {
		$this->language_code = $language_code;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getCountryCode () {
		return $this->country_code;
	}

	/**
	 * @param string $country_code
	 * @return IdealClientTransaction
	 */
	public function setCountryCode ($country_code) {
		$this->country_code = $country_code;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getCurrencyCode () {
		return $this->currency_code;
	}

	/**
	 * @param string $currency_code
	 * @return IdealClientTransaction
	 */
	public function setCurrencyCode ($currency_code) {
		$this->currency_code = $currency_code;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTransactionId () {
		return $this->transaction_id;
	}

	/**
	 * @param string $transaction_id
	 * @return IdealClientTransaction
	 */
	public function setTransactionId ($transaction_id) {
		$this->transaction_id = $transaction_id;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTransactionCode () {
		return $this->transaction_code;
	}

	/**
	 * @param string $transaction_code
	 * @return IdealClientTransaction
	 */
	public function setTransactionCode ($transaction_code) {
		$this->transaction_code = $transaction_code;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getTransactionParams () {
		if (!is_array($this->transaction_params)) {
			$this->transaction_params = json_decode($this->transaction_params, true);
		}
		return $this->transaction_params;
	}

	/**
	 * @param array $transaction_params
	 * @return IdealClientTransaction
	 */
	public function setTransactionParams ($transaction_params) {
		$this->transaction_params = $transaction_params;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTransactionDate () {
		return $this->transaction_date;
	}

	/**
	 * @param string $transaction_date
	 * @return IdealClientTransaction
	 */
	public function setTransactionDate ($transaction_date) {
		$this->transaction_date = $transaction_date;
		return $this;
	}

	/**
	 * @return float
	 */
	public function getTransactionAmount () {
		return $this->transaction_amount;
	}

	/**
	 * @param float $transaction_amount
	 * @return IdealClientTransaction
	 */
	public function setTransactionAmount ($transaction_amount) {
		$this->transaction_amount = $transaction_amount;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTransactionDescription () {
		return $this->transaction_description;
	}

	/**
	 * @param string $transaction_description
	 * @return IdealClientTransaction
	 */
	public function setTransactionDescription ($transaction_description) {
		$this->transaction_description = $transaction_description;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTransactionStatus () {
		return $this->transaction_status;
	}

	/**
	 * @param string $transaction_status
	 * @return IdealClientTransaction
	 */
	public function setTransactionStatus ($transaction_status) {
		$this->transaction_status = $transaction_status;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTransactionUrl () {
		return $this->transaction_url;
	}

	/**
	 * @param string $transaction_url
	 * @return IdealClientTransaction
	 */
	public function setTransactionUrl ($transaction_url) {
		$this->transaction_url = $transaction_url;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTransactionPaymentUrl () {
		return $this->transaction_payment_url;
	}

	/**
	 * @param string $transaction_payment_url
	 * @return IdealClientTransaction
	 */
	public function setTransactionPaymentUrl ($transaction_payment_url) {
		$this->transaction_payment_url = $transaction_payment_url;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTransactionSuccessUrl () {
		return $this->transaction_success_url;
	}

	/**
	 * @param string $transaction_success_url
	 * @return IdealClientTransaction
	 */
	public function setTransactionSuccessUrl ($transaction_success_url) {
		$this->transaction_success_url = $transaction_success_url;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTransactionPendingUrl () {
		return $this->transaction_pending_url;
	}

	/**
	 * @param string $transaction_pending_url
	 * @return IdealClientTransaction
	 */
	public function setTransactionPendingUrl ($transaction_pending_url) {
		$this->transaction_pending_url = $transaction_pending_url;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTransactionFailureUrl () {
		return $this->transaction_failure_url;
	}

	/**
	 * @param string $transaction_failure_url
	 * @return IdealClientTransaction
	 */
	public function setTransactionFailureUrl ($transaction_failure_url) {
		$this->transaction_failure_url = $transaction_failure_url;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTransactionLog () {
		return $this->transaction_log;
	}

	/**
	 * @param string $transaction_log
	 * @return IdealClientTransaction
	 */
	public function setTransactionLog ($transaction_log) {
		$this->transaction_log = $transaction_log;
		return $this;
	}



}