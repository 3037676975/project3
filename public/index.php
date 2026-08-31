<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';
$c=load_config();$ok=ready($c);
function maskid(string $v):string{$v=trim($v);return $v===''?'未配置':(strlen($v)>8?substr($v,0,4).'****'.substr($v,-4):$v);}
?>
<!doctype html><html lang="zh-CN"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>支付宝支付测试台</title><link rel="stylesheet" href="assets/app.css"></head>
<body class="provider-alipay">
<header class="app-header"><div class="header-inner"><a class="brand" href="./"><div class="brand-mark">付</div><div><div class="brand-title">Payment Lab</div><div class="brand-sub">支付接口联调中心</div></div></a><nav class="top-nav"><a class="active" href="./">支付宝测试</a><a href="wechat.php">微信支付</a><a href="settings.php">支付宝配置</a><a href="wechat-settings.php">微信配置</a></nav></div></header>
<main class="page">
<section class="hero"><div><div class="eyebrow">ALIPAY / REAL PAYMENT</div><h1>支付宝真实扫码支付</h1><p>创建测试订单、生成真实付款二维码，并自动轮询订单状态。</p></div><div class="status-pill <?=$ok?'':'off'?>"><?=$ok?'接口已就绪':'等待配置'?></div></section>
<div class="notice <?=$ok?'':'warn'?>"><span class="notice-dot"></span><div><?php if($ok):?>支付宝参数已配置完成。创建订单后，服务器会真实请求 <b>alipay.trade.precreate</b>。<?php else:?>请先进入 <a href="settings.php">支付宝配置</a> 填写 APPID、应用私钥和支付宝公钥。<?php endif;?></div></div>
<div class="grid-2">
<section class="card"><div class="card-head"><div><div class="card-title">创建测试订单</div><div class="card-sub">建议先使用 ¥0.01 完成完整链路测试</div></div><span class="state-badge">TEST MODE</span></div><div class="card-body">
<div class="mini-stats"><div class="mini-stat"><span>APPID</span><b><?=htmlspecialchars(maskid((string)$c['app_id']))?></b></div><div class="mini-stat"><span>异步通知</span><b><?=htmlspecialchars(notify_url($c))?></b></div></div>
<div class="field"><label>商品名称 <small>subject</small></label><input class="input" id="subject" value="支付宝扫码测试订单"></div>
<div class="field"><label>支付金额 <small>CNY</small></label><input class="input" id="amount" type="number" value="0.01" min="0.01" step="0.01"></div>
<button class="btn btn-primary btn-block" id="payBtn" <?=$ok?'':'disabled'?>>创建真实付款二维码</button>
<div class="payment-state"><div><div class="label">订单号</div><div class="value" id="orderNo">尚未创建</div></div><span id="orderStatus" class="state-badge">WAITING</span></div>
<div class="terminal" id="log">等待操作...</div>
</div></section>
<section class="card"><div class="card-head"><div><div class="card-title">付款二维码</div><div class="card-sub">使用手机支付宝扫描完成支付</div></div></div><div class="card-body"><div id="qrbox" class="qr-stage"><div class="qr-placeholder">⌁</div><strong>二维码将在这里生成</strong><div class="qr-tip">创建订单后会显示支付宝返回的真实 qr_code</div></div><div id="qrraw" class="raw"></div></div></section>
</div><div class="footer-note">Project3 · Payment Integration Test Console</div></main>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
let currentOrder='',timer=null;const logEl=document.getElementById('log'),statusEl=document.getElementById('orderStatus');
function log(s){if(logEl.textContent==='等待操作...')logEl.textContent='';logEl.textContent+='['+new Date().toLocaleTimeString()+'] '+s+'\n';logEl.scrollTop=logEl.scrollHeight}
function setStatus(text,paid=false){statusEl.textContent=text;statusEl.className='state-badge'+(paid?' paid':'')}
async function createPay(){const b=document.getElementById('payBtn');b.disabled=true;if(timer)clearInterval(timer);setStatus('CREATING');document.getElementById('qrbox').innerHTML='<div class="qr-placeholder">···</div><strong>正在创建支付订单</strong>';try{log('请求 alipay.trade.precreate');const r=await fetch('api/create.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({subject:document.getElementById('subject').value,amount:document.getElementById('amount').value})});const d=await r.json();if(!d.ok)throw new Error((d.code?d.code+'：':'')+(d.message||'创建失败'));currentOrder=d.order_no;document.getElementById('orderNo').textContent=d.order_no;setStatus('WAITING');log('支付宝已返回真实 qr_code');const box=document.getElementById('qrbox');box.innerHTML='<div id="qrcode"></div><div class="qr-tip">请使用支付宝扫码支付 ¥'+d.amount+'</div>';if(window.QRCode)new QRCode(document.getElementById('qrcode'),{text:d.qr_code,width:240,height:240,correctLevel:QRCode.CorrectLevel.M});else box.innerHTML='<strong>二维码组件加载失败</strong><div class="qr-tip">请查看下方 qr_code 原文</div>';document.getElementById('qrraw').textContent='qr_code: '+d.qr_code;timer=setInterval(queryOrder,2500)}catch(e){setStatus('FAILED');document.getElementById('qrbox').innerHTML='<div class="qr-placeholder">!</div><strong>创建失败</strong><div class="qr-tip">'+e.message+'</div>';log('错误：'+e.message)}finally{b.disabled=false}}
async function queryOrder(){if(!currentOrder)return;try{const r=await fetch('api/query.php?order_no='+encodeURIComponent(currentOrder),{cache:'no-store'}),d=await r.json();if(d.ok&&d.status==='PAID'){setStatus('PAID',true);log('订单确认已支付');clearInterval(timer);timer=null}}catch(e){}}
document.getElementById('payBtn')?.addEventListener('click',createPay);
</script></body></html>