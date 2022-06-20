<?php

namespace Packinst\Package;

class GitPackage
{
	private $group;
	private $project;

	public function __construct(string $group, string $project)
	{
		$this->group = $group;
		$this->project = $project;
	}

	public function getApiUri(string $branch = null)
	{
		$branch = $branch ?? 'master';
		//
		return 'https://api.github.com/repos/'
			. $this->group . '/'
			. $this->project . '/zipball/'
			. $branch;
	}

	public function getBrowserUri(string $branch = null)
	{
		$branch = $branch ?? 'master';
		//
		return 'http://github.com/'
			. $this->group . '/'
			. $this->project . '/archive/'
			. $branch . '.zip';
	}

}
