-- 通用异步任务表结构
-- 适用于所有支付方式

CREATE TABLE IF NOT EXISTS `easypay_async_tasks` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `task_type` varchar(50) NOT NULL COMMENT '任务类型(支付方式_notify)',
    `order_no` varchar(100) NOT NULL COMMENT '订单号',
    `transaction_id` varchar(100) DEFAULT NULL COMMENT '第三方交易号',
    `data` text COMMENT '任务数据(JSON)',
    `status` enum('pending','processing','completed','failed') DEFAULT 'pending' COMMENT '任务状态',
    `retry_count` int(11) DEFAULT '0' COMMENT '重试次数',
    `max_retries` int(11) DEFAULT '3' COMMENT '最大重试次数',
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `processed_at` datetime DEFAULT NULL COMMENT '处理完成时间',
    `error_message` text COMMENT '错误信息',
    PRIMARY KEY (`id`),
    KEY `idx_task_type` (`task_type`),
    KEY `idx_order_no` (`order_no`),
    KEY `idx_status` (`status`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='支付异步任务表';

-- 任务日志表
CREATE TABLE IF NOT EXISTS `easypay_async_logs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `task_id` int(11) NOT NULL COMMENT '关联任务ID',
    `payment_type` varchar(50) NOT NULL COMMENT '支付方式',
    `order_no` varchar(100) NOT NULL COMMENT '订单号',
    `action` varchar(50) NOT NULL COMMENT '操作类型',
    `message` text COMMENT '日志内容',
    `data` text COMMENT '相关数据(JSON)',
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_task_id` (`task_id`),
    KEY `idx_payment_type` (`payment_type`),
    KEY `idx_order_no` (`order_no`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='异步任务日志表';

-- 订单状态跟踪表
CREATE TABLE IF NOT EXISTS `easypay_order_status` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `payment_type` varchar(50) NOT NULL COMMENT '支付方式',
    `order_no` varchar(100) NOT NULL COMMENT '订单号',
    `transaction_id` varchar(100) DEFAULT NULL COMMENT '第三方交易号',
    `status` varchar(50) NOT NULL COMMENT '状态',
    `amount` decimal(10,2) DEFAULT NULL COMMENT '金额',
    `notify_data` text COMMENT '通知数据(JSON)',
    `processed` tinyint(1) DEFAULT '0' COMMENT '是否已处理',
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_payment_order` (`payment_type`,`order_no`),
    KEY `idx_status` (`status`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单状态跟踪表';