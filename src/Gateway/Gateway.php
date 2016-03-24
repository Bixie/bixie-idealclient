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

		$this->config['TEMP_PATH'] = !empty($config['TEMP_PATH']) ? $config['TEMP_PATH'] : dirname(dirname(__DIR__)) . '/temp';
		$this->config['CERTIFICATE_PATH'] = !empty($config['CERTIFICATE_PATH']) ? $config['CERTIFICATE_PATH'] : dirname(dirname(__DIR__)) . '/certificates';

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