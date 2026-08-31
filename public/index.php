<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';
$c=load_config();$ok=ready($c);
function maskid(string $v):string{$v=trim($v);return $v===''?'未配置':(strlen($v)>8?substr($v,0,4).'****'.substr($v,-4):$v);}
?>
<!doctype html>
<html lang="zh-CN">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>支付宝真实二维码测试</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#f4f7fb;font-family:Arial,"Microsoft YaHei",sans-serif;color:#1f2937}.top{background:#1677ff;color:#fff;padding:18px 22px;font-size:21px;font-weight:700}.wrap{max-width:900px;margin:28px auto;padding:0 16px}.nav{margin-bottom:15px}.nav a{margin-right:16px;color:#1677ff;text-decoration:none}.grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}.card{background:#fff;border-radius:15px;padding:24px;box-shadow:0 8px 30px #0000000d}h2{margin:0 0 16px}label{display:block;font-size:13px;font-weight:700;margin:14px 0 7px}input{width:100%;padding:12px;border:1px solid #d7deea;border-radius:9px}.btn{width:100%;border:0;background:#1677ff;color:#fff;padding:13px;border-radius:9px;font-weight:700;margin-top:16px;cursor:pointer}.btn:disabled{opacity:.5}.notice{padding:12px 14px;border-radius:10px;margin-bottom:20px;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;font-size:13px;line-height:1.7}.bad{background:#fff7ed;color:#c2410c;border-color:#fed7aa}.status{margin-top:16px;padding:12px;background:#f8fafc;border-radius:9px;font-size:13px;line-height:1.8}.qrbox{min-height:285px;display:flex;align-items:center;justify-content:center;flex-direction:column;border:1px dashed #cbd5e1;border-radius:12px;padding:18px}.log{margin-top:14px;background:#0f172a;color:#cbd5e1;border-radius:9px;padding:12px;min-height:120px;max-height:190px;overflow:auto;white-space:pre-wrap;font:12px/1.6 Consolas,monospace}.paid{color:#047857;font-weight:700}.wait{color:#c2410c}.raw{font-size:11px;color:#64748b;word-break:break-all;margin-top:10px}.meta{font-size:13px;color:#64748b;line-height:1.8}a{color:#1677ff;text-decoration:none}@media(max-width:760px){.grid{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="top">支付宝真实扫码支付测试</div>
<div class="wrap">
<div class="nav"><a href="./"><b>支付宝测试</b></a><a href="wechat.php">微信支付测试</a><a href="settings.php">支付宝配置</a><a href="wechat-settings.php">微信支付配置</a></div>
<div class="notice <?=$ok?'':'bad'?>"><?php if($ok):?>支付宝参数已经配置。点击创建后，服务器会真实请求 <b>alipay.trade.precreate</b>。<?php else:?>请先进入 <a href="settings.php">支付宝配置</a> 填写 APPID、应用私钥和支付宝公钥。<?php endif;?></div>
<div class="grid">
<div class="card">
<h2>创建测试订单</h2>
<div class="meta">APPID：<b><?=htmlspecialchars(maskid((string)$c['app_id']))?></b><br>通知地址：<b><?=htmlspecialchars(notify_url($c))?></b></div>
<label>商品名称</label><input id="subject" value="支付宝扫码测试订单">
<label>支付金额（元）</label><input id="amount" type="number" value="0.01" min="0.01" step="0.01">
<button class="btn" id="payBtn" <?=$ok?'':'disabled'?>>创建真实付款二维码</button>
<div class="status">订单号：<b id="orderNo">-</b><br>状态：<span id="orderStatus" class="wait">尚未创建</span></div>
<div class="log" id="log">等待操作...</div>
</div>
<div class="card"><h2>支付宝付款二维码</h2><div id="qrbox" class="qrbox">创建订单后，这里会显示支付宝二维码</div><div id="qrraw" class="raw"></div></div>
</div></div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
let currentOrder='',timer=null;const logEl=document.getElementById('log');function log(s){if(logEl.textContent==='等待操作...')logEl.textContent='';logEl.textContent+='['+new Date().toLocaleTimeString()+'] '+s+'\n';logEl.scrollTop=logEl.scrollHeight}
async function createPay(){const b=document.getElementById('payBtn');b.disabled=true;if(timer)clearInterval(timer);document.getElementById('orderStatus').textContent='正在请求支付宝...';document.getElementById('qrbox').innerHTML='正在创建...';try{log('请求 alipay.trade.precreate');const r=await fetch('api/create.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({subject:document.getElementById('subject').value,amount:document.getElementById('amount').value})});const d=await r.json();if(!d.ok)throw new Error((d.code?d.code+'：':'')+(d.message||'创建失败'));currentOrder=d.order_no;document.getElementById('orderNo').textContent=d.order_no;document.getElementById('orderStatus').textContent='等待支付宝付款';log('支付宝已返回真实 qr_code');const box=document.getElementById('qrbox');box.innerHTML='<div id="qrcode"></div>';if(window.QRCode)new QRCode(document.getElementById('qrcode'),{text:d.qr_code,width:240,height:240,correctLevel:QRCode.CorrectLevel.M});else box.innerHTML='二维码组件加载失败，请查看 qr_code 原文';document.getElementById('qrraw').textContent='qr_code: '+d.qr_code;timer=setInterval(queryOrder,2500)}catch(e){document.getElementById('orderStatus').textContent='创建失败';document.getElementById('qrbox').textContent='创建失败';log('错误：'+e.message)}finally{b.disabled=false}}
async function queryOrder(){if(!currentOrder)return;try{const r=await fetch('api/query.php?order_no='+encodeURIComponent(currentOrder),{cache:'no-store'}),d=await r.json();if(d.ok&&d.status==='PAID'){document.getElementById('orderStatus').textContent='支付成功 PAID';document.getElementById('orderStatus').className='paid';log('订单确认已支付');clearInterval(timer);timer=null}}catch(e){}}
document.getElementById('payBtn')?.addEventListener('click',createPay);
</script>
</body></html>
