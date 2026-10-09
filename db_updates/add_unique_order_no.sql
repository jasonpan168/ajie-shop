-- 订单号唯一索引：支付回调按订单号定位订单，必须唯一。
-- 执行前先确认没有重复订单号：SELECT order_no, COUNT(*) c FROM orders GROUP BY order_no HAVING c > 1;
ALTER TABLE orders ADD UNIQUE KEY uk_order_no (order_no);
