<?php
declare(strict_types=1);

define('WECHAT_CONFIG_FILE_RUNTIME', STORAGE_DIR.'/wechat.json');
function load_wechat_runtime_config():array{
    $d=['mch_id'=>'','app_id'=>'','api_v2_key'=>'','h5_enabled'=>false,'jsapi_enabled'=>false,'notify_url'=>''];
    if(!is_file(WECHAT_CONFIG_FILE_RUNTIME)) return $d;
    $j=json_decode((string)file_get_contents(WECHAT_CONFIG_FILE_RUNTIME),true);
    return is_array($j)?array_merge($d,$j):$d;
}
function wechat_runtime_ready(array $c):bool{
    return trim((string)$c['mch_id'])!==''&&trim((string)$c['app_id'])!==''&&trim((string)$c['api_v2_key'])!=='';
}
function wechat_runtime_notify_url(array $c):string{
    return trim((string)($c['notify_url']??''))?:base_url().'/wechat-notify.php';
}
