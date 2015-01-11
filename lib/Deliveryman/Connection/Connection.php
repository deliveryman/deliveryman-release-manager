<?php

namespace Deliveryman\Connection;

/**
 * Default connection implementation
 * 
 * @author Alexander Sergeychik
 */
class Connection extends AbstractConnection {

	public function isDir($path) {
		return $this->getGateway(true)->is_dir($path);
	}
	
	public function isFile($path) {
		return $this->getGateway(true)->is_file($path);
	}
	
	public function isLink($path) {
		return $this->getGateway(true)->is_link($path);
	}

	public function mkdir($dir, $mode = null, $recursive = false) {
		$gateway =  $this->getGateway(true);
		$result = $gateway->mkdir($dir, ($mode === null ? -1 : $mode), $recursive);
		if ($result === false) {
			throw new ConnectionException(sprintf('Unable to create directory "%s": %s', $dir, $gateway->getLastSFTPError()));
		}
		return $result;
	}
	
	public function symlink($target, $link, $force = false) {
		$gateway = $this->getGateway(true);
		$result = $gateway->symlink($target, $link);
		if ($result === false) {
			throw new ConnectionException(sprintf('Unable to create symlink "%s" to "%s": %s', $link, $target, $gateway->getLastSFTPError()));
		}
		return $result;
	}
	
	
}
