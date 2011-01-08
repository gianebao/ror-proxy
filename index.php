<?php
$repo = 'downloads';
$path = 'http://rubygems.org/';
$username = '<username>';
$password = '<password>';
$proxy = 'tcp://<proxy>';
$port = '8080';

$extTypeMap = array(
    'gz' => 'application/gzip',
    'rz' => 'application'
);

/**
 * Fetches all the real headers sent by the server in response to a HTTP request without redirects
 *
 * @link      http://php.net/function.get_headers
 * @link      http://bugs.php.net/bug.php?id=50719 
 */

function get_real_headers($url, $format=0, $follow_redirect=0) {
  if (!$follow_redirect) {
    //set new default options
    $opts = array('http' =>
        array('max_redirects'=>1,'ignore_errors'=>1)
    );
    stream_context_get_default($opts);
  }
  //get headers
    $headers=get_headers($url,$format);
    //restore default options
  if (isset($opts)) {
    $opts = array('http' =>
        array('max_redirects'=>20,'ignore_errors'=>0)
    );
    stream_context_get_default($opts);
  }
  //return
    return $headers;
}

if (false === is_dir($repo)) {
    mkdir( $repo, 0775);
}

$auth = base64_encode($username . ':' . $password);
$item = '';

if (!empty($_GET['_p'])) {
    $item = $_GET['_p'];
    $ext = strtolower(substr(strrchr($item, '.'), 1));
    
    if (empty($extTypeMap[$ext])) {
        $item = str_replace('_', '.', $item);
        $ext = strtolower(substr(strrchr($item, '.'), 1));
        $type = empty($extTypeMap[$ext]) ? 'application/gzip': $extTypeMap[$ext];
    } else {
        $type = $extTypeMap[$ext];
    }
    header('Content-Disposition: filename="' . substr(strrchr($item, '/'), 1) . '"');
    $path .= $item;
}

if (!empty($item)) { 
    file_put_contents('proxy.log', date('Y/m/d H:i:s') . ": downloading ... " . $item . "\r\n", FILE_APPEND);
} else {
    file_put_contents('error.log', 'GET:::' . print_r($_GET, true) . 'POST:::' . print_r($_POST, true), FILE_APPEND);
}

$aContext = array(
    'http' => array(
        'proxy' => $proxy . ':' . $port,
        'request_fulluri' => true,
        'header' => 'Proxy-Authorization: Basic ' . $auth
    )
);
$default = stream_context_get_default($aContext);
$sFile = file_get_contents($path);
$headers = get_real_headers($path, 1);

if (!empty($item)) {
    file_put_contents($repo . '/' . time() . '--' . $item, $sFile);
}

header('Content-Type: ' . (is_array($headers['Content-Type']) ? implode(';', $headers['Content-Type']): $headers['Content-Type']));
echo $sFile;
