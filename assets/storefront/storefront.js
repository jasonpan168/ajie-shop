'use strict';
const grid = document.getElementById('product-grid');
if (grid) {
  const cards = [...grid.querySelectorAll('.product-card')];
  const search = document.getElementById('product-search');
  const sort = document.getElementById('product-sort');
  const stock = document.getElementById('stock-filter');
  let category = 'all';
  function update() {
    const query = search.value.trim().toLocaleLowerCase();
    const visible = cards.filter(card => (category === 'all' || card.dataset.category === category) && card.dataset.search.includes(query) && (!stock.checked || Number(card.dataset.stock) > 0));
    const ordered = [...visible];
    const moreTitle=grid.querySelector('.more-products-title'); if(moreTitle)moreTitle.hidden=visible.length<=3;
    if (sort.value === 'price-up') ordered.sort((a,b) => Number(a.dataset.price) - Number(b.dataset.price));
    if (sort.value === 'price-down') ordered.sort((a,b) => Number(b.dataset.price) - Number(a.dataset.price));
    if (sort.value === 'name') ordered.sort((a,b) => a.dataset.search.localeCompare(b.dataset.search, 'zh-CN'));
    // Reflow the featured positions after filtering; hidden nodes must not occupy nth-child slots.
    ordered.forEach(card => { card.hidden = false; grid.append(card); });
    cards.filter(card => !visible.includes(card)).forEach(card => { card.hidden = true; grid.append(card); });
    document.getElementById('empty-state').hidden = visible.length > 0;
    document.getElementById('results-count').textContent = `找到 ${visible.length} 个商品`;
  }
  document.querySelectorAll('[data-filter]').forEach(button => button.addEventListener('click', () => {
    category = button.dataset.filter;
    document.querySelectorAll('[data-filter]').forEach(b => b.setAttribute('aria-pressed', String(b === button)));
    update();
  }));
  search.addEventListener('input', update); sort.addEventListener('change', update); stock.addEventListener('change', update);
  document.getElementById('reset-filters')?.addEventListener('click', () => {
    search.value = ''; stock.checked = false; sort.value = 'default'; document.querySelector('[data-filter="all"]').click(); search.focus();
  });
  update();
}
const quantity = document.getElementById('quantity');
if (quantity) quantity.addEventListener('input', () => {
  const price = Number(quantity.dataset.price);
  const amount = document.getElementById('product-total');
  amount.textContent = (price * Math.max(1, Number(quantity.value) || 1)).toFixed(2);
});
document.querySelectorAll('.product-emblem img').forEach(img => {
  const fallback = () => { const parent = img.parentElement; const card = img.closest('.product-card, .product-showcase'); const title = card?.querySelector('h1,h2')?.textContent || 'V'; const span = document.createElement('span'); span.textContent = title.slice(0,1); parent.replaceChildren(span); };
  img.addEventListener('error', fallback, {once:true});
  if (img.complete && !img.naturalWidth) fallback();
});
const queryForm = document.getElementById('order-query');
if (queryForm) queryForm.addEventListener('submit', async event => {
  event.preventDefault();
  const result = document.getElementById('query-result');
  const button = queryForm.querySelector('button');
  const label = button.querySelector('span');
  const orderNo = queryForm.elements.order_no.value.trim();
  if (!orderNo) { queryForm.elements.order_no.focus(); return; }
  const element = (tag, className, text) => {
    const node = document.createElement(tag); node.className = className;
    if (text !== undefined) node.textContent = text;
    return node;
  };
  const showMessage = (title, detail) => {
    const box = element('div', 'order-placeholder');
    box.append(element('h2', '', title), element('p', '', detail)); result.replaceChildren(box);
  };
  button.disabled = true; if (label) label.textContent = '正在查询…';
  result.setAttribute('aria-busy', 'true');
  showMessage('正在查找你的订单', '请稍候，正在获取订单信息。');
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), 15000);
  try {
    const response = await fetch('order_query.php?order_no=' + encodeURIComponent(orderNo), {signal: controller.signal});
    if (!response.ok) throw new Error();
    const order = await response.json();
    if (order.error) { showMessage(order.error, '请检查订单号是否完整，或查看购买帮助。'); return; }
    const ticket = element('div', 'order-ticket');
    const heading = element('div', 'order-ticket-heading');
    const title = element('div', ''); title.append(element('span', 'order-ticket-label', '会员商品'), element('h2', '', order.product_title || '会员订单'));
    const status = element('span', 'order-status', order.status || '状态未知');
    status.dataset.state = ({'已支付':'paid','待支付':'pending','已取消':'cancelled'})[order.status] || 'unknown';
    heading.append(title, status); ticket.append(heading);
    for (const [key, name] of Object.entries({amount:'订单金额',order_no:'订单号',quantity:'购买数量',email:'接收邮箱',created_at:'创建时间'})) {
      const row = element('div', 'summary-line');
      const value = key === 'amount' && Number.isFinite(Number(order[key])) ? '¥ ' + Number(order[key]).toFixed(2) : (order[key] ?? '—');
      row.append(element('span', '', name), element('span', key === 'amount' ? 'order-amount' : '', value)); ticket.append(row);
    }
    ticket.append(element('p', 'order-result-note', order.status === '待支付' ? '订单尚未确认支付。如你刚刚完成付款，请稍后重新查询。' : '以上为当前订单记录。如需了解交付说明，请查看购买帮助。'));
    result.replaceChildren(ticket);
  } catch { showMessage('暂时无法查询', '网络连接超时或服务暂不可用，请稍后重试。'); }
  finally { clearTimeout(timeout); button.disabled = false; if (label) label.textContent = '查询订单'; result.setAttribute('aria-busy', 'false'); }
});
