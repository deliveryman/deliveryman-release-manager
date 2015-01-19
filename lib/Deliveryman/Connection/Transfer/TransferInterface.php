<?php

namespace Deliveryman\Connection\Transfer;

/**
 * Transfer interface
 *
 * @author Alexander Sergeychik
 */
interface TransferInterface {

	/**
	 * Uploads file/dir to remote
	 *
	 * @param string $remotePath        	
	 * @param string $localPath        	
	 * @param boolean $keepPermissions        	
	 */
	public function upload($remotePath, $localPath, $keepPermissions = false);

	/**
	 * Downloads
	 *
	 * @param string $remotePath        	
	 * @param string $localPath        	
	 * @param boolean $keepPermissions        	
	 */
	public function download($remotePath, $localPath, $keepPermissions = false);

}