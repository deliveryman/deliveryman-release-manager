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
		
		$archiveLocal = pathinfo($remotePath, PATHINFO_BASENAME);
		
		$filelist = array(
			$archiveLocal => $localPath
		);
		
		// compress local artifact
		$compressedSourcePath = $this->getCompressed($filelist);
		$compressedDestPath = dirname($remotePath) . '/' . pathinfo($compressedSourcePath, PATHINFO_BASENAME) . '.tar.gz';

		// upload artifact to remote host
		$this->getDriver()->upload($compressedDestPath, $compressedSourcePath, $keepPermissions);
		unlink($compressedSourcePath);
		
		// uncompress artifact on remote host
		$connection = $this->getConnection();
		$connection->exec('tar -xf ' . pathinfo($compressedDestPath, PATHINFO_BASENAME), dirname($compressedDestPath));
		$connection->delete($compressedDestPath);

		// fixing permissions
		if ($keepPermissions) {
			$mapSourcePath = $this->getPermissionsMap($filelist);
			$mapDestPath = dirname($remotePath) . '/' . pathinfo($mapSourcePath, PATHINFO_BASENAME) . '.map';
			$this->getDriver()->upload($mapDestPath, $mapSourcePath, $keepPermissions);
			unlink($mapSourcePath);
			$connection->exec('. ' . pathinfo($mapDestPath, PATHINFO_BASENAME), dirname($mapDestPath));
			$connection->delete($mapDestPath);
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
	 * Creates permissions map file and returns path to it
	 * 
	 * @param array $filelist
	 * @param string $mapPath
	 * @return string
	 */
	protected function getPermissionsMap(array $filelist, $mapPath = null) {
		if (!$mapPath) {
			$mapPath = tempnam(sys_get_temp_dir(), 'release_manager_'.time());
		}
		
		$f = fopen($mapPath, 'w');
		if (!$f) {
			throw new TransferException(sprintf('Unable to create map file at "%"', $mapPath));
		}
		
		//fputs($f, '#!/usr/bin/env sh' . "\n");
		
		foreach ($filelist as $destination => $local) {
			
			if (is_dir($local)) {
				$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($local, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);
			} elseif (file_exists($local)) {
				$iterator = new \ArrayIterator(array(
					new \SplFileInfo(dirname(realpath($local)) . '/' . pathinfo(realpath($local), PATHINFO_BASENAME))
				));
			} else {
				throw new TransferException(sprintf('Local path "%s" not exists', $local));
			}
			
			foreach ($iterator as $file) {
				if (0 !== strpos($file->getPathname(), $local)) {
					throw new TransferException(sprintf('Unexpected nested path "%s" of dir "%s"', $file->getPathname(), $local));
				}
				$relativePath = ltrim(substr($file->getPathname(), strlen($local)), '/\\');
				$permissions = fileperms($file->getPathname()) & 0x0FFF;
				$command = sprintf('chmod %3$s %1$s/%2$s', $destination, $relativePath, decoct($permissions));
				fputs($f, $command . "\n");
			}
			
		}
		
		fclose($f);
		
		return $mapPath;
	}
	
	
	/**
	 * {@inheritDoc}
	 * @see \Deliveryman\Connection\Transfer\TransferInterface::download()
	 */
	public function download($remotePath, $localPath, $keepPermissions = false) {
		throw new TransferException('Downloading is not supported');
	}
	
}