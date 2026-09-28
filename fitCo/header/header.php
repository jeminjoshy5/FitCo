<header class="site-nav" id="siteNav">
  <div class="site-nav__inner">
    <a href="/mini-projectTEMP/index.php" class="site-nav__brand">
      <span class="brand-mark" aria-hidden="true">
        <svg viewBox="0 0 28 28">
          <rect width="28" height="28" rx="7" fill="#17140f"/>
          <text x="14" y="19.5" text-anchor="middle" font-family="Inter, sans-serif" font-weight="800" font-size="15" fill="#fafaf8">F</text>
        </svg>
      </span>
      <span>FITCO</span>
    </a>

    <nav class="site-nav__links">
      <a href="#programs">Programs</a>
      <a href="#how-it-works">How it works</a>
      <a href="#for-gyms">For gym owners</a>
      <a href="#contact">Contact</a>
    </nav>

    <div class="site-nav__actions">
      <div class="nav-dropdown">
        <button type="button" class="btn btn--ghost nav-dropdown__toggle">Log in</button>
        <div class="nav-dropdown__menu">
          <a href="/mini-projectTEMP/fitCo/user-auth/login/login.php">Member login</a>
          <a href="/mini-projectTEMP/fitCo/auth/login/login.php">Gym owner login</a>
          <a href="/mini-projectTEMP/fitCo/admin-auth/login/login.php">Admin login</a>
        </div>
      </div>
      <a href="/mini-projectTEMP/fitCo/user-auth/signup/signup.php" class="btn btn--primary">Get started</a>
    </div>

    <button type="button" class="site-nav__burger" id="navBurger" aria-label="Open menu" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>
  </div>

  <div class="site-nav__mobile" id="navMobile">
    <a href="#programs">Programs</a>
    <a href="#how-it-works">How it works</a>
    <a href="#for-gyms">For gym owners</a>
    <a href="#contact">Contact</a>
    <hr>
    <a href="/mini-projectTEMP/fitCo/user-auth/login/login.php">Member login</a>
    <a href="/mini-projectTEMP/fitCo/auth/login/login.php">Gym owner login</a>
    <a href="/mini-projectTEMP/fitCo/admin-auth/login/login.php">Admin login</a>
    <a class="btn btn--primary" style="margin-top:10px;" href="/mini-projectTEMP/fitCo/user-auth/signup/signup.php">Get started</a>
  </div>
</header>
