<?php

namespace Bixie\IdealClient\Gateway;

use Bixie\IdealClient\IdealClient;
use Bixie\IdealClient\IdealClientResult;
use Bixie\IdealClient\IdealClientTransaction;

class Gateway {

	/**
	 * @var IdealClient
	 */
	protected $client;
	/**
	 * @var array
	 */
	protected $config;

	/**
	 * Gateway constructor.
	 * @param IdealClient $client
	 * @param array       $config
	 */
	public function __construct (IdealClient $client, array $config) {
		$this->client = $client;
		$this->setConfig($config);
	}

	protected function setConfig ($config) {
		// Fix temp path
		if (empty($config['TEMP_PATH'])) {
			$this->config['TEMP_PATH'] = dirname(dirname(__DIR__)) . '/temp';
		}

		// Fix certificate path
		if (empty($config['CERTIFICATE_PATH'])) {
			$this->config['CERTIFICATE_PATH'] = dirname(dirname(__DIR__)) . '/certificates';
		}

	}

	/**
	 * @param IdealClientTransaction $transaction
	 * @return IdealClientResult
	 */
	public function doSetup (IdealClientTransaction $transaction) {

		$result = new IdealClientResult();
		$result['messages'] = 'Method not implemented.';

		return $result;
	}

	/**
	 * @return IdealClientResult
	 */
	public function doTransaction () {

		$result = new IdealClientResult();
		$result['messages'] = 'Method not implemented.';

		return $result;
	}

	/**
	 * @return IdealClientResult
	 */
	public function doReturn () {

		$result = new IdealClientResult();
		$result['messages'] = 'Method not implemented.';

		return $result;
	}

}