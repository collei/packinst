<?php

namespace Packinst\Package;

use Packinst\Package\Downloader;

/**
 *	@author	alarido.su@gmail.com
 *	@since	2022-06-19
 *
 *	Just basic capabilities of a Git package
 *
 */
class GitPackage
{
	/**
	 *	@const string META_URI_API
	 */
	public const META_URI_API_INFO = 'https://api.github.com/repos/:group/:project';

	/**
	 *	@const string META_URI_API
	 */
	public const META_URI_API_DOWNLOAD = 'https://api.github.com/repos/:group/:project/zipball/:branch';

	/**
	 *	@const string META_URI_BROWSER
	 */
	public const META_URI_BROWSER = 'http://github.com/:group/:project/archive/:branch.zip';

	/**
	 *	@const string UA
	 */
	private const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:99.0) Gecko/20100101 Firefox/99.0';

	/**
	 *	@var string $group
	 */
	private $group;

	/**
	 *	@var string $project
	 */
	private $project;

	/**
	 *	@var string $repoInfo
	 */
	private $repositoryInfo = null;

	/**
	 *	Initializes a new package info
	 *
	 *	@param	string	$group
	 *	@param	string	$project
	 *	@return	self
	 */
	public function __construct(string $group, string $project)
	{
		$this->group = $group;
		$this->project = $project;
	}

	/**
	 *	Retrieves meta-info on the repository from Github 
	 *
	 *	@return	self
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
		$uriInfo = str_replace(
			[':group', ':project'],
			[$this->group, $this->project],
			self::META_URI_API_INFO
		);
		//
		$options = [
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_BINARYTRANSFER => 1,
			CURLOPT_FOLLOWLOCATION => 1,
			CURLOPT_SSL_VERIFYHOST => 0,
			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_USERAGENT => self::UA,
			CURLOPT_URL => $uriInfo,
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
			$jsonStr = json_decode($result);
			//
			if (json_last_error() == JSON_ERROR_NONE)
			{
				$this->repositoryInfo = $jsonStr;
			}
		}
	}

	/**
	 *	Returns an API URI for the package
	 *
	 *	@param	string	$branch = null
	 *	@return	string
	 */
	public function getApiUri(string $branch = null)
	{
		$branch = $branch ?? 'master';
		//
		return str_replace(
			[':group', ':project', ':branch'],
			[$this->group, $this->project, $branch],
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
			[':group', ':project', ':branch'],
			[$this->group, $this->project, $branch],
			self::META_URI_BROWSER
		);
	}

}


