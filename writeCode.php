<?php
/*
@name	writeCode.php
@description	renames old file and saves the modified one which comes as a POST variable
@author	Kevin Yilmaz
$year	2014

@package: searchFor.php, scanDirs.php, showCode.php;
@security security was not provided. You must add security tools or use at your own risk.

*/
 
//***  var_dump('$_POST: ' , $_POST);

$changed = null;

if(!$_POST['fileName'])
{
	echo '<h4>Error: No file name was given.<h4>';
}
else
{
	$oldFile = $_POST['fileName'].date("YmdHis");
	$changed = rename($_POST['fileName'], $oldFile);
	if($changed)
		echo 'Backup: <br>' .  $_POST['fileName'] . '<br>. was renamed as : ' . $oldFile;
	else
	{
		echo '<h4>Code DID NOT change because old file cannot be renamed.<h4>';
	}
}

if(!$changed && empty($_POST['newCode']))
	echo '<h4>Code DID NOT change because no code was given.<h4>';
else
{
	$changed = file_put_contents($_POST['fileName'], $_POST['newCode']);

	if($changed) {
		echo '<h4>Code changed <h4>';
		$changed = 'yes';
	}
	else {
		echo '<h4>Code DID NOT change because cannot write to the file<h4>';
	}
}

if(!$changed) {
	include_once('editCode.php');
}
//*** edit "editCode.php" so POST values would show up





