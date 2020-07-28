<?php

define('WEB_ROOT', __DIR__);

global $_SITE;
$_SITE = new stdClass();

/*


`7MM"""YMM
  MM    `7
  MM   d    `7Mb,od8 `7Mb,od8 ,pW"Wq.`7Mb,od8
  MMmmMM      MM' "'   MM' "'6W'   `Wb MM' "'
  MM   Y  ,   MM       MM    8M     M8 MM
  MM     ,M   MM       MM    YA.   ,A9 MM
.JMMmmmmMMM .JMML.   .JMML.   `Ybmd9'.JMML.



                                                        ,,
`7MM"""Mq.                                       mm     db
  MM   `MM.                                      MM
  MM   ,M9  .gP"Ya `7MMpdMAo.  ,pW"Wq.`7Mb,od8 mmMMmm `7MM  `7MMpMMMb.  .P"Ybmmm
  MMmmdM9  ,M'   Yb  MM   `Wb 6W'   `Wb MM' "'   MM     MM    MM    MM :MI  I8
  MM  YM.  8M""""""  MM    M8 8M     M8 MM       MM     MM    MM    MM  WmmmP"
  MM   `Mb.YM.    ,  MM   ,AP YA.   ,A9 MM       MM     MM    MM    MM 8M
.JMML. .JMM.`Mbmmd'  MMbmmd'   `Ybmd9'.JMML.     `Mbmo.JMML..JMML  JMML.YMMMMMb
                     MM                                                6'     dP
                   .JMML.                                              Ybmmmd'
*/

$_SITE->errorLevel = E_ALL ^ E_NOTICE;
error_reporting($_SITE->errorLevel);

/*

                                     ,,                                ,,                   ,,
`7MMF'                             `7MM      `7MMF'                  `7MM                 `7MM
  MM                                 MM        MM                      MM                   MM
  MM         ,pW"Wq.   ,6"Yb.   ,M""bMM        MM  `7MMpMMMb.  ,p6"bo  MM `7MM  `7MM   ,M""bMM  .gP"Ya  ,pP"Ybd
  MM        6W'   `Wb 8)   MM ,AP    MM        MM    MM    MM 6M'  OO  MM   MM    MM ,AP    MM ,M'   Yb 8I   `"
  MM      , 8M     M8  ,pm9MM 8MI    MM        MM    MM    MM 8M       MM   MM    MM 8MI    MM 8M"""""" `YMMMa.
  MM     ,M YA.   ,A9 8M   MM `Mb    MM        MM    MM    MM YM.    , MM   MM    MM `Mb    MM YM.    , L.   I8
.JMMmmmmMMM  `Ybmd9'  `Moo9^Yo.`Wbmd"MML.    .JMML..JMML  JMML.YMbmd'.JMML. `Mbod"YML.`Wbmd"MML.`Mbmmd' M9mmmP'


*/
foreach (glob(WEB_ROOT . '/inc/*.php') as $path) {
    require $path;
}

/*


`7MM"""Mq.                                        .M"""bgd
  MM   `MM.                                      ,MI    "Y
  MM   ,M9 ,6"Yb.  `7Mb,od8 ,pP"Ybd  .gP"Ya      `MMb.      .gP"Ya `7Mb,od8 `7M'   `MF'.gP"Ya `7Mb,od8
  MMmmdM9 8)   MM    MM' "' 8I   `" ,M'   Yb       `YMMNq. ,M'   Yb  MM' "'   VA   ,V ,M'   Yb  MM' "'
  MM       ,pm9MM    MM     `YMMMa. 8M""""""     .     `MM 8M""""""  MM        VA ,V  8M""""""  MM
  MM      8M   MM    MM     L.   I8 YM.    ,     Mb     dM YM.    ,  MM         VVV   YM.    ,  MM
.JMML.    `Moo9^Yo..JMML.   M9mmmP'  `Mbmmd'     P"Ybmmd"   `Mbmmd'.JMML.        W     `Mbmmd'.JMML.




`7MMF'   `7MF'
  `MA     ,V
   VM:   ,V ,6"Yb.  `7Mb,od8 ,pP"Ybd
    MM.  M'8)   MM    MM' "' 8I   `"
    `MM A'  ,pm9MM    MM     `YMMMa.
     :MM;  8M   MM    MM     L.   I8
      VF   `Moo9^Yo..JMML.   M9mmmP'


*/
$_SITE->siteDir = substr(WEB_ROOT, strlen($_SERVER['DOCUMENT_ROOT'])) . '/';
$_SITE->siteDir = str_replace('\\', '/', $_SITE->siteDir);

$_SITE->baseUrl = 'http' . ($_SERVER['SERVER_PORT'] === '443' ? 's' : '') . '://';
$_SITE->baseUrl .= $_SERVER['SERVER_NAME'];
if (!in_array($_SERVER['SERVER_PORT'], array('80', '443')))
    $_SITE->baseUrl .= ':' . $_SERVER['SERVER_PORT'];
$_SITE->baseUrl .= $_SITE->siteDir;

list($_SITE->requestUri) = explode('?', $_SERVER['REQUEST_URI'], 2);
$_SITE->requestUri = substr($_SITE->requestUri, strlen($_SITE->siteDir));
$_SITE->requestUri = rtrim($_SITE->requestUri, '/');
$_SITE->requestUri = urldecode($_SITE->requestUri);
$_SITE->rawRequestUri = $_SITE->requestUri;

if (isset($_POST['dirsize']))
{
    $bailTime = time() + 5;
    $resp = array();

    foreach ($_POST['list'] as $name => $dir)
    {
        if (time() >= $bailTime) {
            break;
        }
        
        $path = str_replace(DIRECTORY_SEPARATOR, '/', $_SERVER['DOCUMENT_ROOT'] . $dir);
        $size = folderSize($path);
        $size = beautifyFileSize($size);
        $resp[$name] = $size;
    }

    die(json_encode($resp));
}
elseif (isset($_GET['download']))
{
    $path = rtrim(str_replace('/', DIRECTORY_SEPARATOR, $_SERVER['DOCUMENT_ROOT'] . $_GET['download']), DIRECTORY_SEPARATOR);
    
	if (is_dir($path))
	{
		$tmp_file = tempnam('.','');
		$zip = new ZipArchive();
	    if ($zip->open($tmp_file, ZipArchive::CREATE)) {
            addDirectoryToZip($zip, $path, $path . DIRECTORY_SEPARATOR);
        }
        $zip->close();
        
	    header('Content-disposition: attachment; filename=' . basename($_GET['download']) . '.zip');
	    header('Content-type: application/zip');
	    readfile($tmp_file);
	    unlink($tmp_file);
	}
	else
	{
		header('Content-Disposition: ' . true . '; filename=' . basename($_GET['download']));
        header('Cache-Control: private');
        header('Pragma: public');
        readfile($path);
    }
    
	exit;
}

ob_start();
require WEB_ROOT . '/templates/page.php';
exit;

/*

                                                 ,,
`7MM"""YMM                                mm     db
  MM    `7                                MM
  MM   d `7MM  `7MM  `7MMpMMMb.  ,p6"bo mmMMmm `7MM  ,pW"Wq.`7MMpMMMb.  ,pP"Ybd
  MM""MM   MM    MM    MM    MM 6M'  OO   MM     MM 6W'   `Wb MM    MM  8I   `"
  MM   Y   MM    MM    MM    MM 8M        MM     MM 8M     M8 MM    MM  `YMMMa.
  MM       MM    MM    MM    MM YM.    ,  MM     MM YA.   ,A9 MM    MM  L.   I8
.JMML.     `Mbod"YML..JMML  JMML.YMbmd'   `Mbmo.JMML.`Ybmd9'.JMML  JMML.M9mmmP'


*/

function baseUrl($url = '', $lang = false) {
    global $_SITE;
    return $_SITE->baseUrl . ltrim($url, '/');
}

function publicUrl($url = '') {
    global $_SITE;
    return $_SITE->baseUrl . 'public/' . ltrim($url, '/');
}

function get_request_uri($uriPart = false, $includeFollowing = false) {
    return get_raw_request_uri($uriPart, $includeFollowing, 'requestUri');
}

function get_raw_request_uri($uriPart = false, $includeFollowing = false, $prop = 'rawRequestUri') {
    global $_SITE;

    if ($uriPart !== false) {
        $parts = explode('/', $_SITE->$prop);

        if ($uriPart < 0) {
            $uriPart = count($parts) + $uriPart;
        }

        if ($includeFollowing) {
            return implode('/', array_slice($parts, $uriPart));
        }

        return $parts[$uriPart];
    }

    return $_SITE->$prop;
}