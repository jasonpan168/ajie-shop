# Ajie Shop 商城系统

<p align="center">
  <img src="https://img.shields.io/badge/php-%3E%3D8.0-blue.svg">
  <img src="https://img.shields.io/badge/mysql-%3E%3D5.6-brightgreen.svg">
  <img src="https://img.shields.io/badge/license-MIT-green.svg">
</p>

一个基于 PHP 的轻量级商城系统，适合个人或中小企业快速部署，功能简洁一看就懂！可以实现简单的发卡功能！

## 📜 法律声明

本商城程序基于 MIT 协议开源，且完全免费。程序初衷为开发者提供学习、研究与个人或企业自部署使用之便利。

但严禁以下行为：

- ❌ 擅自将本程序用于商业用途（包括但不限于倒卖源码、搭建付费平台、提供 SaaS 服务）
- ❌ 擅自以本程序为基础进行商业销售、换皮转售或增值服务
- ❌ 违反中国大陆及其他国家/地区法律法规的使用行为

✅ 允许范围：

- 个人学习、研究
- 企业或组织内部自建平台、自用部署

使用本程序即表示您已充分理解并同意本法律声明的所有条款。违者将依法追究法律责任。

## ✅ 功能简介
- 商品分类、管理
- 订单创建、状态管理
- 后台配置、支付集成
- 支持微信，TG订单消息推送
- 原生 PHP + Nginx 支持

  <p align="center">
  <img src="https://cdn.laikr.com//shujuku/202503311454729.png" alt="后台截图" style="max-width: 100%; border-radius: 8px;" />
</p>

🚀 快速开始

```
项目 YouTube 视频介绍安装演示：https://www.youtube.com/watch?v=ZfiJaCK7XVo
项目的支付和机器人通知配置介绍：https://www.1993.cm/1633.html

安装步骤1 分钟内完成安装
1：打开宝塔，把整个项目文件导入进去
2：打开安装页面https://你的域名/install
3：输入数据库内容和管理员账号信息
4：登录后台配置支付接口添加商品以及邮件等核心配置
5：可以打开运行项目了https://你的域名
6：待支付订单自动删除设置：宝塔左侧菜单栏点击计划任务-添加任务如图，脚本为：php /www/wwwroot/你的域名/clean_orders.php

```
<!-- ✅ 后台截图补充1 -->
<p align="center">
  <img src="https://cdn.laikr.com//shujuku/202503311638888.png" alt="后台图1" style="max-width: 100%; border-radius: 8px;" />
</p>

<!-- ✅ 后台截图补充2 -->
<p align="center">
  <img src="https://cdn.laikr.com//shujuku/202503311638972.png" alt="后台图2" style="max-width: 100%; border-radius: 8px;" />
</p>

<!-- ✅ 宝塔计划任务截图1 -->
<p align="center">
  <img src="https://github.com/user-attachments/assets/b8932f82-d0ed-4f3c-bb8b-50f3304037e6" alt="计划任务图1" style="max-width: 100%; border-radius: 8px;" />
</p>

<!-- ✅ 宝塔计划任务截图2 -->
<p align="center">
  <img src="https://github.com/user-attachments/assets/047c9f00-3bea-4a8b-8858-d31076fa3e4d" alt="计划任务图2" style="max-width: 100%; border-radius: 8px;" />
</p>

<!-- ✅ 日志输出说明 -->
<p align="center">
  <strong>运行日志显示这个就是正常了：</strong>
</p>

<!-- ✅ 日志输出截图 -->
<p align="center">
  <img src="https://github.com/user-attachments/assets/d5a1211b-1dd3-4a1b-8fcd-de6db8f5c6b4" alt="日志截图" style="max-width: 100%; border-radius: 8px;" />
</p>


## 🔐 授权说明

本程序使用 MIT 开源协议发布，但附加以下限制：

- ✅ 允许：个人 / 企业 自用部署、学习研究
- ❌ 禁止：源码倒卖、SaaS 商业销售

详细说明见 LICENSE 与 法律声明.txt

## 👤 作者信息https://www.1993.cm

<p align="center">
  <strong>开源社区新人，大家多关照！欢迎关注支持 🫡</strong>
</p>

<p align="center">
  <a href="https://github.com/jasonpan168" target="_blank">
    <img src="https://img.shields.io/badge/作者-阿杰-blueviolet?style=for-the-badge&logo=github" alt="作者 阿杰" />
  </a>
  &nbsp;
  <a href="mailto:weijianao@gmail.com">
    <img src="https://img.shields.io/badge/邮箱联系-weijianao@gmail.com-blue?style=for-the-badge&logo=gmail" alt="邮箱联系" />
  </a>
  &nbsp;
  <a href="https://www.youtube.com/@ajieshuo?sub_confirmation=1" target="_blank">
    <img src="https://img.shields.io/badge/订阅油管频道-Ajieshuo-red?style=for-the-badge&logo=youtube" alt="油管" />
  </a>
  &nbsp;
  <a href="https://t.me/+yK7diUyqmxI2MjZl" target="_blank">
    <img src="https://img.shields.io/badge/加入TG交流群-电报-blue?style=for-the-badge&logo=telegram" alt="TG 群" />
  </a>
</p>


### 💰 大佬打赏

TRC20：TCLcZpwsert2kZhoKj7Qwmeh8666666666

<p align="center">
  <img src="https://github.com/user-attachments/assets/f9718026-9afc-4096-a6de-bbbe04b31b1b" alt="微信打赏 - 人民币" width="200"/>
  &nbsp;&nbsp;&nbsp;&nbsp;
  <img src="https://github.com/user-attachments/assets/5a9544f2-f504-443a-9838-86f66e1c71b4" alt="微信打赏 - 港币" width="200"/>
</p>

## 🙋‍♂️ 作者心里话

这个项目是我用了将近 2 个月时间完成的，个人完成，当时遇到了无数个问题，熬了无数个夜，最终完成！希望大家喜欢！

这次比较赶着上线，也牺牲了不少工作时间，当前版本可能还有不完善的地方。希望各位大佬在使用过程中多提建议、有空的话帮忙优化下源码 🫡

未来有机会我会继续更新迭代，也欢迎大家 PR、参与改进！

谢谢大家支持 ❤️
