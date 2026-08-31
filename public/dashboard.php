<?php
require dirname(__DIR__).'/app/bootstrap.php';
$c=load_config();
$stats=['orders'=>0,'paid'=>0,'pending'=>0,'amount'=>'0.00'];
if(is_dir(dirname(__DIR__).'/storage/orders')){
 foreach(glob(dirname(__DIR__).'/storage/orders/*') as $f){
  $stats['orders']++;
  $d=@json_decode(file_get_contents($f),true);
  if(($d['status']??'')==='PAID'){$stats['paid']++;$stats['amount']=number_format((float)$stats['amount']+(float)($d['amount']??0),2);}
  else{$stats['pending']++;}
 }
}
?><!doctype html><html lang="zh-CN"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Payment Lab 控制台</title><link rel="stylesheet" href="assets/app.css"></head><body class="provider-settings"><header class="app-header"><div class="header-inner"><a class="brand" href="dashboard.php"><div class="brand-mark">付</div><div><div class="brand-title">Payment Lab</div><div class="brand-sub">通用支付模板中心</div></div></a><nav class="top-nav"><a class="active" href="dashboard.php">控制台</a><a href="./">支付宝</a><a href="wechat.php">微信</a><a href="orders.php">订单</a><a href="logs.php">日志</a><a href="settings.php">配置</a></nav></div></header><main class="page"><section class="hero"><div><div class="eyebrow">PAYMENT TEMPLATE</div><h1>支付模板控制台</h1><p>以后新项目参考本项目结构，复制支付模块。</p></div><div class="status-pill <?=ready($c)?'':'off'?>"><?=ready($c)?'支付已配置':'等待配置'?></div></section><div class="mini-stats"><div class="mini-stat"><span>订单总数</span><b><?=$stats['orders']?></b></div><div class="mini-stat"><span>支付成功</span><b><?=$stats['paid']?></b></div><div class="mini-stat"><span>待支付</span><b><?=$stats['pending']?></b></div><div class="mini-stat"><span>支付金额</span><b>¥<?=$stats['amount']?></b></div></div><div class="grid-2"><section class="card"><div class="card-head"><div class="card-title">快捷入口</div></div><div class="card-body"><a class="btn btn-primary btn-block" href="./">支付宝真实扫码</a><br><br><a class="btn btn-primary btn-block" href="wechat.php">微信支付测试</a><br><br><a class="btn btn-soft btn-block" href="orders.php">订单管理</a><br><br><a class="btn btn-soft btn-block" href="logs.php">日志中心</a></div></section><section class="card"><div class="card-head"><div class="card-title">模板能力</div></div><div class="card-body"><p>✅ 支付宝扫码支付</p><p>✅ 微信扫码支付</p><p>✅ 异步通知</p><p>✅ 订单查询</p><p>✅ 配置后台</p><p>以后新项目直接复制支付模块。</p></div></section></div></main></body></html>