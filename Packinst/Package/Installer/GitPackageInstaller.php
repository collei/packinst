<?php

namespace Packinst\Package\Installer;

use Packinst\Package\Installable;
use ZipArchive;
use DateTime;
use Closure;

/**
 *	@author	alarido.su@gmail.com
 *	@since	2022-06-19
 *
 *	Class for installing a downloaded GIT .zip package.
 *	It assumes the .zip is in the right location, then extracts it
 *	and arrange their content based on class namespace structure.
 *
 */
class GitPackageInstaller implements Installable
{
	/**
	 *	@const string DS
	 */
	private const DS = DIRECTORY_SEPARATOR;

	/**
	 *	@const array BASE_SOURCES
	 */
	private const BASE_SOURCES = ['src','lib'];

	/**
	 *	@var array $installLog
	 */
	private $installLog = [];

	/**
	 *	@var array $logListener
	 */
	private $logListener = null;

	/**
	 *	Logs to the listener (if any) and returns the same string
	 *
	 *	@param	string	$log
	 *	@return	string
	 */
	private function callListener(string $log)
	{
		if ($this->logListener instanceof Closure)
		{
			$call = $this->logListener;
			$call($log);
		}
		//
		return $log;
	}

	/**
	 *	Logs into internal installation log in a new line
	 *
	 *	@param	string	...$things
	 *	@return	void
	 */
	private function log(string ...$things)
	{
		$date = '[' . (new DateTime())->format('Y-m-d H:i:s.u') . '] ';

		$this->installLog[] = $this->callListener(
			$date . implode('', $things ?? [])
		);
	}

	/**
	 *	Extracts namespace info from a php class source file.
	 *	Returns false if no namespace is found.
	 *
	 *	@param	string	$phpClassFile
	 *	@return	string|bool
	 */
	private function getClassNamespace(string $phpClassFile)
	{
		// file contents (the first 8KB)
		$contents = file_get_contents($phpClassFile, false, null, 0, 8192);
		$matches = [];

		// try capture the namespace (if any)
		if (preg_match('#namespace\s+([\w\\\\/]+);#i', $contents, $matches))
		{
			return trim($matches[1]);
		}

		// no namespace found
		return false;
	}

	/**
	 *	Moves php class files to their correct folder paths, according
	 *	their declared namespaces. Also removes empty folders, if possible.
	 *	The second parameter is for internal use only!
	 *
	 *	@param	string	$sourcePath
	 *	@param	string	$originalPath = null
	 *	@return	int
	 */
	private function organizePhpClasses(
		string $sourcePath, string $originalPath = null
	)
	{
		$items = array_diff(scandir($sourcePath), ['..', '.']);
		$arranged = 0;
		$originalPath = $originalPath ?? $sourcePath;

		foreach ($items as $item)
		{
			// source class path
			$sourceItem = $sourcePath . self::DS . $item;

			// a file (either php class file, or others)
			if (is_file($sourceItem))
			{
				// extracts namespace from class file
				$namespace = $this->getClassNamespace($sourceItem);

				// No namespace? Skip to next
				if ($namespace === false)
				{
					$this->log('class ', $item);
					continue;
				}

				$this->log('class ', $item, ' namespace ', $namespace);

				// destination location
				$namespaceFolder = $originalPath . self::DS . $namespace;

				// try to create correct location
				@mkdir($namespaceFolder, 0777, true);

				$this->log('current path    : ', $sourcePath);
				$this->log('namespaced path : ', $namespaceFolder);

				// destination class path
				$namespacedItem = $namespaceFolder . self::DS . $item;

				$this->log('current path (i)   : ', $sourceItem);
				$this->log('namspaced path (i) : ', $namespacedItem);

				// move to correct location
				rename($sourceItem, $namespacedItem);

				++$arranged;
			}
			// a subfolder
			elseif (is_dir($sourceItem))
			{
				// work in the subfolder
				$arranged += $this->organizePhpClasses($sourceItem, $originalPath);

				// remove it (if empty)
				@rmdir($sourceItem);
			}
		}

		return $arranged;
	}

	/**
	 *	Moves the extracted files to the same folder where the .zip lies.
	 *	It is necessary for the PIPS (Plat Integrated Package System)
	 *
	 *	@param	string	$thezip
	 *	@return	self
	 */
	private function organizePackageContents(string $packagePath)
	{
		// lists the path contents
		$dirItems = array_diff(scandir($packagePath), ['..', '.']);
		$subFolder = '';

		foreach ($dirItems as $dItem)
		{
			// erases everything, except the extracted folder
			$athing = $packagePath . self::DS . $dItem;

			if (!is_dir($athing) && is_file($athing))
			{
				unlink($athing);
			}
			else
			{
				$subFolder = $athing;
			}
		}

		// fails if no extracted folder is present 
		if (empty($subFolder))
		{
			return false;
		}

		// scan the extracted folder
		$subFolderItems = array_diff(scandir($subFolder), ['..', '.']);

		// move the extracted folder items to outside
		foreach ($subFolderItems as $subItem)
		{
			$fromIt = $subFolder . self::DS . $subItem;
			$toIt = $packagePath . self::DS . $subItem;

			rename($fromIt, $toIt);
		}

		// then removes the (now empty) extracted folder
		rmdir($subFolder);

		// returns the same path
		return $packagePath;
	}

	/**
	 *	Initializes the package installer engine.
	 *
	 *	@param	string	$thezip
	 *	@return	self
	 */
	private function unzipPackage(string $zipFile)
	{
		// assuming file.zip is in the same directory as the executing script.
		$file = $zipFile;

		// get the absolute path to $file
		$path = pathinfo($file, PATHINFO_DIRNAME);

		$za = new ZipArchive;

		// try starting the magic
		if ($za->open($file) === TRUE)
		{
			// extract it to the path we determined above
			$za->extractTo($path);
			$za->close();
		}
		else
		{
			return false;
		}

		return $path;
	}

	/**
	 *	Initializes the package installer engine.
	 *
	 *	@param	string	$packageZip = null
	 *	@return	self
	 */
	public function __construct(string $packageZip = null)
	{
		if (!empty($packageZip))
		{
			$this->setPackage($packageZip);
		}
	}

	/**
	 *	Set the package path to be handled.
	 *
	 *	@param	string	$packageZip
	 *	@return	self
	 */
	public function setPackage(string $packageZip)
	{
		$this->package = $packageZip;
		//
		return $this;
	}

	/**
	 *	Set the listener for the log produced by the installer.
	 *
	 *	@param	Closure	$listener
	 *	@return	self
	 */
	public function setLogListener(Closure $listener)
	{
		$this->logListener = $listener;
		//
		return $this;
	}

	/**
	 *	Unpacks and organizes the package 
	 *
	 *	@param	string	$sourcePath
	 *	@param	string	$originalPath = null
	 *	@return	int
	 */
	public function install()
	{
		// if the zip does not exists or no zip was set
		if (empty($this->package))
		{
			$this->log('no package zip set');
			return false;
		}

		// if the zip does not exists or no zip was set
		if (!file_exists($this->package))
		{
			$this->log('package not found: ', $this->package);
			return false;
		}

		// try to unpack the downloaded package
		if (($dest = $this->unzipPackage($this->package)) === false)
		{
			$this->log('package may be empty or corrupted: ', $this->package);
			return false;
		}

		// try to organize the extracted content
		if ($path2 = $this->organizePackageContents($dest))
		{
			// try possible source folders
			foreach (self::BASE_SOURCES as $baseFolder)
			{
				// assemble the path
				$basePath = $path2 . self::DS . $baseFolder;

				// and if it exists, does the workout
				if (is_dir($basePath))
				{
					$qtd = $this->organizePhpClasses($basePath);

					$this->log('Detected class files: ', $qtd);
				}
			}

			$this->log('Class files organized successfully.');
		}
		else
		{
			$this->log('Something gone wrong...');
		}

		return true;
	}

}

