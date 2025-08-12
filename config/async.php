<?php

/**
 * 异步处理配置文件
 * 
 * 配置所有支付方式的异步处理参数
 */

return [
    // 队列存储类型: file, redis, database
    'queue_type' => 'file',
    
    // Redis配置 (当queue_type为redis时)
    'redis' => [
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => '',
        'database' => 0,
        'timeout' => 5,
        'retry_interval' => 100,
    ],
    
    // 数据库配置 (当queue_type为database时)
    'database' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'easypay',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    
    // 文件队列配置 (当queue_type为file时)
    'file_queue' => [
        'directory' => __DIR__ . '/../queue',
        'max_size' => 100 * 1024 * 1024, // 100MB
    ],
    
    // 重试策略
    'retry' => [
        'max_retries' => 3,
        'delay_base' => 1, // 基础延迟秒数
        'delay_multiplier' => 2, // 指数退避乘数
        'max_delay' => 300, // 最大延迟5分钟
    ],
    
    // 批处理配置
    'batch' => [
        'size' => 50, // 每批处理的任务数量
        'timeout' => 30, // 批处理超时时间
    ],
    
    // 日志配置
    'logging' => [
        'enabled' => true,
        'file' => __DIR__ . '/../logs/async_queue/async.log',
        'level' => 'INFO', // DEBUG, INFO, WARNING, ERROR
        'max_size' => 100 * 1024 * 1024, // 100MB
        'backup_count' => 5,
    ],
    
    // 支付服务类映射
    'payment_services' => [
        'alipay' => 'AlipayService',
        'wxpay' => 'WxpayService',
        'huaweipay' => 'HuaweiPayService',
        'applepay' => 'ApplePayService',
        'douyinpay' => 'DouyinPayService',
        'kuaishoupay' => 'KuaishouPayService',
        'jdpay' => 'JdPayService',
        'pinduoduopay' => 'PinduoduoPayService',
        'meituanpay' => 'MeituanPayService',
        'cmbpay' => 'CmbPayService',
    ],
    
    // 监控配置
    'monitoring' => [
        'enabled' => true,
        'alert_threshold' => [
            'failed_tasks' => 100, // 失败任务超过100个时告警
            'queue_size' => 1000, // 队列超过1000个任务时告警
            'processing_time' => 60, // 单个任务处理超过60秒时告警
        ],
        'notification' => [
            'email' => '', // 告警邮箱
            'webhook' => '', // 告警webhook
        ],
    ],
    
    // 性能优化
    'performance' => [
        'memory_limit' => '256M',
        'max_execution_time' => 300,
        'gc_probability' => 0.1, // 垃圾回收概率
    ],
];