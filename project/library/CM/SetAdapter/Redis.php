<?php

class CM_SetAdapter_Redis extends CM_SetAdapter_Abstract {

	/**
	 * @param string  $key
	 * @param string  $value
	 */
	public function add($key, $value) {
		CM_Cache_Redis::sAdd($key, $value);
	}

	/**
	 * @param string  $key
	 * @param string  $value
	 */
	public function delete($key, $value) {
		CM_Cache_Redis::sRem($key, $value);
	}

	/**
	 * @param string $key
	 * @return string[]
	 */
	public function flush($key) {
		return CM_Cache_Redis::sFlush($key);
	}

	/**
	 * @param string $key
	 * @return array
	 */
	public function popAll($key) {
		$members = CM_Cache_Redis::getInstance()->multi()->sMembers($key)->delete($key)->exec();
		return $members[0];
	}
}
