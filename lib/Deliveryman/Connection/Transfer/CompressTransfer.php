<?php

namespace Deliveryman\Connection\Transfer;

use Alchemy\Zippy\Zippy;
use Deliveryman\Connection\Connection;
/**
 * Compressed transfer
 * 
 * @author Alexander Sergeychik
 */
class CompressTransfer implements TransferInterface {
	
	/**
	 * Connection
	 * 
	 * @var Connection
	 */
	protected $connection;
	
	/**
	 * Creates compress transfer
	 * 
	 * @param TransferInterface $driver
	 * @param number $compression
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
	 * Returns driver
	 * 
	 * @return TransferInterface
	 */
	public function getDriver() {
		return new DefaultTransfer($this->getConnection());
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Deliveryman\Connection\Transfer\AbstractTransfer::upload()
	 */
	public function upload($remotePath, $localPath, $keepPermissions = false) {
		
		// compress local artifact
		$archiveLocal = pathinfo($remotePath, PATHINFO_BASENAME);
		$compressedSourcePath = $this->getCompressed(array(
			$archiveLocal => $localPath
		));
		$compressedDestPath = dirname($remotePath) . '/' . pathinfo($compressedSourcePath, PATHINFO_BASENAME) . '.tar.gz';

		// upload artifact to remote host
		$this->getDriver()->upload($compressedDestPath, $compressedSourcePath, $keepPermissions);
		unlink($compressedSourcePath);
		
		// uncompress artifact on remote host
		$connection = $this->getConnection();
		$connection->exec('tar -xf ' . pathinfo($compressedDestPath, PATHINFO_BASENAME), dirname($compressedDestPath));
		$connection->delete($compressedDestPath);

		// resolving permissions
		if ($keepPermissions) {
			// @todo						
		}
		
	}

	/**
	 * Compresses $path and returns archive file name
	 * 
	 * @param array $filelist - array of files where key is alias and value is filepath
	 * @param string $archivePath
	 * @return string
	 */
	protected function getCompressed(array $filelist, $archivePath = null) {
		if (!$archivePath) {
			$archivePath = tempnam(sys_get_temp_dir(), 'release_manager_'.time());
		}
		
		foreach ($filelist as $filepath) {
			if (!file_exists($filepath)) {
				throw new TransferException(sprintf('Path %s is not exists', $filepath));
			}
		}
		
		$zippy = Zippy::load();
		$zippy->create($archivePath, $filelist, true, 'tgz');
		
		return $archivePath;
	}
	
	
	
	/**
	 * {@inheritDoc}
	 * @see \Deliveryman\Connection\Transfer\TransferInterface::download()
	 */
	public function download($remotePath, $localPath, $keepPermissions = false) {
		throw new TransferException('Downloading is not supported');
	}
	
}