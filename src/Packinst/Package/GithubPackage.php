<?php

namespace Packinst\Package;

use Packinst\Package\GitPackage;

/**
 *	@author	alarido.su@gmail.com
 *	@since	2022-06-19
 *
 *	Just basic capabilities of a Git package
 *
 */
class GithubPackage implements GitPackage 
{
	/**
	 *	@const string META_URI_API
	 */
	public const META_URI_API_INFO = 'https://api.github.com/repos/:vendor/:project';

	/**
	 *	@const string META_URI_API_BRANCH_INFO
	 */
	public const META_URI_API_BRANCH_INFO = 'https://api.github.com/repos/:vendor/:project/branches/:branch';

	/**
	 *	@const string META_URI_API
	 */
	public const META_URI_API_DOWNLOAD = 'https://api.github.com/repos/:vendor/:project/zipball/:branch';

	/**
	 *	@const string META_URI_BROWSER
	 */
	public const META_URI_BROWSER = 'http://github.com/:vendor/:project/archive/:branch.zip';

	/**
	 *	@const string UA
	 */
	private const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:99.0) Gecko/20100101 Firefox/99.0';

	/**
	 *	@var string $fullName
	 */
	private $fullName;

	/**
	 *	@var string $vendor
	 */
	private $vendor;

	/**
	 *	@var string $project
	 */
	private $project;

	/**
	 *	@var string $repoInfo
	 */
	private $repositoryInfo = null;

	/**
	 *	@var string $repositoryExists
	 */
	private $repositoryFound = false;

	/**
	 *	Initializes a new package info
	 *
	 *	@param	string	$vendorOrFullName
	 *	@param	string	$project = null
	 *	@return	self
	 */
	public function __construct(string $vendorOrFullName, string $project = null)
	{
		if (strpos($vendorOrFullName, '/') !== false)
		{
			$parts = explode('/', $vendorOrFullName);
			//
			$this->vendor = $parts[0];
			$this->project = $parts[1];
			$this->fullName = $vendorOrFullName;
		}
		else
		{
			$this->vendor = $vendorOrFullName;
			$this->project = $project ?? $vendorOrFullName;
			$this->fullName = $this->vendor . '/' . $this->project;
		}
	}

	/**
	 *	Retrieves meta-info on the repository from Github 
	 *
	 *	@param	string	$name
	 *	@return	mixed
	 */
	public function __get(string $name)
	{
		if (empty($this->repositoryInfo))
		{
			return;
		}
		//
		if ($name == 'repositoryInfo')
		{
			return $this->repositoryInfo;
		}
		//
		return $this->repositoryInfo->$name ?? null;
	}

	/**
	 *	Retrieves meta-info on the repository from Github 
	 *
	 *	@return	self
	 */
	public function fetchRepositoryInfo()
	{
		$options = [
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_BINARYTRANSFER => 1,
			CURLOPT_FOLLOWLOCATION => 1,
			CURLOPT_SSL_VERIFYHOST => 0,
			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_USERAGENT => self::UA,
			CURLOPT_URL => $this->getApiInfoUri(),
		];
		//
		$curlHandle = curl_init();
		curl_setopt_array($curlHandle, $options);
		$result = curl_exec($curlHandle);
		$errstr = curl_error($curlHandle);
		curl_close($curlHandle);
		//
		if ($result && empty($errstr))
		{
			$jsonObj = json_decode($result);
			//
			if (json_last_error() == JSON_ERROR_NONE)
			{
				if ($this->repositoryFound = isset($jsonObj->full_name))
				{
					$this->repositoryInfo = $jsonObj;
				}
			}
		}
	}

	/**
	 *	Returns whether the repo was found or not
	 *
	 *	@return	bool
	 */
	public function repositoryExists()
	{
		return $this->repositoryFound;
	}

	/**
	 *	Returns the full package name
	 *
	 *	@return	string
	 */
	public function getName()
	{
		return $this->fullName;
	}

	/**
	 *	Returns the package vendor name
	 *
	 *	@return	string
	 */
	public function getVendor()
	{
		return $this->vendor;
	}

	/**
	 *	Returns the package project name
	 *
	 *	@return	string
	 */
	public function getProject()
	{
		return $this->project;
	}

	/**
	 *	Returns an API URI for the package
	 *
	 *	@return	string
	 */
	public function getApiInfoUri()
	{
		return str_replace(
			[':vendor', ':project'],
			[$this->vendor, $this->project],
			self::META_URI_API_INFO
		);
	}

	/**
	 *	Returns an API URI for the info on the related branch
	 *
	 *	@param	string	$branch = null
	 *	@return	string
	 */
	public function getApiBranchInfoUri(string $branch = null)
	{
		$branch = $branch ?? 'master';
		//
		return str_replace(
			[':vendor', ':project', ':branch'],
			[$this->vendor, $this->project, $branch],
			self::META_URI_API_BRANCH_INFO
		);
	}

	/**
	 *	Returns an API URI for the package
	 *
	 *	@param	string	$branch = null
	 *	@return	string
	 */
	public function getApiDownloadUri(string $branch = null)
	{
		$branch = $branch ?? 'master';
		//
		return str_replace(
			[':vendor', ':project', ':branch'],
			[$this->vendor, $this->project, $branch],
			self::META_URI_API_DOWNLOAD
		);
	}

	/**
	 *	Returns an user-browseable URI for the package
	 *
	 *	@param	string	$branch = null
	 *	@return	string
	 */
	public function getBrowserUri(string $branch = null)
	{
		$branch = $branch ?? 'master';
		//
		return str_replace(
			[':vendor', ':project', ':branch'],
			[$this->vendor, $this->project, $branch],
			self::META_URI_BROWSER
		);
	}

}


