<?php

namespace Deliveryman\ReleaseManager\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Creates maintenance mode release on remote server
 *
 * @author Alexander Sergeychik
 */
class MaintenanceCreateCommand extends AbstractCommand {

	/**
	 * {@inheritDoc}
	 *
	 * @see \Deliveryman\ReleaseManager\Command\AbstractCommand::configure()
	 */
	public function configure() {
		parent::configure();
		$this->setName('maintenance:create');
		$this->setDescription('Creates maintenance mode release on remote server');
		
		$this->addArgument('artifact', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'Artifacts to upload');
		$this->addOption('shared', 's', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Relative to release shared resource to bind');
		$this->addOption('select', null, InputOption::VALUE_NONE, 'Select maintenance mode after creation');
	}

	/**
	 * {@inheritDoc}
	 *
	 * @see \Symfony\Component\Console\Command\Command::execute()
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		
		$releaseManager = $this->getManager($input, $output);
		
		// create maintenance release
		$artifacts = (array)$input->getArgument('artifact');
		$releaseManager->createRelease('maintenance', $artifacts);
		$output->writeln(sprintf('Created <info>maintenance mode</info> release'));
		
		// bind maintenance shared files
		foreach ($input->getOption('shared') as $shared) {
			$path = $releaseManager->bindMaintenanceShared($shared);
			$output->writeln(sprintf('Shared resource <comment>"%s"</comment> binded to <comment>"%s"</comment>', $shared, $path));
		}
		
		if ($input->getOption('select')) {
			$releaseManager->selectMaintenance();
		}
	
	}

}
