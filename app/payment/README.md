# Payment Template Module

这里是通用支付模块目录。

以后新项目接入支付时，复制本目录结构，根据项目业务订单进行适配。

规划结构：

```
app/payment/
├── alipay/
│   ├── create.php
│   ├── query.php
│   └── notify.php
│
└── wechat/
    ├── create.php
    ├── query.php
    └── notify.php
```

原则：

- 支付密钥只放服务器配置
- 业务订单和支付订单分离
- 支付回调必须验签
- 支付状态必须幂等处理
