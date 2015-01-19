<?php

namespace Deliveryman\Connection;

use Deliveryman\Connection\Transfer\TransferInterface;
use Deliveryman\Connection\Transfer\DefaultTransfer;

/**
 * Default connection implementation
 *
 * @author Alexander Sergeychik
 */
class Connection extends AbstractConnection implements TransferInterface {

	/**
	 * Transfer adapter
	 *
	 * @var TransferInterface
	 */
	protected $transfer;

	/**
	 * {@inheritDoc}
	 *
	 * @param string $hostname        	
	 * @param integer $port        	
	 * @param TransferInterface $transfer        	
	 */
	public function __construct($hostname, $port = 22, TransferInterface $transfer = null) {
		parent::__construct($hostname, $port);
		if ($transfer !== null) $this->setTransfer($transfer);
	}

	/**
	 * Returns transfer interface to connection
	 *
	 * @return TransferInterface
	 */
	public function getTransfer() {
		if (!$this->transfer) {
			$transfer = new DefaultTransfer($this);
			$this->setTransfer($transfer);
		}
		return $this->transfer;
	}

	/**
	 * Sets transfer adapter to connection
	 *
	 * @param TransferInterface $transfer        	
	 * @return Connection
	 */
	public function setTransfer(TransferInterface $transfer) {
		$this->transfer = $transfer;
		return $this;
	}

	/**
	 * Checks if $path is directory
	 *
	 * @param string $path        	
	 * @return boolean
	 */
	public function isDir($path) {
		return $this->getGateway(true)->is_dir($path);
	}

	/**
	 * Checks if $path is file
	 *
	 * @param string $path        	
	 * @return boolean
	 */
	public function isFile($path) {
		return $this->getGateway(true)->is_file($path);
	}

	/**
	 * Checks if $path is link
	 *
	 * @param string $path        	
	 * @return boolean
	 */
	public function isLink($path) {
		return $this->getGateway(true)->is_link($path);
	}

	/**
	 * Checks if $path exists
	 *
	 * @param string $path        	
	 * @return boolean
	 */
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
		$gateway = $this->getGateway(true);
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
	 * Return the target of a symbolic link
	 * 
	 * @param string $path
	 * @throws ConnectionException
	 * @return string
	 */
	public function readlink($path) {
		$gateway = $this->getGateway(true);
		$result = $gateway->readlink($path);
		if ($result === false) {
			throw new ConnectionException(sprintf('Unable to read link "%s": %s', $path, $gateway->getLastSFTPError()));
		}
		return $result;
	}
	

	/**
	 * Returns realpath for $path
	 * 
	 * @param string $path
	 * @return string
	 */
	public function realpath($path) {
		$output = $this->exec('readlink -f ' . $path);
		return trim(reset($output));
	}

	/**
	 * Removes $path.
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
	 * Renames a file or a directory on the remote server.
	 * 
	 * @param string $oldname
	 * @param string $newname
	 * @throws ConnectionException
	 * @return boolean
	 */
	public function rename($oldname, $newname) {
		$gateway = $this->getGateway(true);
		$result = $gateway->rename($oldname, $newname);
		if ($result === false) {
			throw new ConnectionException(sprintf('Unable to renamce "%s" to "%s": %s', $oldname, $newname, $gateway->getLastSFTPError()));
		}
		return $result;
	}

	/**
	 * Set permissions on a file.
	 *
	 * @param int $mode        	
	 * @param string $filename        	
	 * @param boolean $recursive        	
	 * @throws ConnectionException
	 * @return int|true - new mode or true when recursive flag is set
	 */
	public function chmod($mode, $filename, $recursive = false) {
		$gateway = $this->getGateway(true);
		$result = $gateway->chmod($mode, $filename, $recursive);
		if ($result === false) {
			throw new ConnectionException(sprintf('Unable to chmod path "%s": %s', $filename, $this->getGateway(false)->getLastSFTPError()));
		}
		return $result;
	}
	
	/**
	 * Returns a list of files in the given directory. Dots are skipped
	 * 
	 * @param string $dirname
	 * @param string $recursive
	 * @return array
	 */
	public function ls($dirname, $recursive = false) {
		$list = $this->getGateway(true)->nlist($dirname, $recursive);
		if ($list === false) {
			throw new ConnectionException(sprintf('Unable to list directory "%s": %s', $dirname, $this->getGateway(false)->getLastSFTPError()));
		}
		
		// filter dots
		foreach ($list as $key=>$path) {
			if (preg_match('/^\.+$/', $path)) {
				unset($list[$key]);
			}
		}
		return $list;
	}
	
	/**
	 * Returns a raw list of files in the given directory. Dots are skipped
	 * 
	 * @param string $dirname
	 * @param string $recursive
	 * @return array
	 */
	public function rawls($dirname, $recursive = false) {
		$list = $this->getGateway(true)->rawlist($dirname, $recursive);
		if ($list === false) {
			throw new ConnectionException(sprintf('Unable to list directory "%s": %s', $dirname, $this->getGateway(false)->getLastSFTPError()));
		}
		
		// filter dots
		foreach (array_keys($list) as $path) {
			if (preg_match('/^\.+$/', $path)) {
				unset($list[$path]);
			}
		}
		return $list;
	}
	
	
	
	

	/**
	 * Executes command.
	 *
	 * @param string $command        	
	 * @param string $pwd
	 *        	- directory of context
	 * @param string $callback        	
	 * @return array - command output lines
	 */
	public function exec($command, $pwd = null, $callback = null) {
		
		$gateway = $this->getGateway(true);
		
		if ($callback && !is_callable($callback)) {
			throw new ConnectionException('Callback provided to exec() is not valid callable');
		}
		
		if ($pwd && !$this->isDir($pwd)) {
			throw new ConnectionException(sprintf('Working directory "%s" is not correct', $pwd));
		}
		
		if ($pwd) {
			$oldPwd = $gateway->pwd();
			$command = sprintf('cd %s && %s', $pwd, $command);
		}
		
		$output = array();
		$result = $gateway->exec($command, function ($line) use(&$output, $callback) {
			$output[] = $line;
			if ($callback) {
				call_user_func($callback, $line);
			}
		});
		$status = $gateway->getExitStatus();
		if ($pwd) $gateway->chdir($oldPwd);
		
		if (!$result) {
			throw new ConnectionException(sprintf('Excution failed: %s', $this->getGateway(false)->getLastSFTPError()));
		}
		
		if ($status != 0) {
			$msg = array(
				sprintf('Command returned non-zero exit status %s: %s', $status, $this->getGateway(false)->getLastSFTPError()),
				sprintf('Command: %s', $command),
				sprintf('Pwd: %s', $pwd ? $pwd : '.'),
				sprintf("Output: \n    %s", implode("\n    ", $output))
			);
			throw new ConnectionException(implode("\n", $msg));
		}
		
		return $output;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @see \Deliveryman\Connection\Transfer\TransferInterface::upload()
	 */
	public function upload($remotePath, $localPath, $keepPermissions = false) {
		return $this->getTransfer()->upload($remotePath, $localPath, $keepPermissions);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @see \Deliveryman\Connection\Transfer\TransferInterface::download()
	 */
	public function download($remotePath, $localPath, $keepPermissions = false) {
		return $this->getTransfer()->download($remotePath, $localPath, $keepPermissions);
	}

}
