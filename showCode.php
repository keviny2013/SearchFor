<?php
/*
@name	showCode.php
@description	shows code pages line by line and highlights the search string
@author	Kevin Yilmaz
$year	2013

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

	
// $cFile = file_get_contents($_GET['file']);

$cLines = file($_GET['file']);
$num_lines = count($cLines);
$scr1 =  '<h3>Code page: ' . $_GET['file'] . '</h3>';
$scr1 .=  'Total: &nbsp;&nbsp;&nbsp;&nbsp; ' . $num_lines . ' lines';
$scr1 .=  '<br>Higlighted: &nbsp; &nbsp; &nbsp; <a href="#" onclick="find(\'' . $includeLine . '\')">'.$includeLine.'</a>';

$scr =  '<hr>';
 
for($i = 0; $i < $num_lines; $i++)
{
	$cLine = $cLines[$i];
	$ln = $i + 1;
	
    $cMatch = TRUE;
    if (!$includeLine)		
    {
          $cMatch = FALSE;
    }            
    elseif (!preg_match('/' . $includeLine . '/i', $cLine) )		
    {
          $cMatch = FALSE;
    } 
    elseif($numExcludeLines) 
    {
        for ($v = 0; $v < $numExcludeLines; $v++)
        {
            $cExclude = trim($excludeLines[$v]);

            if(!empty($cExclude))
            {
                if (stripos($cLine, $cExclude) !== false )
                {
                    $cMatch = FALSE; 
                }
            }
        }
    }    
    
    
    if ($cMatch) 
    {
		$matches[] = $ln;
        $scr .=  '<br>' . $ln . '<span style="background-color: yellow">' . htmlentities($cLine) . '</span>';		
    }
    else
    {
        $scr .=  '<br>' . $ln . ': ' . htmlentities($cLine);		
    }    

}

$scr1 .=  '<br>Matches: &nbsp; &nbsp; &nbsp;' . count($matches) . ' lines: ';
$scr1 .=  implode($matches, ', ');
echo $scr1 . $scr;
 
 
 