<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>商城系统使用手册</title>
    <link rel="stylesheet" href="../admin/css/admin-style.css">
    <style>
        .manual-container {
            display: flex;
            min-height: 100vh;
            padding: 20px;
        }
        .manual-nav {
            width: 250px;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 5px;
            margin-right: 20px;
            position: sticky;
            top: 20px;
            height: fit-content;
        }
        .manual-content {
            flex: 1;
            padding: 20px;
            background: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .manual-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .manual-nav li {
            margin: 5px 0;
        }
        .manual-nav a {
            display: block;
            padding: 8px 10px;
            color: #333;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.2s;
        }
        .manual-nav a:hover {
            background-color: #e9ecef;
        }
        .section {
            margin-bottom: 30px;
        }
        h1, h2, h3, h4 {
            color: #2c3e50;
            margin-top: 20px;
        }
        .qa-item {
            margin: 15px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .qa-item strong {
            color: #2c3e50;
        }
        .contact-info {
            background: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .update-log {
            list-style: none;
            padding: 0;
        }
        .update-log li {
            margin: 10px 0;
            padding-left: 20px;
            position: relative;
        }
        .update-log li:before {
            content: "•";
            position: absolute;
            left: 0;
            color: #007bff;
        }
    </style>
</head>
<body>
    <div class="manual-container">
        <nav class="manual-nav">
            <ul>
                <li><a href="#overview">系统概述</a></li>
                <li><a href="#admin">管理员后台</a></li>
                <li><a href="#user">用户前台</a></li>
                <li><a href="#faq">常见问题</a></li>
                <li><a href="#security">安全提醒</a></li>
                <li><a href="#contact">联系方式</a></li>
                <li><a href="#updates">更新日志</a></li>
                <li><a href="#disclaimer">免责声明</a></li>
            </ul>
        </nav>

        <div class="manual-content">
            <h1>商城系统使用手册</h1>

            <section id="overview" class="section">
                <h2>系统概述</h2>
                <p>本商城系统是一个功能完整的在线销售平台，支持商品管理、订单处理、自动发卡、多种支付方式等功能。系统采用现代化的设计，操作简单直观，适合各类数字商品的在线销售需求。</p>
            </section>

            <section id="admin" class="section">
                <h2>管理员后台</h2>

                <h3>1.1 商品管理 (products.php)</h3>
                <ul>
                    <li>添加/编辑商品：设置商品名称、价格、描述、库存等信息</li>
                    <li>商品上下架：控制商品是否在前台显示</li>
                    <li>商品分类管理：对商品进行分类，方便管理和展示</li>
                </ul>

                <h3>1.2 订单管理 (order_details.php)</h3>
                <ul>
                    <li>查看订单详情：订单状态、支付信息、购买商品等</li>
                    <li>订单处理：手动处理特殊订单情况</li>
                    <li>订单统计：查看销售数据和统计信息</li>
                </ul>

                <h3>1.3 优惠码管理 (coupons.php)</h3>
                <ul>
                    <li>创建优惠码：设置折扣金额、使用期限等</li>
                    <li>管理优惠码：查看使用情况、停用或删除优惠码</li>
                </ul>

                <h3>1.4 发卡任务管理 (manage_card_tasks.php)</h3>
                <ul>
                    <li>创建发卡任务：设置卡密内容、数量等</li>
                    <li>查看任务状态：监控自动发卡执行情况</li>
                    <li>手动处理异常：处理发卡失败的情况</li>
                </ul>

                <h3>1.5 系统设置</h3>
                <h4>支付配置</h4>
                <ul>
                    <li>易支付配置 (epay_config.php)：设置商户ID、密钥等</li>
                    <li>微信支付配置 (wechat_config.php)：配置微信支付参数</li>
                </ul>

                <h4>通知设置</h4>
                <ul>
                    <li>邮件通知 (email_settings.php)：配置SMTP服务器信息</li>
                    <li>Telegram通知 (telegram_config.php)：设置Bot Token和群组ID</li>
                    <li>WxPusher通知 (wxpusher_config.php)：配置应用ID和通知参数</li>
                </ul>

                <h4>IP限制管理 (ip_limits.php)</h4>
                <ul>
                    <li>查看IP访问记录</li>
                    <li>解锁被限制的IP</li>
                    <li>设置IP访问规则</li>
                </ul>
            </section>

            <section id="user" class="section">
                <h2>用户前台</h2>

                <h3>2.1 商品浏览和购买 (index.php)</h3>
                <ul>
                    <li>浏览商品列表</li>
                    <li>查看商品详情</li>
                    <li>选择商品数量</li>
                    <li>使用优惠码</li>
                </ul>

                <h3>2.2 支付流程 (choose_pay.php)</h3>
                <ol>
                    <li>选择支付方式</li>
                    <li>跳转到支付页面</li>
                    <li>完成支付</li>
                    <li>等待系统自动发货</li>
                </ol>

                <h3>2.3 订单查询 (order_query.php)</h3>
                <ul>
                    <li>输入订单号查询订单状态</li>
                    <li>查看订单详细信息</li>
                    <li>获取卡密信息（如果已发货）</li>
                </ul>
            </section>

            <section id="faq" class="section">
                <h2>常见问题解答</h2>

                <div class="qa-item">
                    <h3>支付问题</h3>
                    <p><strong>Q: 支付成功但未收到商品怎么办？</strong><br>
                    A: 请使用订单查询功能查看订单状态，如果显示已支付但未发货，请联系管理员处理。</p>

                    <p><strong>Q: 能否更换支付方式？</strong><br>
                    A: 订单生成后支付方式无法更改，请重新下单选择需要的支付方式。</p>
                </div>

                <div class="qa-item">
                    <h3>商品问题</h3>
                    <p><strong>Q: 购买的卡密可以退换吗？</strong><br>
                    A: 数字商品属于即时消费品，一经发送不支持退换。</p>

                    <p><strong>Q: 如何使用优惠码？</strong><br>
                    A: 在下单页面的优惠码输入框中输入有效的优惠码，系统会自动计算折扣金额。</p>
                </div>

                <div class="qa-item">
                    <h3>系统问题</h3>
                    <p><strong>Q: 被系统限制IP了怎么办？</strong><br>
                    A: 请联系管理员解除IP限制，并说明原因。</p>

                    <p><strong>Q: 订单号丢失怎么办？</strong><br>
                    A: 请查看您的邮箱或其他通知方式，系统会自动发送订单信息。</p>
                </div>
            </section>

            <section id="security" class="section">
                <h2>安全提醒</h2>
                <ul>
                    <li>请妥善保管您的订单号和卡密信息</li>
                    <li>不要在不安全的网络环境下进行支付操作</li>
                    <li>如发现系统异常，请立即联系管理员</li>
                </ul>
            </section>

            <section id="contact" class="section">
                <h2>联系方式</h2>
                <div class="contact-info">
                    <p>电报群：<a href="https://t.me/+yK7diUyqmxI2MjZl" target="_blank">https://t.me/+yK7diUyqmxI2MjZl</a></p>
                    <p>邮箱：<a href="mailto:weijianao@gmail.com">weijianao@gmail.com</a></p>
                    <p>YouTube频道：<a href="https://www.youtube.com/@ajieshuo" target="_blank">https://www.youtube.com/@ajieshuo</a></p>
                </div>
            </section>

            <section id="updates" class="section">
                <h2>更新日志</h2>
                <ul class="update-log">
                    <li>2025年3月31日：系统首版开发完成</li>
                    <li>2025年2月6日：项目开发启动</li>
                </ul>
            </section>

            <section id="disclaimer" class="section">
                <h2>免责声明</h2>
                <p>本系统仅供学习研究使用，未经允许禁止商用。使用本系统进行的任何交易行为，请确保符合当地法律法规。</p>
            </section>
        </div>
    </div>

    <script>
        // 平滑滚动到锚点
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // 高亮当前阅读的章节
        window.addEventListener('scroll', function() {
            const sections = document.querySelectorAll('section');
            const navLinks = document.querySelectorAll('.manual-nav a');

            let currentSection = '';
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                if (window.pageYOffset >= sectionTop - 60) {
                    currentSection = '#' + section.getAttribute('id');
                }
            });

            navLinks.forEach(link => {
                link.style.backgroundColor = link.getAttribute('href') === currentSection ? '#e9ecef' : '';
            });
        });
    </script>
</body>
</html>