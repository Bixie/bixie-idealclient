<?php

namespace Bixie\IdealClient;


class IdealClientResult implements \ArrayAccess{

	public $title = '';

	public $html = '';

	public $issuerlist = '';

	public $messages = [];

	public function toArray () {
		$data = [];
		foreach (get_object_vars($this) as $key => $value) {
			$data[$key] = $value;
		}
		return $data;
	}

	/**
	 * Whether a offset exists
	 * @param mixed $offset
	 * @return bool
	 */
	public function offsetExists ($offset) {
		return property_exists($this, $offset);
	}

	/**
	 * Offset to retrieve
	 * @param mixed $offset
	 * @return mixed|string
	 */
	public function offsetGet ($offset) {
		return property_exists($this, $offset) ? $this->$offset : '';
	}

	/**
	 * Offset to set
	 * @param mixed $offset
	 * @param mixed $value
	 */
	public function offsetSet ($offset, $value) {
		if ($offset == 'messages') {
			$this->messages[] = $value;
		} elseif (property_exists($this, $offset)) {
			 $this->$offset = $value;
		}
	}

	/**
	 * Offset to unset
	 * @param mixed $offset
	 */
	public function offsetUnset ($offset) {
		if (property_exists($this, $offset)) $this->$offset = '';
	}
}