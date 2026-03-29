<?php

declare(strict_types=1);

namespace App\Controllers;

final class HomeController
{
    public function index(): void
    {
        header('Content-Type: text/html; charset=utf-8');
        echo <<<'HTML'
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>WebConversion Admin</title>
  <style>
    body{margin:0;font-family:Arial,sans-serif;background:#fff;color:#111}
    .container{max-width:980px;margin:0 auto;padding:12px}
    .video{position:sticky;top:0;background:#000;color:#fff;height:220px;display:flex;align-items:center;justify-content:center;border-radius:10px;z-index:2}
    .chat{margin-top:10px;border:1px solid #eee;border-radius:10px;padding:10px;min-height:120px}
    .chat .msg{padding:6px 0;border-bottom:1px solid #f1f1f1}
    .card{border:1px solid #eee;border-radius:10px;padding:12px;margin-top:10px}
    input,textarea,button{width:100%;margin-top:8px;padding:10px;border:1px solid #ddd;border-radius:8px}
    .sticky-offer{position:fixed;left:0;right:0;bottom:0;background:#fff;border-top:1px solid #ddd;padding:10px;display:flex;gap:8px}
    .sticky-offer button{margin:0;background:#e60023;color:#fff;border:none}
    pre{background:#f7f7f7;padding:10px;border-radius:8px;overflow:auto}
    @media(min-width:900px){.video{height:360px}.sticky-offer{max-width:980px;margin:0 auto;left:50%;transform:translateX(-50%);border:1px solid #ddd;border-radius:10px 10px 0 0}}
  </style>
</head>
<body>
<div class="container">
  <h1>WebConversion — mobile-first preview</h1>
  <div class="video">Видео-зона (fixed top на mobile)</div>
  <div class="chat">
    <div class="msg">[Система] Добро пожаловать!</div>
    <div class="msg">[Пользователь] Будет ли запись?</div>
    <div class="msg">[Модератор] Да, запись будет.</div>
    <div class="msg">[Оффер] Спецусловие активно 15 минут.</div>
  </div>

  <div class="card">
    <h3>ACE / Analytics quick test</h3>
    <input id="webinar" placeholder="webinar_id" />
    <textarea id="transcript" rows="4" placeholder="Транскрипт для ACE"></textarea>
    <button onclick="generateAce()">Сгенерировать ACE-контент</button>
    <button onclick="loadHeatmap()">Загрузить retention heatmap</button>
    <pre id="out">{}</pre>
  </div>
</div>

<div class="sticky-offer">
  <button onclick="alert('Переход к оплате')">Купить сейчас</button>
  <button onclick="alert('Открыть оффер')">Показать оффер</button>
</div>

<script>
const out = document.getElementById('out');
async function req(url, method, body){
  const r = await fetch(url,{method,headers:{'Content-Type':'application/json'},body:body?JSON.stringify(body):undefined});
  const j = await r.json(); out.textContent = JSON.stringify(j,null,2); return j;
}
function generateAce(){return req('/api/ace/generate','POST',{webinar_id:document.getElementById('webinar').value,transcript:document.getElementById('transcript').value});}
function loadHeatmap(){const id=document.getElementById('webinar').value; return req('/api/analytics/retention-heatmap?webinar_id='+encodeURIComponent(id),'GET');}
</script>
</body>
</html>
HTML;
    }
}
