<?php

namespace Deliveryman\ReleaseManager\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
class CreateRelease extends AbstractCommand {
	
	public function configure() {
		parent::configure();
		$this->setName('create-release');
		$this->setDescription('Creates release on remote server');		
		
		$this->addArgument('name', InputArgument::REQUIRED, 'Release name');
		$this->addArgument('artifact', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'Artifacts to upload');
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Command\Command::execute()
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		
		$name = $input->getArgument('name');
		$artifacts = (array)$input->getArgument('artifact');
		
		$manager = $this->getManager($input, $output);
		$manager->createRelease($name, $artifacts);
		
	}

	
}
