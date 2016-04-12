<?php

namespace Dyndns;

/**
* Host database.
*/
class Hosts
{
	/**
	* Filename of the host file (dyndns.host)
	* @var string
	*/
	private $hostFile;

	/**
	* Host/Users array:  'hostname' => array ('user1', 'user2', ...)
	* @var array
	*/
	private $hostarray;

	/**
	* List of updates in the format 'hostname' => 'ip'
	* @var array
	*/
	private $hostupdates;

	/**
	* This is true if the status / user files were read
	* @var boolean
	*/
	private $hostsinitialized;

	/**
	* This is true if the api file wasd read
	* @var boolean
	*/
	private $apiinitialized;

	/**
	* Host/domainid:  'hostname' => domainid
	* @var array
	*/
	private $domainidarray;

	/**
	* This is true if the domainid file was read
	* @var boolean
	*/
	private $domainidinitialized;

	/**
	* Host/domainid:  'hostname' => resourceid 
	* @var array
	*/
	private $resourceidarray;

	/**
	* This is true if the resourceid file was read
	* @var boolean
	*/
	private $resourceidinitialized;

	private $apikey;


	/**
	* Constructor.
	*
	* @param string $hostFile
	*/
	public function __construct($hostFile)
	{
		$this->hostFile = $hostFile;
		$this->hostsinitialized = false;
		$this->hostarray = array();
		$this->hostupdates = array();
		$this->domainidinitialized = false;
		$this->domainidarray = array();
		$this->resourceidinitialized = false;
		$this->resourceidarray = array();
		$this->apikey = array();
		$this->apiinitialized = false;
	}

	/**
	* Adds an update to the list
	*
	* @param string $hostname
	* @param string $ip
	*/
	public function update($hostname, $ip)
	{
		if (! $this->hostsinitialized) {
			$this->hostsinit();
		}

		$this->debug('Update: ' . $hostname . ':' . $ip);
		$this->hostupdates[$hostname] = $ip;
		return true;
	}

	/**
	* Checks if the host belongs to the user
	*
	* @param string $user
	* @param string $hostname
	* @return boolean True if the user is allowed to update the host
	*/
	public function checkUserHost($user, $hostname)
	{
		if (! Helper::checkValidHost($hostname)) {
			$this->debug('Invalid host: ' . $hostname);
			return false;
		}

		if (! $this->hostsinitialized) {
			$this->hostsinit();
		}

		if (is_array($this->hostarray)) {
			foreach ($this->hostarray as $line) {
				if (preg_match("/^(.*?):(.*)/", $line, $matches)) {
					if (Helper::compareHosts($matches[1], $hostname, '*') &&
					in_array($user, explode(',', strtolower($matches[2])))) {
						return true;
					}
				}
			}
		}
		$this->debug('Host '.$hostname.' does not belong to user '.$user);
		return false;
	}

	/**
	* Write cached changes to the status file
	*/
	public function flush()
	{
		return $this->updateLinode();
	}

	/**
	* Initializes the user and status list from the file
	*
	* @access private
	*/
	private function hostsinit()
	{
		if ($this->hostsinitialized) return;
		if ( $this->readHostsFile() == false )
			return false;
		$this->hostsinitialized = true;

		if ($this->domainidinitialized) return;
		if ($this->readDomainidFile() == false )
			return false;
		$this->domainidinitialized = true;

		if ($this->resourceidinitialized) return;
		if ($this->readResourceidFile() == false )
			return false;
		$this->resourceidinitialized = true;

		if ($this->apiinitialized) return;
		if ($this->readApiFile() == false )
			return false;
		$this->apiinitialized = true;

		return true;
	}

	/**
	* Reads the hosts file into array
	*
	* @access private
	* @return boolean True file read ok
	*/
	private function readHostsFile()
	{
		$lines = @file($this->hostFile);
		if (is_array($lines)) {
			$this->hostarray = $lines;
		} else {
			$this->debug('Empty host file: "' . $this->hostFile . '"');
			return false;
		}
		return true;
	}

	/**
	* Reads the domainid file into array
	*
	* @access private
	* @return boolean True file read ok
	*/
	private function readDomainidFile()
	{
		$lines = @file($this->getConfig('domainidFile'));
		if (is_array($lines)) {
			$this->domainidarray = $lines;
		} else {
			$this->debug('Empty domainids file: "' . $this->getConfig('domainidFile') . '"');
			return false;
		}
		return true;
	}


	private function getDomainid( $hostname )
	{

		if (! $this->domainidinitialized) 
			$this->hostsinit();
		
		if (! $this->domainidinitialized)
			return false;

		if (is_array($this->domainidarray)) {
			foreach ($this->domainidarray as $line) {
				if (preg_match("/^(.*?):(.*)/", $line, $matches)) {
					if (Helper::compareHosts($matches[1], $hostname, '*')) 
					return $matches[2]; 
				}
			}
		}
		return FALSE;
	}

	/**
	* Reads the resourceid file into array
	*
	* @access private
	* @return boolean True file read ok
	*/
	private function readResourceidFile()
	{
		$lines = @file($this->getConfig('resourceidFile'));
		if (is_array($lines)) {
			$this->resourceidarray = $lines;
		} else {
			$this->debug('Empty resourceids file: "' . $this->getConfig('resourceidFile') . '"');
			return false;
		}
		return true;
	}

	private function getResourceid( $hostname )
	{
		if (! $this->resourceidinitialized) 
			$this->hostsinit();
		
		if (! $this->resourceidinitialized)
			return false;

		if (is_array($this->resourceidarray)) {
			foreach ($this->resourceidarray as $line) {
				if (preg_match("/^(.*?):(.*)/", $line, $matches)) {
					if (Helper::compareHosts($matches[1], $hostname )) 
					return $matches[2]; 
				}
			}
		}
		return FALSE;
	}

	/**
	* Reads the api file 
	*
	* @access private
	* @return boolean True file read ok
	*/
	private function readApiFile()
	{
		$apikeyfile = $this->getConfig('apikeyFile');

		if (! is_readable($apikeyfile)) {
			$this->debug('ERROR: Invalid linode.api_key file config value');
			return false;
		}
		$this->api_key = file($apikeyfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

		return true;
	}

	/**
	*
	* Sends DNS Updates to Linode server
	*
	* @access private
	*/
	private function updateLinode()
	{

		foreach ($this->hostupdates as $host => $ip) {

			if (! Helper::hasIPChanged($host, $ip)) continue;

			$domainid = $this->getDomainid( $host );
			$resourceid = $this->getResourceid( $host );

			$apiCallStr = 'https://api.linode.com/?&api_action=domain.resource.update'
			. '&api_key=' . $this->api_key[0]
				. '&domainid=' . $domainid
				. '&resourceid=' . $resourceid
				. '&target=' . $ip ;

			$this->debug('INFO: Linode API Call: ' . $apiCallStr);

			$result = file_get_contents( $apiCallStr );

			if ( strpos($result , '"ERRORARRAY":[]') == FALSE ) {
				$this->debug('ERROR: Linode Api returns: ' . $result);
				return false;
			}
		}

		return true;
	}

	private function getConfig($key)
	{
		return $GLOBALS['dyndns']->getConfig($key);
	}

	private function debug($message)
	{
		return $GLOBALS['dyndns']->debug($message);
	}
}
