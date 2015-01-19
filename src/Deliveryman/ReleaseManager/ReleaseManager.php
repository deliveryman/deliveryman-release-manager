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
	 * Returns release path
	 * 
	 * @param string $name
	 * @return string
	 */
	public function getReleasePath($name) {
		return $this->getReleasesPath() . '/' . $name;
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
	
	/**
	 * Returns currently installed releases
	 * 
	 * @return array
	 */
	public function getReleases() {
		return array();
	}

	/**
	 * Creates release with specified name
	 * 
	 * @param string $name - release name
	 * @param array $artifacts - artifacts paths
	 * @param array $shared - shared resources paths
	 * @throws ReleaseManagerException
	 * @return string - new release name
	 */
	public function createRelease($name, array $artifacts, array $shared = array()) {
		$connection = $this->getConnection();

		// create release directory
		$releasePath = $this->getReleasePath($name);
		if ($connection->exists($releasePath)) {
			$connection->delete($releasePath, true);
		}
		$connection->mkdir($this->getReleasesPath() . '/' . $name);
		
		// transfer artifacts		
		foreach ($artifacts as $artifact) {
			if (realpath($artifact) === false) {
				throw new ReleaseManagerException(sprintf('Artifact "%s" does not exists', $artifact));	
			}
			if (realpath(dirname($artifact)) == realpath($artifact)) {
				$artifactPath = $releasePath;
			} else {
				$artifactName = pathinfo(realpath($artifact), PATHINFO_BASENAME);
				$artifactPath = $releasePath . '/' . $artifactName;
			}
			
			$connection->upload($artifactPath, $artifact, true);
		}
		
		// bind shared resources
		/*foreach ($shared as $resource) {

			$sharedPath = $this->getSharedPath() . '/' . $resource;
			$releaseSharedPath = $releasePath . '/' . $resource;
			
			if ($connection->exists($sharedPath)) {
				if ($connection->exists($releaseSharedPath)) {
					$connection->delete($releaseSharedPath, true);
				} elseif (!$connection->isDir(dirname($releaseSharedPath))) {
					$connection->mkdir(dirname($releaseSharedPath), null, true);
				}
				$connection->symlink($sharedPath, $releaseSharedPath);
			}						
			
		}*/		
		
		return $name;
	}
	
	/**
	 * Returns weither release with $name exists 
	 * 
	 * @param string $name
	 * @return boolean
	 */	
	public function hasRelease($name) {
		$connection = $this->getConnection();
		$releasePath = $this->getReleasePath($name);
		return $connection->isDir($releasePath);
	}
	
	/**
	 * Removes release
	 * 
	 * @param string $name
	 * @return void
	 */	
	public function removeRelease($name) {}
	
	public function getCurrentRelease() {}
	public function selectRelease($name) {}
	public function selectMaintenance() {}
	
}
