<?php

/**
 * 华为支付异步处理配置示例
 * 
 * 展示如何配置华为支付的异步处理功能
 */

// 基础配置
$config = [
    // 华为支付基本配置
    'merchant_id' => 'your_huaweipay_merchant_id',
    'app_id' => 'your_huaweipay_app_id',
    'private_key' => 'your_huaweipay_private_key',
    'public_key' => 'your_huaweipay_public_key',
    'gateway_url' => 'https://pay-api.huawei.com/gateway',
    'notify_url' => 'https://your-domain.com/callbacks/huaweipay/huawei_notify.php',
    
    // 异步处理配置
    'queue_type' => 'file', // 可选：file, redis, database
    
    // Redis配置（当queue_type=redis时）
    'redis_host' => '127.0.0.1',
    'redis_port' => 6379,
    'redis_password' => '',
    'redis_db' => 0,
    
    // 数据库配置（当queue_type=database时）
    'db_host' => 'localhost',
    'db_port' => 3306,
    'db_name' => 'easypay',
    'db_user' => 'root',
    'db_pass' => '',
    
    // 重试配置
    'max_retries' => 3,
    'retry_delay' => 5, // 秒
    
    // 日志配置
    'log_level' => 'INFO', // DEBUG, INFO, WARNING, ERROR
    'log_file' => __DIR__ . '/../../logs/huaweipay/async.log',
];

/**
 * 使用示例
 */

// 1. 创建异步处理器
$processor = new \Huaweipay\Payment\AsyncProcessor($config);

// 2. 处理任务队列（定时任务）
// $results = $processor->processTaskQueue(10);

// 3. 处理单个任务
// $task = [
//     'type' => 'huawei_pay_notify',
//     'order_no' => 'HW202412010001',
//     'transaction_id' => '1234567890',
//     'status' => 'SUCCESS',
//     'amount' => 0.01
// ];
// $result = $processor->executeTask($task);

/**
 * 定时任务配置示例
 * 
 * 添加到crontab（每5分钟执行一次）：
 * */5 * * * * /usr/bin/php /path/to/easypay/callbacks/huaweipay/async_processor.php --action=process --limit=10
 * 
 * 或者使用systemd定时器：
 * 
 * 创建文件 /etc/systemd/system/huawei-pay-async.service：
 * [Unit]
 * Description=华为支付异步任务处理器
 * After=network.target
 * 
 * [Service]
 * Type=oneshot
 * ExecStart=/usr/bin/php /path/to/easypay/callbacks/huaweipay/async_processor.php --action=process --limit=10
 * 
 * 创建文件 /etc/systemd/system/huawei-pay-async.timer：
 * [Unit]
 * Description=每5分钟运行华为支付异步任务
 * Requires=huawei-pay-async.service
 * 
 * [Timer]
 * OnCalendar=*:0/5
 * Persistent=true
 * 
 * [Install]
 * WantedBy=timers.target
 * 
 * 启用定时器：
 * systemctl enable huawei-pay-async.timer
 * systemctl start huawei-pay-async.timer
 */

/**
 * Supervisor配置示例
 * 
 * 创建文件 /etc/supervisor/conf.d/huawei-pay-async.conf：
 * [program:huawei-pay-async]
 * command=/usr/bin/php /path/to/easypay/callbacks/huaweipay/async_processor.php --action=process --limit=50
 * directory=/path/to/easypay
 * autostart=true
 * autorestart=true
 * user=www-data
 * numprocs=1
 * redirect_stderr=true
 * stdout_logfile=/var/log/huawei-pay-async.log
 */

return $config;