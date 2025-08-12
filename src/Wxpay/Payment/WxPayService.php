<?php
namespace Wxpay\Payment;

use EasyWeChat\Factory;

class WxPayService {
    /**
     * 支付实例
     * @var \EasyWeChat\Payment\Payment
     */
    private $payment;

    /**
     * WxPayService constructor.
     */
    public function __construct() {
        // 从配置加载器获取微信支付配置
        $config = [
            'app_id' => config('wxpay.app_id'),
            'mch_id' => config('wxpay.mch_id'),
            'key' => config('wxpay.key'),
            'cert_path' => config('wxpay.cert_path'),
            'key_path' => config('wxpay.key_path'),
            'notify_url' => config('wxpay.notify_url'),
        ];

        $this->payment = Factory::payment($config);
    }

    /**
     * 统一下单
     * @param array $order 订单信息
     * @return array
     */
    public function createOrder(array $order) {
        try {
            $result = $this->payment->order->unify($order);
            return [
                'success' => true,
                'data' => $result
            ];
        } catch (\Exception $e) {
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
            $payParams = $this->payment->jssdk->bridgeConfig($result['data']['prepay_id']);
            return [
                'success' => true,
                'data' => $payParams
            ];
        } catch (\Exception $e) {
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
            $result = $this->payment->order->queryByOutTradeNumber($outTradeNo);
            return [
                'success' => true,
                'data' => $result
            ];
        } catch (\Exception $e) {
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
            $result = $this->payment->order->close($outTradeNo);
            return [
                'success' => true,
                'data' => $result
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 处理支付回调
     * @return array
     */
    public function handleNotify() {
        try {
            $response = $this->payment->handlePaidNotify(function ($message, $fail) {
                // 检查订单是否已经处理
                // 如果订单已处理，则返回成功
                // 否则进行处理
                return true;
            });

            return [
                'success' => true,
                'data' => $response
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}