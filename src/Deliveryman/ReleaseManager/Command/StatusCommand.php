<?php

namespace Deliveryman\ReleaseManager\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Deliveryman\Connection\ConnectionException;

/**
 * Displays current status of releases
 *
 * @author Alexander Sergeychik
 */
class StatusCommand extends AbstractCommand {

	/**
	 * {@inheritDoc}
	 *
	 * @see \Deliveryman\ReleaseManager\Command\AbstractCommand::configure()
	 */
	protected function configure() {
		parent::configure();
		
		$this->setName('status');
		$this->setDescription('Displays current status of releases');
	}

	/**
	 * {@inheritDoc}
	 * 
	 * @see \Symfony\Component\Console\Command\Command::execute()
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		
		// Profile information
		$profile = $this->getProfile($input);
		$output->writeln('Current profile information:');
		
		$output->writeln(sprintf('Loaded profiles: <info>%s</info>', !empty($profile['profiles']) ? implode(', ', $profile['profiles']) : '<comment>none</comment>'));
		$output->writeln(sprintf('Host: <info>%s</info>', isset($profile['host']) ? $profile['host'] : '<comment>none</comment>'));
		$output->writeln(sprintf('Username: <info>%s</info>', isset($profile['username']) ? $profile['username'] : '<comment>none</comment>'));
		
		if (array_key_exists('password', $profile)) {
			$output->writeln(sprintf('SSH password: <info>%s</info>', isset($profile['password']) ? '<secured>' : '<comment>empty</comment>'));
		}
		
		if (array_key_exists('ssh_key', $profile)) {
			if ($profile['ssh_key'] && is_file($profile['ssh_key'])) {
				$output->writeln(sprintf('SSH key file: <info>%s</info>', isset($profile['ssh_key']) ? $profile['ssh_key'] : '<comment>empty</comment>'));
			} else {
				$output->writeln(sprintf('SSH key content: <info>%s</info>', isset($profile['ssh_key']) ? '<secured>' : '<comment>empty</comment>'));
			}
			
			$output->writeln(sprintf('SSH key passphrase: <info>%s</info>', isset($profile['ssh_key_passphrase']) ? '<secured>' : '<comment>empty</comment>'));
		}
		
		$output->writeln(sprintf('Path: <info>%s</info>', isset($profile['path']) ? $profile['path'] : '<comment>empty</comment>'));
		$output->writeln('');

		// Connection status
		$output->write('Connection test... ');
		$connection = $this->getConnection($input, $output);
		try {
			$connection->connect();
			$output->writeln('<info>OK</info>');
		} catch (ConnectionException $e) {
			$output->writeln(sprintf('<error>Error: %s</error>', $e->getMessage()));
			return;
		}
		$output->writeln('');
		
		// Release manager information
		$releaseManager = $this->getManager($input, $output);
		$output->writeln('Release manager information:');
		
		
		$releases = $releaseManager->getReleases();
		$output->writeln(sprintf('Releases: <info>%s</info>', $releases ? implode(', ', $releases) : '<comment>none</comment>'));
		
		$current = $releaseManager->getCurrentRelease();
		$output->writeln(sprintf('Current: <info>%s</info>', $current === null ? '<comment>maintenance</comment>' : ($current ? $current : '<error>invalid or not selected</error>')));	
		
	}

}