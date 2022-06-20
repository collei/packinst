<?php

namespace Packinst\Package;

/**
 *	@author	alarido.su@gmail.com
 *	@since	2022-06-19
 *
 *	Common tasks for package installers
 *
 */
interface Installable
{
	/**
	 *	Set the package path to be handled.
	 *
	 *	@param	string	$package
	 *	@return	object
	 */
	public function setPackage(string $package);

	/**
	 *	Unpacks and organizes the package 
	 *
	 *	@param	string	$sourcePath
	 *	@param	string	$originalPath = null
	 *	@return	int
	 */
	public function install();

}

