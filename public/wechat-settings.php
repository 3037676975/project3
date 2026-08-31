<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';

define('WECHAT_CONFIG_FILE', STORAGE_DIR.'/wechat.json');
function load_wechat_config():array{
    $d=['mch_id'=>'','app_id'=>'','api_v2_key'=>'','h5_enabled'=>false,'jsapi_enabled'=>false,'notify_url'=>''];
    if(!is_file(WECHAT_CONFIG_FILE)) return $d;
    $j=json_decode((string)file_get_contents(WECHAT_CONFIG_FILE),true);
    return is_array($j)?array_merge($d,$j):$d;
}
function save_wechat_config(array $c):void{
    if(file_put_contents(WECHAT_CONFIG_FILE,json_encode($c,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),LOCK_EX)===false) throw new RuntimeException('无法写入 storage/wechat.json');
    @chmod(WECHAT_CONFIG_FILE,0600);
}
function wechat_notify_url(array $c):string{return trim((string)($c['notify_url']??''))?:base_url().'/wechat-notify.php';}
function wechat_ready(array $c):bool{return trim((string)$c['mch_id'])!==''&&trim((string)$c['app_id'])!==''&&trim((string)$c['api_v2_key'])!=='';}

$main=load_config();$has=!empty($main['admin_password_hash']);$wc=load_wechat_config();$error='';$success='';
if(!$has){header('Location: settings.php');exit;}
if(isset($_GET['logout'])){unset($_SESSION['alipay_admin']);header('Location: wechat-settings.php');exit;}
if(empty($_SESSION['alipay_admin'])){
    if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['login_password'])){
        if(password_verify((string)$_POST['login_password'],(string)$main['admin_password_hash'])){$_SESSION['alipay_admin']=true;header('Location: wechat-settings.php');exit;}
        $error='管理密码错误';
    }
    if(empty($_SESSION['alipay_admin'])){?><!doctype html><html lang="zh-CN"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>微信支付配置登录</title><style>body{font-family:Arial,"Microsoft YaHei";background:#f4f7fb}.box{max-width:420px;margin:12vh auto;background:#fff;padding:28px;border-radius:16px;box-shadow:0 10px 35px #0001}input,button{width:100%;box-sizing:border-box;padding:13px;border-radius:9px}input{border:1px solid #ddd}button{border:0;background:#07c160;color:white;font-weight:700;margin-top:12px}.err{color:#dc2626}</style></head><body><div class="box"><h2>微信支付配置</h2><?php if($error):?><p class="err"><?=htmlspecialchars($error)?></p><?php endif;?><form method="post"><input type="password" name="login_password" required autofocus placeholder="管理密码"><button>登录</button></form></div></body></html><?php exit;}
}
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['save'])){
    try{
        $wc['mch_id']=trim((string)($_POST['mch_id']??''));
        $wc['app_id']=trim((string)($_POST['app_id']??''));
        $key=trim((string)($_POST['api_v2_key']??''));if($key!=='')$wc['api_v2_key']=$key;
        $wc['h5_enabled']=isset($_POST['h5_enabled']);
        $wc['jsapi_enabled']=isset($_POST['jsapi_enabled']);
        $wc['notify_url']=trim((string)($_POST['notify_url']??''));
        save_wechat_config($wc);$success='微信支付配置已保存';
    }catch(Throwable $e){$error=$e->getMessage();}
}
$host=$_SERVER['HTTP_HOST']??'localhost';
?><!doctype html><html lang="zh-CN"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>微信企业支付配置</title><style>*{box-sizing:border-box}body{margin:0;background:#f5f7fa;font-family:Arial,"Microsoft YaHei";color:#334155}.top{background:#fff;border-bottom:1px solid #dce3ea;padding:15px 22px;font-size:20px;font-weight:700}.wrap{max-width:1000px;margin:22px auto;padding:0 14px}.nav{margin-bottom:14px}.nav a{color:#1677ff;text-decoration:none;margin-right:16px}.panel{background:#fff;border:1px solid #d7dee8}.title{padding:12px 18px;border-bottom:1px solid #d7dee8;font-weight:700}.info{background:#dff3fb;padding:18px 24px;line-height:2;font-size:13px;color:#256078}.form{padding:22px 26px}.row{display:grid;grid-template-columns:190px 1fr;align-items:center;gap:16px;margin:13px 0}.label{text-align:right;color:#0877cf;font-weight:700;font-size:13px}.hint{font-size:12px;color:#94a3b8;margin-top:5px}.field input{width:100%;padding:10px 12px;border:1px solid #aeb8c4;border-radius:3px}.switch{display:flex;align-items:center;gap:10px}.switch input{width:18px;height:18px}.btn{background:#07c160;border:0;color:#fff;padding:11px 24px;border-radius:5px;font-weight:700;cursor:pointer}.ok,.err{margin:14px 0;padding:11px 13px;border-radius:5px}.ok{background:#ecfdf5;color:#047857}.err{background:#fef2f2;color:#b91c1c}.code{font-family:Consolas,monospace;word-break:break-all}.secret{color:#b45309}@media(max-width:700px){.row{grid-template-columns:1fr}.label{text-align:left}}</style></head><body><div class="top">微信企业支付</div><div class="wrap"><div class="nav"><a href="./">支付宝测试</a><a href="wechat.php">微信支付测试</a><a href="settings.php">支付宝配置</a><a href="?logout=1">退出</a></div><div class="panel"><div class="title">微信企业支付</div><div class="info"><b>微信企业支付：</b>官方接口，商家可申请<br>Native 支付回调链接：<span class="code"><?=htmlspecialchars(wechat_notify_url($wc))?></span><br>JSAPI 支付授权目录：<span class="code"><?=htmlspecialchars(base_url().'/')?></span><br>JS 接口安全域名：<span class="code"><?=htmlspecialchars(explode(':',$host)[0])?></span><br>• 支持 PC 端扫码支付（Native 支付）<br>• 支持微信 APP 内直接支付（JSAPI 支付，需 openid/OAuth）<br>• 支持移动网页中唤起微信 APP 支付（H5 支付）</div><div class="form"><p>当前状态：<b><?=wechat_ready($wc)?'配置完整':'配置未完成'?></b></p><?php if($success):?><div class="ok"><?=htmlspecialchars($success)?></div><?php endif;?><?php if($error):?><div class="err"><?=htmlspecialchars($error)?></div><?php endif;?><form method="post" autocomplete="off"><input type="hidden" name="save" value="1"><div class="row"><div class="label">商户号 PartnerID</div><div class="field"><input name="mch_id" value="<?=htmlspecialchars((string)$wc['mch_id'])?>" placeholder="微信支付商户号 mch_id"></div></div><div class="row"><div class="label">授权绑定的 AppID</div><div class="field"><input name="app_id" value="<?=htmlspecialchars((string)$wc['app_id'])?>" placeholder="公众号/开放平台 AppID"></div></div><div class="row"><div class="label">支付 APIv2 密钥</div><div class="field"><input type="password" name="api_v2_key" placeholder="<?=$wc['api_v2_key']?'已保存；不修改请留空':'输入 APIv2 密钥'?>"><div class="hint secret">密钥只保存到服务器 storage/wechat.json，不会再次显示。</div></div></div><div class="row"><div class="label">H5 支付</div><div class="switch"><input type="checkbox" name="h5_enabled" <?=$wc['h5_enabled']?'checked':''?>><span>移动端跳转到微信 APP 支付</span></div></div><div class="row"><div class="label">JSAPI 支付</div><div class="switch"><input type="checkbox" name="jsapi_enabled" <?=$wc['jsapi_enabled']?'checked':''?>><span>微信 APP 内直接发起支付</span></div></div><div class="row"><div class="label">异步通知地址</div><div class="field"><input name="notify_url" value="<?=htmlspecialchars((string)$wc['notify_url'])?>" placeholder="<?=htmlspecialchars(base_url().'/wechat-notify.php')?>"><div class="hint">留空自动使用：<?=htmlspecialchars(wechat_notify_url($wc))?></div></div></div><div class="row"><div></div><div><button class="btn">保存微信支付配置</button></div></div></form></div></div></div></body></html>