<?php

namespace Deliveryman\ReleaseManager\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Setups environment for deployment
 * 
 * @author Alexander Sergeychik
 */
class SetupCommand extends AbstractCommand {

	/**
	 * {@inheritDoc}
	 * @see \Deliveryman\ReleaseManager\Command\AbstractCommand::configure()
	 */
	protected function configure() {
		parent::configure();
		$this->setName('setup');
		$this->setDescription('Setups environment on remote server for futhur deployments');
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Command\Command::execute()
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		// TODO: Auto-generated method stub
		$manager = $this->getManager($input, $output);
		$manager->setup();
	}

	
}
