// Exam state machine — sidebar, 25min round timer, prev/next, event delegation
(function(){
  function boot(){
  const sc = document.getElementById('screen-container');
  // Use safe fallbacks to avoid ReferenceError when scripts run on other pages
  const _R1 = (typeof R1_QUESTIONS !== 'undefined') ? R1_QUESTIONS : (window.R1_QUESTIONS || []);
  const _R2 = (typeof R2_QUESTIONS !== 'undefined') ? R2_QUESTIONS : (window.R2_QUESTIONS || []);
  const ALL_Q   = [..._R1, ..._R2];
  const R1N     = _R1.length;
  const TOTAL   = ALL_Q.length;
  const ROUND_S = 25 * 60; // 25 minutes

  let state = {
    phase: 'intro1',
    roundIdx: 0,        // 0 = round 1, 1 = round 2
    idx: 0,             // absolute question index
    answers: {},        // { [idx]: { answer, correct, timeSpent } }
    score: 0,
    roundTimer: null,
    roundTimeLeft: ROUND_S,
    qStart: Date.now(),
  };

  const roundStart = [0, R1N];
  const roundEnd   = [R1N-1, TOTAL-1];

  /* ── helpers ─────────────────────────────────────────────────────────── */
  function notify(msg, type='info'){
    const el=document.getElementById('notification');
    el.className=`notification ${type}`; el.textContent=msg;
    clearTimeout(el._t); el._t=setTimeout(()=>el.classList.add('hidden'),3500);
  }
  function fmt(s){ return `${String(Math.floor(s/60)).padStart(2,'0')}:${String(s%60).padStart(2,'0')}`; }

  /* ── ROUND TIMER ─────────────────────────────────────────────────────── */
  function startRoundTimer(){
    state.roundTimeLeft = ROUND_S;
    clearInterval(state.roundTimer);
    state.roundTimer = setInterval(()=>{
      state.roundTimeLeft--;
      const el = document.getElementById('rtimerVal');
      if(el){
        el.textContent = fmt(state.roundTimeLeft);
        if(state.roundTimeLeft>600) el.style.color='#22c55e';
        else if(state.roundTimeLeft>300) el.style.color='#f59e0b';
        else { el.style.color='#ef4444'; el.style.animation='pulse 1s infinite'; }
      }
      if(state.roundTimeLeft<=0){ clearInterval(state.roundTimer); notify("⏰ Time's up! Submitting round…",'error'); endRound(); }
    },1000);
  }

  /* ── INTRO SCREEN ────────────────────────────────────────────────────── */
  function showIntro(round){
    const isR1=round===1;
    sc.innerHTML=`
    <div class="intro-screen animate-in">
      <div class="glass-card intro-card">
        <div class="round-badge"><span class="badge badge-accent">Round ${round} of 2</span></div>
        <span class="intro-icon ${isR1 ? 'intro-icon-aptitude' : 'intro-icon-technical'}" aria-hidden="true"><i class="fas ${isR1 ? 'fa-graduation-cap' : 'fa-laptop'}" aria-hidden="true"></i></span>
        <h2 class="intro-title text-gradient">${isR1?'Aptitude Assessment':'Technical Assessment'}</h2>
        <p class="intro-sub">
          ${isR1
            ? '30 questions covering <strong>Verbal Ability</strong>, <strong>Logical Reasoning</strong> &amp; <strong>Quantitative Aptitude</strong>. Medium to advanced difficulty.'
            : `30 technical questions for <strong>${CANDIDATE.position}</strong>. Includes MCQ, code ordering, fill-in-logic, and debug challenges.`}
        </p>
        <div class="round-rules">
          <div class="rule-item"><i class="far fa-clock" aria-hidden="true"></i><span>25 minutes total</span></div>
          <div class="rule-item"><i class="fas fa-clipboard" aria-hidden="true"></i><span>30 questions</span></div>
          <div class="rule-item"><i class="fas fa-undo-alt" aria-hidden="true"></i><span>Review &amp; change answers</span></div>
          <div class="rule-item"><i class="fas fa-bolt" aria-hidden="true"></i><span>Auto-submit on timeout</span></div>
        </div>
        <button class="btn btn-primary btn-lg" id="startRoundBtn">
          <i class="fas ${isR1 ? 'fa-rocket' : 'fa-bolt'}" aria-hidden="true"></i><span>${isR1 ? 'Start Round 1' : 'Start Round 2'}</span>
        </button>
      </div>
    </div>`;
    const introIcon = sc.querySelector('.intro-icon');
    if (introIcon) {
      introIcon.classList.add(isR1 ? 'intro-icon-aptitude' : 'intro-icon-technical');
      introIcon.innerHTML = `<i class="fas ${isR1 ? 'fa-graduation-cap' : 'fa-laptop'}" aria-hidden="true"></i>`;
    }
    const ruleItems = sc.querySelectorAll('.round-rules .rule-item');
    const ruleIcons = ['far fa-clock', 'fas fa-clipboard', 'fas fa-undo-alt', 'fas fa-bolt'];
    const ruleLabels = ['25 minutes total', '30 questions', 'Review & change answers', 'Auto-submit on timeout'];
    ruleItems.forEach((item, index) => {
      item.innerHTML = `<i class="${ruleIcons[index] || 'fas fa-circle'}" aria-hidden="true"></i><span>${ruleLabels[index] || item.textContent}</span>`;
    });
    const startBtn = document.getElementById('startRoundBtn');
    if (startBtn) {
      startBtn.innerHTML = `<i class="fas ${isR1 ? 'fa-rocket' : 'fa-bolt'}" aria-hidden="true"></i><span>${isR1 ? 'Start Round 1' : 'Start Round 2'}</span>`;
    }
    document.getElementById('startRoundBtn').addEventListener('click',()=>{
      state.idx = roundStart[state.roundIdx];
      state.qStart = Date.now();
      startRoundTimer();
      renderExam();
    });
  }

  /* ── TRANSITION ──────────────────────────────────────────────────────── */
  function showTransition(){
    clearInterval(state.roundTimer);
    const r1a = Object.entries(state.answers).filter(([k])=>+k<R1N);
    const r1s  = r1a.filter(([,v])=>v.correct).length;
    sc.innerHTML=`
    <div class="transition-screen animate-in">
      <div class="glass-card transition-card">
        <span class="badge badge-success" style="margin-bottom:20px">✓ Round 1 Complete</span>
        <h2 class="text-gradient" style="margin-bottom:8px">Aptitude Round Done, ${CANDIDATE.name.split(' ')[0]}!</h2>
        <p class="text-muted" style="margin-bottom:24px">Round 1 results snapshot.</p>
        <div class="score-preview">
          <div class="score-big text-gradient">${r1s}<span style="font-size:1.5rem;color:#94a3b8">/${R1N}</span></div>
          <div class="score-label">Aptitude Score</div>
        </div>
        <button class="btn btn-primary btn-lg btn-full" id="startR2Btn" style="margin-top:24px">⚡ Begin Technical Round</button>
      </div>
    </div>`;
    document.getElementById('startR2Btn').addEventListener('click',()=>{
      state.roundIdx=1;
      state.idx=roundStart[1];
      state.phase='intro2';
      showIntro(2);
    });
  }

  /* ── MAIN EXAM RENDER ────────────────────────────────────────────────── */
  function renderExam(){
    let q     = ALL_Q[state.idx];
    const rStart= roundStart[state.roundIdx];
    const rEnd  = roundEnd[state.roundIdx];
    const rTotal= rEnd - rStart + 1;
    const qInR  = state.idx - rStart;          // 0-based within round
    const saved = state.answers[state.idx] ?? null;

    // If question data is missing/corrupt, keep UI usable and allow navigation.
    if (!q || typeof q !== 'object') {
      console.warn('[Exam] Missing question at idx', state.idx, 'round', state.roundIdx + 1);
      q = { type: 'mcq', category: 'Unknown', question: '[Missing question data]', options: [] };
    }

    const type  = q.type || 'mcq';

    // Question body HTML
    let bodyHTML = '';
    try {
      if      (type==='mcq')        bodyHTML = QRenderer.mcq(q, saved);
      else if (type==='fill_blank') bodyHTML = QRenderer.fill_blank(q, saved);
      else if (type==='debug')      bodyHTML = QRenderer.debug(q, saved);
      else if (type==='drag_drop')  bodyHTML = QRenderer.drag_drop(q, saved);
      else                          bodyHTML = QRenderer.mcq(q, saved);
    } catch (e) {
      console.error('[Exam] Render failed for question', state.idx, q, e);
      bodyHTML = `
        <div class="glass-card" style="padding:16px;border-radius:12px;border:1px solid rgba(239,68,68,.25);background:rgba(239,68,68,.06)">
          <div style="font-weight:800;color:#fca5a5;margin-bottom:6px">Render error</div>
          <div style="color:var(--t2);font-size:.9rem;line-height:1.6">
            This question could not be displayed due to a formatting issue. You can click <strong>Skip</strong> or <strong>Next</strong> to continue.
          </div>
        </div>`;
    }

    // Sidebar buttons
    const navBtns = Array.from({length:rTotal},(_,i)=>{
      const abs = rStart+i;
      let cls = 'q-nav-btn';
      if (abs===state.idx)        cls+=' qnav-current';
      else if(state.answers[abs]) cls+=state.answers[abs].answer!==null?' qnav-answered':' qnav-skipped';
      return `<button class="${cls}" data-navto="${abs}">${i+1}</button>`;
    }).join('');

    const pct = Math.round(((qInR+1)/rTotal)*100);
    const answered = Object.keys(state.answers).filter(k=>+k>=rStart&&+k<=rEnd).length;

    const safeQuestion = (q && q.question) ? q.question : '[Missing question text]';
    sc.innerHTML=`
    <div class="exam-layout" id="examLayout">

      <!-- HEADER -->
      <div class="exam-header">
        <div class="eh-left">
          <span class="badge badge-accent">Round ${state.roundIdx+1}</span>
          <span style="font-size:.78rem;color:var(--t3)">Q${qInR+1}/${rTotal}</span>
          <span style="font-size:.78rem;color:var(--t3)">·</span>
          <span style="font-size:.78rem;color:var(--t2)">Answered: <strong style="color:#4ade80">${answered}</strong></span>
        </div>
        <div class="eh-center">
          <div class="progress-bar-wrap"><div class="progress-bar-bg"><div class="progress-bar-fill" style="width:${pct}%"></div></div></div>
          <div class="progress-text">Score: <strong>${state.score}</strong> · ${answered}/${rTotal} attempted</div>
        </div>
        <div class="eh-right">
          <div class="round-timer">
            <div class="rtimer-val" id="rtimerVal" style="color:#22c55e">${fmt(state.roundTimeLeft)}</div>
            <div class="rtimer-lbl">Round Time</div>
          </div>
        </div>
      </div>

      <!-- BODY -->
      <div class="exam-body">

        <!-- SIDEBAR -->
        <div class="exam-sidebar">
          <div class="sidebar-head"><div class="sidebar-label">Questions</div></div>
          <div class="q-nav-grid">${navBtns}</div>
          <div class="sidebar-legend">
            <div class="legend-row"><div class="legend-dot" style="background:var(--accent)"></div>Current</div>
            <div class="legend-row"><div class="legend-dot" style="background:rgba(34,197,94,.5)"></div>Answered</div>
            <div class="legend-row"><div class="legend-dot" style="background:rgba(245,158,11,.4)"></div>Skipped</div>
            <div class="legend-row"><div class="legend-dot" style="background:rgba(255,255,255,.08)"></div>Not visited</div>
          </div>
        </div>

        <!-- MAIN -->
        <div class="exam-main">
          <div class="glass-card q-card animate-q">
            <div class="q-meta">
              <span class="badge ${state.roundIdx===1?'badge-info':'badge-accent'}">${(q && q.category) ? q.category : (CANDIDATE.position || 'Category')}</span>
              <span class="badge badge-warning">${{mcq:'MCQ',drag_drop:'Code Order',fill_blank:'Fill Blank',debug:'Debug'}[type]||'MCQ'}</span>
              <span class="q-num">Question ${state.idx+1} of ${TOTAL}</span>
            </div>
            <p class="q-question">${safeQuestion}</p>
            <div id="qBody">${bodyHTML}</div>
          </div>
        </div>
      </div>

      <!-- FOOTER -->
      <div class="exam-footer" style="display:flex !important;justify-content:flex-end !important">
  <div style="display:flex;gap:10px;margin-left:auto">
    <button class="btn btn-danger btn-sm" id="skipBtn">Skip →</button>
    <button class="btn btn-primary" id="nextBtn">${state.idx>=rEnd?'🏁 Submit Round':'Next →'}</button>
  </div>
</div>`;

    // Init drag-drop if needed
    if (type==='drag_drop' && !saved) initDragDrop();
    if (window.Prism) Prism.highlightAll();

    // ── DIRECT click listeners (no delegation) ───────────────────────────
    // MCQ
    document.querySelectorAll('.mcq-option').forEach(opt => {
      opt.addEventListener('click', () => {
        if (document.getElementById('mcqOpts')?.dataset.locked) return;
        handleMCQ(opt, q);
      });
    });

    // Fill blank
    document.querySelectorAll('.fill-option').forEach(opt => {
      opt.addEventListener('click', () => {
       // if (document.getElementById('fillOpts')?.dataset.locked) return;
        handleFill(opt, q);
      });
    });

    // Debug lines
    document.querySelectorAll('.debug-line').forEach(line => {
      line.addEventListener('click', () => {
        //if (document.getElementById('debugLines')?.dataset.locked) return;
        handleDebug(line, q);
      });
    });

    // Drag submit
    document.getElementById('submitDragBtn')?.addEventListener('click', () => handleDragSubmit(q));

    // Footer buttons
    document.getElementById('prevBtn')?.addEventListener('click', () => navigate(-1));
    document.getElementById('nextBtn')?.addEventListener('click',  () => navigate(1));
    document.getElementById('skipBtn')?.addEventListener('click',  () => skipQuestion());

    // Sidebar navigation
    document.querySelectorAll('.q-nav-btn[data-navto]').forEach(btn => {
      btn.addEventListener('click', () => { state.idx = +btn.dataset.navto; state.qStart = Date.now(); renderExam(); });
    });
  }

  /* ── ANSWER HANDLERS ─────────────────────────────────────────────────── */
function handleMCQ(el, q){
  const wrap = document.getElementById('mcqOpts');
  if (!wrap) return;
  // wrap.dataset.locked='1';  ← remove
  const chosen = +el.dataset.idx;
  const correct = chosen===q.correct;
  wrap.querySelectorAll('.mcq-option').forEach((o,i)=>{
    o.classList.toggle('selected', i===chosen);
  });
  saveAnswer(chosen, correct, q);
}

 function handleFill(el, q){
  const wrap = document.getElementById('fillOpts');
  if (!wrap) return;                          // ← remove dataset.locked check
  // wrap.dataset.locked='1';                 // ← remove this line too

  const chosen = +el.dataset.idx;
  const correct = chosen === q.correct;
  wrap.querySelectorAll('.fill-option').forEach((o,i)=>{
    o.classList.toggle('selected', i===chosen);
  });
  const blank = document.getElementById('blankDisplay');
  if(blank) blank.textContent = q.options[chosen];
  saveAnswer(chosen, correct, q);
}

function handleDebug(el, q){
  const wrap = document.getElementById('debugLines');
  if (!wrap) return;                          // ← remove dataset.locked check
  // wrap.dataset.locked='1';                 // ← remove this line too

  const chosen = +el.dataset.idx;
  const correct = chosen === q.correct;
  wrap.querySelectorAll('.debug-line').forEach((o,i)=>{
    o.classList.toggle('selected', i===chosen);
  });
  saveAnswer(chosen, correct, q);
}

  function handleDragSubmit(q){
    const area = document.getElementById('dragArea');
    const btn  = document.getElementById('submitDragBtn');
    if (!area || btn?.disabled) return;
    if(btn) btn.disabled=true;
    const currentOrder = [...area.querySelectorAll('.drag-item')].map(el=>+el.dataset.orig);
    const correct = JSON.stringify(currentOrder)===JSON.stringify(q.correct_order);
    area.querySelectorAll('.drag-item').forEach((el,i)=>{
      el.classList.add(currentOrder[i]===q.correct_order[i]?'drag-correct':'drag-wrong');
    });
    document.getElementById('explanation')?.classList.add('show');
    saveAnswer(currentOrder, correct, q);
  }

  function saveAnswer(answer, correct, q){
    const timeSpent = Math.round((Date.now()-state.qStart)/1000);
    if (!state.answers[state.idx]) {
      if(correct) state.score++;
    } else {
      if(state.answers[state.idx].correct && !correct) state.score--;
      else if(!state.answers[state.idx].correct && correct) state.score++;
    }
    state.answers[state.idx] = { answer, correct, timeSpent };
    // Update sidebar dot
    const btn = document.querySelector(`.q-nav-btn[data-navto="${state.idx}"]`);
    if(btn){ btn.classList.remove('qnav-skipped'); btn.classList.add('qnav-answered'); }
  }

  function skipQuestion(){
    if (!state.answers[state.idx]) {
      state.answers[state.idx] = { answer:null, correct:false, timeSpent:Math.round((Date.now()-state.qStart)/1000) };
      const btn=document.querySelector(`.q-nav-btn[data-navto="${state.idx}"]`);
      if(btn){ btn.classList.remove('qnav-answered'); btn.classList.add('qnav-skipped'); }
    }
    navigate(1);
  }

  /* ── NAVIGATION ──────────────────────────────────────────────────────── */
  function navigate(dir){
    const rStart = roundStart[state.roundIdx];
    const rEnd   = roundEnd[state.roundIdx];
    const next   = state.idx + dir;
    if(next<rStart) return;
    if(next>rEnd){ endRound(); return; }
    state.idx=next; state.qStart=Date.now(); renderExam();
  }

  /* ── END ROUND ───────────────────────────────────────────────────────── */
  async function endRound(){
    clearInterval(state.roundTimer);
    const rStart=roundStart[state.roundIdx], rEnd=roundEnd[state.roundIdx];
    const answers=Object.entries(state.answers).filter(([k])=>+k>=rStart&&+k<=rEnd).map(([,v])=>v);
    await fetch('api/save.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({round:state.roundIdx+1,answers})}).catch(()=>{});
    if(state.roundIdx===0) showTransition();
    else window.location.href='results.php';
  }

  // Boot
  showIntro(1);
  }

  // Ensure question arrays are available before booting — some heavy scripts
  // (face-api, tfjs) may change execution timing. If arrays are missing, wait
  // for window load then start the exam.
  if (typeof R1_QUESTIONS === 'undefined' || typeof R2_QUESTIONS === 'undefined') {
    window.addEventListener('load', () => { setTimeout(boot, 50); });
  } else {
    boot();
  }
})();
