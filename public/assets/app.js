async function api(path, method = 'GET', body = null) {
  const res = await fetch(path, {
    method,
    headers: { 'Content-Type': 'application/json' },
    body: body ? JSON.stringify(body) : null,
    credentials: 'include',
  });
  if (!res.ok) throw new Error('API error');
  return await res.json();
}

document.getElementById('login-btn').onclick = async () => {
  const email = document.getElementById('email').value;
  const password = document.getElementById('password').value;
  const r = await api('/api/login', 'POST', { email, password });
  alert(r.ok ? 'Вход выполнен' : 'Неверные данные');
};

document.getElementById('logout-btn').onclick = async () => {
  await api('/api/logout', 'POST', {});
  alert('Вы вышли');
};

document.getElementById('save-context-btn').onclick = async () => {
  const product_text = document.getElementById('product-context').value;
  await api('/api/product-context', 'POST', { product_text });
  alert('Контекст сохранен');
};

document.getElementById('save-integrations-btn').onclick = async () => {
  const crm_endpoint = document.getElementById('crm-endpoint').value;
  const webhook_url = document.getElementById('out-webhook').value;
  await api('/api/integrations', 'POST', { crm_endpoint, webhook_url });
  alert('Интеграции сохранены');
};

function renderItem(item) {
  const coaching = JSON.parse(item.coaching_json || '[]');
  const patterns = JSON.parse(item.patterns_json || '[]');
  const painPoints = JSON.parse(item.pain_points_json || '[]');

  return `
    <div class="feed-item">
      <div><b>Клиент:</b> ${item.client_handle ?? 'unknown'}</div>
      <div class="kpi">sentiment: ${item.sentiment}</div>
      <div class="kpi">confidence: ${item.confidence_level}</div>
      <div class="kpi">lead score: ${item.lead_score}</div>
      <div class="kpi">risk: ${item.churn_risk}</div>
      <div><b>Pain points:</b> ${painPoints.join(', ')}</div>
      <div><b>Подсказка:</b> ${coaching[0] ?? ''}</div>
      <div><b>Паттерн ответа:</b> ${patterns[0] ?? ''}</div>
    </div>
  `;
}

async function refreshFeed() {
  try {
    const data = await api('/api/live-feed');
    document.getElementById('feed').innerHTML = (data.items || []).map(renderItem).join('');
  } catch (_) {
    document.getElementById('feed').innerHTML = '<i>Авторизуйтесь для просмотра live-ленты.</i>';
  }
}

setInterval(refreshFeed, 4000);
refreshFeed();
