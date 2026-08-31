<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/app/bootstrap.php';
require APP_ROOT.'/app/OrderStore.php';
require APP_ROOT.'/app/WechatConfig.php';
require APP_ROOT.'/app/WechatPayClient.php';

$no=trim((string)($_GET['order_no']??''));if($no==='')json_response(['ok'=>false,'message'=>'缺少 order_no'],400);
try{
    $o=OrderStore::get($no);if(!$o)json_response(['ok'=>false,'message'=>'订单不存在'],404);
    if(($o['status']??'')==='PAID')json_response(['ok'=>true,'status'=>'PAID','order'=>$o]);
    $c=load_wechat_runtime_config();if(!wechat_runtime_ready($c))json_response(['ok'=>true,'status'=>$o['status']??'WAITING']);
    $r=(new WechatPayClient($c))->query($no);
    if(($r['return_code']??'')==='SUCCESS'&&($r['result_code']??'')==='SUCCESS'&&($r['trade_state']??'')==='SUCCESS'){
        $o=OrderStore::markPaid($no,(string)($r['transaction_id']??''));
        log_event('WECHAT_ORDER_PAID_BY_QUERY',['order_no'=>$no,'transaction_id'=>$r['transaction_id']??'']);
        json_response(['ok'=>true,'status'=>'PAID','trade_state'=>'SUCCESS','order'=>$o]);
    }
    json_response(['ok'=>true,'status'=>$o['status']??'WAITING','trade_state'=>$r['trade_state']??'','trade_state_desc'=>$r['trade_state_desc']??'']);
}catch(Throwable $e){json_response(['ok'=>false,'message'=>$e->getMessage()],500);}
