<?php

include 'Packinst\Package\GitPackage.php';
include 'Packinst\Package\GithubPackage.php';
include 'Packinst\Package\Downloader\GitPackageDownloader.php';
include 'Packinst\Package\Installer\GitPackageInstaller.php';

use Packinst\Package\GitPackage;
use Packinst\Package\GithubPackage;
use Packinst\Package\Downloader\GitPackageDownloader;
use Packinst\Package\Installer\GitPackageInstaller;


?>
<!doctype html>
<html>
<head>
</head>
<body>
<hr>
<form action="" method="post">
<p>
	In order to install a package from GITHUB,
	please inform the repository in format <b>groupname/projectname</b>
	in the field below and then hit <b>DO IT</b>.
</p>
<p>
	<input type="text" name="git_package" />
	&nbsp; &nbsp;
	<input type="submit" name="git_package_installer" value="DO IT" />
</p>
</form>
<hr>
<pre>
<?php

$nl = "\r\n";

$git_package = $_REQUEST['git_package'] ?? '';


function install_git_into($packageName, $destination)
{
	list($group, $project) = explode('/', $packageName);

	$to_path = "./{$destination}/{$group}/{$project}";
	$to_zip = $to_path . '/master.zip';

	@mkdir($to_path, 0777, true);

	$gp = new GithubPackage($group, $project);
	$gp->fetchRepositoryInfo();

	$gpd = new GitPackageDownloader();
	$gpd->setPackage($gp);

	if ($gpd->downloadTo($to_zip))
	{
		$listener = function($event)
		{
			echo $event . "\r\n";
		};

		echo "- listener set \r\n";

		$gpi = (new GitPackageInstaller())
			->setLogListener($listener)
			->setPackageDownloader($gpd)
			->install();

		//$gpd->writeLoaderFileTo($to_path . '/init.php');

		return $gpi;
	}

	return false;
}

if (!empty($git_package))
{
	if (preg_match('/([\w_\-.]+)[\\/]([\w_\-.]+)/', $git_package))
	{
		echo '<fieldset>' . print_r($git_package, true) . '</fieldset>' . $nl;

		if (install_git_into($git_package, 'vendor'))
		{
			echo "- Package $git_package installed successfully. $nl";
		}
		else
		{
			echo "- Error occurred while installing $git_package. Please verify. $nl";
		}
	}
	else
	{
		echo "- Invalid package: <b>$git_package</b> $nl";
	}
}
elseif (1 == 1)
{
	echo '<hr>';
	$edd = new GithubPackage('endroid/qr-code');
	$edd->fetchRepositoryInfo();

	echo '<fieldset>' . print_r($edd->repositoryInfo, true) . '</fieldset>';

	echo '<hr>';
	$col = new GithubPackage('collei/plat');
	$col->fetchRepositoryInfo();

	echo '<fieldset>' . print_r($col->repositoryInfo, true) . '</fieldset>';

}

?>
</pre>
</body>
</html>
