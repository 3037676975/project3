<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';
require APP_ROOT.'/app/WechatConfig.php';
$c=load_wechat_runtime_config();$ok=wechat_runtime_ready($c);
function wxmask(string $v):string{$v=trim($v);return $v===''?'未配置':(strlen($v)>8?substr($v,0,4).'****'.substr($v,-4):$v);}
?>
<!doctype html><html lang="zh-CN"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>微信支付测试台</title><link rel="stylesheet" href="assets/app.css"></head>
<body class="provider-wechat">
<header class="app-header"><div class="header-inner"><a class="brand" href="./"><div class="brand-mark">微</div><div><div class="brand-title">Payment Lab</div><div class="brand-sub">支付接口联调中心</div></div></a><nav class="top-nav"><a href="./">支付宝测试</a><a class="active" href="wechat.php">微信支付</a><a href="settings.php">支付宝配置</a><a href="wechat-settings.php">微信配置</a></nav></div></header>
<main class="page">
<section class="hero"><div><div class="eyebrow">WECHAT PAY / REAL PAYMENT</div><h1>微信支付真实测试</h1><p>支持 Native 扫码支付；开启 H5 后也可生成移动支付跳转地址。</p></div><div class="status-pill <?=$ok?'':'off'?>"><?=$ok?'接口已就绪':'等待配置'?></div></section>
<div class="notice <?=$ok?'success':'warn'?>"><span class="notice-dot"></span><div><?php if($ok):?>微信支付参数已配置。Native 模式会请求微信统一下单并展示真实 <b>code_url</b>。<?php else:?>请先进入 <a href="wechat-settings.php">微信支付配置</a> 填写商户号、AppID 和 APIv2 密钥。<?php endif;?></div></div>
<div class="grid-2">
<section class="card"><div class="card-head"><div><div class="card-title">创建微信测试订单</div><div class="card-sub">使用测试金额验证下单、扫码与查单链路</div></div><span class="state-badge">TEST MODE</span></div><div class="card-body">
<div class="mini-stats"><div class="mini-stat"><span>商户号</span><b><?=htmlspecialchars(wxmask((string)$c['mch_id']))?></b></div><div class="mini-stat"><span>AppID</span><b><?=htmlspecialchars(wxmask((string)$c['app_id']))?></b></div></div>
<div class="field"><label>商品名称 <small>body</small></label><input class="input" id="subject" value="微信扫码测试订单"></div>
<div class="field"><label>支付金额 <small>CNY</small></label><input class="input" id="amount" type="number" value="0.01" min="0.01" step="0.01"></div>
<div class="field"><label>支付方式 <small>trade_type</small></label><select class="select" id="tradeType"><option value="NATIVE">Native 扫码支付</option><?php if(!empty($c['h5_enabled'])):?><option value="MWEB">H5 支付</option><?php endif;?></select></div>
<button class="btn btn-primary btn-block" id="payBtn" <?=$ok?'':'disabled'?>>创建微信支付</button>
<div class="payment-state"><div><div class="label">订单号</div><div class="value" id="orderNo">尚未创建</div></div><span id="orderStatus" class="state-badge">WAITING</span></div>
<div class="terminal" id="log">等待操作...</div>
</div></section>
<section class="card"><div class="card-head"><div><div class="card-title">微信付款二维码</div><div class="card-sub">Native 支付会在此展示真实 code_url</div></div></div><div class="card-body"><div id="qrbox" class="qr-stage"><div class="qr-placeholder">⌁</div><strong>二维码将在这里生成</strong><div class="qr-tip">创建 Native 订单后请使用微信扫码付款</div></div><div id="qrraw" class="raw"></div></div></section>
</div><div class="footer-note">Project3 · Payment Integration Test Console</div></main>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
let currentOrder='',timer=null;const logEl=document.getElementById('log'),statusEl=document.getElementById('orderStatus');
function log(s){if(logEl.textContent==='等待操作...')logEl.textContent='';logEl.textContent+='['+new Date().toLocaleTimeString()+'] '+s+'\n';logEl.scrollTop=logEl.scrollHeight}
function setStatus(text,paid=false){statusEl.textContent=text;statusEl.className='state-badge'+(paid?' paid':'')}
async function createPay(){const b=document.getElementById('payBtn'),type=document.getElementById('tradeType').value;b.disabled=true;if(timer)clearInterval(timer);setStatus('CREATING');document.getElementById('qrbox').innerHTML='<div class="qr-placeholder">···</div><strong>正在创建微信订单</strong>';document.getElementById('qrraw').textContent='';try{log('请求微信统一下单，trade_type='+type);const r=await fetch('api/wechat-create.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({subject:document.getElementById('subject').value,amount:document.getElementById('amount').value,trade_type:type})});const d=await r.json();if(!d.ok)throw new Error((d.code?d.code+'：':'')+(d.message||'创建失败'));currentOrder=d.order_no;document.getElementById('orderNo').textContent=d.order_no;setStatus('WAITING');if(type==='MWEB'){document.getElementById('qrbox').innerHTML='<div class="qr-placeholder">↗</div><strong>H5 支付地址已生成</strong><a class="btn btn-primary" style="margin-top:18px" href="'+d.pay_url+'">打开微信支付</a>';log('微信返回 mweb_url');}else{log('微信返回真实 code_url');const box=document.getElementById('qrbox');box.innerHTML='<div id="qrcode"></div><div class="qr-tip">请使用微信扫码支付 ¥'+d.amount+'</div>';if(window.QRCode)new QRCode(document.getElementById('qrcode'),{text:d.pay_url,width:240,height:240,correctLevel:QRCode.CorrectLevel.M});else box.innerHTML='<strong>二维码组件加载失败</strong><div class="qr-tip">请查看下方 code_url 原文</div>';document.getElementById('qrraw').textContent='code_url: '+d.pay_url;}timer=setInterval(queryOrder,2500)}catch(e){setStatus('FAILED');document.getElementById('qrbox').innerHTML='<div class="qr-placeholder">!</div><strong>创建失败</strong><div class="qr-tip">'+e.message+'</div>';log('错误：'+e.message)}finally{b.disabled=false}}
async function queryOrder(){if(!currentOrder)return;try{const r=await fetch('api/wechat-query.php?order_no='+encodeURIComponent(currentOrder),{cache:'no-store'}),d=await r.json();if(d.ok&&d.status==='PAID'){setStatus('PAID',true);log('微信订单确认已支付');clearInterval(timer);timer=null}else if(d.trade_state){setStatus(d.trade_state)}}catch(e){}}
document.getElementById('payBtn')?.addEventListener('click',createPay);
</script></body></html>