<?php

namespace Deliveryman\ReleaseManager\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Deliveryman\Connection\ConnectionFactory;
use Deliveryman\Connection\Connection;
use Deliveryman\ReleaseManager\ReleaseManager;

abstract class AbstractCommand extends Command {
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Command\Command::configure()
	 */
	protected function configure() {
		
		$this->addOption('profile', null, InputOption::VALUE_REQUIRED, 'Path to profile file');
		$this->addOption('host', null, InputOption::VALUE_REQUIRED, 'Connection host');
		$this->addOption('user', null, InputOption::VALUE_REQUIRED, 'Connection username');
		$this->addOption('password', null, InputOption::VALUE_REQUIRED, 'Connection password');
		$this->addOption('ssh-key', null, InputOption::VALUE_REQUIRED, 'Connection SSH key path');
		$this->addOption('ssh-passphrase', null, InputOption::VALUE_REQUIRED, 'Connection SSH key passphrase');
		$this->addOption('basepath', null, InputOption::VALUE_REQUIRED, 'Connection base path', '.');
		
	}
	
	/**
	 * Returns connection instance
	 * 
	 * @param InputInterface $input
	 * @return Connection
	 */
	protected function getConnection(InputInterface $input, OutputInterface $output = null) {
		
		// @todo add profile reading
		
		$config = array(
			'hostname' => $input->getOption('host'),
			'username' => $input->getOption('user'),
			'password' => $input->getOption('password'),
			'ssh_key' => $input->getOption('ssh-key'),
			'ssh_key_passphrase' => $input->getOption('ssh-passphrase'),
		);

		$factory = new ConnectionFactory();
		$connection = $factory->create($config);
		
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
		$basePath = $input->getOption('basepath');
		$manager = new ReleaseManager($this->getConnection($input, $output), $basePath);
		return $manager;
	}
	
}
