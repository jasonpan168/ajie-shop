'use strict';
const payForm = document.getElementById('payForm');
let submitting = false;
let couponRequest = 0;
function clearCoupon() {
  if (!document.getElementById('coupon_id')) return;
  document.getElementById('coupon_id').value = '';
  document.getElementById('coupon_code_hidden').value = '';
  document.getElementById('coupon_amount').value = '0';
  document.getElementById('total_amount').textContent = Number(payForm.dataset.total).toFixed(2);
}
document.querySelectorAll('[data-payment]').forEach(button => button.addEventListener('click', () => {
  if (submitting || !payForm.reportValidity()) return;
  submitting = true;
  document.querySelectorAll('[data-payment]').forEach(b => b.disabled = true);
  if (button.dataset.payment === 'wechat') {
    payForm.action = 'order.php';
    payForm.querySelectorAll('input[name="type"]').forEach(input => input.disabled = true);
    const type = document.createElement('input'); type.type = 'hidden'; type.name = 'type'; type.value = 'wxpay'; payForm.append(type);
  } else { payForm.action = 'rainbow_pay.php'; }
  payForm.submit();
}));
// 从支付页返回（含浏览器页面缓存）时恢复表单：按钮可点、易支付选项恢复、去掉微信直付追加的 type
window.addEventListener('pageshow', () => {
  submitting = false;
  document.querySelectorAll('[data-payment]').forEach(b => b.disabled = false);
  payForm.querySelectorAll('input[name="type"]').forEach(input => { if (input.type === 'hidden') input.remove(); else input.disabled = false; });
});
document.getElementById('coupon_code')?.addEventListener('input', () => {
  couponRequest++; clearCoupon(); document.getElementById('coupon_message').textContent = '';
});
document.getElementById('check_coupon')?.addEventListener('click', async () => {
  const code = document.getElementById('coupon_code').value.trim();
  const message = document.getElementById('coupon_message');
  const request = ++couponRequest;
  clearCoupon();
  if (!code) { message.textContent = '请输入优惠码'; return; }
  message.textContent = '正在验证…';
  try {
    const response = await fetch('check_coupon.php?code=' + encodeURIComponent(code) + '&amount=' + encodeURIComponent(payForm.dataset.total));
    if (!response.ok) throw new Error();
    const data = await response.json();
    if (request !== couponRequest) return;
    if (!data.success) { message.textContent = data.message || '优惠码不可用'; return; }
    const discount = Number(data.data.discount_amount);
    if (!Number.isFinite(discount) || discount < 0) throw new Error();
    document.getElementById('coupon_id').value = data.data.id;
    document.getElementById('coupon_code_hidden').value = data.data.code;
    document.getElementById('coupon_amount').value = discount;
    document.getElementById('total_amount').textContent = Math.max(0.01,Number(payForm.dataset.total)-discount).toFixed(2); // 与服务端一致：最少实付 0.01
    message.textContent = `优惠码有效，可抵扣 ¥${discount.toFixed(2)}`;
  } catch { if (request === couponRequest) { clearCoupon(); message.textContent = '验证失败，请稍后再试'; } }
});
