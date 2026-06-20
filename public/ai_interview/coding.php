<?php
session_start();
require_once 'config.php';
if (
    empty($_SESSION['candidate']) ||
    empty($_SESSION['coding_problems']) || 
    empty($_SESSION['totalScore']) ||
    $_SESSION['totalScore'] < 35
) {
    header("Location: index.php");
    exit;
}  
$cand    = $_SESSION['candidate'];
$probs   = json_encode($_SESSION['coding_problems']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Coding Round - HireMatrix AI</title>
<link rel="icon" type="image/png" href="../jobboard/images/Serp Hwak Logo.png">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="css/style.css"/>

<style>
body{overflow:hidden}
.split-handle{width:5px;background:var(--border-sub);cursor:col-resize;flex-shrink:0;transition:background .2s}
.split-handle:hover{background:var(--accent)}
#proctoring-container {

    position: fixed;
    bottom: 20px;
    right: 20px;

    width: 250px;
    height: 180px;

    z-index: 9999;

    cursor: move;
}

#video {

    width: 100%;
    height: 100%;

    border-radius: 12px;
    border: 3px solid #6c63ff;

    background: black;
}
</style>
</head>
<body>
  <?php include 'theme-toggle.php'; ?>
<canvas id="particleCanvas"></canvas>
<div id="app" style="position:relative;z-index:1;height:100vh">

<div class="coding-layout">

  <!-- HEADER -->
  <div class="coding-header">
    <span style="font-size:1.1rem;font-weight:800" class="text-gradient">HireMatrix AI</span>
    <span style="color:var(--border-sub)">|</span>
    <span style="font-size:.82rem;color:var(--t2)">Coding Round — <?= htmlspecialchars($cand['name']) ?></span>
    <div style="flex:1"></div>
    <div class="prob-nav" id="probNav"></div>
    <div style="color:var(--border-sub)">|</div>
    <div class="round-timer">
      <div class="rtimer-val" id="rtimerVal" style="color:#22c55e;font-size:1rem">45:00</div>
      <div class="rtimer-lbl">Time Left</div>
    </div>
    <button class="btn btn-primary btn-sm" id="submitAllBtn" style="margin-left:8px">🏁 Submit All</button>
  </div>

  <!-- BODY -->
  <div class="coding-body" id="codingBody">

    <!-- PROBLEM PANEL -->
    <div class="problem-panel" id="problemPanel">
      <div id="problemContent"><!-- rendered by JS --></div>
    </div>

    <div class="split-handle" id="splitHandle"></div>

    <!-- EDITOR PANEL -->
    <div class="editor-panel">
      <div class="editor-toolbar">
        <select class="lang-select" id="langSelect">
          <option value="python">🐍 Python 3</option>
          <option value="javascript">🟨 JavaScript (Node)</option>
          <option value="java">☕ Java</option>
          <option value="cpp">⚙️ C++</option>
          <option value="c">🔵 C</option>
          <option value="go">🐹 Go</option>
          <option value="ruby">💎 Ruby</option>
          <option value="rust">🦀 Rust</option>
          <option value="typescript">🔷 TypeScript</option>
          <option value="kotlin">🎯 Kotlin</option>
          <option value="csharp">🔶 C#</option>
          <option value="php">🐘 PHP</option>
        </select>
        <span style="flex:1"></span>
        <button class="btn btn-secondary btn-sm" id="resetBtn">↺ Reset</button>
      </div>

      <div id="monacoEditor" style="flex:1;min-height:0"></div>

      <div class="test-results-panel" id="testResultsPanel">
        <div class="trp-line info">▶ Run your code to see test results here…</div>
      </div>

      <div class="editor-footer">
        <button class="btn btn-secondary" id="runSampleBtn">▶ Run Samples</button>
        <button class="btn btn-primary" id="runAllBtn">⚡ Run All Tests</button>
        <span style="flex:1"></span>
        <span id="testSummary" style="font-size:.8rem;color:var(--t2)"></span>
      </div>
    </div>
  </div>
</div>
</div>

<div id="notification" class="notification hidden"></div>
<!-- ===================================================== -->
<!-- AI PROCTORING -->
<!-- ===================================================== -->

<div id="proctoring-container">

    <video
        id="video"
        autoplay
        muted
        playsinline
        >
    </video>

    <canvas
        id="canvas"
        style="display:none;">
    </canvas>

</div> 

<!-- FACE API -->
<script src="js/face-api.min.js"></script>
<script>
const candidate_id = <?= $_SESSION['candidateId']; ?>;
const candidate_name = <?= json_encode($_SESSION['candidateName'] ?? ($_SESSION['candidate']['name'] ?? '')) ?>;
const jobrole = <?= json_encode($_SESSION['position'] ?? '') ?>;
</script>
<!-- PROCTORING -->
<script src="js/proctoring.js?v=2"></script>
<script>

const proctoringBox =
    document.getElementById(
        "proctoring-container"
    );

let isDragging = false;

let offsetX = 0;
let offsetY = 0;

/*
|--------------------------------------------------------------------------
| START DRAG
|--------------------------------------------------------------------------
*/

proctoringBox.addEventListener(
    "mousedown",
    e => {

        isDragging = true;

        offsetX =
            e.clientX -
            proctoringBox.offsetLeft;

        offsetY =
            e.clientY -
            proctoringBox.offsetTop;
    }
);

/*
|--------------------------------------------------------------------------
| DRAGGING
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "mousemove",
    e => {

        if (!isDragging) return;

        proctoringBox.style.left =
            (
                e.clientX - offsetX
            ) + "px";

        proctoringBox.style.top =
            (
                e.clientY - offsetY
            ) + "px";

        proctoringBox.style.right =
            "auto";

        proctoringBox.style.bottom =
            "auto";
    }
);

/*
|--------------------------------------------------------------------------
| STOP DRAG
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "mouseup",
    () => {

        isDragging = false;
    }
);

</script>
<!-- Monaco Editor CDN -->
<script src="https://cdn.jsdelivr.net/npm/monaco-editor@0.45.0/min/vs/loader.js"></script>
<script src="js/particles.js"></script>

<script>
const CANDIDATE = <?= json_encode($cand) ?>;
const PROBLEMS  = <?= $probs ?>;
const TIMER_S   = 45 * 60;

/* ── State ───────────────────────────────────────── */
let state = {
  problemIdx: 0,
  codes: {},       // { probId_lang: code }
  lang: 'python',
  testResults: {}, // { probId: [{ pass, input, expected, actual }] }
  timerLeft: TIMER_S,
  timerInterval: null,
  editor: null,
};

/* ── Timer ─────────────────────────────────────── */
function startTimer() {
  state.timerInterval = setInterval(() => {
    state.timerLeft--;
    const el = document.getElementById('rtimerVal');
    if (el) {
      const m = Math.floor(state.timerLeft/60), s = state.timerLeft%60;
      el.textContent = `${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
      el.style.color = state.timerLeft>600?'#22c55e':state.timerLeft>300?'#f59e0b':'#ef4444';
    }
    if (state.timerLeft <= 0) { clearInterval(state.timerInterval); submitAll(); }
  }, 1000);
}

/* ── Monaco ──────────────────────────────────────── */
require.config({ paths: { vs: 'https://cdn.jsdelivr.net/npm/monaco-editor@0.45.0/min/vs' } });
require(['vs/editor/editor.main'], function() {
  monaco.editor.defineTheme('nexus', {
    base: 'vs-dark', inherit: true,
    rules: [
      {token:'comment',foreground:'5c6370',fontStyle:'italic'},
      {token:'keyword',foreground:'c678dd'},
      {token:'string',foreground:'98c379'},
      {token:'number',foreground:'d19a66'},
      {token:'type',foreground:'e5c07b'},
    ],
    colors: {
      'editor.background': '#0d1117',
      'editor.lineHighlightBackground': '#161b22',
      'editorLineNumber.foreground': '#3d4450',
      'editorLineNumber.activeForeground': '#6366f1',
      'editor.selectionBackground': '#264f78',
      'editorCursor.foreground': '#6366f1',
    }
  });

  state.editor = monaco.editor.create(document.getElementById('monacoEditor'), {
    value: getStarterCode(),
    language: 'python',
    theme: 'nexus',
    fontSize: 14,
    fontFamily: "'JetBrains Mono', 'Fira Code', monospace",
    fontLigatures: true,
    minimap: { enabled: false },
    automaticLayout: true,
    tabSize: 4,
    insertSpaces: true,
    scrollBeyondLastLine: false,
    padding: { top: 16, bottom: 16 },
    lineNumbers: 'on',
    renderLineHighlight: 'all',
    bracketPairColorization: { enabled: true },
    smoothScrolling: true,
    cursorBlinking: 'phase',
    wordWrap: 'off',
    scrollbar: { verticalScrollbarSize: 6, horizontalScrollbarSize: 6 },
    // ✅ ADD THIS HERE
    contextmenu: false,
  });

  state.editor.onDidChangeModelContent(() => {
    saveCurrentCode();
  });

  renderProblem();
  renderProbNav();
  startTimer();
});

/* ── Helpers ──────────────────────────────────── */
function prob() { return PROBLEMS[state.problemIdx]; }
function codeKey() { return `${prob().id}_${state.lang}`; }

function getStarterCode() {
  const p = prob();
  return (p.starter_code && p.starter_code[state.lang]) || `# Write your ${state.lang} solution here\n`;
}

function saveCurrentCode() {
  if (state.editor) state.codes[codeKey()] = state.editor.getValue();
}

function loadCode() {
  const saved = state.codes[codeKey()];
  const code  = saved !== undefined ? saved : getStarterCode();
  if (state.editor) {
    const langMap = { cpp:'cpp', c:'c', javascript:'javascript', java:'java', python:'python',
                      go:'go', ruby:'ruby', rust:'rust', typescript:'typescript',
                      kotlin:'kotlin', csharp:'csharp', php:'php' };
    monaco.editor.setModelLanguage(state.editor.getModel(), langMap[state.lang] || state.lang);
    state.editor.setValue(code);
  }
}

/* ── Problem render ─────────────────────────────── */
function renderProblem() {
  const p = prob();
  const diffColor = p.difficulty==='Easy'?'#4ade80':p.difficulty==='Medium'?'#fcd34d':'#fca5a5';
  const diffBg    = p.difficulty==='Easy'?'rgba(34,197,94,.1)':p.difficulty==='Medium'?'rgba(245,158,11,.1)':'rgba(239,68,68,.1)';

  const examples = (p.examples||[]).map((ex,i)=>`
    <div class="example-box">
      <div class="example-label">Example ${i+1}</div>
      <div class="example-io"><span style="color:var(--t3)">Input:</span>\n${esc(ex.input)}\n\n<span style="color:var(--t3)">Output:</span>\n${esc(ex.output)}${ex.explanation?`\n\n<span style="color:var(--t3)">Explanation:</span>\n${esc(ex.explanation)}`:''}
      </div>
    </div>`).join('');

  const constraints = (p.constraints||[]).map(c=>`<li>• ${esc(c)}</li>`).join('');

  const tcHTML = renderTCTabs();

  document.getElementById('problemContent').innerHTML = `
    <div class="problem-header">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
        <span style="font-size:.78rem;font-weight:700;color:var(--t3)">Problem ${state.problemIdx+1}</span>
        <span style="padding:2px 10px;border-radius:var(--r-full);background:${diffBg};color:${diffColor};font-size:.72rem;font-weight:700">${p.difficulty}</span>
        <span class="badge badge-accent" style="font-size:.68rem">${p.topic||''}</span>
      </div>
      <div class="problem-title">${esc(p.title)}</div>
    </div>
    <div class="problem-body">
      <div class="problem-section">
        <div class="problem-desc">${esc(p.description).replace(/\n/g,'<br>')}</div>
      </div>
      <div class="problem-section">
        <div class="problem-section-title">Input Format</div>
        <div class="problem-desc">${esc(p.input_format||'').replace(/\n/g,'<br>')}</div>
      </div>
      <div class="problem-section">
        <div class="problem-section-title">Output Format</div>
        <div class="problem-desc">${esc(p.output_format||'').replace(/\n/g,'<br>')}</div>
      </div>
      <div class="problem-section">
        <div class="problem-section-title">Examples</div>
        ${examples}
      </div>
      <div class="problem-section">
        <div class="problem-section-title">Constraints</div>
        <ul class="constraint-list">${constraints}</ul>
      </div>
      <div class="problem-section">
        <div class="problem-section-title">Test Cases</div>
        ${tcHTML}
      </div>
    </div>`;

  bindTCTabs();
}

function renderTCTabs() {
  const p    = prob();
  const tcs  = p.test_cases || [];
  const res  = state.testResults[p.id] || [];
  const tabs = tcs.map((tc,i)=>{
    const r = res[i];
    let cls = 'tc-tab' + (i===0?' active':'');
    if (r) cls += r.pass?' pass':' fail';
    return `<div class="${cls}" data-tci="${i}">Case ${i+1}${r?(r.pass?' ✓':' ✗'):''}</div>`;
  }).join('');

  const tc0 = tcs[0]||{};
  const r0  = res[0];
  const detail = renderTCDetail(tc0, r0);
  return `<div class="tc-tabs" id="tcTabs">${tabs}</div><div id="tcDetail">${detail}</div>`;
}

function renderTCDetail(tc, result) {
  const statusHTML = result
    ? `<div class="tc-result ${result.pass?'pass':'fail'}">${result.pass?'✅ Passed':'❌ Failed'}</div>`
    : '';
  return `<div class="tc-detail">
    <div class="tc-row"><div class="tc-key">Input</div><div class="tc-val">${esc(tc.input||'')}</div></div>
    <div class="tc-row"><div class="tc-key">Expected</div><div class="tc-val">${esc(tc.output||'')}</div></div>
    ${result&&!result.pass?`<div class="tc-row"><div class="tc-key">Got</div><div class="tc-val" style="color:#fca5a5">${esc(result.actual||'')}</div></div>`:''}
    ${statusHTML}
  </div>`;
}

function bindTCTabs() {
  document.querySelectorAll('.tc-tab[data-tci]').forEach(tab=>{
    tab.addEventListener('click',()=>{
      document.querySelectorAll('.tc-tab').forEach(t=>t.classList.remove('active'));
      tab.classList.add('active');
      const i   = +tab.dataset.tci;
      const tc  = (prob().test_cases||[])[i]||{};
      const res = (state.testResults[prob().id]||[])[i];
      document.getElementById('tcDetail').innerHTML = renderTCDetail(tc, res);
    });
  });
}

/* ── Problem nav ─────────────────────────────────── */
function renderProbNav() {
  document.getElementById('probNav').innerHTML = PROBLEMS.map((p,i)=>{
    const res = state.testResults[p.id];
    const done = res && res.length>0;
    const allPass = done && res.every(r=>r.pass);
    let cls = 'prob-nav-btn' + (i===state.problemIdx?' active':'') + (allPass?' done':'');
    return `<button class="${cls}" data-pi="${i}">Problem ${i+1}${done?(allPass?' ✓':' ·'):''}  </button>`;
  }).join('');
  document.querySelectorAll('.prob-nav-btn[data-pi]').forEach(btn=>{
    btn.addEventListener('click',()=>{
      saveCurrentCode();
      state.problemIdx=+btn.dataset.pi;
      loadCode();
      renderProblem();
      renderProbNav();
      clearResults();
    });
  });
}

function clearResults() {
  document.getElementById('testResultsPanel').innerHTML='<div class="trp-line info">▶ Run your code to see test results here…</div>';
  document.getElementById('testSummary').textContent='';
}

/* ── Run Tests ───────────────────────────────────── */
async function runTests(sampleOnly) {
  saveCurrentCode();
  const code = state.editor?.getValue()||'';
  const p    = prob();
  const tcs  = sampleOnly ? (p.test_cases||[]).slice(0,2) : (p.test_cases||[]);
  const panel= document.getElementById('testResultsPanel');
  panel.innerHTML = `<div class="trp-line info">⏳ Running ${tcs.length} test case${tcs.length>1?'s':''}…</div>`;

  const results = [];
  for (let i=0; i<tcs.length; i++) {
    const tc = tcs[i];
    try {
      const res = await fetch('api/run_code.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ language: state.lang, code, stdin: tc.input })
      });
      const d = await res.json();
      if (!d.success) throw new Error(d.error||'Execution failed');

      const rawActual   = d.stdout || '';
      const rawExpected = tc.output || '';

      // Smart normalize: trim lines, collapse whitespace, normalize separators
      const normalize = s => s
        .replace(/\r\n/g, '\n').replace(/\r/g, '\n')  // CRLF → LF
        .split('\n')
        .map(l => l.trim().replace(/\s+/g, ' '))       // trim each line + collapse spaces
        .filter((l,i,a) => !(l==='' && i===a.length-1)) // remove single trailing blank line
        .join('\n')
        .trim();

      const actual   = normalize(rawActual);
      const expected = normalize(rawExpected);
      const pass     = actual === expected;
      results.push({ pass, input:tc.input, expected, actual, rawActual, stderr: d.stderr });
    } catch(e) {
      results.push({ pass:false, input:tc.input, expected:tc.output||'', actual:'ERROR', rawActual:'', stderr:'', error:e.message });
    }
  }

  // Store results
  if (!state.testResults[p.id]) state.testResults[p.id] = [];
  if (sampleOnly) {
    results.forEach((r,i)=>{ state.testResults[p.id][i]=r; });
  } else {
    state.testResults[p.id] = results;
  }

  // Render results with full detail
  const passed = results.filter(r=>r.pass).length;
  panel.innerHTML = results.map((r,i)=>`
    <div class="trp-line ${r.pass?'pass':'fail'}" style="padding:6px 0;border-bottom:1px solid rgba(255,255,255,.05)">
      <span>${r.pass?'✅':'❌'} <strong>Case ${i+1}:</strong> ${r.pass?'<span style="color:#4ade80">Passed ✓</span>':'<span style="color:#fca5a5">Failed ✗</span>'}</span>
      ${!r.pass ? `
        <div style="margin-top:4px;padding-left:20px;font-size:.78rem">
          <span style="color:var(--t3)">Expected:</span> <code style="color:#fcd34d">${esc(r.expected)||'(empty)'}</code><br>
          <span style="color:var(--t3)">Got&nbsp;&nbsp;&nbsp;&nbsp;:</span> <code style="color:#fca5a5">${esc(r.actual)||'(empty)'}</code>
          ${r.stderr?`<br><span style="color:#f97316">stderr: ${esc(r.stderr.slice(0,200))}</span>`:''}
          ${r.error?`<br><span style="color:#fcd34d">⚠ ${esc(r.error)}</span>`:''}
        </div>` : ''}
    </div>`).join('');

  document.getElementById('testSummary').textContent = `${passed}/${results.length} tests passed`;
  document.getElementById('testSummary').style.color = passed===results.length?'#4ade80':'#fca5a5';

  renderProblem(); // refresh TC tabs
  renderProbNav();
}

/* ── Submit All ──────────────────────────────────── */
async function submitAll() {
  clearInterval(state.timerInterval);
  const btn = document.getElementById('submitAllBtn');
  if(btn){ btn.disabled=true; btn.textContent='Submitting…'; }

  // Collect final codes
  const submissions = PROBLEMS.map(p=>({
    id:      p.id,
    title:   p.title,
    lang:    state.lang,
    code:    state.codes[`${p.id}_${state.lang}`] || '',
    results: state.testResults[p.id] || [],
  }));

  await fetch('api/save.php',{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({ round: 'coding', answers: submissions })
  }).catch(()=>{});

  window.location.href = 'coding_results.php';
}

/* ── Event wiring ────────────────────────────────── */
document.getElementById('langSelect').addEventListener('change', e=>{
  saveCurrentCode();
  state.lang = e.target.value;
  loadCode();
});

document.getElementById('resetBtn').addEventListener('click', ()=>{
  if (confirm('Reset code to starter template?')) {
    delete state.codes[codeKey()];
    loadCode();
  }
});

document.getElementById('runSampleBtn').addEventListener('click', ()=> runTests(true));
document.getElementById('runAllBtn').addEventListener('click',    ()=> runTests(false));
document.getElementById('submitAllBtn').addEventListener('click', ()=> submitAll());

/* ── Drag-resize split ───────────────────────────── */
(function(){
  const handle = document.getElementById('splitHandle');
  const left   = document.getElementById('problemPanel');
  let dragging = false, startX, startW;
  handle.addEventListener('mousedown', e=>{ dragging=true; startX=e.clientX; startW=left.offsetWidth; document.body.style.userSelect='none'; });
  document.addEventListener('mousemove', e=>{ if(!dragging) return; const w=Math.max(280,Math.min(startW+e.clientX-startX,700)); left.style.width=w+'px'; state.editor?.layout(); });
  document.addEventListener('mouseup',  ()=>{ dragging=false; document.body.style.userSelect=''; });
})();

function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
</script>
<script src="js/theme.js"></script>
 <script src="js/prevent-back.js"></script> 
</body>
</html>
