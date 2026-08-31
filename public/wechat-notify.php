<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';
require APP_ROOT.'/app/OrderStore.php';
require APP_ROOT.'/app/WechatConfig.php';
require APP_ROOT.'/app/WechatPayClient.php';

header('Content-Type: text/xml; charset=utf-8');
try{
    $c=load_wechat_runtime_config();
    if(!wechat_runtime_ready($c)) throw new RuntimeException('微信支付配置不完整');
    $raw=(string)file_get_contents('php://input');
    $d=(new WechatPayClient($c))->parseNotify($raw);
    if(($d['return_code']??'')!=='SUCCESS'||($d['result_code']??'')!=='SUCCESS') throw new RuntimeException('支付结果不是 SUCCESS');
    if((string)($d['appid']??'')!==(string)$c['app_id']) throw new RuntimeException('AppID 不匹配');
    if((string)($d['mch_id']??'')!==(string)$c['mch_id']) throw new RuntimeException('商户号不匹配');
    $no=(string)($d['out_trade_no']??'');
    $o=OrderStore::get($no);if(!$o) throw new RuntimeException('本地订单不存在');
    $fee=(int)($d['total_fee']??0);$expected=(int)round(((float)$o['amount'])*100);
    if($fee!==$expected) throw new RuntimeException('订单金额不匹配');
    OrderStore::markPaid($no,(string)($d['transaction_id']??''));
    log_event('WECHAT_ORDER_PAID_BY_NOTIFY',['order_no'=>$no,'transaction_id'=>$d['transaction_id']??'']);
    echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
}catch(Throwable $e){
    log_event('WECHAT_NOTIFY_ERROR',['error'=>$e->getMessage()]);
    http_response_code(400);
    echo '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA['.htmlspecialchars($e->getMessage(),ENT_XML1|ENT_QUOTES,'UTF-8').']]></return_msg></xml>';
}
