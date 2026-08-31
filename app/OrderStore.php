<?php
declare(strict_types=1);
final class OrderStore{
 private static function path(string $no):string{if(!preg_match('/^[A-Za-z0-9_-]{8,64}$/',$no))throw new InvalidArgumentException('订单号格式错误');return ORDER_DIR.'/'.$no.'.json';}
 public static function create(array $o):void{$o['created_at']=date('c');$o['updated_at']=date('c');self::write((string)$o['order_no'],$o);}
 public static function get(string $no):?array{$p=self::path($no);if(!is_file($p))return null;$d=json_decode((string)file_get_contents($p),true);return is_array($d)?$d:null;}
 public static function update(string $no,array $c):array{$o=self::get($no);if(!$o)throw new RuntimeException('订单不存在');$o=array_merge($o,$c);$o['updated_at']=date('c');self::write($no,$o);return $o;}
 public static function markPaid(string $no,string $tradeNo=''):array{$o=self::get($no);if(!$o)throw new RuntimeException('订单不存在');if(($o['status']??'')==='PAID')return $o;return self::update($no,['status'=>'PAID','trade_no'=>$tradeNo,'paid_at'=>date('c')]);}
 private static function write(string $no,array $d):void{$p=self::path($no);if(file_put_contents($p,json_encode($d,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),LOCK_EX)===false)throw new RuntimeException('订单写入失败');@chmod($p,0600);}
}
