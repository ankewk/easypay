#!/bin/bash

# 异步处理服务启动脚本
# 用于启动和管理所有支付方式的异步处理服务

# 设置基础路径
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PHP_BIN=${PHP_BIN:-$(which php)}
CONFIG_FILE="$SCRIPT_DIR/config/async.php"

# 颜色输出
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 帮助信息
show_help() {
    echo "用法: $0 [命令] [参数]"
    echo ""
    echo "命令:"
    echo "  start [type]     - 启动异步处理服务"
    echo "  stop [type]      - 停止异步处理服务"
    echo "  restart [type]   - 重启异步处理服务"
    echo "  status [type]    - 查看服务状态"
    echo "  monitor          - 显示监控信息"
    echo "  install          - 安装异步处理系统"
    echo "  test [type]      - 运行测试"
    echo "  help             - 显示帮助信息"
    echo ""
    echo "参数:"
    echo "  type             - 支付方式类型 (如: alipay, wxpay, all)"
    echo ""
    echo "示例:"
    echo "  $0 start all     - 启动所有支付方式的异步处理"
    echo "  $0 start alipay  - 启动支付宝异步处理"
    echo "  $0 stop all      - 停止所有服务"
    echo "  $0 status        - 查看所有状态"
}

# 检查环境
check_environment() {
    if [[ ! -f "$PHP_BIN" ]]; then
        echo -e "${RED}错误: 找不到PHP执行文件${NC}"
        exit 1
    fi
    
    if [[ ! -f "$CONFIG_FILE" ]]; then
        echo -e "${RED}错误: 配置文件不存在: $CONFIG_FILE${NC}"
        exit 1
    fi
    
    # 检查必要的目录
    mkdir -p "$SCRIPT_DIR/queue"
    mkdir -p "$SCRIPT_DIR/logs/async_queue"
    
    # 检查权限
    if [[ ! -w "$SCRIPT_DIR/queue" ]]; then
        echo -e "${RED}错误: queue目录不可写${NC}"
        exit 1
    fi
    
    if [[ ! -w "$SCRIPT_DIR/logs" ]]; then
        echo -e "${RED}错误: logs目录不可写${NC}"
        exit 1
    fi
}

# 获取PID文件路径
get_pid_file() {
    local type=$1
    echo "$SCRIPT_DIR/logs/async_queue/${type}_async.pid"
}

# 检查服务是否运行
is_running() {
    local type=$1
    local pid_file=$(get_pid_file $type)
    
    if [[ -f "$pid_file" ]]; then
        local pid=$(cat "$pid_file")
        if kill -0 "$pid" 2>/dev/null; then
            return 0
        else
            rm -f "$pid_file"
            return 1
        fi
    fi
    return 1
}

# 启动服务
start_service() {
    local type=$1
    local pid_file=$(get_pid_file $type)
    
    if is_running "$type"; then
        echo -e "${YELLOW}$type 服务已在运行${NC}"
        return 0
    fi
    
    echo -e "${GREEN}启动 $type 异步处理服务...${NC}"
    
    # 构建命令
    local cmd="$PHP_BIN $SCRIPT_DIR/async_processor.php $type"
    
    # 后台运行
    nohup $cmd > "$SCRIPT_DIR/logs/async_queue/${type}_output.log" 2>&1 &
    local pid=$!
    
    # 保存PID
    echo $pid > "$pid_file"
    
    # 等待确认
    sleep 2
    
    if is_running "$type"; then
        echo -e "${GREEN}$type 服务启动成功 (PID: $pid)${NC}"
    else
        echo -e "${RED}$type 服务启动失败${NC}"
        rm -f "$pid_file"
        return 1
    fi
}

# 停止服务
stop_service() {
    local type=$1
    local pid_file=$(get_pid_file $type)
    
    if ! is_running "$type"; then
        echo -e "${YELLOW}$type 服务未运行${NC}"
        return 0
    fi
    
    local pid=$(cat "$pid_file")
    echo -e "${GREEN}停止 $type 服务 (PID: $pid)...${NC}"
    
    kill "$pid"
    
    # 等待进程结束
    local count=0
    while is_running "$type" && [[ $count -lt 10 ]]; do
        sleep 1
        ((count++))
    done
    
    if is_running "$type"; then
        echo -e "${RED}强制停止 $type 服务...${NC}"
        kill -9 "$pid" 2>/dev/null || true
    fi
    
    rm -f "$pid_file"
    echo -e "${GREEN}$type 服务已停止${NC}"
}

# 显示状态
show_status() {
    local type=$1
    
    if [[ "$type" == "all" || -z "$type" ]]; then
        # 显示所有支付方式状态
        local types=(alipay wxpay huaweipay applepay douyinpay kuaishoupay jdpay pinduoduopay meituanpay cmbpay)
        
        echo "异步处理服务状态:"
        echo "=================="
        
        for t in "${types[@]}"; do
            if is_running "$t"; then
                local pid=$(cat $(get_pid_file $t))
                echo -e "${GREEN}✓ $t: 运行中 (PID: $pid)${NC}"
            else
                echo -e "${RED}✗ $t: 已停止${NC}"
            fi
        done
    else
        # 显示单个支付方式状态
        if is_running "$type"; then
            local pid=$(cat $(get_pid_file $type))
            echo -e "${GREEN}$type: 运行中 (PID: $pid)${NC}"
        else
            echo -e "${RED}$type: 已停止${NC}"
        fi
    fi
}

# 安装异步处理系统
install_system() {
    echo -e "${GREEN}安装异步处理系统...${NC}"
    
    # 运行安装脚本
    if [[ -f "$SCRIPT_DIR/install_async.php" ]]; then
        $PHP_BIN "$SCRIPT_DIR/install_async.php"
    else
        echo -e "${RED}安装脚本不存在${NC}"
        exit 1
    fi
    
    echo -e "${GREEN}安装完成${NC}"
}

# 运行测试
run_test() {
    local type=$1
    
    echo -e "${GREEN}运行测试...${NC}"
    
    if [[ -f "$SCRIPT_DIR/tests/test_async_processor.php" ]]; then
        if [[ "$type" == "all" || -z "$type" ]]; then
            $PHP_BIN "$SCRIPT_DIR/tests/test_async_processor.php"
        else
            $PHP_BIN "$SCRIPT_DIR/tests/test_async_processor.php" "$type"
        fi
    else
        echo -e "${RED}测试脚本不存在${NC}"
        exit 1
    fi
}

# 显示监控信息
show_monitor() {
    if [[ -f "$SCRIPT_DIR/monitor_async.php" ]]; then
        $PHP_BIN "$SCRIPT_DIR/monitor_async.php"
    else
        echo -e "${RED}监控脚本不存在${NC}"
        exit 1
    fi
}

# 主程序
main() {
    check_environment
    
    local command=$1
    local param=$2
    
    case $command in
        start)
            if [[ "$param" == "all" || -z "$param" ]]; then
                local types=(alipay wxpay huaweipay applepay douyinpay kuaishoupay jdpay pinduoduopay meituanpay cmbpay)
                for type in "${types[@]}"; do
                    start_service "$type"
                done
            else
                start_service "$param"
            fi
            ;;
            
        stop)
            if [[ "$param" == "all" || -z "$param" ]]; then
                local types=(alipay wxpay huaweipay applepay douyinpay kuaishoupay jdpay pinduoduopay meituanpay cmbpay)
                for type in "${types[@]}"; do
                    stop_service "$type"
                done
            else
                stop_service "$param"
            fi
            ;;
            
        restart)
            if [[ "$param" == "all" || -z "$param" ]]; then
                local types=(alipay wxpay huaweipay applepay douyinpay kuaishoupay jdpay pinduoduopay meituanpay cmbpay)
                for type in "${types[@]}"; do
                    stop_service "$type"
                    start_service "$type"
                done
            else
                stop_service "$param"
                start_service "$param"
            fi
            ;;
            
        status)
            show_status "$param"
            ;;
            
        monitor)
            show_monitor
            ;;
            
        install)
            install_system
            ;;
            
        test)
            run_test "$param"
            ;;
            
        help|--help|-h)
            show_help
            ;;
            
        *)
            show_help
            ;;
    esac
}

# 运行主程序
main "$@"