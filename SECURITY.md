# Security Policy

**English below.**

AjieShop 是一套会真实处理**支付回调、订单和买家个人信息**的商城程序。请不要在公开 Issue、讨论区、截图或日志里披露漏洞细节、真实的商户密钥、API Key、数据库口令、买家邮箱或任何可利用的生产环境信息。

## 报告安全问题

**请通过 GitHub 私下报告，不要发邮件。**

👉 **[点此私下提交漏洞报告](https://github.com/jasonpan168/ajie-shop/security/advisories/new)**
（也可从仓库页面进入 **Security → Advisories → Report a vulnerability**）

这个入口是私密的：只有维护者能看到，在修复发布之前不会公开。**这是本项目唯一的漏洞报告渠道**——它可以直接讨论、附代码、留存记录，比邮件更好用，也不会漏掉。

请**不要**公开提 Issue 来报告漏洞，也**不要**发邮件。

报告建议包括：

- 受影响的版本或提交号；
- 受影响的文件、页面或接口（例如 `notify.php`、`admin/login.php`）；
- 可复现的最小步骤；
- 潜在影响和利用前提；
- 已采取的临时缓解措施（如有）。

请使用你自己的测试站点、测试账户和虚构数据复现。不要访问、修改或下载不属于你的数据，不要对任何第三方部署的站点发起测试，也不要进行拒绝服务、真实资金转移或社会工程测试。

## 我们尤其关心的方向

- 支付回调的**签名验证绕过**、**重放**、**金额篡改**（`notify.php`、`notify_url.php`、`rainbow_notify.php`）；
- 订单金额、优惠码折扣可被操纵到 0 或负数；
- 后台鉴权绕过、越权访问 `admin/` 下的任意页面；
- SQL 注入、存储型/反射型 XSS、CSRF；
- 任意文件读写、路径穿越、上传导致的代码执行；
- 安装向导（`install/`）相关的接管路径；
- 日志、备份、`.env`、`config.php` 等敏感文件可被公网下载。

## 响应方式

维护者会尽力确认收到报告、评估影响并协调修复与披露时间。响应时间不作保证。修复公开前，请给维护者合理的处理时间。

本项目是个人开源项目，**没有安全团队，也没有漏洞赏金**。我们能提供的是认真的对待、快速的修复，以及公开致谢。

## 密钥泄露

如果发现商户密钥、API Key 或数据库口令已经进入提交历史，仅从最新版本删除是不够的：应立即在对应服务商处**撤销并轮换**该密钥，再评估是否需要清理 Git 历史。微信支付 API 密钥、易支付商户密钥一旦暴露，攻击者就能伪造「支付成功」回调，必须视为永久失效并立即更换。

## 支持范围

一般优先处理默认分支当前版本中的可复现问题。过期分支、第三方修改版、以及已停止支持的 PHP / MySQL 版本，可能不会获得修复。

部署方的配置问题（例如未配置 HTTPS、把 `logs/` 暴露在公网、使用弱口令、装完不删 `install/` 目录）不属于本项目的漏洞，但如果是**默认配置就不安全**，那就是本项目的问题，欢迎报告。部署加固清单见 [README 的安全性章节](README.md#-安全性)。

---

# Security Policy (English)

AjieShop processes real **payment callbacks, orders and buyer personal
information**. Please do not post vulnerability details, real merchant keys,
API keys, database passwords, buyer email addresses or any exploitable
production information in public issues, discussions, screenshots or logs.

## Reporting a vulnerability

**Please report through GitHub. Do not email us.**

👉 **[Report a vulnerability privately](https://github.com/jasonpan168/ajie-shop/security/advisories/new)**
(or from the repository page: **Security → Advisories → Report a vulnerability**)

That form is private — only the maintainer can see it, and nothing is published
until a fix ships. **It is the only reporting channel for this project.** It keeps
the discussion, the code and the history in one place, which works better than
email and means nothing gets lost in a spam folder.

Please do **not** open a public issue for a vulnerability, and do **not** send email.

A useful report usually includes:

- the affected version or commit hash;
- the affected file, page or endpoint (for example `notify.php`, `admin/login.php`);
- minimal steps to reproduce;
- the potential impact and any preconditions for exploitation;
- any temporary mitigation you have already applied.

Please reproduce on your own test deployment, with test accounts and fictitious
data. Do not access, modify or download data that is not yours, do not test
against anyone else's deployment, and do not attempt denial of service, real
fund transfers or social engineering.

## What we care about most

- Payment callback **signature bypass**, **replay** or **amount tampering**
  (`notify.php`, `notify_url.php`, `rainbow_notify.php`);
- Order totals or coupon discounts that can be driven to zero or negative;
- Admin authentication bypass, or unauthorised access to anything under `admin/`;
- SQL injection, stored or reflected XSS, CSRF;
- Arbitrary file read/write, path traversal, upload-to-RCE;
- Takeover paths through the installer (`install/`);
- Logs, backups, `.env` or `config.php` being downloadable from the internet.

## How we respond

We will do our best to acknowledge the report, assess the impact, and coordinate
the fix and disclosure timing with you. We cannot guarantee a response time.
Please give us a reasonable window before publishing.

This is a personal open-source project. **There is no security team and no bug
bounty.** What we can offer is that we take reports seriously, fix them quickly,
and credit you publicly.

## Leaked credentials

If a merchant key, API key or database password has already reached the commit
history, removing it from the latest version is not enough: revoke and rotate it
at the provider immediately, then decide whether the Git history needs rewriting.
An exposed WeChat Pay API key or e-pay merchant key lets an attacker forge
"payment succeeded" callbacks, so treat it as permanently compromised and replace
it at once.

## Scope

We generally prioritise reproducible issues in the current version of the default
branch. Stale branches, third-party modified builds, and end-of-life PHP or MySQL
versions may not receive fixes.

A deployer's own misconfiguration (no HTTPS, `logs/` exposed to the internet, a
weak password, leaving `install/` in place) is not a vulnerability in this
project — but if the **default** configuration is unsafe, that is our problem and
we want to hear about it. The hardening checklist is in the
[security section of the README](README.md#-安全性).

## 致谢 / Acknowledgements

感谢按本策略私下报告问题、并在修复发布前主动不公开披露的安全研究者。
We thank the security researchers who report privately and withhold public
disclosure until fixes ship.

如果你私下报告了一个我们采纳的问题，欢迎告诉我们希望以何种方式署名（姓名、GitHub 账号、主页链接，或匿名）。默认我们只写你的 GitHub 用户名。
If you privately report an issue we act on, tell us how you would like to be
credited — name, GitHub handle, a link, or anonymously. By default we credit your
GitHub username and nothing else.
