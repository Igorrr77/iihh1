<!doctype html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Graph Editor MVP</title>
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<div class="editor-layout">
  <aside class="palette">
    <h3>Blocks</h3>
    <button data-type="start">Start</button>
    <button data-type="send_text">Send Text</button>
    <button data-type="wait_input">Wait Input</button>
    <button data-type="condition">Condition</button>
  </aside>
  <section class="canvas-wrap">
    <div class="toolbar">
      <button id="validateBtn">Validate</button>
      <button id="compileBtn">Compile</button>
      <button id="zoomInBtn">Zoom +</button>
      <button id="zoomOutBtn">Zoom -</button>
      <button id="resetViewBtn">Reset view</button>
      <span id="autosaveState">Autosave: idle</span>
    </div>
    <div id="viewport">
      <svg id="edges"></svg>
      <div id="canvas"></div>
    </div>
  </section>
  <aside class="inspector"><pre id="output">{}</pre></aside>
</div>
<script src="/assets/js/editor.js"></script>
</body>
</html>
