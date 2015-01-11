<?php

namespace Omez\ReleaseManager;

use Illuminate\Remote\ConnectionInterface;

class Manager {

	/**
	 * Connection
	 * 
	 * @var ConnectionInterface
	 */
	protected $connection;
	
	public function __construct(ConnectionInterface $connection) {
		$this->connection = $connection;
	}

	
	/**
	 * Creates new release on remote server using $path-files
	 * 
	 * @param string $path
	 * @param string $name
	 */
	public function createRelease($path, $name = null) {
		
	}
	
}
