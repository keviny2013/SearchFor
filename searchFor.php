<?php
/*
@name	searchFor.php
@description	search within files in all subfolders and applies exclusions
@author	Kevin Yilmaz
$year	2013

@package: searchFor.php, scanFolders.php, showCode.php;

*/

error_reporting(E_ALL ^E_NOTICE);
set_error_handler('myErrorHandler');

// function to call before PHP shuts down
register_shutdown_function('beforeExit'); 

$out = '';
$search = '';
	
if (!empty($_POST))
{
	$params = ifPost();
	$out = form($params);
	
	require_once('scanFolders.php');
	$search = new scanFolders($params);
	if($search->getBreak() != 'yes')
	{
		$search->getDirs($params['startFrom'], '');
		$search->renderResults($params['excludeLines']);	
		$properties = $search->getProperties();

		if(count($properties['issues']))
		{
			var_dump('ISSUES: ' , $properties['issues']);
		}

		$out .=  $properties['out'];
	}
}
else
{
	$params = noPost();
	$out .= form($params);
}
	
	
///////////////  PHP Functions 	//////////////////////////////////////////////

// customized error handler function
function myErrorHandler($errno, $errstr, $errfile, $errline)
{
	global $search;
	
	$cError = '<h4>Sorry an error ocurred. Results may not be correct or complete.</h4>';
	$cError .= $errno . '<br>' .  $errstr  . '<br>' .  $errfile . '<br>' .  $errline  . '<br><br><hr><br>';	
	beforeExit($cError);
}

function beforeExit($cError = NULL)
{
	global $search;

	$lastError = error_get_last();
	if(empty($lastError))
	{
		return;		
	}
	else
	{
		var_dump('Last error: ' , $lastError);
	}
	if (!empty($_POST))
	{
		$params = ifPost();
	}
	else
	{
		return;
	}
		
	// if timed out
	if(connection_status() == 2)
	{
		echo '<h3>Error: Maximum execution time was exceeded. Results are not final.</h3>';
	}
	// if beforeExit() called within myErrorHandler()
	elseif($cError)
	{
		echo 'Error: ' . $cError;
	}
	elseif(!empty($lastError))
	{
		echo '<h3>Ooops, something went wrong!</h3>';
	}
	
	$search->renderResults($params['excludeLines']);				
	$properties = $search->getProperties();
	echo '<h5>Last path: ' . $properties['lastPath'] . '</h5>';
	echo '<h5>Last file: ' . $properties['lastFile'] . '</h5>';		
	unset($search);
	
	echo form($params);

	if(count($properties['issues']))
	{
	 var_dump('ISSUES: ' , $properties['issues']);
	}
	
	echo $properties['out'];
		
}


/*
* figures out form params if there is $_POST
* security check may need before ifPost()
*/
function ifPost()
{
	global $search;
	
	$params = array(
		'max_folders'	=> $_POST['max_folders'],
		'max_files'		=>	$_POST['max_files'],	
		'startFrom'		=>	$_POST['startFrom'],
        'includeFileName'	=>  $_POST['includeFileName'],
		'searchFor'		=>	$_POST['searchFor'],        
        'includeLine'	=>  $_POST['includeLine'],         
	); 

		$params['excludeFiles'] = trim($_POST['excludeFiles']);	
		$params['excludeFiles'] = trim($params['excludeFiles'], " \n\r\t");		

		$params['excludeFileNames'] = trim($_POST['excludeFileNames']);	
		$params['excludeFileNames'] = trim($params['excludeFileNames'], " \n\r\t");        

		$params['excludeLines'] = trim($_POST['excludeLines']);	
		$params['excludeLines'] = trim($params['excludeLines'], " \n\r\t");
		
		return $params;
}

/*
* sets default form params (applied if there is no $_POST)
*/
function noPost()
{
	// parameters
	$params = array(
		'max_folders' => 1000,
		'max_files'	=>	1000,	
		'startFrom'	=>	'',                
		'searchFor'	=>	'define',
		'excludeFiles'	=>	'',
		'includeFileName'   => '(php|js)$',
		'excludeFileNames'   => '',               
		'includeLine'  =>  '',
		'excludeLines'  =>  '',                
	); 
	
	return $params;
			
}

/*
* renders form
*/
function form($params)
{
	$out = '';
	$excludeFileNames = trim($params['excludeFileNames'], " \n\r\t");
	$excludeFileNames = trim($excludeFileNames);

	$excludeFiles = trim($params['excludeFiles'], " \n\r\t");
	$excludeFiles = trim($excludeFiles);

	$excludeLines = trim($params['excludeLines'], " \n\r\t");
	$excludeLines = trim($excludeLines);
  
    $out .= '
        <form action="#" method=POST>
        <table>  
          <tr><td>START:</td>
          <td colspan="4">Path: <input name="startFrom" size="120" value="'. $params["startFrom"] . '"> </td> </tr>

          <tr> <td>SCAN:</td>
          <td >Maximum folders: <input name="max_folders" size="5" value="'. $params["max_folders"] . '">  </td>
          <td> Maximum files: <input name="max_files" size="5" value="'. $params["max_files"] . '">  </td></tr>
        
    ';
    
    $out .= '  
     <tr style="background:beige;"> <td>FILE Name:</td>
      <td>Include: <input name="includeFileName" size="50"  value="'. $params["includeFileName"] . '">  </td>
      <td>Exclude: <input name="excludeFileNames" size="50" value="' . $excludeFileNames . '" > </td></tr> 
    ';
            
    $out .= '  
      <tr style="background:tan;"> <td>FILE Content:</td>
      <td >Include: <input name="searchFor" size="50" value="'. $params["searchFor"] . '">  </td> 
      <td>Exclude: <input name="excludeFiles"  size="50" value="' . $excludeFiles . '"> </td></tr> 
      ';

    $out .= '  
      <tr style="background:beige;"> <td>LINE:</td>
      <td>Include: <input name="includeLine" size="50"  value="'. $params["includeLine"] . '">  </td>
      <td>Exclude: <input name="excludeLines"  size="50" value="' . $excludeLines . '" > </td></tr> 
    ';
      
    $out .= '</table> 
        <input type="SUBMIT" value="SUBMIT"> </form> ';
  
  return $out;

}	// end f form()

	
/////////////////   HTML	//////////////////////////////////////////////

?>
<!DOCTYPE HTML>
<html>
	<head>
		<title>Search files and contents</title>
		<meta charset="UTF-8">
	</head>
<body>


<?php


echo '<h3>Search Through Folders - <a href="helpSearchFor.html" target="_blank">Help</a></h3>';


echo $out; 
	
