<?php
// 设置响应头
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 处理OPTIONS预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 检查是否为POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '只允许POST请求']);
    http_response_code(405);
    exit;
}

// 获取数据（优先从JSON获取，然后从表单获取作为备用）
$data = [];
$isJson = false;

// 尝试从JSON获取数据
$input = file_get_contents('php://input');
if (!empty($input)) {
    $jsonData = json_decode($input, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
        $data = $jsonData;
        $isJson = true;
    }
}

// 如果JSON数据不完整，尝试从表单获取
if (empty($data) || !isset($data['name']) || !isset($data['message'])) {
    $data = [
        'name' => $_POST['name'] ?? '',
        'email' => $_POST['email'] ?? '',
        'message' => $_POST['message'] ?? '',
        'timestamp' => $_POST['timestamp'] ?? date('Y-m-d H:i:s')
    ];
}

// 验证必填字段
if (empty($data['name']) || empty($data['message'])) {
    echo json_encode(['success' => false, 'message' => '姓名和反馈内容为必填项']);
    http_response_code(400);
    exit;
}

// 清理和验证数据
$name = trim($data['name']);
$email = trim($data['email']);
$message = trim($data['message']);
$timestamp = !empty($data['timestamp']) ? $data['timestamp'] : date('Y-m-d H:i:s');

// 验证邮箱格式（如果提供了邮箱）
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => '邮箱格式不正确']);
    http_response_code(400);
    exit;
}

// 准备反馈数据
$feedbackData = "\n=== 反馈记录 ===\n";
$feedbackData .= "时间: " . htmlspecialchars($timestamp) . "\n";
$feedbackData .= "姓名: " . htmlspecialchars($name) . "\n";
$feedbackData .= "邮箱: " . (empty($email) ? '未提供' : htmlspecialchars($email)) . "\n";
$feedbackData .= "反馈内容:\n" . nl2br(htmlspecialchars($message)) . "\n";

// 文件路径
$filename = 'fankui.txt';

try {
    // 检查并创建文件（如果不存在）
    if (!file_exists($filename)) {
        $file = fopen($filename, 'w');
        if ($file === false) {
            throw new Exception('无法创建反馈文件');
        }
        fclose($file);
    }
    
    // 检查文件是否可写
    if (!is_writable($filename)) {
        throw new Exception('反馈文件不可写，请检查文件权限');
    }
    
    // 追加写入文件
    $result = file_put_contents($filename, $feedbackData, FILE_APPEND | LOCK_EX);
    
    if ($result === false) {
        throw new Exception('写入反馈文件失败');
    }
    
    // 成功响应
    echo json_encode([
        'success' => true, 
        'message' => '反馈已成功保存',
        'data' => [
            'name' => $name,
            'timestamp' => $timestamp
        ]
    ]);
    
} catch (Exception $e) {
    // 错误响应
    error_log('反馈保存错误: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => '保存失败: ' . $e->getMessage()
    ]);
    http_response_code(500);
}
?>