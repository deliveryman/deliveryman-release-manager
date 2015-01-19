<?php

namespace Deliveryman\ReleaseManager\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Enables maintenance mode
 *
 * @author Alexander Sergeychik
 */
class MaintenanceSelectCommand extends AbstractCommand {

	/**
	 * {@inheritDoc}
	 *
	 * @see \Deliveryman\ReleaseManager\Command\AbstractCommand::configure()
	 */
	protected function configure() {
		parent::configure();
		$this->setName('maintenance:select');
		$this->setDescription('Enables maintenance mode');
	}

	/**
	 * {@inheritDoc}
	 *
	 * @see \Symfony\Component\Console\Command\Command::execute()
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$releaseManager = $this->getManager($input, $output);
		
		$releaseManager->selectMaintenance();
		$output->writeln(sprintf('<info>Maintenance mode</info> is now selected'));
	}

}
