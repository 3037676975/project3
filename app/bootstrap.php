<?php
declare(strict_types=1);
session_start();
define('APP_ROOT', dirname(__DIR__));
define('STORAGE_DIR', APP_ROOT.'/storage');
define('ORDER_DIR', STORAGE_DIR.'/orders');
define('LOG_DIR', STORAGE_DIR.'/logs');
define('CONFIG_FILE', STORAGE_DIR.'/config.json');
foreach ([STORAGE_DIR,ORDER_DIR,LOG_DIR] as $d) if(!is_dir($d)) @mkdir($d,0775,true);
function json_response(array $d,int $s=200):never{http_response_code($s);header('Content-Type: application/json; charset=utf-8');header('Cache-Control:no-store');echo json_encode($d,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
function load_config():array{$x=['gateway'=>'https://openapi.alipay.com/gateway.do','app_id'=>'','app_private_key'=>'','alipay_public_key'=>'','notify_url'=>'','admin_password_hash'=>''];if(!is_file(CONFIG_FILE))return $x;$d=json_decode((string)file_get_contents(CONFIG_FILE),true);return is_array($d)?array_merge($x,$d):$x;}
function save_config(array $c):void{if(file_put_contents(CONFIG_FILE,json_encode($c,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),LOCK_EX)===false)throw new RuntimeException('无法写入 storage/config.json');@chmod(CONFIG_FILE,0600);}
function base_url():string{$https=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')||(($_SERVER['HTTP_X_FORWARDED_PROTO']??'')==='https');$scheme=$https?'https':'http';$host=$_SERVER['HTTP_HOST']??'localhost';$script=str_replace('\\','/',$_SERVER['SCRIPT_NAME']??'/');$base=preg_replace('#/(?:api/[^/]+|settings\.php|notify\.php|index\.php)$#','',$script);return $scheme.'://'.$host.rtrim((string)$base,'/');}
function notify_url(array $c):string{return trim((string)($c['notify_url']??''))?:base_url().'/notify.php';}
function ready(array $c):bool{return trim((string)$c['app_id'])!==''&&trim((string)$c['app_private_key'])!==''&&trim((string)$c['alipay_public_key'])!=='';}
function log_event(string $t,array $d=[]):void{unset($d['app_private_key']);@file_put_contents(LOG_DIR.'/alipay-'.date('Y-m-d').'.log','['.date('Y-m-d H:i:s').'] '.$t.' '.json_encode($d,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL,FILE_APPEND|LOCK_EX);}
