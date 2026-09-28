<?php
// Public landing page — no auth required. This is the front door at
// http://localhost/mini-projectTEMP/ once XAMPP serves this directory.
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FITCO — Run your gym. Track your progress.</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="fitCo/assets/public-home.css">
<link rel="stylesheet" href="fitCo/header/header.css">
<link rel="stylesheet" href="fitCo/footer/footer.css">
</head>
<body>

<?php require __DIR__ . "/fitCo/header/header.php"; ?>

<section class="hero">
  <div>
    <span class="hero__eyebrow hero-anim-1"><span class="pulse-dot" aria-hidden="true"></span>Gym management, made simple</span>
    <h1 class="hero-anim-2">Run your gym.<br>Track your progress.</h1>
    <p class="hero-anim-3">FITCO connects gym owners and members on one platform — membership plans, renewals, attendance and payments for gyms; BMI tracking, workout splits, and membership history for members.</p>
    <div class="hero__actions hero-anim-4">
      <a href="fitCo/user-auth/signup/signup.php" class="btn btn--primary btn--lg btn--arrow">Join as a member<span class="btn__arrow" aria-hidden="true">→</span></a>
      <a href="fitCo/auth/signup/signup.php" class="btn btn--lg">List your gym</a>
    </div>
  </div>

  <div class="hero__visual hero-anim-4">
    <div class="hero-figure">
      <div class="hero-figure__particles" aria-hidden="true">
        <span class="drift" id="d1"></span>
        <span class="drift" id="d2"></span>
        <span class="drift" id="d3"></span>
      </div>

      <svg class="hero-figure__ring" viewBox="0 0 300 300" aria-hidden="true">
        <circle cx="150" cy="150" r="128" fill="none" stroke="rgba(23,20,15,0.5)" stroke-width="1" stroke-dasharray="2 10" id="orbit"/>
        <circle id="ping" cx="150" cy="150" r="100" fill="none" stroke="#3d7a56" stroke-width="2"/>
        <circle cx="150" cy="150" r="100" fill="none" stroke="rgba(23,20,15,0.1)" stroke-width="12"/>
        <circle id="ring" cx="150" cy="150" r="100" fill="none" stroke="#17140f" stroke-width="12"
                stroke-linecap="round" stroke-dasharray="628.3" stroke-dashoffset="628.3"
                transform="rotate(-90 150 150)"/>
        <g id="dumbbell" transform="translate(103,132)">
          <rect x="0" y="16" width="94" height="12" rx="6" fill="#17140f"/>
          <rect x="-13" y="6" width="20" height="32" rx="7" fill="#17140f"/>
          <rect x="87" y="6" width="20" height="32" rx="7" fill="#17140f"/>
        </g>
      </svg>

      <div class="hero-figure__bars" aria-hidden="true">
        <div class="bar" style="animation-delay:0s;"></div>
        <div class="bar" style="animation-delay:0.12s;"></div>
        <div class="bar" style="animation-delay:0.24s;"></div>
        <div class="bar" style="animation-delay:0.36s;"></div>
        <div class="bar" style="animation-delay:0.48s;"></div>
        <div class="bar" style="animation-delay:0.6s;"></div>
        <div class="bar" style="animation-delay:0.72s;"></div>
        <div class="bar" style="animation-delay:0.84s;"></div>
        <div class="bar" style="animation-delay:0.96s;"></div>
        <div class="bar" style="animation-delay:1.08s;"></div>
        <div class="bar" style="animation-delay:1.2s;"></div>
        <div class="bar" style="animation-delay:1.32s;"></div>
      </div>

      <svg class="hero-figure__pulse" viewBox="0 0 480 40" preserveAspectRatio="none" aria-hidden="true">
        <path id="pulse" d="M0,20 L180,20 L206,3 L232,37 L258,20 L480,20"
              fill="none" stroke="#726d5e" stroke-width="2" stroke-dasharray="700" stroke-dashoffset="700"/>
      </svg>
    </div>
  </div>
</section>

<section class="section" id="programs">
  <div class="section__head reveal">
    <span class="section__eyebrow">For members</span>
    <h2>Everything to stay on track</h2>
    <p>One account, linked to your gym — your history follows you.</p>
  </div>
  <div class="feature-grid">
    <div class="feature-card reveal">
      <div class="feature-card__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12h16M4 8h16M4 16h10"/></svg></div>
      <h3>BMI tracking</h3>
      <p>Log height and weight, see your BMI category, and track how it changes over time.</p>
    </div>
    <div class="feature-card reveal">
      <div class="feature-card__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="2"/><path d="M4 10h16M10 4v16"/></svg></div>
      <h3>Workout split builder</h3>
      <p>Plan your weekly split by muscle group and keep it saved to your account.</p>
    </div>
    <div class="feature-card reveal">
      <div class="feature-card__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v4l3 3"/><circle cx="12" cy="12" r="9"/></svg></div>
      <h3>Membership & renewals</h3>
      <p>See exactly when your plan expires and renew in a couple of clicks when it's time.</p>
    </div>
    <div class="feature-card reveal">
      <div class="feature-card__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div>
      <h3>Attendance history</h3>
      <p>Every check-in recorded automatically, so you can see your consistency at a glance.</p>
    </div>
  </div>
</section>

<section class="section section--muted" id="how-it-works">
  <div class="section__head reveal">
    <span class="section__eyebrow">How it works</span>
    <h2>Three steps to get moving</h2>
  </div>
  <div class="steps">
    <div class="step reveal">
      <span class="step__num">1</span>
      <h3>Create your account</h3>
      <p>Sign up with your email and verify it with a one-time code.</p>
    </div>
    <div class="step reveal">
      <span class="step__num">2</span>
      <h3>Link your gym membership</h3>
      <p>Match your account to the membership your gym already has on file.</p>
    </div>
    <div class="step reveal">
      <span class="step__num">3</span>
      <h3>Track & pay from one place</h3>
      <p>BMI, workouts, attendance, and renewals — all under your account.</p>
    </div>
  </div>
</section>

<section class="section" id="for-gyms">
  <div class="owner-split">
    <div class="reveal">
      <span class="section__eyebrow">For gym owners</span>
      <h2 style="font-size:clamp(22px,3vw,30px); letter-spacing:-0.01em; margin-bottom:14px;">Run the whole gym from one dashboard</h2>
      <ul class="owner-list">
        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg> Member records, plans, and renewals in one place</li>
        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg> Trainer assignments and daily attendance</li>
        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg> Payments and revenue, tracked automatically</li>
        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg> Members get their own self-service portal</li>
      </ul>
      <a href="fitCo/auth/signup/signup.php" class="btn btn--primary btn--lg">List your gym</a>
    </div>
    <div class="owner-visual reveal">
      <div class="owner-visual__row"><span>Active members</span><strong>128</strong></div>
      <div class="owner-visual__row"><span>Expiring this week</span><strong>6</strong></div>
      <div class="owner-visual__row"><span>Today's attendance</span><strong>34</strong></div>
      <div class="owner-visual__row"><span>Monthly revenue</span><strong>₹1,84,000</strong></div>
    </div>
  </div>
</section>

<section class="section section--muted">
  <div class="section__head reveal">
    <span class="section__eyebrow">What people say</span>
    <h2>Built around how gyms actually run</h2>
  </div>
  <div class="testimonial-grid">
    <div class="testimonial reveal">
      <p>"Renewals used to be a spreadsheet nightmare. Now I just glance at the dashboard each morning."</p>
      <div class="testimonial__who">
        <span class="testimonial__avatar">A</span>
        <div><div class="testimonial__name">Aditya R.</div><div class="testimonial__role">Gym owner</div></div>
      </div>
    </div>
    <div class="testimonial reveal">
      <p>"I like that my BMI history and workout split are just there when I log in — nothing to re-enter."</p>
      <div class="testimonial__who">
        <span class="testimonial__avatar">P</span>
        <div><div class="testimonial__name">Priya S.</div><div class="testimonial__role">Member</div></div>
      </div>
    </div>
    <div class="testimonial reveal">
      <p>"Attendance tracking alone saved my front desk an hour a day."</p>
      <div class="testimonial__who">
        <span class="testimonial__avatar">K</span>
        <div><div class="testimonial__name">Karan M.</div><div class="testimonial__role">Gym owner</div></div>
      </div>
    </div>
  </div>
</section>

<div class="cta-band reveal">
  <div>
    <h2>Ready to get moving?</h2>
    <p>Join as a member or list your gym — both take a couple of minutes.</p>
  </div>
  <div style="display:flex; gap:10px; flex-wrap:wrap;">
    <a href="fitCo/user-auth/signup/signup.php" class="btn btn--primary">Join as a member</a>
    <a href="fitCo/auth/signup/signup.php" class="btn btn--ghost">List your gym</a>
  </div>
</div>

<?php require __DIR__ . "/fitCo/footer/footer.php"; ?>

<script>
  // Sticky-nav shadow once the page has scrolled past the hero.
  (function(){
    var nav = document.getElementById('siteNav');
    function onScroll(){ nav.classList.toggle('is-scrolled', window.scrollY > 8); }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  })();

  // Mobile menu toggle.
  (function(){
    var burger = document.getElementById('navBurger');
    var mobile = document.getElementById('navMobile');
    burger.addEventListener('click', function(){
      var open = mobile.classList.toggle('is-open');
      burger.setAttribute('aria-expanded', String(open));
    });
    mobile.querySelectorAll('a').forEach(function(a){
      a.addEventListener('click', function(){
        mobile.classList.remove('is-open');
        burger.setAttribute('aria-expanded', 'false');
      });
    });
  })();

  // Login dropdown.
  (function(){
    var dropdown = document.querySelector('.nav-dropdown');
    if (!dropdown) return;
    var toggle = dropdown.querySelector('.nav-dropdown__toggle');
    toggle.addEventListener('click', function(e){
      e.stopPropagation();
      dropdown.classList.toggle('is-open');
    });
    document.addEventListener('click', function(){ dropdown.classList.remove('is-open'); });
  })();

  // Reveal-on-scroll — animate each section in once, the first time it
  // enters the viewport. Occasional (one pass per pageview), so a real
  // animation is worth it here, unlike something the user sees often.
  (function(){
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var items = document.querySelectorAll('.reveal');
    if (reduce || !('IntersectionObserver' in window)) {
      items.forEach(function(el){ el.classList.add('is-visible'); });
      return;
    }
    var observer = new IntersectionObserver(function(entries){
      entries.forEach(function(entry){
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.15 });
    items.forEach(function(el){ observer.observe(el); });
  })();

  // Count owner-visual stats up from zero once they scroll into view —
  // ties the motion to something meaningful (the numbers themselves)
  // instead of animating for its own sake, and only runs once.
  (function(){
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var stats = document.querySelectorAll('.owner-visual__row strong');
    if (!stats.length) return;

    function animateCount(el){
      var raw = el.textContent.trim();
      var match = raw.match(/[\d,]+/);
      if (!match) return;
      var target = parseInt(match[0].replace(/,/g, ''), 10);
      var prefix = raw.slice(0, match.index);
      var suffix = raw.slice(match.index + match[0].length);
      if (reduce) { return; }

      var start = null;
      var duration = 1100;
      function frame(ts){
        if (!start) start = ts;
        var progress = Math.min((ts - start) / duration, 1);
        var eased = 1 - Math.pow(1 - progress, 3);
        var current = Math.round(target * eased);
        el.textContent = prefix + current.toLocaleString('en-IN') + suffix;
        if (progress < 1) requestAnimationFrame(frame);
      }
      requestAnimationFrame(frame);
    }

    if (!('IntersectionObserver' in window)) return;
    var statObserver = new IntersectionObserver(function(entries){
      entries.forEach(function(entry){
        if (entry.isIntersecting) {
          animateCount(entry.target);
          statObserver.unobserve(entry.target);
        }
      });
    }, { threshold: 0.5 });
    stats.forEach(function(el){ statObserver.observe(el); });
  })();
</script>

</body>
</html>
