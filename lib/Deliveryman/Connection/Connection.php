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
	
	public function exists($path) {
		return $this->getGateway(true)->file_exists($path);
	}
	
	/**
	 * Returns filetype for $path
	 * 
	 * @param string $path
	 * @return string
	 * @throws ConnectionException
	 */
	public function filetype($path) {
		$gateway = $this->getGateway(true);
		$result = $gateway->filetype($path); 
		if ($result === false) {
			throw new ConnectionException(sprintf('Unable to fetch type of "%s": %s', $path, $gateway->getLastSFTPError()));
		}
		return $result; 
	}

	/**
	 * Creates directory
	 * 
	 * @param string $dir
	 * @param integer $mode
	 * @param boolean $recursive
	 * @throws ConnectionException
	 * @return boolean
	 */
	public function mkdir($dir, $mode = null, $recursive = false) {
		$gateway =  $this->getGateway(true);
		$result = $gateway->mkdir($dir, ($mode === null ? -1 : $mode), $recursive);
		if ($result === false) {
			throw new ConnectionException(sprintf('Unable to create directory "%s": %s', $dir, $gateway->getLastSFTPError()));
		}
		return $result;
	}
	
	/**
	 * Creates symlink from $target to $link
	 * 
	 * @param string $target
	 * @param string $link
	 * @param boolean $force
	 * @throws ConnectionException
	 * @return boolean
	 */
	public function symlink($target, $link, $force = false) {
		$gateway = $this->getGateway(true);

		// handle force unlink
		if ($force && ($this->exists($link))) $this->unlink($link);
		
		$result = $gateway->symlink($target, $link);
		if ($result === false) {
			throw new ConnectionException(sprintf('Unable to create symlink "%s" to "%s": %s', $link, $target, $gateway->getLastSFTPError()));
		}
		return $result;
	}
	
	/**
	 * Alias to delete() without recursion
	 * 
	 * @param string $path
	 * @return boolean
	 */
	public function unlink($path) {
		return $this->delete($path, false);
	}
	
	/**
	 * Removes $path 
	 * 
	 * @param string $path
	 * @param boolean $recursive
	 * @throws ConnectionException
	 * @return boolean
	 */
	public function delete($path, $recursive = false) {
		$gateway = $this->getGateway(true);
		$result = $gateway->delete($path, $recursive);
		if ($result === false) {
			throw new ConnectionException(sprintf('Unable to remove "%s" %s: %s', $path, $recursive ? 'recursively' : 'non-recursively', $gateway->getLastSFTPError()));
		}
		return $result;
	}
	

	/**
	 * Uploads file or resource to remote server
	 * 
	 * @param string $path
	 * @param string|resource $local
	 * @throws ConnectionException
	 * @return boolean
	 */
	public function upload($path, $local) {
		if (is_dir($local)) {
			return $this->uploadDirectory($path, $local);
		} elseif (is_file($local) || is_resource($local)) {
			return $this->uploadFile($path, $local);
		} else {
			throw new ConnectionException('Local path should be a valid file/directory path or resource');
		}
	}
	
	/**
	 * Uploads directory to remote server
	 * 
	 * @param string $path
	 * @param string $local
	 * @throws ConnectionException
	 */
	protected function uploadDirectory($path, $local) {
		if (!is_dir($local)) {
			throw new ConnectionException('Local path should be a valid directory path');
		}
		
		if (!$this->isDir($path)) {
			$result = $this->mkdir($path);
		} else {
			$result = true;
		}
		
		foreach (new \DirectoryIterator($local) as $file) {
			if ($file->isDot()) continue;
			elseif ($file->isDir()) {
				$result&= $this->uploadDirectory($path . '/' . $file->getFilename(), $file->getPathname());
			} elseif ($file->isFile()) {
				$result&= $this->uploadFile($path . '/' . $file->getFilename(), $file->getPathname());
			} else {
				throw new ConnectionException(sprintf('Unknown path "%s" in directory upload', $file->getPathname()));
			}
		}
		
		if ($result === false) {
			throw new ConnectionException(sprintf('Unable to upload directory "%s": %s', $path, $this->getGateway(false)->getLastSFTPError()));
		}
		
		return true;
	}
	
	/**
	 * Uploads file or resource to remote server
	 * 
	 * @param string $path
	 * @param string|resource $local
	 * @throws ConnectionException
	 * @return boolean
	 */
	protected function uploadFile($path, $local) {
		
		$mode = null;
		$toClose = null;
		
		if (is_string($local) && is_file($local)) {
			$mode = fileperms($local);
			$local = fopen($local, 'r');
			$toClose = true;
		} elseif (!is_resource($local)) {
			throw new ConnectionException('Local file should be a valid file path or resource');
		}
		
		$gateway = $this->getGateway(true);
		$result = $gateway->put($path, $local, NET_SFTP_LOCAL_FILE);
		
		if ($mode !== null)	$result&= $gateway->chmod($mode, $path, false);
		if ($toClose !== null) fclose($local);
		
		if ($result === false) {
			throw new ConnectionException(sprintf('Unable to put local file to "%s": %s', $path, $gateway->getLastSFTPError()));
		}
		return $result;
	}
	
}
