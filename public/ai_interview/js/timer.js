// Timer ring (SVG-based circular countdown)
class Timer {
  constructor(wrapId, seconds, onTick, onExpire) {
    this.wrap     = document.getElementById(wrapId);
    this.total    = seconds;
    this.remaining= seconds;
    this.onTick   = onTick;
    this.onExpire = onExpire;
    this._interval= null;
    this._render();
  }

  _render() {
    const R = 26, C = 2 * Math.PI * R;
    this.wrap.innerHTML = `
      <svg width="64" height="64" viewBox="0 0 64 64">
        <circle class="timer-track" cx="32" cy="32" r="${R}"/>
        <circle class="timer-fill" id="timerArc" cx="32" cy="32" r="${R}"
          stroke-dasharray="${C}" stroke-dashoffset="0" stroke="#6366f1"/>
      </svg>
      <div class="timer-text" id="timerText">${this.total}</div>`;
    this.arc  = document.getElementById('timerArc');
    this.text = document.getElementById('timerText');
    this._C   = C;
  }

  _update() {
    const pct    = this.remaining / this.total;
    const offset = this._C * (1 - pct);
    this.arc.style.strokeDashoffset = offset;
    this.text.textContent = this.remaining;
    // Color: green → yellow → red
    if (pct > 0.5)      this.arc.style.stroke = '#22c55e';
    else if (pct > 0.25) this.arc.style.stroke = '#f59e0b';
    else {
      this.arc.style.stroke = '#ef4444';
      this.wrap.style.animation = 'timerPulse 1s infinite';
    }
    if (this.onTick) this.onTick(this.remaining);
  }

  start() {
    this._update();
    this._interval = setInterval(() => {
      this.remaining--;
      this._update();
      if (this.remaining <= 0) { this.stop(); this.onExpire && this.onExpire(); }
    }, 1000);
  }

  stop()  { clearInterval(this._interval); this._interval = null; }
  reset(s){ this.stop(); this.remaining = s || this.total; this.wrap.style.animation=''; this._update(); }
  pause() { this.stop(); }
}
