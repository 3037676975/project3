# 支付宝真实二维码测试站

用于测试支付宝当面付真实二维码。

## 功能
- `alipay.trade.precreate` 创建真实付款二维码
- `alipay.trade.query` 主动查单
- `notify.php` 接收支付宝异步通知并 RSA2 验签
- 支付成功后订单状态变为 `PAID`

## 宝塔部署
1. 拉取本仓库。
2. 网站运行目录设置为 `/public`。
3. PHP 8.0+，开启 `curl`、`openssl`。
4. 确保 `storage/` 可写（程序会自动创建）。
5. 访问 `/settings.php`，首次设置管理密码并填写 APPID、应用私钥、支付宝公钥。
6. 返回首页，用 0.01 元创建真实付款二维码。

> 应用私钥只保存在服务器端 `storage/config.json`。不要把项目根目录作为 Web 根目录。
