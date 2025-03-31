<?php
/**
 * 支付跳转处理系统
 * 
 * 作者：阿杰
 * 电报群：https://t.me/+yK7diUyqmxI2MjZl
 * 作者邮箱：weijianao@gmail.com
 * 作者油管：https://www.youtube.com/@ajieshuo
 * 开发日期：2025年2月6日
 * 首板开发完成日期：2025年3月31日
 * 
 * 该文件主要用途是：
 * 处理支付网关跳转，包括参数组装和签名验证，
 * 确保用户能够安全顺利地跳转到支付页面。
 * 
 * 未经允许禁止商用，仅供学习研究个人使用
 */

require_once("lib/epay.config.php");
require_once("lib/EpayCore.class.php");

/**************************请求参数**************************/
$notify_url = "http://127.0.0.1/SDK/notify_url.php";
//需http://格式的完整路径，不能加?id=123这类自定义参数

//页面跳转同步通知页面路径
$return_url = "http://127.0.0.1/SDK/return_url.php";
//需http://格式的完整路径，不能加?id=123这类自定义参数

//商户订单号
$out_trade_no = $_POST['WIDout_trade_no'];
//商户网站订单系统中唯一订单号，必填

//支付方式（可传入alipay,wxpay,qqpay,bank,jdpay）
$type = $_POST['type'];
//商品名称
$name = $_POST['WIDsubject'];
//付款金额
$money = $_POST['WIDtotal_fee'];

/************************************************************/

//构造要请求的参数数组，无需改动
$parameter = array(
    "pid" => $epay_config['pid'],
    "type" => $type,
    "notify_url" => $notify_url,
    "return_url" => $return_url,
    "out_trade_no" => $out_trade_no,
    "name" => $name,
    "money" => $money,
);

//建立请求
$epay = new EpayCore($epay_config);
$html_text = $epay->pagePay($parameter);
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>正在为您跳转到支付页面，请稍候...</title>
    <style type="text/css">
        body{margin:0;padding:0}
        p{position:absolute;left:50%;top:50%;height:35px;margin:-35px 0 0 -160px;padding:20px;font:bold 16px/30px "宋体",Arial;text-indent:40px;border:1px solid #c5d0dc}
        #waiting{font-family:Arial}
    </style>
</head>
<body>
<?php echo $html_text; ?>
<p>正在为您跳转到支付页面，请稍候...</p>
</body>
</html>