<?php
declare(strict_types=1);

final class WechatPayClient {
    private const UNIFIED_ORDER = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
    private const ORDER_QUERY = 'https://api.mch.weixin.qq.com/pay/orderquery';

    public function __construct(private array $config) {}

    public function create(string $orderNo, string $amount, string $subject, string $tradeType = 'NATIVE'): array {
        $fee = (int)round(((float)$amount) * 100);
        if ($fee < 1) throw new RuntimeException('支付金额不能低于 0.01 元');
        $p = [
            'appid' => trim((string)$this->config['app_id']),
            'mch_id' => trim((string)$this->config['mch_id']),
            'nonce_str' => bin2hex(random_bytes(16)),
            'body' => mb_substr($subject ?: '微信支付测试订单', 0, 40),
            'out_trade_no' => $orderNo,
            'total_fee' => $fee,
            'spbill_create_ip' => $this->clientIp(),
            'notify_url' => $this->notifyUrl(),
            'trade_type' => $tradeType,
        ];
        if ($tradeType === 'MWEB') {
            $p['scene_info'] = json_encode(['h5_info'=>['type'=>'Wap','wap_url'=>base_url(),'wap_name'=>'WeChat Pay Test']], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        }
        return $this->post(self::UNIFIED_ORDER, $p);
    }

    public function query(string $orderNo): array {
        return $this->post(self::ORDER_QUERY, [
            'appid' => trim((string)$this->config['app_id']),
            'mch_id' => trim((string)$this->config['mch_id']),
            'nonce_str' => bin2hex(random_bytes(16)),
            'out_trade_no' => $orderNo,
        ]);
    }

    public function parseNotify(string $xml): array {
        $data = $this->parseXml($xml);
        if (!$data || !$this->verify($data)) throw new RuntimeException('微信支付通知签名验证失败');
        return $data;
    }

    public function verify(array $params): bool {
        $sign = strtoupper((string)($params['sign'] ?? ''));
        if ($sign === '') return false;
        return hash_equals($sign, $this->sign($params));
    }

    private function post(string $url, array $params): array {
        if (!function_exists('curl_init')) throw new RuntimeException('PHP 未启用 curl');
        $params['sign'] = $this->sign($params);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $this->toXml($params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_HTTPHEADER => ['Content-Type: text/xml; charset=utf-8'],
        ]);
        $body = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($body === false || $body === '') throw new RuntimeException('请求微信支付失败：'.($err ?: 'empty response'));
        if ($code >= 400) throw new RuntimeException('微信支付网关 HTTP '.$code);
        $r = $this->parseXml($body);
        log_event('WECHAT_RESPONSE', ['return_code'=>$r['return_code']??'','result_code'=>$r['result_code']??'','err_code'=>$r['err_code']??'','err_code_des'=>$r['err_code_des']??'']);
        return $r;
    }

    private function sign(array $params): string {
        unset($params['sign']);
        ksort($params);
        $parts = [];
        foreach ($params as $k=>$v) if ($v !== '' && $v !== null) $parts[] = $k.'='.$v;
        $parts[] = 'key='.trim((string)$this->config['api_v2_key']);
        return strtoupper(md5(implode('&', $parts)));
    }

    private function toXml(array $data): string {
        $xml = '<xml>';
        foreach ($data as $k=>$v) {
            $xml .= '<'.$k.'><![CDATA['.str_replace(']]>', ']]]]><![CDATA[>', (string)$v).']]></'.$k.'>';
        }
        return $xml.'</xml>';
    }

    private function parseXml(string $xml): array {
        $old = libxml_use_internal_errors(true);
        $obj = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA|LIBXML_NONET);
        libxml_clear_errors(); libxml_use_internal_errors($old);
        if ($obj === false) throw new RuntimeException('微信支付返回 XML 无法解析');
        $out = [];
        foreach ($obj as $k=>$v) $out[(string)$k] = (string)$v;
        return $out;
    }

    private function notifyUrl(): string {
        $v = trim((string)($this->config['notify_url'] ?? ''));
        return $v !== '' ? $v : base_url().'/wechat-notify.php';
    }

    private function clientIp(): string {
        foreach (['HTTP_X_FORWARDED_FOR','REMOTE_ADDR','SERVER_ADDR'] as $k) {
            $v = trim(explode(',', (string)($_SERVER[$k] ?? ''))[0]);
            if (filter_var($v, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) return $v;
        }
        return '127.0.0.1';
    }
}
