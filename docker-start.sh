#!/bin/bash

# 简化版Docker启动脚本
set -e

echo "🚀 启动EasyPay异步处理环境..."

# 检查Docker
command -v docker >/dev/null 2>&1 || { echo "❌ 请先安装Docker"; exit 1; }
command -v docker-compose >/dev/null 2>&1 || { echo "❌ 请先安装Docker Compose"; exit 1; }

# 创建必要目录
mkdir -p queue logs/async_queue
chmod -R 755 queue logs

# 启动服务
docker-compose up -d

# 等待服务启动
sleep 5

# 初始化并启动异步处理
docker-compose exec php-fpm sh -c "
  php install_async.php
  nohup php async_processor.php all > /dev/null 2>&1 &
"

echo "✅ 启动完成！"
echo "🌐 Web服务: http://localhost:8000"
echo "📊 管理: ./docker-status.sh"
echo "📋 日志: ./docker-logs.sh"