// Particle background
(function(){
  const canvas = document.getElementById('particleCanvas');
  if(!canvas) return;
  const ctx = canvas.getContext('2d');
  let W, H, particles = [];

  function resize(){ W = canvas.width = window.innerWidth; H = canvas.height = window.innerHeight; }
  resize();
  window.addEventListener('resize', resize);

  class Particle {
    constructor(){
      this.x = Math.random()*W;
      this.y = Math.random()*H;
      this.r = Math.random()*1.5+0.5;
      this.vx = (Math.random()-.5)*.3;
      this.vy = (Math.random()-.5)*.3;
      this.alpha = Math.random()*.5+.1;
    }
    update(){
      this.x += this.vx; this.y += this.vy;
      if(this.x<0) this.x=W; if(this.x>W) this.x=0;
      if(this.y<0) this.y=H; if(this.y>H) this.y=0;
    }
    draw(){
      ctx.beginPath();
      ctx.arc(this.x,this.y,this.r,0,Math.PI*2);
      ctx.fillStyle = `rgba(99,102,241,${this.alpha})`;
      ctx.fill();
    }
  }

  for(let i=0;i<80;i++) particles.push(new Particle());

  // Draw connecting lines
  function drawLines(){
    for(let i=0;i<particles.length;i++){
      for(let j=i+1;j<particles.length;j++){
        const dx=particles[i].x-particles[j].x, dy=particles[i].y-particles[j].y;
        const dist=Math.sqrt(dx*dx+dy*dy);
        if(dist<120){
          ctx.beginPath();
          ctx.moveTo(particles[i].x, particles[i].y);
          ctx.lineTo(particles[j].x, particles[j].y);
          ctx.strokeStyle = `rgba(99,102,241,${.08*(1-dist/120)})`;
          ctx.lineWidth = .5;
          ctx.stroke();
        }
      }
    }
  }

  function animate(){
    ctx.clearRect(0,0,W,H);
    // Grid dots
    ctx.fillStyle='rgba(99,102,241,.03)';
    for(let x=0;x<W;x+=40) for(let y=0;y<H;y+=40){
      ctx.beginPath(); ctx.arc(x,y,1,0,Math.PI*2); ctx.fill();
    }
    particles.forEach(p=>{ p.update(); p.draw(); });
    drawLines();
    requestAnimationFrame(animate);
  }
  animate();
})();
