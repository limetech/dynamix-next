<?PHP
/* Copyright 2005-2021, Lime Technology
 * Copyright 2012-2021, Bergware International.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 */
?>
<?
$docroot = $docroot ?? $_SERVER['DOCUMENT_ROOT'] ?: '/usr/local/emhttp';
// add translations
$_SERVER['REQUEST_URI'] = 'settings';
require_once "$docroot/webGui/include/Translations.php";
require_once "$docroot/webGui/include/Helpers.php";

$cli = php_sapi_name()=='cli';

function response_complete($httpcode, $result, $cli_success_msg='') {
  global $cli;
  if ($cli) {
    $json = @json_decode($result,true);
    if (!empty($json['error'])) {
      echo 'Error: '.$json['error'].PHP_EOL;
      exit(1);
    }
    exit($cli_success_msg.PHP_EOL);
  }
  header('Content-Type: application/json');
  http_response_code($httpcode);
  exit((string)$result);
}

// keyfile
$var = parse_ini_file("/var/local/emhttp/var.ini");
$keyfile = @file_get_contents($var['regFILE']);
if ($keyfile === false) {
  response_complete(406, '{"error":"'._('Registration key required').'"}');
}
$keyfile = @base64_encode($keyfile);

// internalip
extract(parse_ini_file('/var/local/emhttp/network.ini',true));
$internalip = $eth0['IPADDR:0'];

$ch = curl_init('https://keys.lime-technology.com/account/server/register');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
  'internalip' => $internalip,
  'keyfile' => $keyfile
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($result === false) {
  response_complete(500, '{"error":"'.$error.'"}');
}

response_complete($httpcode, $result, _('success'));
?>
