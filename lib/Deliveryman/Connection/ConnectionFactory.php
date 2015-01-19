<?php
namespace Deliveryman\Connection;

/**
 * Creates connection from connection configuration
 * 
 * @author Alexander Sergeychik
 */
class ConnectionFactory {
	
	/**
	 * Creates connection from config
	 * 
	 * @param array $config
	 * @throws ConnectionFactoryException
	 * @return Connection
	 */
	public function create($config) {

		if (!isset($config['host'])) {
			throw new ConnectionFactoryException('Host is not defined in configuration "host" key');
		}
		
		if (!isset($config['username'])) {
			throw new ConnectionFactoryException('Username is not defined in configuration "username" key');
		}
		
		// setup credentials for connection
		if (isset($config['ssh_key'])) {
			
			$credentials = new \Crypt_RSA();		

			if (is_file($config['ssh_key'])) {
				$credentials->loadKey(file_get_contents($config['ssh_key']));
			} else {
				$credentials->loadKey($config['ssh_key']);
			}
			
			if (isset($config['ssh_key_passphrase'])) {
				$credentials->setPassword($config['ssh_key_passphrase']);
			}
			
		} elseif (isset($config['password'])) {
			$credentials = $config['password'];
		} else {
			$credentials = new \System_SSH_Agent();
		}
		
		
		
		// create connection
		if (isset($config['port'])) {
			$connection = new Connection($config['host'], $config['port']);
		} else {
			$connection = new Connection($config['host']);
		}
		
		$connection->setUsername($config['username']);
		$connection->setCredentials($credentials);
		
		return $connection;
	}
	
}
