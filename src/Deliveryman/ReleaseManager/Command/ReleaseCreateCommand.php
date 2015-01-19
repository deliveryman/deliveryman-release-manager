<?php

namespace Deliveryman\ReleaseManager\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Deliveryman\ReleaseManager\Generator\TimestampGenerator;
use Symfony\Component\Console\Input\InputOption;
use Deliveryman\ReleaseManager\ReleaseManager;

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
		$this->addOption('shared', 's', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Relative to release shared resource to bind');
		$this->addOption('force', 'f', InputOption::VALUE_NONE, 'Force overwrite release with same name');
		$this->addOption('select', null, InputOption::VALUE_NONE, 'Select release after creation');
		
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
		
		$name = $input->getArgument('name');
		if (strtolower($name) == 'auto') {
			$generator = new TimestampGenerator();
			$name = $generator->generate($releaseManager->getReleases());
		}
		
		// create release
		$releaseName = $releaseManager->createRelease($name, $input->getOption('force'));
		$output->writeln(sprintf('Created new release: <info>%s</info>', $releaseName));
		
		// uploading artifacts
		foreach ($input->getOption('archive') as $artifactPath) {
			$uploadedArtifactPath = $releaseManager->uploadRelease($name, $artifactPath, ReleaseManager::ARTIFACT_ARCHIVE);
			$output->writeln(sprintf('Uploaded archive <comment>%s</comment> contents', $artifactPath, $uploadedArtifactPath));
		}
		foreach ($input->getOption('dir') as $artifactPath) {
			$uploadedArtifactPath = $releaseManager->uploadRelease($name, $artifactPath, ReleaseManager::ARTIFACT_DIR);
			$output->writeln(sprintf('Uploaded directory <comment>%s</comment> contents', $artifactPath, $uploadedArtifactPath));
		}
		foreach ($input->getOption('file') as $artifactPath) {
			$uploadedArtifactPath = $releaseManager->uploadRelease($name, $artifactPath, ReleaseManager::ARTIFACT_FILE);
			$output->writeln(sprintf('Uploaded file/dir <comment>%s</comment>', $artifactPath, $uploadedArtifactPath));
		}
		
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
