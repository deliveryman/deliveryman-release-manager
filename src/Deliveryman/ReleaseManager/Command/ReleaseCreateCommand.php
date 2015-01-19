<?php

namespace Deliveryman\ReleaseManager\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Deliveryman\ReleaseManager\Generator\TimestampGenerator;
use Symfony\Component\Console\Input\InputOption;

/**
 * Creates release on remote server.
 *
 * @author Alexander Sergeychik
 */
class ReleaseCreateCommand extends AbstractCommand {

	/**
	 * {@inheritDoc}
	 * 
	 * @see \Deliveryman\ReleaseManager\Command\AbstractCommand::configure()
	 */
	public function configure() {
		parent::configure();
		$this->setName('release:create');
		$this->setDescription('Creates release on remote server');
		
		$this->addArgument('name', InputArgument::REQUIRED, 'Release name');
		$this->addArgument('artifact', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'Artifacts to upload');
		$this->addOption('shared', 's', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Relative to release shared resource to bind');
		$this->addOption('force', 'f', InputOption::VALUE_NONE, 'Force overwrite release with same name');
		$this->addOption('select', null, InputOption::VALUE_NONE, 'Select release after creation');
	}

	/**
	 * {@inheritDoc}
	 *
	 * @see \Symfony\Component\Console\Command\Command::execute()
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		
		$releaseManager = $this->getManager($input, $output);
		
		$name = $input->getArgument('name');
		if (strtolower($name) == 'auto') {
			$generator = new TimestampGenerator();
			$name = $generator->generate($releaseManager->getReleases());
		}
		
		
		// create release
		$artifacts = (array)$input->getArgument('artifact');
		$releaseName = $releaseManager->createRelease($name, $artifacts, $input->getOption('force'));
		$output->writeln(sprintf('Created new release: <info>%s</info>', $releaseName));
		
		// bind maintenance shared files
		foreach ($input->getOption('shared') as $shared) {
			$path = $releaseManager->bindReleaseShared($name, $shared);
			$output->writeln(sprintf('Shared resource <comment>"%s"</comment> binded to <comment>"%s"</comment>', $shared, $path));
		}
		
		if ($input->getOption('select')) {
			$releaseManager->selectRelease($releaseName);
		}
	
	}

}
