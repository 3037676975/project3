<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/app/bootstrap.php';
require APP_ROOT.'/app/OrderStore.php';
require APP_ROOT.'/app/WechatConfig.php';
require APP_ROOT.'/app/WechatPayClient.php';

if($_SERVER['REQUEST_METHOD']!=='POST') json_response(['ok'=>false,'message'=>'仅支持 POST'],405);
$c=load_wechat_runtime_config();
if(!wechat_runtime_ready($c)) json_response(['ok'=>false,'message'=>'请先填写微信支付配置'],400);
$in=json_decode((string)file_get_contents('php://input'),true);if(!is_array($in))$in=$_POST;
$subject=trim((string)($in['subject']??'微信扫码测试订单'));
$raw=trim((string)($in['amount']??'0.01'));
$type=strtoupper(trim((string)($in['trade_type']??'NATIVE')));
if(!in_array($type,['NATIVE','MWEB'],true)) $type='NATIVE';
if($type==='MWEB'&&empty($c['h5_enabled'])) json_response(['ok'=>false,'message'=>'后台尚未开启 H5 支付'],400);
if(!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/',$raw)) json_response(['ok'=>false,'message'=>'金额格式错误'],400);
$amount=number_format((float)$raw,2,'.','');if((float)$amount<0.01)json_response(['ok'=>false,'message'=>'金额不能低于 0.01 元'],400);
$no='WX'.date('YmdHis').strtoupper(bin2hex(random_bytes(4)));
OrderStore::create(['order_no'=>$no,'subject'=>$subject?:'微信扫码测试订单','amount'=>$amount,'status'=>'WAITING','payment_provider'=>'wechat','trade_type'=>$type]);
try{
    $r=(new WechatPayClient($c))->create($no,$amount,$subject?:'微信扫码测试订单',$type);
    if(($r['return_code']??'')!=='SUCCESS'||($r['result_code']??'')!=='SUCCESS'){
        $msg=$r['err_code_des']??$r['return_msg']??'微信支付创建失败';
        OrderStore::update($no,['status'=>'CREATE_FAILED','error'=>$msg]);
        json_response(['ok'=>false,'message'=>$msg,'code'=>$r['err_code']??'','order_no'=>$no],400);
    }
    $payload=$type==='MWEB'?($r['mweb_url']??''):($r['code_url']??'');
    if($payload==='') throw new RuntimeException('微信支付未返回支付地址');
    OrderStore::update($no,['pay_url'=>$payload]);
    log_event('WECHAT_ORDER_CREATED',['order_no'=>$no,'amount'=>$amount,'trade_type'=>$type]);
    json_response(['ok'=>true,'order_no'=>$no,'amount'=>$amount,'subject'=>$subject,'trade_type'=>$type,'pay_url'=>$payload]);
}catch(Throwable $e){
    OrderStore::update($no,['status'=>'CREATE_FAILED','error'=>$e->getMessage()]);
    log_event('WECHAT_CREATE_ERROR',['order_no'=>$no,'error'=>$e->getMessage()]);
    json_response(['ok'=>false,'message'=>$e->getMessage(),'order_no'=>$no],500);
}
