<?php

namespace Packinst\Package\Downloader;

use Packinst\Package\GitPackage;

/**
 *	@author	alarido.su@gmail.com
 *	@since	2022-06-18
 *
 *	Class for downloading a GITHUB hosted package
 *
 */
class GitPackageDownloader
{
	/**
	 *	@const string PATTERN
	 */
	private const PATTERN = '/([\w_\-.]+)[\\/\\\\]([\w_\-.]+)/';

	/**
	 *	@const string PATTERN
	 */
	private const TEMP_FETCH = '.tempfetch';

	/**
	 *	@property array $options
	 */
	private $options = [
		CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_BINARYTRANSFER => 1,
		CURLOPT_FOLLOWLOCATION => 1,
		CURLOPT_SSL_VERIFYHOST => 0,
		CURLOPT_SSL_VERIFYPEER => 0,
		CURLOPT_TIMEOUT =>  28800, // 8 hours
		CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:99.0) Gecko/20100101 Firefox/99.0',
	];

	/**
	 *	@property \Packinst\Package\GitPackage $package
	 */
	private $package = null;

	/**
	 *	Performs CURL download operation from $uri and, if successful,
	 *	saves the result to $destination path
	 *
	 *	@param	string	$uri
	 *	@param	string	$destination
	 *	@return	bool
	 */
	private function fetchCurlDownload(string $uri, string $destination)
	{
		if (empty($uri) || empty($destination))
		{
			return false;
		}
		//
		// create file handle for destination
		$to_tried = $destination . self::TEMP_FETCH;
		$fileHandle = fopen($to_tried, 'w');
		//
		// set the needed options
		$options = $this->options;
		$options[CURLOPT_URL] = $uri;
		$options[CURLOPT_FILE] = $fileHandle;
		//
		// performs download operation
		$curlHandle = curl_init();
		curl_setopt_array($curlHandle, $options);
		curl_exec($curlHandle);
		$errt = curl_error($curlHandle);
		curl_close($curlHandle);
		fclose($fileHandle);
		//
		// if file is too small, high chances to be a response header dump
		if (@filesize($to_tried) < 128)
		{
			// so, let's erase it and fail
			unlink($to_tried);
			return false;
		}
		// let's fail if file is empty
		elseif (!empty($errt))
		{
			return false;
		}
		//
		// rename to the target dest name
		rename($to_tried, $destination);
		//
		return true;
	}

	/**
	 *	Initializes a new instance
	 *
	 *	@param	\Packinst\Package\GitPackage	$package = null
	 *	@return	self
	 */
	public function __construct(GitPackage $package = null)
	{
		$this->setPackage($package);
	}

	/**
	 *	Set the package the downloader should work with.
	 *	Accepts either a GitPackage instance or a string in the
	 *	group-name/project-name format.
	 *
	 *	@param	string|\Packinst\Package\GitPackage	$packageDef
	 *	@return	self
	 */
	public function setPackage($packageDef)
	{
		if ($packageDef instanceof GitPackage)
		{
			$this->package = $packageDef;
		}
		elseif (is_string($packageDef))
		{
			$matches = [];
			//
			if (preg_match(self::PATTERN, $git_package, $matches))
			{
				$this->package = new GitPackage($matches[1], $matches[2]);
			}
		}
		//
		return $this;
	}

	/**
	 *	Performs the download operation on the defined package to
	 *	the destination at $to. You can pass one or more $branches
	 *	to be searched upon the GIT repo, then it will return the first
	 *	successful one. If no branch is given, 'master' and 'main'
	 *	(in this order) will be tried instead.
	 *
	 *		$downloader->downloadTo($dest, 'master')
	 *		$downloader->downloadTo($dest, 'master', 'main', 'desenv')
	 *
	 *	@param	string	$to
	 *	@param	string	...$branches
	 *	@return	self
	 */
	public function downloadTo(string $to, string ...$branches)
	{
		if (empty($this->package))
		{
			return false;
		}
		//
		if (empty($branches))
		{
			$branches = ['master','main'];
		}
		//
		set_time_limit(0);
		//
		foreach ($branches as $branch)
		{
			$uri = $this->package->getApiUri($branch);
			//
			if ($this->fetchCurlDownload($uri, $to))
			{
				return true;
			}
		}
		//
		return false;
	}

}

