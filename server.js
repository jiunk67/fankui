const express = require('express');
const fs = require('fs').promises;
const path = require('path');
const cors = require('cors');

const app = express();
const PORT = process.env.PORT || 3000;
const FEEDBACK_FILE = path.join(__dirname, 'fankui.txt');

// 中间件
app.use(cors());
app.use(express.json());
app.use(express.static(__dirname)); // 如果HTML文件也在同一目录

// 确保反馈文件存在
async function ensureFeedbackFile() {
    try {
        await fs.access(FEEDBACK_FILE);
    } catch (error) {
        // 文件不存在，创建空文件
        await fs.writeFile(FEEDBACK_FILE, '');
    }
}

// 提交反馈接口
app.post('/submit-feedback', async (req, res) => {
    try {
        const { userName, userEmail, feedbackContent, timestamp } = req.body;
        
        // 验证必填字段
        if (!userName || !feedbackContent) {
            return res.status(400).json({
                success: false,
                message: '姓名和反馈内容为必填项'
            });
        }
        
        // 格式化反馈数据
        const feedbackData = `
=== 反馈记录 ===
时间：${timestamp}
姓名：${userName}
邮箱：${userEmail || '未提供'}
反馈内容：${feedbackContent}

`;
        
        // 写入文件
        await fs.appendFile(FEEDBACK_FILE, feedbackData);
        
        console.log('收到新反馈:', { userName, timestamp });
        
        res.json({
            success: true,
            message: '反馈提交成功'
        });
        
    } catch (error) {
        console.error('处理反馈时出错:', error);
        res.status(500).json({
            success: false,
            message: '服务器内部错误'
        });
    }
});

// 获取反馈列表接口（可选）
app.get('/get-feedback', async (req, res) => {
    try {
        await ensureFeedbackFile();
        const data = await fs.readFile(FEEDBACK_FILE, 'utf8');
        res.json({
            success: true,
            data: data
        });
    } catch (error) {
        console.error('读取反馈时出错:', error);
        res.status(500).json({
            success: false,
            message: '读取反馈失败'
        });
    }
});

// 启动服务器
async function startServer() {
    await ensureFeedbackFile();
    app.listen(PORT, () => {
        console.log(`🚀 反馈服务器运行在 http://localhost:${PORT}`);
        console.log(`📝 反馈将保存到: ${FEEDBACK_FILE}`);
        console.log(`🌐 访问 http://localhost:${PORT}/feedback.html 查看反馈页面`);
    });
}

startServer();
