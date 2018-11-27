<?php
/*
@name	scanFolders.php
@description	search files by names and/or string within files. Includes subfolders and apply exclusions
@author	Kevin Yilmaz
$year	2013

@package: searchFor.php, scanFolders.php, showCode.php

*/


class scanFolders {
	protected $kountDirs = 0;
	protected $kountFiles = 0;
	protected $startFrom;	
	protected $found = 0;
	protected $selected = array();
	protected $out;
	protected $max_folders = 1000;
	protected $max_files = 1000;
	
	// for exception handling purpose
	protected $issues = array();
	protected $break = 'no';	
	protected $lastFile = '';
	protected $lastPath = '';
	
	// inclusions, exclusions in file names
    protected $includeFileName = '';	
    protected $excludeFileNames;
    protected $numExcludeFileNames = 0;
	
	// inclusions, exclusions in file content
	protected $searchFor;
	protected $searchForEscaped;		
	protected $excludeFiles;
    protected $numExcludeFiles = 0;	
	
	// inclusions, exclusions in lines
    protected $includeLine = '';
    protected $excludeLines = '';
    protected $numExcludeLines = 0;    

    
	/*	explode exclusions
	*	converts params to properties
	*	starts scanning folders from $this->startFrom 
	*	gets rendered results
	*/
	function __CONSTRUCT($params)
	{
		$this->searchFor = trim($params['searchFor']);
        $this->includeFileName =  $params['includeFileName'];
        $this->includeLine = $params['includeLine'];
                
		if (empty($this->searchFor) && empty($this->includeFileName))
		{
			$this->issues[] = "No search string";
			$this->break = 'yes';
			return;
		}
        
		$this->searchForEscaped = $this->searchFor;
		
		/*  depreciated because don't know if user enters '\' to escape next char or not
			// preg escapes
			$toBeReplaced = array('.', '(', ')', '[', ']');
			$replaceWith = array('\.', '\(', '\)', '\[', '\]');
			$this->searchForEscaped = str_replace($toBeReplaced, $replaceWith, $this->searchFor);
		*/
		
		$this->max_folders = $params['max_folders'];
		$this->max_files = $params['max_files'];
		$this->startFrom = trim($params['startFrom'], '/'); 

		// excluded file content
		if($params['excludeFiles'])
		{	
			$this->excludeFiles = explode(', ',  $params['excludeFiles'] );
			$this->numExcludeFiles = count($this->excludeFiles);
		}
		
		// excluded file names
		if($params['excludeFileNames'])
		{
			$this->excludeFileNames =  explode(', ',  $params['excludeFileNames'] );
			$this->numExcludeFileNames = count($this->excludeFileNames);
		}
		
		// excluded lines
		if($params['excludeLines'])
		{
			$this->excludeLines =  explode(', ',  $params['excludeLines'] );
			$this->numExcludeLines = count($this->excludeLines);
		}
	}
	
	/*
	*	loops through folders and subfolders
	*	checks limitations in number of folders	
	*	reads file names and calls fileCheck() to proceed
	*/
	public function getDirs($dirName, $lastPath)
	{
		if($this->break == 'yes')
		{
			return;
		}
		
		$this->kountDirs++;		
		
			if (empty($dirName))
			{
				$cPath = $dirName;
				$tDirectory = opendir('.');
			}
			elseif (!$lastPath)
			{
				$cPath = trim($dirName, '/');
				if(is_dir($cPath))
				{
					$tDirectory = opendir($cPath);
				}
				else
				{
					return false;
				} 				
			}			
			else
			{	
				$cPath = $lastPath . '/' . $dirName ;
				$cPath = ltrim($cPath, '/');
				if(is_dir($cPath))
				{
					$tDirectory = opendir($cPath);
				}
				else
				{
					return false;
				} 			
			}
			
		// get each entry
		 $types = array();
		while($entryName = readdir($tDirectory)) 
		{
			if (substr($entryName, 0, 1) != '.')
			{
				if (empty($dirName))
				{
					$types[$entryName] = filetype($entryName);
				}
				else
				{
					$entryName = ltrim($entryName, '/');
					if (!empty($entryName))
					{
						$types[$entryName] = filetype($cPath . '/' . $entryName);
					}
				}
			}
		}

		// close directory - handle
		closedir($tDirectory);
		
		if (count($types) > 0)
		{
		
			foreach($types as $key => $val)
			{
				if ($val == 'dir')
				{
					if (!empty($this->max_folders) && $this->kountDirs >= $this->max_folders)
					{
						return;
					}
					else
					{
						$this->lastPath = $cPath;
						$this->getDirs($key, $cPath);
					}
				}
				elseif($val == 'file')
				{
					if ($this->found >= 100 or ($this->max_files && $this->kountFiles >= $this->max_files) )
					{
						return;
					}
					else
					{
						$this->fileCheck($key, $cPath);
					}
				}
			}
		}
		
	}	// end f getDirs

	/*
	*	checks if file name matches include and exclude criteria
	*	checks the file size
	*	calls getFile() if checks are ok
	*/
	protected function fileCheck($file, $path)
	{
		if($this->break == 'yes')
		{
			return;
		}	
		$this->lastFile = $file;
        // check include in file name
        if ($this->includeFileName && !preg_match('/' . $this->includeFileName . '/i', $file) )		
        {
            return;
        } 
 
        // check exclude in file name
		if($this->numExcludeFileNames) 
        {
            for ($v = 0; $v < $this->numExcludeFileNames; $v++)
            {
                $cExclude = trim($this->excludeFileNames[$v]);
                if(!empty($cExclude))
                {
                    if (stripos($file, $cExclude) !== false )		
                    {
                        return;
                    }
                }
            }
        }
		
		if(!empty($path))
		{
			$file = $path . '/' . $file;
		}
		else
		{
			$file = $file;
		}
		
		$stat = stat($file);
		$filesize = $stat['size'];
		$this->lastFile = $file;
		if($filesize > 0 && $filesize < 1234567)
		{
			$this->getFile($file);
		}
		elseif($filesize > 0)
		{
			$this->issues[] = 'File is too big: '. $file .' : '. $filesize .' bytes';
			if(count($this->issues) > 99)
			{
				$this->issues[] = 'Program was stopped running because of too many issues.';
				$this->break = 'yes';
			}
			return;
		}	
	}

	/*
	*	gets file's html content, search for 'search string' within file content
	*/
	 protected function getFile($file)
	 {
		if (!file_exists($file) )
		{
			$this->issues[] = '<br>File does not exists: '  . $file;
			return false;
		}
		
		$this->kountFiles++;
					
		if (!empty($this->searchForEscaped))
		{
			$cFile = '';
			$cFile = file_get_contents($file);
			
			$nexcludeFiles = count($this->excludeFiles);
			for ($v = 0; $v < $nexcludeFiles; $v++)
			{
				$cExclude = trim($this->excludeFiles[$v]);
				
				if(!empty($cExclude))
				{
					// in file content
					if (stripos($cFile, $cExclude) !== false )		
					{
						return;
					}
			   
				}
			}
		
			if (preg_match('/' . $this->searchForEscaped . '/i', $cFile) ) 
			{
					$this->selected[] = $file; 
					$this->found++;
			 }

			unset($cFile);
			
		}
		else
		{
			$this->selected[] = $file; 
		}
		
	 }	// end getFile

	/*
	*	reads each line of the file
	*	evaluates each line by includeLine, excludeLines criteria
	* 	renders matching lines
	*/
	protected function readLines($file)
	{
		if ($this->includeLine)
		{
			$includeLine = $this->includeLine;
		}
		else
		{
			$includeLine = $this->searchFor;
		}
	
		$cLines = file($file);
		
		$num_lines = count($cLines);

		for($p = 0; $p < $num_lines; $p++)
		{
			$num = $p + 1;
			$cLine = $cLines[$p];
 
            // check include, exclude in line
            $cMatch = TRUE;
			if (!preg_match('/' . $includeLine . '/i', $cLine) )		
            {
                $cMatch = FALSE;
            } 
            for ($v = 0; $v < $this->numExcludeLines; $v++)
            {
				$cExclude = trim($this->excludeLines[$v]);

				if(!empty($cExclude))
				{
					if (stripos($cLine, $cExclude) !== false )
					{
						$cMatch = FALSE; 
					}
				}
            }
              
            if ($cMatch) 
			{
				if(strlen($cLine) > 255)
				{
					$this->out .=  '<br>' . $num . ' : <span style="background-color: yellow">This line matches but it is too long. Open file to see the code.</span>';	
				}
				else
				{
					$this->out .=  '<br>' . $num . ' ' . htmlentities($cLine);
				}
			}
			
		}
	}
	 
	// renders results for output
	public function renderResults($excludeLines)
	{
		$this->out .=  '<b>RESULTS</b>';
		$this->out .=  '<br>Search string: ' . " &#09;" . stripslashes($this->searchForEscaped);	
		$this->out .=  '<br>Folders: '  . "&#09;" . $this->kountDirs;
		$this->out .=  '<br>Files before reading: '  . " &#09;" . $this->kountFiles;
		$this->out .=  '<br>Files with matching content: '  . " &#09;" . $this->found;
		if ($this->found >99)
		{
			$this->out .= ' (More files are available)';
		}

		$num_selected = count($this->selected);
		for ($i = 0; $i < $num_selected; $i++)
		{
			$file = $this->selected[$i];
			$lineNumber = $i + 1;
			$href = '?file='. $file . '&searchFor=' . urlencode($this->searchFor) 
            . '&excludeLines=' . urlencode($excludeLines) 
            . '&includeLine=' . urlencode($this->includeLine);
			
			$this->out .=  '<br><br>' . $lineNumber . ': ' . $file . ' <a href="showCode.php' . $href . '" target="_blank">Read</a>';
			$this->out .=  ' : <a href="editCode.php' . $href . '" target="_blank">Edit</a>';			
			
/*			
			$this->out .=  '<br><br>' . $lineNumber . ': <a href="showCode.php?file='. $file 
            . '&searchFor=' . urlencode($this->searchFor) 
            . '&excludeLines=' . urlencode($excludeLines) 
            . '&includeLine=' . urlencode($this->includeLine) 
            . '" target="_blank">'. $file . '</a>';
*/			
			
			if($this->includeLine || $this->numExcludeLines)
			{
				$this->readLines($file);
			}
		}

	}
	
	/*
	* to get protected properties
	*/
	public function getProperties()
	{
		$properties = array (
			'out'	=> $this->out,
			'issues'	=>	$this->issues,
			'lastPath' 	=>	$this->lastPath,
			'lastFile'	=>	$this->lastFile
		);
		
		return $properties;
	} 
	
	public function getBreak()
	{
		return $this->break;
	}
	
} 	// end class scanFolders

