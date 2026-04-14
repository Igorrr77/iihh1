const canvas = document.getElementById('canvas');
const edges = document.getElementById('edges');
const output = document.getElementById('output');
const viewport = document.getElementById('viewport');
const autosaveState = document.getElementById('autosaveState');

const saved = localStorage.getItem('botmother_editor_state');
const state = saved ? JSON.parse(saved) : { nodes: [], edges: [], view: { scale: 1, x: 0, y: 0 } };
let autosaveTimer = null;

function addNode(type) {
  const node = {
    uuid: crypto.randomUUID(),
    type,
    title: type,
    position: { x: 80 + state.nodes.length * 24, y: 80 + state.nodes.length * 24 },
    size: { w: 220, h: 80 },
    ports: { out: ['next'] },
    config: type === 'start' ? { trigger_type: 'command', command: '/start' } : {},
    meta: {}
  };

  state.nodes.push(node);
  if (state.nodes.length > 1) {
    const prev = state.nodes[state.nodes.length - 2];
    state.edges.push({
      uuid: crypto.randomUUID(),
      from: { node_uuid: prev.uuid, port: 'next' },
      to: { node_uuid: node.uuid, port: 'in' },
      condition_key: null,
      sort_order: state.edges.length
    });
  }

  render();
}

function render() {
  canvas.innerHTML = '';
  edges.innerHTML = '';

  state.nodes.forEach((n) => {
    const el = document.createElement('div');
    el.className = 'node';
    el.style.left = `${n.position.x}px`;
    el.style.top = `${n.position.y}px`;
    el.style.width = `${n.size.w}px`;
    el.style.height = `${n.size.h}px`;
    el.textContent = `${n.title} (${n.type})`;
    makeDraggable(el, n);
    canvas.appendChild(el);
  });

  drawEdges();
  applyView();
  output.textContent = JSON.stringify(graphPayload(), null, 2);
  scheduleAutosave();
}

function drawEdges() {
  const byId = Object.fromEntries(state.nodes.map(n => [n.uuid, n]));
  state.edges.forEach((e) => {
    const from = byId[e.from.node_uuid];
    const to = byId[e.to.node_uuid];
    if (!from || !to) return;

    const x1 = from.position.x + from.size.w;
    const y1 = from.position.y + from.size.h / 2;
    const x2 = to.position.x;
    const y2 = to.position.y + to.size.h / 2;
    const c1 = x1 + 40;
    const c2 = x2 - 40;

    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    path.setAttribute('d', `M ${x1} ${y1} C ${c1} ${y1}, ${c2} ${y2}, ${x2} ${y2}`);
    path.setAttribute('stroke', '#2563eb');
    path.setAttribute('fill', 'none');
    path.setAttribute('stroke-width', '2');
    edges.appendChild(path);
  });
}

function graphPayload() {
  return { schema_version: '1.0.0', nodes: state.nodes, edges: state.edges, comments: [], groups: [], editor: state.view };
}

function applyView() {
  viewport.style.transform = `translate(${state.view.x}px, ${state.view.y}px) scale(${state.view.scale})`;
}

function makeDraggable(el, node) {
  let sx = 0, sy = 0;
  el.onpointerdown = (e) => {
    sx = e.clientX - node.position.x;
    sy = e.clientY - node.position.y;
    document.onpointermove = (mv) => {
      node.position.x = mv.clientX - sx;
      node.position.y = mv.clientY - sy;
      render();
    };
    document.onpointerup = () => document.onpointermove = null;
  };
}

function scheduleAutosave() {
  autosaveState.textContent = 'Autosave: pending';
  clearTimeout(autosaveTimer);
  autosaveTimer = setTimeout(() => {
    localStorage.setItem('botmother_editor_state', JSON.stringify(state));
    autosaveState.textContent = `Autosave: ${new Date().toLocaleTimeString()}`;
  }, 900);
}

document.querySelectorAll('[data-type]').forEach(btn => btn.onclick = () => addNode(btn.dataset.type));
document.getElementById('zoomInBtn').onclick = () => { state.view.scale = Math.min(2, state.view.scale + 0.1); render(); };
document.getElementById('zoomOutBtn').onclick = () => { state.view.scale = Math.max(0.5, state.view.scale - 0.1); render(); };
document.getElementById('resetViewBtn').onclick = () => { state.view = { scale: 1, x: 0, y: 0 }; render(); };

document.getElementById('validateBtn').onclick = async () => {
  const r = await fetch('/api/process-versions/compile', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({graph_json: graphPayload()})});
  output.textContent = JSON.stringify(await r.json(), null, 2);
};

document.getElementById('compileBtn').onclick = async () => {
  const r = await fetch('/api/process-versions/compile', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({graph_json: graphPayload()})});
  output.textContent = JSON.stringify(await r.json(), null, 2);
};

render();
