<?php

namespace Packinst\Package;

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
	 *	@const stirng META_URI_API
	 */
	public const META_URI_API = 'https://api.github.com/repos/:group/:project/zipball/:branch';

	/**
	 *	@const stirng META_URI_BROWSER
	 */
	public const META_URI_BROWSER = 'http://github.com/:group/:project/archive/:branch.zip';

	/**
	 *	@var string $group
	 */
	private $group;

	/**
	 *	@var string $project
	 */
	private $project;

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
			self::META_URI_API
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


