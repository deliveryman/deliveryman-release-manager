<?php

namespace Deliveryman\ReleaseManager\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Selects release as current
 *
 * @author Alexander Sergeychik
 */
class ReleaseSelectCommand extends AbstractCommand {

	/**
	 * {@inheritDoc}
	 *
	 * @see \Deliveryman\ReleaseManager\Command\AbstractCommand::configure()
	 */
	protected function configure() {
		parent::configure();
		$this->setName('release:select');
		$this->setDescription('Selects release as current');
		$this->addArgument('name', InputArgument::REQUIRED, 'Release name');
	}

	/**
	 * {@inheritDoc}
	 *
	 * @see \Symfony\Component\Console\Command\Command::execute()
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$releaseManager = $this->getManager($input, $output);
		
		$selectedReleaseName = $releaseManager->selectRelease($input->getArgument('name'));
		$output->writeln(sprintf('Release <info>%s</info> is now selected', $selectedReleaseName));
	}

}
