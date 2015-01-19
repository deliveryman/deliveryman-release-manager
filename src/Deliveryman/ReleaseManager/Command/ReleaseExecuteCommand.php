<?php

namespace Deliveryman\ReleaseManager\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Executes command at specified release pwd
 * 
 * @author Alexander Sergeychik
 */
class ReleaseExecuteCommand extends AbstractCommand {

	/**
	 * {@inheritDoc}
	 * 
	 * @see \Omez\ReleaseManager\Command\AbstractConnectionAwareCommand::configure()
	 */
	protected function configure() {
		parent::configure();
		$this->setName('release:execute');
		$this->setDescription('Executes command at specified release pwd');
		
		$this->addArgument('name', InputArgument::REQUIRED, 'Release name');
		$this->addArgument('cmd', InputArgument::REQUIRED, 'Remote command to execute');
	}

	/**
	 * {@inheritDoc}
	 * 
	 * @see \Symfony\Component\Console\Command\Command::execute()
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {

		$name = $input->getArgument('name');
		$command = $input->getArgument('cmd');
		
		$releaseManager = $this->getManager($input, $output);
		
		if (strtolower($name) == 'current') {
			$name = $releaseManager->getCurrentRelease();
			if (!$name) {
				throw new \RuntimeException('Current release is not selected');
			}
		}
		
		$output->writeln(sprintf('Executing command <comment>"%s"</comment> at release <info>%s</info>', $command, $name));
		$pwd = $releaseManager->getReleasePath($name);
		
		$output->writeln('Output:');
		$releaseManager->getConnection()->exec($input->getArgument('cmd'), $pwd, function($line) use ($output) {
			$output->writeln(sprintf('<comment>%s</comment>', $line));			
		});
		
	}

}
