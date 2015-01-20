<?php

namespace Deliveryman\ReleaseManager\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Input\InputOption;

/**
 * Creates profile configuration
 *
 * @author Alexander Sergeychik
 */
class ConfigureCommand extends AbstractCommand {

	/**
	 * {@inheritDoc}
	 *
	 * @see \Deliveryman\ReleaseManager\Command\AbstractCommand::configure()
	 */
	protected function configure() {
		parent::configure();
		$this->setName('configure');
		$this->setDescription('Creates profile configuration for current environment');
		$this->addArgument('filename', InputArgument::OPTIONAL, 'New profile filename', self::DEFAULT_PROFILE_NAME);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @see \Symfony\Component\Console\Command\Command::execute()
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		try {
			$profile = $this->getProfile($input);
		} catch (\Exception $e) {
			$profile = array(
				'host' => 'example.com',
				'username' => 'user',
				'path' => '.'
			);
		}
		unset($profile['profiles']);
		
		$yaml = Yaml::dump($profile, 4);
		$filename = $input->getArgument('filename');
		
		if (!file_put_contents($filename, $yaml)) {
			throw new \RuntimeException(sprintf('Unable to write file "%s"', $filename));
		}
		
		$output->writeln(sprintf('Profile configuration has beed written to <info>%s</info>', $filename));
	}

}
