// 表单防重复提交功能
function preventDoubleSubmit() {
    // 生成随机token
    function generateToken() {
        return Math.random().toString(36).substring(2) + Date.now().toString(36);
    }

    // 为所有表单添加防重复提交功能
    document.addEventListener('DOMContentLoaded', function() {
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            // 添加token隐藏字段
            const tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = 'submit_token';
            tokenInput.value = generateToken();
            form.appendChild(tokenInput);

            // 添加提交状态控制
            form.addEventListener('submit', function(e) {
                if (form.dataset.submitting === 'true') {
                    e.preventDefault();
                    return false;
                }

                form.dataset.submitting = 'true';
                
                // 禁用提交按钮
                const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
                submitButtons.forEach(button => {
                    button.disabled = true;
                    if (button.tagName === 'BUTTON') {
                        button.dataset.originalText = button.innerHTML;
                        button.innerHTML = '提交中...';
                    } else {
                        button.dataset.originalValue = button.value;
                        button.value = '提交中...';
                    }
                });

                // 5秒后重置表单状态（以防提交失败）
                setTimeout(() => {
                    form.dataset.submitting = 'false';
                    submitButtons.forEach(button => {
                        button.disabled = false;
                        if (button.tagName === 'BUTTON') {
                            button.innerHTML = button.dataset.originalText;
                        } else {
                            button.value = button.dataset.originalValue;
                        }
                    });
                }, 5000);
            });
        });
    });
}

// 初始化防重复提交功能
preventDoubleSubmit();