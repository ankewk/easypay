<?php

namespace Meituanpay\Payment;

/**
 * 美团支付服务类
 * 
 * 支持美团支付的各种功能，包括统一下单、订单查询、关闭订单、退款等
 * 
 * @author EasyPay
 * @version 1.0.0
 */
class MeituanPayService
{
    private $config;
    private $merchantId;
    private $appKey;
    private $appSecret;
    private $gatewayUrl;
    private $notifyUrl;
    
    /**
     * 构造函数
     * 
     * @param array $config 美团支付配置
     */
    public function __construct($config = null)
    {
        if ($config === null) {
            $config = require_once __DIR__ . '/../../../config/config_loader.php';
            $config = $config['meituanpay'];
        }
        
        $this->config = $config;
        $this->merchantId = $config['merchant_id'];
        $this->appKey = $config['app_key'];
        $this->appSecret = $config['app_secret'];
        $this->gatewayUrl = $config['gateway_url'];
        $this->notifyUrl = $config['notify_url'];
    }
    
    /**
     * 统一下单
     * 
     * @param array $order 订单信息
     * @return array 支付结果
     */
    public function unifiedOrder($order)
    {
        $params = [
            'app_key' => $this->appKey,
            'merchant_id' => $this->merchantId,
            'timestamp' => time(),
            'out_trade_no' => $order['out_trade_no'],
            'total_amount' => $order['total_amount'],
            'subject' => $order['subject'],
            'body' => $order['body'],
            'notify_url' => $this->notifyUrl,
            'return_url' => $order['return_url'] ?? '',
            'timeout_express' => $order['timeout_express'] ?? '30m',
        ];
        
        $params['sign'] = $this->generateSign($params);
        
        return $this->request('POST', '/api/pay/unifiedorder', $params);
    }
    
    /**
     * 生成支付参数
     * 
     * @param array $order 订单信息
     * @return array 支付参数
     */
    public function generatePayParams($order)
    {
        $result = $this->unifiedOrder($order);
        
        if ($result['code'] === 0) {
            return [
                'code' => 0,
                'message' => 'success',
                'data' => [
                    'pay_url' => $result['data']['pay_url'],
                    'out_trade_no' => $order['out_trade_no'],
                    'total_amount' => $order['total_amount'],
                    'qr_code' => $result['data']['qr_code'] ?? '',
                ]
            ];
        }
        
        return $result;
    }
    
    /**
     * 订单查询
     * 
     * @param string $outTradeNo 商户订单号
     * @return array 订单信息
     */
    public function queryOrder($outTradeNo)
    {
        $params = [
            'app_key' => $this->appKey,
            'merchant_id' => $this->merchantId,
            'timestamp' => time(),
            'out_trade_no' => $outTradeNo,
        ];
        
        $params['sign'] = $this->generateSign($params);
        
        return $this->request('GET', '/api/pay/queryorder', $params);
    }
    
    /**
     * 关闭订单
     * 
     * @param string $outTradeNo 商户订单号
     * @return array 关闭结果
     */
    public function closeOrder($outTradeNo)
    {
        $params = [
            'app_key' => $this->appKey,
            'merchant_id' => $this->merchantId,
            'timestamp' => time(),
            'out_trade_no' => $outTradeNo,
        ];
        
        $params['sign'] = $this->generateSign($params);
        
        return $this->request('POST', '/api/pay/closeorder', $params);
    }
    
    /**
     * 申请退款
     * 
     * @param string $outTradeNo 商户订单号
     * @param float $refundAmount 退款金额
     * @param string $outRefundNo 商户退款单号
     * @param string $reason 退款原因
     * @return array 退款结果
     */
    public function refund($outTradeNo, $refundAmount, $outRefundNo, $reason = '')
    {
        $params = [
            'app_key' => $this->appKey,
            'merchant_id' => $this->merchantId,
            'timestamp' => time(),
            'out_trade_no' => $outTradeNo,
            'out_refund_no' => $outRefundNo,
            'refund_amount' => $refundAmount,
            'reason' => $reason,
        ];
        
        $params['sign'] = $this->generateSign($params);
        
        return $this->request('POST', '/api/pay/refund', $params);
    }
    
    /**
     * 验证回调签名
     * 
     * @param array $data 回调数据
     * @return bool 验证结果
     */
    public function verifySign($data)
    {
        if (!isset($data['sign'])) {
            return false;
        }
        
        $sign = $data['sign'];
        unset($data['sign']);
        
        return $sign === $this->generateSign($data);
    }
    
    /**
     * 生成签名
     * 
     * @param array $params 参数
     * @return string 签名
     */
    private function generateSign($params)
    {
        ksort($params);
        
        $string = '';
        foreach ($params as $key => $value) {
            if ($value !== '' && $key !== 'sign') {
                $string .= $key . '=' . $value . '&';
            }
        }
        
        $string = rtrim($string, '&');
        $string .= $this->appSecret;
        
        return strtoupper(md5($string));
    }
    
    /**
     * 发送HTTP请求
     * 
     * @param string $method 请求方法
     * @param string $endpoint 接口地址
     * @param array $params 请求参数
     * @return array 响应结果
     */
    private function request($method, $endpoint, $params = [])
    {
        $url = $this->gatewayUrl . $endpoint;
        
        $ch = curl_init();
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        } else {
            $url .= '?' . http_build_query($params);
        }
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'code' => -1,
                'message' => '网络请求错误: ' . $error,
                'data' => []
            ];
        }
        
        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'code' => -2,
                'message' => '响应解析错误',
                'data' => ['raw_response' => $response]
            ];
        }
        
        return $result;
    }
}