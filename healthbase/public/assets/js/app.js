(function(){
  const basePath = window.APP_BASE_PATH || '';
  const withBase = (path) => `${basePath}${path}`;
  const form = document.getElementById('ai-search-form');
  if (!form) return;
  const out = document.getElementById('ai-search-result');
  form.addEventListener('submit', async function(e){
    e.preventDefault();
    const fd = new FormData(form);
    out.textContent = 'Идёт AI-поиск...';
    const resp = await fetch(withBase('/search/ai'), {method:'POST', body:fd});
    const data = await resp.json();
    out.textContent = JSON.stringify(data, null, 2);
  });
})();
