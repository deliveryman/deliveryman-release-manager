<?php

namespace Deliveryman\Connection\Transfer;

use Deliveryman\Connection\Connection;

/**
 * Default transfer of object
 *
 * @author Alexander Sergeychik
 */
class DefaultTransfer implements TransferInterface {

	/**
	 * Connection instance
	 *
	 * @var Connection
	 */
	private $connection;

	/**
	 * Creates default transfer
	 *
	 * @param Connection $connection        	
	 */
	public function __construct(Connection $connection) {
		$this->connection = $connection;
	}

	/**
	 * Returns connection
	 *
	 * @return Connection
	 */
	public function getConnection() {
		return $this->connection;
	}

	/**
	 * {@inheritDoc}
	 * 
	 * @see \Deliveryman\Connection\Transfer\AbstractTransfer::upload()
	 */
	public function upload($remotePath, $localPath, $keeppems = true) {
		
		$connection = $this->getConnection();
		
		// do upload
		if (is_dir($localPath)) {
			$connection->mkdir($remotePath);
			foreach (new \DirectoryIterator($localPath) as $file) {
				if (!$file->isDot()) {
					$this->upload($remotePath . '/' . $file->getFilename(), $file->getPathname(), $keeppems);					
				}
			}	
		} elseif (is_resource($localPath)) {
			throw new TransferException('Resources are not supported');
		} else {
			$result = $connection->getGateway()->put($remotePath, $localPath, NET_SFTP_LOCAL_FILE);
			if (!$result) {
				throw new TransferException(sprintf('Unable to upload file "%s" to "%s"', $localPath, $remotePath));
			}
		}
		
		// set permissions to resource
		if ($keeppems) {
			$perms = fileperms($localPath);
			$connection->chmod($perms, $remotePath);
		}
		
		return true;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Deliveryman\Connection\Transfer\TransferInterface::download()
	 */
	public function download($remotePath, $localPath, $keepperm = true) {
		throw new TransferException('Downloading is not supported');
	}

}
