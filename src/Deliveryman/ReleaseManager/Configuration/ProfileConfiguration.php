<?php

namespace Deliveryman\ReleaseManager\Configuration;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class ProfileConfiguration implements ConfigurationInterface {

	/**
	 * {@inheritDoc}
	 * 
	 * @see \Symfony\Component\Config\Definition\ConfigurationInterface::getConfigTreeBuilder()
	 */
	public function getConfigTreeBuilder() {
		$builder = new TreeBuilder();
	
		$root = $builder->root('profile');
		
		$rootNodeChildren = $root->children();
		
		$rootNodeChildren->scalarNode('host')
			->info('Hostname or IP address of server')
			->example('example.com')
			->isRequired();
		
		$rootNodeChildren->scalarNode('username')
			->info('SSH connection username')
			->example('john.doe')
			->isRequired();
		
		$rootNodeChildren->scalarNode('password')
			->info('SSH connection password for default authentication')
			->example('mysecurepassword')
			->cannotBeEmpty();
		
		$rootNodeChildren->scalarNode('ssh_key')
			->info('SSH connection key path')
			->example('~/.ssh/id_rsa')
			->cannotBeEmpty();
		
		$rootNodeChildren->scalarNode('ssh_key_passphrase')
			->info('SSH connection key phrase')
			->example('mykeyphrase')
			->cannotBeEmpty();
		
		$rootNodeChildren->scalarNode('path')
			->info('Project location on target')
			->example('/var/www/example.com')
			->isRequired();
		
		
		return $builder;
	}

}