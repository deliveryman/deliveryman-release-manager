<?php

namespace Deliveryman\ReleaseManager\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Deliveryman\Connection\ConnectionFactory;
use Deliveryman\Connection\Connection;
use Deliveryman\ReleaseManager\ReleaseManager;
use Symfony\Component\Config\Definition\Processor;
use Deliveryman\ReleaseManager\Configuration\ProfileConfiguration;
use Symfony\Component\Yaml\Yaml;
use Deliveryman\Connection\Transfer\CompressTransfer;

abstract class AbstractCommand extends Command {
	
	const DEFAULT_PROFILE_NAME = 'deliveryman.yml';
	
	/**
	 * Profile cache
	 * 
	 * @var array
	 */
	private $profileCache;
	
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Command\Command::configure()
	 */
	protected function configure() {
		
		$this->addOption('profile', 'p', InputOption::VALUE_REQUIRED, 'Path to profile file');
		$this->addOption('ssh-host', null, InputOption::VALUE_REQUIRED, 'Connection host');
		$this->addOption('ssh-username', null, InputOption::VALUE_REQUIRED, 'Connection username');
		$this->addOption('ssh-password', null, InputOption::VALUE_REQUIRED, 'Connection password');
		$this->addOption('ssh-key', null, InputOption::VALUE_REQUIRED, 'Connection SSH key path');
		$this->addOption('ssh-key-passphrase', null, InputOption::VALUE_REQUIRED, 'Connection SSH key passphrase');
		$this->addOption('basepath', null, InputOption::VALUE_REQUIRED, 'Connection base path');
		
	}
	
	/**
	 * Returns profile configuration
	 * 
	 * @param InputInterface $input
	 * @throws \InvalidArgumentException
	 * @return array
	 */
	protected function getProfile(InputInterface $input) {
		
		if ($this->profileCache) return $this->profileCache;
		
		$configs = array();
		
		// load profile files
		$profiles = array();
		if (getenv('HOME')) $profiles[getenv('HOME') . '/' . self::DEFAULT_PROFILE_NAME] = false;
		$profiles[self::DEFAULT_PROFILE_NAME] = false;
		if ($input->getOption('profile')) $profiles[$input->getOption('profile')] = true; 
		
		$loadedProfiles = array();
		foreach ($profiles as $file => $required) {
			if (is_file($file) && is_readable($file)) {
				$configs[] = Yaml::parse(file_get_contents($file));
				$loadedProfiles[] = $file;
			} elseif ($required) {
				throw new \InvalidArgumentException(sprintf('Profile path "%s" is not exists or is not readable', $file));
			}
		}
		
		// load options configuration
		$configs[] = array_filter(array(
			'host' => $input->getOption('ssh-host'),
			'username' => $input->getOption('ssh-username'),
			'password' => $input->getOption('ssh-password'),
			'ssh_key' => $input->getOption('ssh-key'),
			'ssh_key_passphrase' => $input->getOption('ssh-key-passphrase'),
			'path' => $input->getOption('basepath'),
		));
		
		$processor = new Processor();
		$configuration = $processor->processConfiguration(new ProfileConfiguration(), $configs);

		// add loaded profile prop
		$configuration['profiles'] = $loadedProfiles; 
		
		$this->profileCache = $configuration;
		return $configuration;
	}
	
	
	/**
	 * Returns connection instance
	 * 
	 * @param InputInterface $input
	 * @return Connection
	 */
	protected function getConnection(InputInterface $input, OutputInterface $output = null) {
		
		$factory = new ConnectionFactory();
		$connection = $factory->create($this->getProfile($input));
		$connection->setTransfer(new CompressTransfer($connection));
		
		//if ($output) $connection->setLogger(new ConsoleLogger($output));
		
		return $connection;
	}
	
	/**
	 * Returns release manager
	 * 
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return ReleaseManager
	 */	
	protected function getManager(InputInterface $input, OutputInterface $output = null) {
		$profile = $this->getProfile($input);
		$connection = $this->getConnection($input, $output);
		$manager = new ReleaseManager($connection, $profile['path']);
		return $manager;
	}
	
}
