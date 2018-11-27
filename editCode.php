<?php
/*
@name	editCode.php
@description	edits code pages
@author	Kevin Yilmaz
$year	2014

@package: searchFor.php, scanDirs.php, showCode.php

*/

$matches = array();

//* sanitize GET	
	$searchForEscaped = $_GET['searchFor'];
    
	if ($_GET['includeLine'])
	{
		$includeLine = $_GET['includeLine'];
	}
	else
	{
		$includeLine = $_GET['searchFor'];
	}

    
    $excludeLines =  explode(', ',  $_GET['excludeLines'] );
    $numExcludeLines = count($excludeLines);    
    
    
	/* depreciated
		// preg escapes
		$toBeReplaced = array('.', '(', ')', '[', ']');
		$replaceWith = array('\.', '\(', '\)', '\[', '\]');
		$searchForEscaped = str_replace($toBeReplaced, $replaceWith, $_GET['searchFor']);
	*/

	
$content = file_get_contents($_GET['file']);

// echo $content;

?>


<form name="code" action="writeCode.php" method="post">
 <input type="submit" value="SUBMIT">
 <br>
 <input name="fileName" type="hidden" value="<?php echo $_GET['file']; ?>" > </input> 
<textarea name="newCode" rows="30" cols="150"><?php echo $content; ?></textarea>
 </form> 
 