// Pure HTML renderers — NO setTimeout, events handled by delegation in exam.js
const QRenderer = {

  mcq(q, saved) {
    const L = ['A','B','C','D'], sel = saved?.answer ?? -1, locked = sel !== -1;
    const raw = Array.isArray(q?.options) ? q.options : [];
    // Filter out null/empty options to avoid rendering "invisible" choices.
    const clean = raw.map(v => (v == null ? '' : String(v))).map(v => v.trim()).filter(v => v !== '');
    if (clean.length < 2) {
      // Keep the exam usable even if a DB row is missing options.
      console.warn('[Exam] MCQ has missing/invalid options:', q);
      return `
        <div class="glass-card" style="padding:16px;border-radius:12px;border:1px solid rgba(239,68,68,.25);background:rgba(239,68,68,.06)">
          <div style="font-weight:800;color:#fca5a5;margin-bottom:6px">Question data issue</div>
          <div style="color:var(--t2);font-size:.9rem;line-height:1.6">
            Options for this question are missing in the database. You can click <strong>Skip</strong> or <strong>Next</strong> to continue.
          </div>
        </div>`;
    }
    const opts = clean.slice(0, 4).map((o,i) => {
      const cls = 'mcq-option' + (i===sel ? ' selected' : '');
      return `<div class="${cls}" data-idx="${i}"><div class="option-letter">${L[i]}</div><div class="option-text">${o}</div></div>`;
    }).join('');
    return `<div class="mcq-options" id="mcqOpts" ${locked?'data-locked="1"':''}>${opts}</div>`;
  },

  fill_blank(q, saved) {
    const sel = saved?.answer ?? -1, locked = sel !== -1;
    const filled = sel>=0 ? escHtml(q.options[sel]) : '___';
    const code = (q.code_template||'').replace(/___/g,
      `<span class="code-blank" id="blankDisplay">${filled}</span>`);
    const opts = q.options.map((o,i) => {
      const cls = 'fill-option' + (i===sel ? ' selected' : '');
      return `<div class="${cls}" data-idx="${i}">${escHtml(o)}</div>`;
    }).join('');
    return `<div class="code-block"><code>${code}</code></div>
      <p class="drag-intro" style="margin-bottom:10px">👆 Click the correct option to fill the blank:</p>
      <div class="fill-options" id="fillOpts" ${locked?'data-locked="1"':''}>${opts}</div>`;
  },

  debug(q, saved) {
    const sel = saved?.answer ?? -1, locked = sel !== -1;
    const lines = (q.code_lines||[]).map((ln,i) => {
      const cls = 'debug-line' + (i===sel ? ' selected' : '');
      return `<div class="${cls}" data-idx="${i}">
        <div class="line-num">${i+1}</div><div class="line-code">${escHtml(ln)}</div></div>`;
    }).join('');
    return `<p class="debug-intro">🐛 Click the line that contains the bug:</p>
      <div class="debug-lines" id="debugLines" ${locked?'data-locked="1"':''}>${lines}</div>`;
  },

  drag_drop(q, saved) {
    const locked = saved !== null && saved !== undefined;
    const order = locked ? q.correct_order : [...q.code_lines.keys()].sort(()=>Math.random()-.5);
    const items = order.map((origIdx, pos) => {
      let cls = 'drag-item';
      if (locked) cls += origIdx===q.correct_order[pos]?' drag-correct':' drag-wrong';
      return `<div class="${cls}" ${locked?'':'draggable="true"'} data-orig="${origIdx}" id="drag_${pos}">
        <span class="drag-handle">${locked?'':'⠿'}</span>
        <span class="drag-code">${escHtml(q.code_lines[origIdx])}</span></div>`;
    }).join('');
    const btn = locked ? '' : `<button class="btn btn-secondary" id="submitDragBtn" data-action="drag-submit" style="margin-top:12px">✅ Submit Order</button>`;
    return `<p class="drag-intro">🖱️ Drag lines into the correct order${locked?'':', then click <strong>Submit Order</strong>'}.</p>
      <div class="drag-area" id="dragArea">${items}</div>${btn}
      <div class="explanation${locked?' show':''}" id="explanation">
        <strong>✅ Explanation:</strong> ${q.explanation||''}</div>`;
  }
};

function initDragDrop() {
  const area = document.getElementById('dragArea');
  if (!area) return;
  let src = null;
  area.querySelectorAll('.drag-item[draggable]').forEach(el => {
    el.addEventListener('dragstart', e => { src=el; el.classList.add('dragging'); e.dataTransfer.effectAllowed='move'; });
    el.addEventListener('dragend',  ()=> el.classList.remove('dragging','drag-over'));
    el.addEventListener('dragover', e => { e.preventDefault(); el.classList.add('drag-over'); });
    el.addEventListener('dragleave',()=> el.classList.remove('drag-over'));
    el.addEventListener('drop', e => {
      e.preventDefault(); el.classList.remove('drag-over');
      if (src && src!==el) {
        const all=[...area.querySelectorAll('.drag-item')];
        if (all.indexOf(src)<all.indexOf(el)) area.insertBefore(src,el.nextSibling);
        else area.insertBefore(src,el);
      }
    });
  });
}

function escHtml(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
