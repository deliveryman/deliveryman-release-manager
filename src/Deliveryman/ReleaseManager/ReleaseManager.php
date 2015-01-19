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
	
	const ARTIFACT_ARCHIVE = 'archive';
	const ARTIFACT_FILE = 'file';
	const ARTIFACT_DIR = 'dir';
	

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
	 * Absolute base path
	 *
	 * @var string
	 */
	private $absoluteBasePathCache;

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
	 * Returns path to base directory.
	 *
	 * @param boolean $absolute
	 *        	- return absolute path
	 * @return string
	 */
	public function getBasePath($absolute = false) {
		if ($absolute) {
			if ($this->absoluteBasePathCache) return $this->absoluteBasePathCache;
			$absoluteBasePath = $this->getConnection()->realpath($this->basePath);
			$this->absoluteBasePathCache = $absoluteBasePath;
			return $absoluteBasePath;
		} else {
			return $this->basePath;
		}
	}

	/**
	 * Returns path to current directory.
	 *
	 * @param boolean $absolute
	 *        	- return absolute path
	 * @return string
	 */
	public function getReleasesPath($absolute = false) {
		return $this->getBasePath($absolute) . '/' . self::RELEASES_NAME;
	}

	/**
	 * Returns path to releases directory.
	 *
	 * @param boolean $absolute
	 *        	- return absolute path
	 * @return string
	 */
	public function getCurrentPath($absolute = false) {
		return $this->getBasePath($absolute) . '/' . self::CURRENT_NAME;
	}

	/**
	 * Returns path to shared directory.
	 * 
	 * @param boolean $absolute
	 *        	- return absolute path
	 * @return string
	 */
	public function getSharedPath($absolute = false) {
		return $this->getBasePath($absolute) . '/' . self::SHARED_NAME;
	}

	/**
	 * Returns path to maintenance directory.
	 * 
	 * @param boolean $absolute
	 *        	- return absolute path
	 * @return string
	 */
	public function getMaintenancePath($absolute = false) {
		return $this->getBasePath($absolute) . '/' . self::MAINTENANCE_NAME;
	}

	/**
	 * Setups remote file structure.
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
	 * Returns currently installed releases.
	 *
	 * @param boolean $absolute - return absolute release paths
	 * @return array
	 */
	public function getReleases($absolute = false) {
		
		$connection = $this->getConnection();
		$list = $connection->rawls($this->getReleasesPath());
		
		// convert to release-name / path
		$releaseMap = array();
		foreach ($list as $path => $info) {
			if ($info['type'] == 2) {
				$releaseMap[$this->getReleasesPath($absolute) . '/' . $path] = $path;
			}
		}
		
		// validate over paths
		foreach ($releaseMap as $path => $name) {
			if ($path != $this->getReleasePath($name, $absolute)) {
				throw new ReleaseManagerException(sprintf('Unexpected release path "%s" against expected "%"', $path, $this->getReleasePath($name)));
			}
		}
		
		return $releaseMap;
	}

	/**
	 * Returns release path.
	 *
	 * @param string $name        	
	 * @param boolean $absolute
	 *        	- return absolute path
	 * @return string
	 */
	public function getReleasePath($name, $absolute = false) {
		return $this->getReleasesPath($absolute) . '/' . $name;
	}

	/**
	 * Returns weither release with $name exists.
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
	 * Creates release with specified name.
	 *
	 * @param string $name
	 *        	- release name
	 * @param boolean $replace
	 *        	- replace release if exists
	 * @throws ReleaseManagerException
	 * @return string - new release name
	 */
	public function createRelease($name, $replace = false) {
		
		if ($this->hasRelease($name)) {
			if ($replace) {
				$this->removeRelease($name);
			} else {
				throw new ReleaseManagerException(sprintf('Release with name "%s" already exists', $name));
			}
		}
		
		$connection = $this->getConnection();
		$connection->mkdir($this->getReleasePath($name));
		
		return $name;
	}
	
	/**
	 * Uploads artifact to release
	 * 
	 * @param string $name
	 * @param string $artifact
	 * @param string $type
	 * @param boolean $overwrite
	 * @throws ReleaseManagerException
	 */
	public function uploadRelease($name, $artifact, $type = self::ARTIFACT_FILE, $overwrite = true) {
		if (!$this->hasRelease($name)) {
			throw new ReleaseManagerException(sprintf('Release "%" is not exists'));
		}
		return $this->uploadArtifactToPath($this->getReleasePath($name), $artifact, $type, $overwrite);
	}
	

	/**
	 * Removes release.
	 *
	 * @param string $name        	
	 * @param boolean $force
	 *        	- remove release even it's current
	 * @return void
	 */
	public function removeRelease($name, $force = true) {
		if (!$this->hasRelease($name)) return;
		
		// check if release is current
		if ($this->getCurrentRelease() == $name) {
			if ($force) {
				$this->selectMaintenance();
			} else {
				throw new ReleaseManagerException(sprintf('Release "%" is set as current and can\'t be removed'));
			}
		}
		
		$connection = $this->getConnection();
		$releasePath = $this->getReleasePath($name);
		$connection->delete($releasePath, true);
	
	}

	/**
	 * Returns current release name, null when maintenance or false if not
	 * selected or invalid path.
	 *
	 * @return string|false|null
	 */
	public function getCurrentRelease() {
		
		$connection = $this->getConnection();
		$target = $connection->readlink($this->getCurrentPath());
		
		if ($target == $connection->realpath($this->getMaintenancePath())) {
			return null;
		} else {
			foreach ($this->getReleases(true) as $path => $name) {
				if ($target == $path) {
					return $name;
				}
			}
		}
		
		return false;
	}

	/**
	 * Selects release as current and returns it's name
	 *
	 * @param string $name        	
	 * @throws ReleaseManagerException
	 * @return string
	 */
	public function selectRelease($name) {
		
		if (strtolower($name) == 'maintenance') {
			return $this->selectMaintenance();
		} elseif (!$this->hasRelease($name)) {
			throw new ReleaseManagerException(sprintf('Release "%" is not exists'));
		}
		
		$connection = $this->getConnection();
		$connection->symlink($this->getReleasePath($name), $this->getCurrentPath(), true);
		
		return $name;
	}
	
	/**
	 * Upload maintenance.
	 * 
	 * @param string $artifact
	 * @param string $type
	 * @param string $erase
	 * @return void
	 */
	public function uploadMaintenance($artifact, $type = self::ARTIFACT_FILE, $erase = true) {
		if ($erase) $this->cleanMaintenance();
		$this->uploadArtifactToPath($this->getMaintenancePath(), $artifact, $type, true);
	}
	
	/**
	 * Cleans maintenance release.
	 * 
	 * @return void
	 */
	public function cleanMaintenance() {
		$connection = $this->getConnection();
		$connection->delete($this->getMaintenancePath(), true);
		$connection->mkdir($this->getMaintenancePath());
	}
	
	/**
	 * Selects maintenance as current.
	 *
	 * @return null
	 */
	public function selectMaintenance() {
		$connection = $this->getConnection();
		$connection->symlink($this->getMaintenancePath(), $this->getCurrentPath(), true);
		return null;
	}
	
	/**
	 * Binds shared resource to release.
	 * 
	 * @param string $name - release name
	 * @param string $sharedPath - relative path to shared
	 * @param boolean $ignoreMissing - ignore missing shared resources or not
	 * @throws ReleaseManagerException
	 * @return string - binded resource path
	 */
	public function bindReleaseShared($name, $sharedPath, $ignoreMissing = true) {
		if (!$this->hasRelease($name)) {
			throw new ReleaseManagerException(sprintf('Release "%" is not exists'));
		}
		return $this->bindSharedWithReleasePath($this->getReleasePath($name), $sharedPath, $ignoreMissing);
	}

	/**
	 * Binds shared resource to maintenance mode release.
	 * 
	 * @param string $sharedPath - relative path to shared
	 * @param boolean $ignoreMissing - ignore missing shared resources or not
	 * @return string - binded resource path
	 */
	public function bindMaintenanceShared($sharedPath, $ignoreMissing = true) {
		return $this->bindSharedWithReleasePath($this->getMaintenancePath(), $sharedPath, $ignoreMissing);
	}
	
	/**
	 * Creates release on specified path.
	 * 
	 * @param string $releasePath
	 * @param array $artifacts
	 * @param string $replace - replace path if exists
	 * @param array $shared
	 * @throws ReleaseManagerException
	 */
	protected function createReleaseWithReleasePath($releasePath, array $artifacts, $replace = false, array $shared = array()) {
		
		$connection = $this->getConnection();
		
		// create release directory
		if ($connection->exists($releasePath)) {
			if ($replace) {
				$connection->delete($releasePath, true);
			} else {
				throw new ReleaseManagerException(sprintf('Release path "%s" already exists and replacement flag not enabled', $releasePath));
			}
		}
		$connection->mkdir($releasePath);
		
		// transfer artifacts
		foreach ($artifacts as $artifactPattern) {
			foreach (glob($artifactPattern) as $artifact) {
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
		}
		
		// bind shared resources
		foreach ($shared as $sharedPath) {
			$this->bindSharedWithReleasePath($releasePath, $sharedPath);
		}
		
		
	}
	
	/**
	 * Uploads artifact to release.
	 * 
	 * @param string $releasePath
	 * @param string $artifactPath
	 * @param string $type
	 * @param boolean $overwrite
	 * @throws ReleaseManagerException
	 * @return void
	 */	
	protected function uploadArtifactToPath($releasePath, $artifactPath, $type = self::ARTIFACT_FILE, $overwrite = false) {
		$connection = $this->getConnection();
		if (!$connection->exists($releasePath)) {
			throw new ReleaseManagerException(sprintf('Release path "%s" is not exists', $releasePath));
		}
				
		if (!file_exists($artifactPath)) {
			throw new ReleaseManagerException(sprintf('Artifact path "%s" is not exists', $artifactPath));
		}

		switch (strtolower($type)) {
			
			// plain file/dir upload
			case self::ARTIFACT_FILE:
				$remotePath = $releasePath . '/' . pathinfo(realpath($artifactPath), PATHINFO_BASENAME);
				$connection->upload($remotePath, $artifactPath, true);
				break;

			// directory contents upload 
			case self::ARTIFACT_DIR:
				foreach (glob(realpath($artifactPath) . '/*') as $value) {
					$this->uploadArtifactToPath($releasePath . '/' . pathinfo(realpath($value), PATHINFO_BASENAME), $value);
				}
				break;
			
			// archive upload
			case self::ARTIFACT_ARCHIVE:
				$extension = pathinfo($artifactPath, PATHINFO_EXTENSION);
				$remotePath = $releasePath . '/___' . time() . ( $extension ? sprintf('.%s', $extension) : '');
				$connection->upload($remotePath, $artifactPath, true);
				
				switch (strtolower($extension)) {
					
					case 'tar.gz':
					case 'tgz':
						$command = sprintf('tar -xf %s -C %s', escapeshellarg($remotePath), escapeshellarg($releasePath));
						break;
					
					case 'zip':
						$command = sprintf('unzip -o %s -d %s', escapeshellarg($remotePath), escapeshellarg($releasePath));
						break;
						
					default: 
						throw new ReleaseManagerException(sprintf('Unsupported archive type "%s"', $extension));
						break;
				}
				$connection->exec($command);
				
				$connection->delete($remotePath);
				break;
			
			default:
				throw new ReleaseManagerException(sprintf('Invalid transfer type "%s"', $type));
		}
		
	}
	
	/**
	 * Binds shared resource to release path and returns binded path.
	 * 
	 * @param string $releasePath - path to release dir
	 * @param string $sharedPath - relative shared resource path
	 * @param boolean $ignoreMissing - ignore missing shared resources or not
	 * @throws ReleaseManagerException
	 * @return string
	 */
	protected function bindSharedWithReleasePath($releasePath, $sharedPath, $ignoreMissing = false) {
		
		$sourcePath = $this->getSharedPath() . '/' . $sharedPath;
		$destPath = $releasePath . '/' . $sharedPath;
		
		$connection = $this->getConnection();
		
		if ($connection->exists($sourcePath)) {
			// map shared to destination
			if ($connection->exists($destPath)) $connection->delete($destPath, true);
			if (!$connection->exists(dirname($destPath))) $connection->mkdir(dirname($destPath), null, true);
			$connection->symlink($sourcePath, $destPath);
			
		} elseif ($connection->exists($destPath)) {
			// make initial shared resource mapping	
			if (!$connection->exists(dirname($sourcePath))) $connection->mkdir(dirname($sourcePath), null, true);
			$connection->rename($destPath, $sourcePath);
			$connection->symlink($sourcePath, $destPath);
			
		} elseif (!$ignoreMissing) {
			throw new ReleaseManagerException(sprintf('Shared resource "%s" not exists neither in share nor destination locations', $sharedPath));
		}
		
		return $destPath;
	}
	
}
