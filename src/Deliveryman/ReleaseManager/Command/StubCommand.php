<?php

namespace Deliveryman\ReleaseManager\Command;

use Symfony\Component\Console\Command\Command;
class StubCommand extends Command {

	protected function configure() {
		parent::configure();
		$this->setName('stub');
		$this->setDescription('Stub description');
		
		$this->setAliases(array(
			'maintenance:enable',
			'maintenance:disable',
			'clean',
		));
		
	}
	
}
