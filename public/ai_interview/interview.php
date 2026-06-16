<?php
session_start(); require_once 'config.php';
if (empty($_SESSION['candidate']) || empty($_SESSION['totalScore']) || $_SESSION['totalScore'] < 35 || empty($_SESSION['coding_score']) || $_SESSION['coding_score'] < 50) { header('Location: index.php'); exit; }
//|| $_SESSION['totalScore'] > 70 || $_SESSION['coding_score'] > 50
$cand = $_SESSION['candidate'];
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>AI Interview - HireMatrix AI</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet"/>

<link rel="stylesheet" href="css/style.css"/>
<style>
*{box-sizing:border-box}
body{overflow:hidden;background:#040810}
/* ── Header ── */
.iv-hdr{position:fixed;top:0;left:0;right:0;height:56px;background:rgba(4,8,16,.9);backdrop-filter:blur(16px);border-bottom:1px solid rgba(255,255,255,.06);display:flex;align-items:center;padding:0 28px;gap:14px;z-index:20}
.iv-hdr .brand{font-weight:800;font-size:1rem;background:linear-gradient(135deg,#818cf8,#c084fc);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.progress-dots{display:flex;gap:6px;align-items:center}
.pdot{width:8px;height:8px;border-radius:50%;background:rgba(255,255,255,.12);transition:all .4s}
.pdot.done{background:#4ade80;box-shadow:0 0 6px #4ade80}
.pdot.active{background:#818cf8;box-shadow:0 0 8px #818cf8;transform:scale(1.3)}
.timer-chip{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:20px;padding:5px 14px;font-size:.82rem;font-weight:700;font-family:'Inter',sans-serif;color:#e2e8f0}
/* ── Main canvas ── */
.iv-main{height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:0;padding:80px 40px 140px;position:relative}
/* ── Avatar with rings ── */
.avatar-wrap{position:relative;width:160px;height:160px;display:flex;align-items:center;justify-content:center;margin-bottom:36px}
.ring{position:absolute;border-radius:50%;border:1.5px solid;opacity:0;transition:all .3s}
.ring1{width:170px;height:170px;border-color:rgba(129,140,248,.5)}
.ring2{width:196px;height:196px;border-color:rgba(129,140,248,.3)}
.ring3{width:224px;height:224px;border-color:rgba(129,140,248,.15)}
.avatar-wrap.speaking .ring1{opacity:1;animation:ringPulse 1.2s infinite}
.avatar-wrap.speaking .ring2{opacity:1;animation:ringPulse 1.2s .3s infinite}
.avatar-wrap.speaking .ring3{opacity:1;animation:ringPulse 1.2s .6s infinite}
.avatar-wrap.listening .ring1{border-color:rgba(74,222,128,.6);opacity:1;animation:ringPulse 0.8s infinite}
.avatar-wrap.listening .ring2{border-color:rgba(74,222,128,.35);opacity:1;animation:ringPulse 0.8s .2s infinite}
@keyframes ringPulse{0%{transform:scale(.95);opacity:.8}50%{transform:scale(1.05);opacity:.3}100%{transform:scale(.95);opacity:.8}}
.avatar-core{width:136px;height:136px;border-radius:50%;background:linear-gradient(135deg,#4f46e5,#7c3aed,#9333ea);display:flex;align-items:center;justify-content:center;font-size:3.2rem;position:relative;z-index:2;box-shadow:0 8px 40px rgba(99,102,241,.4),inset 0 1px 0 rgba(255,255,255,.15)}
/* ── State label ── */
.state-chip{padding:6px 20px;border-radius:20px;font-size:.78rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;transition:all .3s;margin-bottom:20px}
.state-chip.speaking{background:rgba(129,140,248,.15);color:#818cf8;border:1px solid rgba(129,140,248,.3)}
.state-chip.listening{background:rgba(74,222,128,.12);color:#4ade80;border:1px solid rgba(74,222,128,.3);animation:listenPulse 1.5s infinite}
.state-chip.processing{background:rgba(245,158,11,.1);color:#fcd34d;border:1px solid rgba(245,158,11,.25)}
.state-chip.idle{background:rgba(255,255,255,.05);color:#64748b;border:1px solid rgba(255,255,255,.08)}
@keyframes listenPulse{0%,100%{box-shadow:0 0 0 0 rgba(74,222,128,.3)}50%{box-shadow:0 0 0 8px rgba(74,222,128,0)}}
/* ── Question text ── */
.question-card{max-width:640px;width:100%;text-align:center;margin-bottom:24px;min-height:80px;display:flex;align-items:center;justify-content:center}
.question-text{font-size:1.2rem;font-weight:600;color:#f1f5f9;line-height:1.7;animation:fadeSlideIn .5s ease}
@keyframes fadeSlideIn{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
/* ── Live transcript ── */
.transcript-wrap{max-width:580px;width:100%;min-height:60px;text-align:center;padding:14px 20px;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06);border-radius:16px;font-size:.95rem;color:#94a3b8;line-height:1.6;font-style:italic;transition:all .3s;margin-bottom:8px}
.transcript-wrap.active{border-color:rgba(74,222,128,.2);color:#e2e8f0;background:rgba(74,222,128,.04)}
.transcript-wrap .interim{color:#64748b}
/* ── Waveform bars ── */
.waveform{display:flex;align-items:center;justify-content:center;gap:4px;height:40px;margin-bottom:24px}
.wb{width:4px;border-radius:4px;transition:height .1s;background:#818cf8}
.waveform.speaking .wb{animation:wbSpeaking .9s infinite ease-in-out}
.waveform.listening .wb{animation:wbListening .6s infinite ease-in-out;background:#4ade80}
.waveform.idle .wb{height:4px!important}
.wb:nth-child(2){animation-delay:.12s}.wb:nth-child(3){animation-delay:.24s}.wb:nth-child(4){animation-delay:.36s}.wb:nth-child(5){animation-delay:.18s}.wb:nth-child(6){animation-delay:.06s}.wb:nth-child(7){animation-delay:.3s}.wb:nth-child(8){animation-delay:.1s}.wb:nth-child(9){animation-delay:.22s}
@keyframes wbSpeaking{0%,100%{height:5px}50%{height:38px}}
@keyframes wbListening{0%,100%{height:8px}50%{height:34px}}
/* ── Bottom controls ── */
.bottom-bar{position:fixed;bottom:0;left:0;right:0;padding:20px 32px;background:linear-gradient(to top,rgba(4,8,16,1) 60%,transparent);display:flex;align-items:center;justify-content:center;gap:20px;z-index:20}
.mic-ring{position:relative;display:flex;align-items:center;justify-content:center}
.mic-outer{width:76px;height:76px;border-radius:50%;border:2px solid rgba(255,255,255,.1);display:flex;align-items:center;justify-content:center;transition:all .3s}
.mic-outer.listening{border-color:#4ade80;animation:micGlow .8s infinite}
@keyframes micGlow{0%,100%{box-shadow:0 0 0 0 rgba(74,222,128,.5)}50%{box-shadow:0 0 0 12px rgba(74,222,128,0)}}
.mic-btn2{width:58px;height:58px;border-radius:50%;border:none;display:flex;align-items:center;justify-content:center;font-size:1.6rem;cursor:pointer;transition:all .2s;background:linear-gradient(135deg,#6366f1,#8b5cf6)}
.mic-btn2:hover{transform:scale(1.07)}
.mic-btn2.listening{background:linear-gradient(135deg,#16a34a,#4ade80)}
.mic-btn2:disabled{opacity:.35;cursor:not-allowed;transform:none}
.redo-btn{padding:10px 20px;border-radius:20px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);color:#94a3b8;font-size:.82rem;cursor:pointer;transition:all .2s;font-family:'Inter',sans-serif}
.redo-btn:hover{border-color:rgba(255,255,255,.2);color:#e2e8f0}
.end-btn2{padding:10px 20px;border-radius:20px;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);color:#fca5a5;font-size:.82rem;cursor:pointer;transition:all .2s;font-family:'Inter',sans-serif;font-weight:600}
.end-btn2:hover{background:rgba(239,68,68,.2)}
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
<link rel="stylesheet" href="css/style.css?v=candidate-ui"/>
</head><body>
  <?php include 'theme-toggle.php'; ?>
<canvas id="particleCanvas"></canvas>

<!-- Header -->
<div class="iv-hdr">
  <span class="brand">HireMatrix AI</span>
  <span style="color:rgba(255,255,255,.15)">|</span>
  <span style="font-size:.8rem;color:#64748b">AI Interview · <?= htmlspecialchars($cand['name']) ?></span>
  <div style="flex:1"></div>
  <div class="progress-dots" id="progressDots">
    <?php for($i=0;$i<12;$i++): ?><div class="pdot<?= $i===0?' active':'' ?>" id="pdot<?=$i?>"></div><?php endfor; ?>
  </div>
  <div style="color:rgba(255,255,255,.15)">|</div>
  <div class="timer-chip" id="timerChip">20:00</div>
</div>

<!-- Main -->
<div class="iv-main">
  <div class="avatar-wrap" id="avatarWrap">
    <div class="ring ring1"></div><div class="ring ring2"></div><div class="ring ring3"></div>
    <div class="avatar-core">🤖</div>
  </div>

  <div class="state-chip idle" id="stateChip">Connecting…</div>

  <div class="question-card">
    <div class="question-text" id="questionText">Preparing your interview…</div>
  </div>

  <div class="waveform idle" id="waveform">
    <?php for($i=0;$i<9;$i++): ?><div class="wb" style="height:4px"></div><?php endfor; ?>
  </div>

  <div class="transcript-wrap" id="transcriptWrap">Your answer will appear here as you speak…</div>
</div>

<!-- Bottom controls -->
<div class="bottom-bar">
  <button class="redo-btn" id="redoBtn" style="display:none">↺ Re-record</button>
  <div class="mic-ring">
    <div class="mic-outer" id="micOuter">
      <button class="mic-btn2" id="micBtn" disabled>🎙️</button>
    </div>
  </div>
  <button class="end-btn2" id="endBtn">🏁 End Interview</button>
</div>
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
<script src="js/proctoring.js"></script>
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
<script src="js/particles.js"></script>
<script>
const CAND = <?= json_encode(['name'=>$cand['name'],'position'=>$cand['position']]) ?>;
let timerLeft=20*60, timerInt=null, exchanges=0;
let isEnded=false, isSpeaking=false, isListening=false, isProcessing=false;
let finalTranscript='', interimTranscript='';
let noSpeechRetries=0;

// ── Timer ──────────────────────────────────────────────
timerInt=setInterval(()=>{
  timerLeft--;
  const m=String(Math.floor(timerLeft/60)).padStart(2,'0'), s=String(timerLeft%60).padStart(2,'0');
  document.getElementById('timerChip').textContent=`${m}:${s}`;
  document.getElementById('timerChip').style.color=timerLeft>600?'#e2e8f0':timerLeft>300?'#fcd34d':'#fca5a5';
  if(timerLeft<=0) endInterview();
},1000);

// ── Progress dots ──────────────────────────────────────
function updateProgress(n){
  for(let i=0;i<12;i++){
    const el=document.getElementById('pdot'+i);
    if(!el)return;
    el.classList.remove('done','active');
    if(i<n) el.classList.add('done');
    else if(i===n) el.classList.add('active');
  }
}

// ── State UI ───────────────────────────────────────────
function setState(mode, label){
  const chip=document.getElementById('stateChip');
  const av=document.getElementById('avatarWrap');
  const wf=document.getElementById('waveform');
  const mic=document.getElementById('micBtn');
  const micO=document.getElementById('micOuter');
  chip.className='state-chip '+mode; chip.textContent=label;
  av.className='avatar-wrap'+(mode==='speaking'?' speaking':mode==='listening'?' listening':'');
  wf.className='waveform'+(mode==='idle'||mode==='processing'?' idle':' '+mode);
  mic.className='mic-btn2'+(mode==='listening'?' listening':'');
  micO.className='mic-outer'+(mode==='listening'?' listening':'');
  mic.disabled=(mode==='speaking'||mode==='processing'||isEnded);
}

// ── Question display ───────────────────────────────────
function setQuestion(text){
  const el=document.getElementById('questionText');
  el.style.animation='none'; el.offsetHeight; // reflow
  el.textContent=text;
  el.style.animation='fadeSlideIn .5s ease';
}

// ── Transcript ─────────────────────────────────────────
function setTranscript(final, interim){
  const el=document.getElementById('transcriptWrap');
  if(!final && !interim){ el.textContent='Your answer will appear here as you speak…'; el.classList.remove('active'); return; }
  el.classList.add('active');
  el.innerHTML=(final?`<span>${escH(final)}</span>`:'')+
               (interim?`<span class="interim"> ${escH(interim)}…</span>`:'');
}
function escH(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')}

// ── TTS ────────────────────────────────────────────────
function speak(text, onDone){
  isSpeaking=true;
  setState('speaking','🔊 Maya is speaking…');
  setQuestion(text);
  window.speechSynthesis.cancel();
  const utt=new SpeechSynthesisUtterance(text);
  utt.rate=0.92; utt.pitch=1.08; utt.volume=1;
  const voices=window.speechSynthesis.getVoices();
  const v=voices.find(v=>/samantha|karen|moira|google uk english female|zira/i.test(v.name))
         ||voices.find(v=>v.lang.startsWith('en')&&v.name.toLowerCase().includes('female'))
         ||voices.find(v=>v.lang.startsWith('en'));
  if(v) utt.voice=v;
  utt.onend=()=>{isSpeaking=false; if(onDone) onDone();};
  utt.onerror=()=>{isSpeaking=false; if(onDone) onDone();};
  window.speechSynthesis.speak(utt);
  // Chrome TTS bug fix — resume if paused
  const fix=setInterval(()=>{ if(!isSpeaking){clearInterval(fix);return;} if(window.speechSynthesis.paused) window.speechSynthesis.resume(); },2000);
}

// ── STT ────────────────────────────────────────────────
const SpeechRec=window.SpeechRecognition||window.webkitSpeechRecognition;
let rec=null;
function createRec(){
  if(!SpeechRec) return null;
  const r=new SpeechRec();
  r.continuous=false; r.interimResults=true; r.lang='en-US'; r.maxAlternatives=3;
  r.onstart=()=>{ isListening=true; noSpeechRetries=0; setState('listening','🎙️ Listening — speak now'); setTranscript('',''); document.getElementById('redoBtn').style.display='none'; };
  r.onresult=e=>{
    let fin='', intr='';
    for(let res of e.results){ if(res.isFinal) fin+=res[0].transcript; else intr+=res[0].transcript; }
    finalTranscript=fin; interimTranscript=intr;
    setTranscript(fin, intr);
  };
  r.onspeechend=()=>r.stop();
  r.onend=()=>{
    isListening=false;
    if(finalTranscript.trim()&&!isProcessing) submitAnswer(finalTranscript.trim());
    else if(!isProcessing){
      if(noSpeechRetries<2){noSpeechRetries++;setState('listening','🎙️ Still listening…');startListening();}
      else{setState('idle','Click 🎙️ to speak');document.getElementById('micBtn').disabled=false;}
    }
  };
  r.onerror=e=>{
    isListening=false;
    if(e.error==='no-speech'&&noSpeechRetries<2){noSpeechRetries++;startListening();}
    else if(e.error!=='aborted') setState('idle','⚠️ Mic error — click to retry');
  };
  return r;
}

function startListening(){
  if(isListening||isSpeaking||isProcessing||isEnded) return;
  finalTranscript=''; interimTranscript='';
  rec=createRec();
  if(!rec){ setState('idle','⚠️ Browser does not support speech recognition'); return; }
  try{ rec.start(); } catch(e){ console.warn(e); }
}

// ── Submit answer ──────────────────────────────────────
async function submitAnswer(text){
  if(!text||isProcessing||isEnded) return;
  isProcessing=true; rec?.abort(); isListening=false;
  setTranscript(text,'');
  document.getElementById('redoBtn').style.display='block';
  setState('processing','⏳ Maya is thinking…');
  try{
    const res=await fetch('api/chat.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({message:text})});
    const d=await res.json();
    if(d.error) throw new Error(d.error);
    exchanges=d.exchanges; updateProgress(exchanges);
    if(d.shouldEnd){
      setState('idle','Interview complete!');
      speak("That concludes our interview, "+CAND.name.split(' ')[0]+"! You did great. Click End Interview to receive your report.",()=>{isProcessing=false;});
    } else {
      speak(d.reply,()=>{ isProcessing=false; document.getElementById('redoBtn').style.display='none'; startListening(); });
    }
  }catch(e){
    setState('idle','⚠️ Error — click mic to retry');
    setQuestion('Connection error. Please click the mic button to retry.');
    isProcessing=false;
  }
}

// ── Mic button ─────────────────────────────────────────
document.getElementById('micBtn').addEventListener('click',()=>{
  if(isListening){ rec?.stop(); }
  else if(!isSpeaking&&!isProcessing) startListening();
});
document.getElementById('redoBtn').addEventListener('click',()=>{
  document.getElementById('redoBtn').style.display='none';
  finalTranscript=''; setTranscript('','');
  isProcessing=false; startListening();
});

// ── End ────────────────────────────────────────────────
async function endInterview(){
  if(isEnded) return; isEnded=true;
  clearInterval(timerInt); window.speechSynthesis.cancel(); rec?.abort();
  setState('processing','⏳ Generating your report…');
  setQuestion('Analysing your interview performance… please wait.');
  document.getElementById('endBtn').disabled=true;
  try{
    const r=await fetch('api/analyze_interview.php',{method:'POST'});
    const d=await r.json();
    if(d.success) window.location.href='interview_results.php';
    else{ setState('idle','Analysis failed'); isEnded=false; document.getElementById('endBtn').disabled=false; }
  }catch(e){ setState('idle','Error — try again'); isEnded=false; document.getElementById('endBtn').disabled=false; }
}
document.getElementById('endBtn').addEventListener('click',()=>{ if(confirm('End interview and generate your report?')) endInterview(); });

// ── Boot ───────────────────────────────────────────────
async function boot(){
  setState('processing','Connecting to Maya…');
  try{
    const res=await fetch('api/chat.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({message:'START_INTERVIEW'})});
    const d=await res.json();
    if(d.reply) speak(d.reply,()=>{isProcessing=false;startListening();});
  }catch(e){
    const fb=`Hello ${CAND.name}! I'm Maya. Please start with a brief introduction about yourself.`;
    speak(fb,()=>{isProcessing=false;startListening();});
  }
}
// Wait for voices
const tryBoot=()=>{ if(window.speechSynthesis.getVoices().length>0) boot(); else setTimeout(tryBoot,300); };
setTimeout(tryBoot,500);
</script>
<script src="js/prevent-back.js"></script> 
<script src="js/theme.js"></script>
</body></html>
