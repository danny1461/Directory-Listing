<?php

class FolderInfo {
	public $path;
	public $relativePath;

	public function __construct() {
		$protocol = $_SERVER['SERVER_PORT'] == 443
			? 'https'
			: 'http';

		$this->relativePath = $protocol . '://' . $_SERVER['SERVER_NAME'] . rtrim($_SERVER['REQUEST_URI'], '/');
		$this->path = str_replace('\\/', '/', $_SERVER['DOCUMENT_ROOT']) . $_SERVER['REQUEST_URI'];
	}

	public function isValid() {
		return file_exists($this->path);
	}

	public function getItems($type) {
		static $items = null;
		if (is_null($items)) {
			$files = [];
			$dirs = [];

			$dirIterator = new DirectoryIterator($this->path);
			foreach ($dirIterator as $item) {
				if ($item->isDot()) {
					continue;
				}

				$basename = $item->getBasename();
				if ($item->isDir()) {
					switch ($basename) {
						case ".svn":
						case ".git":
							continue 2;

						default:
							$dirs[] = $this->path . '/' . $basename;
					}
				}
				else {
					$files[] = $this->path . '/' . $basename;
				}
			}

			natcasesort($files);
			natcasesort($dirs);

			$items = [
				'dirs' => $dirs,
				'files' => $files
			];
		}
		
		return $items[$type];
	}

	public function getRules() {
		static $rules = null;

		if (is_null($rules)) {
			$rules = json_decode(file_get_contents(__DIR__ . '/../config.json'), true);
			uasort($rules, function($a, $b) {
				$aWeight = isset($a['combines'])
					? ($a['combines'] ? 0 : 1)
					: 0;
				$bWeight = isset($b['combines'])
					? ($b['combines'] ? 0 : 1)
					: 0;

				return $aWeight - $bWeight;
			});
		}

		return $rules;
	}

	public function getSubDirs() {
		$dirs = [];

		foreach ($this->getItems('dirs') as $dir) {
			$ret = [
				'name' => basename($dir),
				'tags' => $this->applyRules($dir),
				'info' => ''
			];
			$ret['hidden'] = strpos($ret['name'], '.') === 0;
			$ret['clean'] = strtolower($ret['name']);
			$ret['path'] = $this->relativePath . '/' . $ret['name'];
			if (is_file($dir . '/favicon.ico')) {
				$ret['icon'] = '/favicon.ico';
			}

			$dirs[] = $ret;
		}

		return $dirs;
	}

	public function getFiles() {
		$files = [];

		foreach ($this->getItems('files') as $file) {
			$ret = [
				'name' => basename($file),
				'tags' => $this->applyRules($file),
				'info' => beautifyFileSize(filesize($file))
			];
			$ret['hidden'] = strpos($ret['name'], '.') === 0;
			$ret['clean'] = strtolower($ret['name']);
			$ret['path'] = $this->relativePath . '/' . $ret['name'];

			$files[] = $ret;
		}

		return $files;
	}

	private function applyRules($path) {
		$search = [];
		$replace = [];
		$isFile = is_file($path);
		if ($isFile) {
			$search[] = '{EXT}';
			$replace[] = pathinfo($path, PATHINFO_EXTENSION);
		}
		$tags = [];
		$conditional = [];
		$certainCount = 0;

		foreach ($this->getRules() as $tag => $tagDef) {
			if ($isFile !== (isset($tagDef['isFile']) ? $tagDef['isFile'] : false)) {
				continue;
			}

			if (!$isFile && isset($tagDef['rules'])) {
				foreach ($tagDef['rules'] as $subItem => $subType) {
					if (!file_exists($path . $subItem)) {
						continue 2;
					}

					if (is_dir($path . $subItem) !== ($subType == 'folder')) {
						continue 2;
					}
				}
			}

			$tag = str_replace($search, $replace, $tag);
			$combines = isset($tagDef['combines'])
				? $tagDef['combines']
				: true;

			if ($combines) {
				if (isset($tagDef['ifTags'])) {
					$conditional[$tag] = $tagDef['ifTags'];
				}
				else {
					$tags[] = $tag;
					$certainCount++;
				}
			}
			else {
				$conditional[$tag] = 0;
			}
		}

		foreach ($conditional as $tag => $cond) {
			if (is_array($cond)) {
				if (count(array_intersect($tags, $cond))) {
					$tags[] = $tag;
				}
			}
			else {
				if ($certainCount == 0) {
					$tags[] = $tag;
					$certainCount++;
				}
			}
		}

		return $tags;
	}
}

function beautifyFileSize($size, $accuracy = 2, $recursive = 10)
{
	$fileSizeNames = array('B', 'KB','MB','GB','TB','PB');

	for ($i = 0; $i < count($fileSizeNames) && $recursive > 0; $i++)
	{
		if ($size > 1024)
		{
			$size /= 1024;
			$recursive--;
		}
		else
			break;
	}

	return round($size, $accuracy) . ' ' . $fileSizeNames[$i];
}

/* function directorySize($path)
{
	if (DIRECTORY_SEPARATOR == '/')
	{
	    $io = popen ( '/usr/bin/du -sk ' . $path, 'r' );
	    $size = fgets ( $io, 4096);
	    $size = substr ( $size, 0, strpos ( $size, "\t" ) );
	    pclose ( $io );
	    return $size;
	}
	else
	{
	    $obj = new COM('scripting.filesystemobject');
	    if (is_object($obj))
	    {
	        $ref = $obj->getfolder($path);
	        $ret = $ref->size;
	        $obj = null;
	        return $ret;
	    }
	    else
	    {
	        die('can not create object');
	    }
	}
} */

function addDirectoryToZip($zip, $dir, $base)
{
	static $skipFile = array("Thumbs.db", ".localized", ".DS_Store");
	static $skipFolder = array(".svn");

    $dirIterator = new DirectoryIterator($dir);
	foreach ($dirIterator as $entry) {
        if ($entry->isDot())
            continue;

        $file = $entry->getPathname();
        $newFile = str_replace($base, '', $file);
        $baseFile = $entry->getFilename();

        if (is_dir($file)) {
        	if (!in_array($baseFile, $skipFolder) && !in_array($newFile, $skipFolder)) {
				addDirectoryToZip($zip, $file, $base);
			}
        }
        else {
        	if (!in_array($newFile, $skipFile) && !in_array($baseFile, $skipFile)) {
				$zip->addFile($file, $newFile);
			}
        }
	}
}

function folderSize($dir)
{
	$size = 0;

    foreach (glob(rtrim($dir, '/') . '/*', GLOB_NOSORT) as $each) {
        $size += is_file($each) ? filesize($each) : folderSize($each);
    }

    return $size;
}