<?php
require_once 'db.php';
require_once 'lib/SafeOutput.php';

$error_type = isset($_GET['type']) ? $_GET['type'] : 'general';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Setup Required - CloudShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .setup-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            overflow: hidden;
        }

        .setup-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 50px 30px;
            text-align: center;
        }

        .setup-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.9;
        }

        .setup-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .setup-subtitle {
            font-size: 1rem;
            opacity: 0.9;
        }

        .setup-body {
            padding: 40px 30px;
        }

        .setup-message {
            background: #f0f4ff;
            border-left: 4px solid #667eea;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            font-size: 1rem;
            color: #2c3e50;
            line-height: 1.6;
        }

        .setup-steps {
            margin-bottom: 30px;
        }

        .setup-step {
            display: flex;
            margin-bottom: 20px;
        }

        .step-number {
            background: #667eea;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .step-content h4 {
            margin: 0 0 8px 0;
            color: #2c3e50;
            font-weight: 700;
        }

        .step-content p {
            margin: 0;
            color: #7f8c8d;
            font-size: 0.95rem;
        }

        .step-content code {
            background: #f5f7fa;
            padding: 2px 6px;
            border-radius: 4px;
            color: #e74c3c;
            font-weight: 600;
        }

        .setup-divider {
            border-top: 1px solid #ecf0f1;
            margin: 30px 0;
        }

        .setup-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .setup-btn {
            padding: 15px;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
            display: inline-block;
        }

        .setup-btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .setup-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
            color: white;
            text-decoration: none;
        }

        .setup-btn-secondary {
            background: #ecf0f1;
            color: #2c3e50;
        }

        .setup-btn-secondary:hover {
            background: #dfe6e9;
            color: #2c3e50;
            text-decoration: none;
        }

        .feature-list {
            background: #f5f7fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .feature-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .feature-item:last-child {
            margin-bottom: 0;
        }

        .feature-icon {
            color: #667eea;
            margin-right: 12px;
            margin-top: 2px;
            font-weight: 700;
        }

        .feature-text {
            color: #555;
            font-size: 0.95rem;
        }

        @media (max-width: 600px) {
            .setup-header {
                padding: 40px 20px;
            }

            .setup-body {
                padding: 30px 20px;
            }

            .setup-buttons {
                grid-template-columns: 1fr;
            }

            .setup-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <!-- Header -->
        <div class="setup-header">
            <div class="setup-icon">
                <i class="fas fa-cog"></i>
            </div>
            <h1 class="setup-title">Setup Required</h1>
            <p class="setup-subtitle">Payment method needs to be configured</p>
        </div>

        <!-- Body -->
        <div class="setup-body">
            <div class="setup-message">
                <strong><i class="fas fa-info-circle"></i> Payment Not Available</strong><br>
                The store admin hasn't configured payment methods yet. This is a quick 5-minute setup!
            </div>

            <h3 style="color: #2c3e50; margin-bottom: 25px; font-weight: 700;">
                <i class="fas fa-graduation-cap"></i> How to Enable Payments
            </h3>

            <div class="setup-steps">
                <div class="setup-step">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <h4>Open Admin Panel</h4>
                        <p>Visit <code>/admin/</code> and login with admin credentials</p>
                    </div>
                </div>

                <div class="setup-step">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h4>Choose Payment Provider</h4>
                        <p>Select either WeChat Official Payment or E-Pay (supports Alipay, WeChat, USDT)</p>
                    </div>
                </div>

                <div class="setup-step">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <h4>Fill in Credentials</h4>
                        <p>Enter your payment provider credentials: App ID, Merchant ID, API Key</p>
                    </div>
                </div>

                <div class="setup-step">
                    <div class="step-number">4</div>
                    <div class="step-content">
                        <h4>Enable & Save</h4>
                        <p>Check the "Enable" checkbox and save your configuration</p>
                    </div>
                </div>

                <div class="setup-step">
                    <div class="step-number">5</div>
                    <div class="step-content">
                        <h4>Ready to Sell!</h4>
                        <p>Payments are now live and customers can complete purchases</p>
                    </div>
                </div>
            </div>

            <div class="setup-divider"></div>

            <h3 style="color: #2c3e50; margin-bottom: 20px; font-weight: 700;">
                <i class="fas fa-lightning-bolt"></i> Quick Setup Options
            </h3>

            <div class="feature-list">
                <div class="feature-item">
                    <div class="feature-icon">💳</div>
                    <div class="feature-text">
                        <strong>WeChat Official Payment</strong> - Direct integration with WeChat Pay (for China)
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">🌍</div>
                    <div class="feature-text">
                        <strong>Easy Payment (E-Pay)</strong> - Supports Alipay, WeChat, USDT (flexible & global)
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">⚡</div>
                    <div class="feature-text">
                        <strong>Instant Setup</strong> - Configuration takes less than 5 minutes
                    </div>
                </div>
            </div>

            <div class="setup-divider"></div>

            <div class="setup-buttons">
                <a href="/admin/login.php" class="setup-btn setup-btn-primary">
                    <i class="fas fa-cog"></i> Admin Panel
                </a>
                <a href="/" class="setup-btn setup-btn-secondary">
                    <i class="fas fa-home"></i> Home
                </a>
            </div>

            <p style="text-align: center; color: #7f8c8d; margin-top: 20px; font-size: 0.9rem;">
                <i class="fas fa-lock"></i> Payment setup is secure and encrypted
            </p>
        </div>
    </div>
</body>
</html>
