#!/bin/bash
# 查看EasyPay服务状态

echo "📊 EasyPay服务状态"
docker-compose ps
echo ""
echo "📋 队列状态："
docker-compose exec php-fpm php monitor_async.php 2>/dev/null || echo "监控服务未启动"