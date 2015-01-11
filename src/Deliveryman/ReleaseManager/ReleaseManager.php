<?php

namespace Deliveryman\ReleaseManager;

use Deliveryman\Connection\Connection;

/**
 * Release manager implementation
 *
 * @author Alexander Sergeychik
 */
class ReleaseManager {
	
	const CURRENT_NAME = 'current';
	const RELEASES_NAME = 'releases';
	const SHARED_NAME = 'shared';
	const MAINTENANCE_NAME = 'maintenance';

	/**
	 * Connection
	 *
	 * @var Connection
	 */
	protected $connection;

	/**
	 * Base path of release management
	 *
	 * @var string
	 */
	protected $basePath;

	/**
	 * Constructs manager
	 *
	 * @param Connection $connection        	
	 * @param string $basePath        	
	 */
	public function __construct(Connection $connection, $basePath = '.') {
		$this->connection = $connection;
		$this->basePath = $basePath;
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
	 * Returns path to base directory
	 *
	 * @return string
	 */
	public function getBasePath() {
		return $this->basePath;
	}

	/**
	 * Returns path to current directory
	 *
	 * @return string
	 */
	public function getReleasesPath() {
		return $this->getBasePath() . '/' . self::RELEASES_NAME;
	}

	/**
	 * Returns path to releases directory
	 *
	 * @return string
	 */
	public function getCurrentPath() {
		return $this->getBasePath() . '/' . self::CURRENT_NAME;
	}
	
	/**
	 * Returns path to shared directory
	 * 
	 * @return string
	 */
	public function getSharedPath() {
		return $this->getBasePath() . '/' . self::SHARED_NAME;
	}
	
	/**
	 * Returns path to maintenance directory
	 * 
	 * @return string
	 */
	public function getMaintenancePath() {
		return $this->getBasePath() . '/' . self::MAINTENANCE_NAME;
	}

	/**
	 * Setups remote file structure
	 *
	 * @return void
	 * @throws ReleaseManagerException
	 */
	public function setup() {
		$connection = $this->getConnection();
		try {
		
			// check if base dir exists
			if (!$connection->isDir($this->getBasePath())) {
				throw new ReleaseManagerException(sprintf('Base directory "%s" is not exists', $this->getBasePath()));
			}
			
			// setup releases directory
			if (!$connection->isDir($this->getReleasesPath())) {
				$connection->mkdir($this->getReleasesPath());
			}
			
			// setup shared directory
			if (!$connection->isDir($this->getSharedPath())) {
				$connection->mkdir($this->getSharedPath());
			}
			
			// setup maintenance directory
			if (!$connection->isDir($this->getMaintenancePath())) {
				$connection->mkdir($this->getMaintenancePath());
			}
			
			// setup current symlink with maintenance page initially
			if (!$connection->isLink($this->getCurrentPath())) {
				$connection->symlink($this->getMaintenancePath(), $this->getCurrentPath(), true);
			}
		
		} catch (\Exception $e) {
			throw new ReleaseManagerException(sprintf('Unable to setup initial structure: %s', $e->getMessage()), null, $e);
		}
			
	}

	
	
	
}
