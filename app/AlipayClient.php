<?php
declare(strict_types=1);
final class AlipayClient{
 public function __construct(private array $config){}
 public function precreate(string $no,string $amount,string $subject):array{return $this->request('alipay.trade.precreate',['out_trade_no'=>$no,'total_amount'=>$amount,'subject'=>$subject,'timeout_express'=>'10m'],true);}
 public function query(string $no):array{return $this->request('alipay.trade.query',['out_trade_no'=>$no],false);}
 private function request(string $method,array $biz,bool $notify):array{
  if(!function_exists('curl_init'))throw new RuntimeException('PHP 未启用 curl');
  if(!function_exists('openssl_sign'))throw new RuntimeException('PHP 未启用 openssl');
  $p=['app_id'=>trim((string)$this->config['app_id']),'method'=>$method,'format'=>'JSON','charset'=>'utf-8','sign_type'=>'RSA2','timestamp'=>date('Y-m-d H:i:s'),'version'=>'1.0','biz_content'=>json_encode($biz,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)];
  if($notify)$p['notify_url']=notify_url($this->config);
  $p['sign']=$this->sign($p);
  $ch=curl_init(trim((string)$this->config['gateway']));curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query($p,'','&',PHP_QUERY_RFC3986),CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>25,CURLOPT_HTTPHEADER=>['Content-Type: application/x-www-form-urlencoded;charset=utf-8']]);
  $body=curl_exec($ch);$err=curl_error($ch);$code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
  if($body===false||$body==='')throw new RuntimeException('请求支付宝失败：'.($err?:'empty response'));
  if($code>=400)throw new RuntimeException('支付宝网关 HTTP '.$code);
  $j=json_decode($body,true);if(!is_array($j))throw new RuntimeException('支付宝返回无法解析');
  $k=str_replace('.','_',$method).'_response';$r=$j[$k]??null;if(!is_array($r))throw new RuntimeException('支付宝返回缺少 '.$k);
  log_event('ALIPAY_RESPONSE',['method'=>$method,'code'=>$r['code']??'','msg'=>$r['msg']??'','sub_code'=>$r['sub_code']??'','sub_msg'=>$r['sub_msg']??'']);return $r;
 }
 private function sign(array $p):string{unset($p['sign']);ksort($p);$a=[];foreach($p as $k=>$v)if($v!==''&&$v!==null)$a[]=$k.'='.$v;$content=implode('&',$a);$key=openssl_pkey_get_private($this->privatePem((string)$this->config['app_private_key']));if($key===false)throw new RuntimeException('应用私钥格式错误');$sig='';if(!openssl_sign($content,$sig,$key,OPENSSL_ALGO_SHA256))throw new RuntimeException('RSA2 签名失败');return base64_encode($sig);}
 public function verifyNotify(array $p):bool{$sign=(string)($p['sign']??'');if($sign==='')return false;unset($p['sign'],$p['sign_type']);ksort($p);$a=[];foreach($p as $k=>$v)if($v!==''&&$v!==null)$a[]=$k.'='.$v;$content=implode('&',$a);$key=openssl_pkey_get_public($this->publicPem((string)$this->config['alipay_public_key']));if($key===false)throw new RuntimeException('支付宝公钥格式错误');return openssl_verify($content,base64_decode($sign),$key,OPENSSL_ALGO_SHA256)===1;}
 private function privatePem(string $k):string{$k=trim($k);if(str_contains($k,'BEGIN'))return $k;$k=preg_replace('/\s+/','',$k);return "-----BEGIN PRIVATE KEY-----\n".chunk_split($k,64,"\n")."-----END PRIVATE KEY-----";}
 private function publicPem(string $k):string{$k=trim($k);if(str_contains($k,'BEGIN'))return $k;$k=preg_replace('/\s+/','',$k);return "-----BEGIN PUBLIC KEY-----\n".chunk_split($k,64,"\n")."-----END PUBLIC KEY-----";}
}
