<?php

namespace Deliveryman\ReleaseManager\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Deliveryman\ReleaseManager\Generator\TimestampGenerator;
class ReleaseCreate extends AbstractCommand {
	
	public function configure() {
		parent::configure();
		$this->setName('release:create');
		$this->setDescription('Creates release on remote server');		
		
		$this->addArgument('name', InputArgument::REQUIRED, 'Release name');
		$this->addArgument('artifact', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'Artifacts to upload');
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Command\Command::execute()
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		
		$releaseManager = $this->getManager($input, $output);
		
		$name = $input->getArgument('name');
		if (strtolower($name) == 'auto') {
			$generator = new TimestampGenerator();
			$name = $generator->generate($releaseManager->getReleases());
		}
		
		$artifacts = (array)$input->getArgument('artifact');
		
		$releaseName = $releaseManager->createRelease($name, $artifacts);
		$output->writeln(sprintf('Created new release: <info>%s</info>', $releaseName));
	}

	
}
