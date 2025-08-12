<?php
namespace Jdpay\Payment;

use Yansongda\Pay\Pay;
use Yansongda\Pay\Exception\Exception;

class JdPayService {
    /**
     * 支付实例
     * @var object
     */
    private $payment;

    /**
     * JdPayService constructor.
     */
    public function __construct() {
        // 从配置加载器获取京东支付配置
        $config = [
            'merchant_no' => config('jdpay.merchant_no'),
            'des_key' => config('jdpay.des_key'),
            'private_key' => config('jdpay.private_key'),
            'public_key' => config('jdpay.public_key'),
            'notify_url' => config('jdpay.notify_url'),
            'gateway_url' => config('jdpay.gateway_url'),
        ];

        // 初始化京东支付客户端
        $this->payment = Pay::jd($config);
    }

    /**
     * 统一下单
     * @param array $order 订单信息
     * @return array
     */
    public function createOrder(array $order) {
        try {
            // 实现京东支付下单逻辑
            $result = $this->payment->order->create($order);
            return [
                'success' => true,
                'data' => $result
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 生成支付参数（用于前端调用）
     * @param array $order 订单信息
     * @return array
     */
    public function generatePayParams(array $order) {
        $result = $this->createOrder($order);
        if (!$result['success']) {
            return $result;
        }

        try {
            // 实现生成支付参数逻辑
            $payParams = $this->payment->generatePayParams($result['data']);
            return [
                'success' => true,
                'data' => $payParams
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 订单查询
     * @param string $outTradeNo 商户订单号
     * @return array
     */
    public function queryOrder($outTradeNo) {
        try {
            // 实现订单查询逻辑
            $result = $this->payment->order->query($outTradeNo);
            return [
                'success' => true,
                'data' => $result
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 关闭订单
     * @param string $outTradeNo 商户订单号
     * @return array
     */
    public function closeOrder($outTradeNo) {
        try {
            // 实现关闭订单逻辑
            $result = $this->payment->order->close($outTradeNo);
            return [
                'success' => true,
                'data' => $result
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 处理支付回调
     * @param array $data 回调数据
     * @param string $sign 签名
     * @return bool
     */
    public function verifyNotify($data, $sign) {
        try {
            // 实现回调验证逻辑
            return $this->payment->verifyNotify($data, $sign);
        } catch (Exception $e) {
            return false;
        }
    }
}