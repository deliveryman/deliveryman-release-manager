<?php

namespace Deliveryman\ReleaseManager\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Deliveryman\ReleaseManager\ReleaseManager;

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
		
		$this->addOption('shared', 's', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Relative to release shared resource to bind');
		$this->addOption('select', null, InputOption::VALUE_NONE, 'Select maintenance mode after creation');
		
		$this->addOption('archive', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Aartifacts archive that should be uncompressed on remote server');
		$this->addOption('file', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Artifact directory or file that should be transfered as is');
		$this->addOption('dir', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Artifacts directory, which contents that should be transfered to remote server');
	}

	/**
	 * {@inheritDoc}
	 *
	 * @see \Symfony\Component\Console\Command\Command::execute()
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		
		$releaseManager = $this->getManager($input, $output);
		
		// create maintenance release
		$releaseManager->cleanMaintenance();
		$output->writeln(sprintf('Createing clean <info>maintenance mode</info> release'));
		
		// uploading artifacts
		foreach ($input->getOption('archive') as $artifactPath) {
			$uploadedArtifactPath = $releaseManager->uploadMaintenance($artifactPath, ReleaseManager::ARTIFACT_ARCHIVE, false);
			$output->writeln(sprintf('Uploaded archive <comment>%s</comment> contents', $artifactPath, $uploadedArtifactPath));
		}
		foreach ($input->getOption('dir') as $artifactPath) {
			$uploadedArtifactPath = $releaseManager->uploadRelease($artifactPath, ReleaseManager::ARTIFACT_DIR, false);
			$output->writeln(sprintf('Uploaded directory <comment>%s</comment> contents', $artifactPath, $uploadedArtifactPath));
		}
		foreach ($input->getOption('file') as $artifactPath) {
			$uploadedArtifactPath = $releaseManager->uploadRelease($artifactPath, ReleaseManager::ARTIFACT_FILE, false);
			$output->writeln(sprintf('Uploaded file/dir <comment>%s</comment>', $artifactPath, $uploadedArtifactPath));
		}
		
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
