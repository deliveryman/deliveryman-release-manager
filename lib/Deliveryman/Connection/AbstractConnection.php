<?php

namespace Deliveryman\Connection;

use Net_SFTP;
use Crypt_RSA;
use System_SSH_Agent;

/**
 * Abstract connection implementation as fascade for Net_SFTP.
 *
 * @author Alexander Sergeychik
 */
abstract class AbstractConnection {

	/**
	 * Undelying connection
	 *
	 * @var Net_SFTP
	 */
	private $gateway;

	/**
	 * Hostname
	 *
	 * @var string
	 */
	private $hostname;

	/**
	 * Port
	 *
	 * @var integer
	 */
	private $port;

	/**
	 * Username
	 *
	 * @var string
	 */
	private $username;

	/**
	 * AbstractConnection credentials
	 *
	 * @var string
	 */
	private $credentials;

	/**
	 * Constructs connection
	 *
	 * @param string $hostname        	
	 * @param integer $port        	
	 */
	public function __construct($hostname, $port = 22) {
		$this->setHostname($hostname);
		$this->setPort($port);
	}

	/**
	 * Returns hostname
	 *
	 * @return string
	 */
	public function getHostname() {
		return $this->hostname;
	}

	/**
	 * Sets hostname
	 *
	 * @param string $hostname        	
	 */
	public function setHostname($hostname) {
		$this->hostname = $hostname;
		$this->reset();
		return $this;
	}

	/**
	 * Returns port
	 *
	 * @return integer
	 */
	public function getPort() {
		return $this->port;
	}

	/**
	 * Sets port
	 *
	 * @param integer $port        	
	 */
	public function setPort($port) {
		$this->port = $port;
		$this->reset();
		return $this;
	}

	/**
	 * Returns username
	 *
	 * @return string
	 */
	public function getUsername() {
		return $this->username;
	}

	/**
	 * Sets username
	 *
	 * @param string $username        	
	 */
	public function setUsername($username) {
		$this->username = $username;
		$this->reset();
		return $this;
	}

	/**
	 * Returns credentials
	 *
	 * @return string|Crypt_RSA|System_SSH_Agent
	 */
	public function getCredentials() {
		return $this->credentials;
	}

	/**
	 * Sets credentials
	 *
	 * @param string|Crypt_RSA|System_SSH_Agent $credentials        	
	 */
	public function setCredentials($credentials) {
		if (!is_string($credentials) && !$credentials instanceof Crypt_RSA && !$credentials instanceof System_SSH_Agent) {
			$type = is_object($credentials) ? get_class($credentials) : gettype($credentials);
			throw new \InvalidArgumentException(sprintf('Credentials should be a string, an instance of Crypt_RSA or System_SSH_Agent, %s provided', $type));
		}
		$this->credentials = $credentials;
		$this->reset();
		return $this;
	}

	/**
	 * Returns connection gateway
	 *
	 * @param boolean $autoconnect
	 *        	- connect automatically when getting gateway
	 * @return Net_SFTP
	 */
	public function getGateway($autoconnect = true) {
		if (!$this->gateway) {
			$this->gateway = new Net_SFTP($this->getHostname(), $this->getPort());
		}
		if ($autoconnect) $this->connect();
		return $this->gateway;
	}

	/**
	 * Connects to host
	 *
	 * @return void
	 * @throws ConnectionException
	 */
	public function connect() {
		$gateway = $this->getGateway(false);
		if (!$gateway->isConnected()) {
			if (!$gateway->login($this->username, $this->credentials)) {
				throw new ConnectionException(sprintf('Unable to connect to host %s, check credentials, username or host', $gateway->host));
			}
		}
		return $gateway;
	}

	/**
	 * Resets connection
	 *
	 * @return void
	 */
	public function reset() {
		if ($this->gateway) {
			$this->gateway->reset();
			$this->gateway = null;
		}
	}

}
