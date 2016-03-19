<?php

namespace Bixie\IdealClient;


use Bixie\IdealClient\Exception\IdealClientException;

class IdealClientDatabase {

	const TRANSACTION_TABLE = 'idealcheckout';

	protected $config;

	protected static $connection;

	/**
	 * IdealClientDatabase constructor.
	 * @param $config
	 */
	public function __construct ($config) {
		$this->config = $config;
	}

	/**
	 * @return \PDO
	 */
	public function getConnection () {
		if (!isset(self::$connection)) {

			$dsn = "mysql:host={$this->config['host']};dbname={$this->config['name']};charset=utf8";
			$opt = [
				\PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
				\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
				\PDO::ATTR_EMULATE_PREPARES   => true,
			];
			self::$connection = new \PDO($dsn, $this->config['user'], $this->config['pass'], $opt);
		}
		return self::$connection;
	}

	/**
	 * @param $order_id
	 * @param $order_code
	 * @return IdealClientTransaction|bool
	 */
	public function findByOrder ($order_id, $order_code) {
		$sql = sprintf("SELECT * FROM %s%s WHERE order_id = :order_id AND order_code = :order_code",
			$this->config['prefix'],
			self::TRANSACTION_TABLE
		);
		$stm = $this->getConnection()->prepare($sql);
		$stm->execute(compact('order_id', 'order_code'));
		if (!$res = $stm->fetch()) {
			return false;
		}
		return new IdealClientTransaction($res);
	}

	/**
	 * @param $transaction_id
	 * @param $transaction_code
	 * @return IdealClientTransaction|bool
	 */
	public function findByTransaction ($transaction_id, $transaction_code) {
		$sql = sprintf("SELECT * FROM %s%s WHERE transaction_id = :transaction_id AND transaction_code = :transaction_code",
			$this->config['prefix'],
			self::TRANSACTION_TABLE
		);
		$stm = $this->getConnection()->prepare($sql);
		$stm->execute(compact('transaction_id', 'transaction_code'));
		if (!$res = $stm->fetch()) {
			return false;
		}
		return new IdealClientTransaction($res);
	}

	/**
	 * @param IdealClientTransaction $transaction
	 * @return IdealClientTransaction
	 */
	public function saveTransaction (IdealClientTransaction $transaction) {

		$data = $transaction->toArray();
		foreach (['order_params', 'transaction_params'] as $key) {
			if (is_array($data[$key])) {
				$data[$key] = json_encode($data[$key], JSON_NUMERIC_CHECK);
			}
		}


		if (empty($data['id'])) {
			$sql = sprintf("INSERT INTO %s%s (%s) VALUES (%s)",
					$this->config['prefix'],
					self::TRANSACTION_TABLE,
					implode(', ', array_keys($data)),
					implode(', ', array_map(function ($key) {
					    return ':' . $key;
					}, array_keys($data)))
				);
		} else {
			$sql = sprintf("UPDATE %s%s SET %s WHERE id = :id",
				$this->config['prefix'],
				self::TRANSACTION_TABLE,
				implode(', ', array_map(function ($key) {
					return sprintf("%s = :%s", $key, $key);
				}, array_keys($data)))
			);
		}
		if (!$r = $this->getConnection()->prepare($sql)->execute($data)) {
			throw new IdealClientException("Error in saving to database");
		}

		if (empty($data['id'])) {
			$transaction->setId((int) $this->getConnection()->lastInsertId());
		}
		return $transaction;
	}

}