<?php
require __DIR__ . "/../includes/bootstrap.php";

$pageTitle = 'Profile';
$activeNav = 'profile';

$avatarUrl = $user['profile_photo']
    ? '/mini-projectTEMP/fitCo/assets/uploads/avatars/' . rawurlencode($user['profile_photo'])
    : null;

require __DIR__ . "/../includes/layout-top.php";
?>

<div class="section-head reveal">
  <div>
    <h2>Your profile</h2>
    <p class="section-sub">Personal details, registration info, and account status.</p>
  </div>
  <a class="btn btn--primary" href="edit.php">Edit profile</a>
</div>

<section class="panels">

  <div class="panel-card reveal" style="animation-delay:0.05s">
    <div class="panel-head">
      <h2>Personal information</h2>
    </div>
    <div class="profile-head">
      <?php if ($avatarUrl): ?>
        <img class="avatar avatar--lg" src="<?php echo h($avatarUrl); ?>" alt="Profile photo">
      <?php else: ?>
        <span class="avatar avatar--lg avatar--placeholder"><?php echo h(strtoupper(substr($full_name, 0, 1))); ?></span>
      <?php endif; ?>
      <div>
        <div class="list-name" style="font-size:16px;"><?php echo h($full_name); ?></div>
        <div class="list-sub"><?php echo h($user['email']); ?></div>
      </div>
    </div>
    <ul class="list">
      <li class="list-row">
        <div class="list-main"><span class="list-name">Mobile</span><span class="list-sub"><?php echo h($user['mobile']); ?></span></div>
      </li>
      <li class="list-row">
        <div class="list-main"><span class="list-name">Address</span><span class="list-sub"><?php echo $user['address'] ? h($user['address']) : '—'; ?></span></div>
      </li>
    </ul>
  </div>

  <div class="panel-card reveal" style="animation-delay:0.1s">
    <div class="panel-head">
      <h2>Emergency contact</h2>
    </div>
    <ul class="list">
      <li class="list-row">
        <div class="list-main"><span class="list-name">Name</span><span class="list-sub"><?php echo $user['emergency_contact_name'] ? h($user['emergency_contact_name']) : '—'; ?></span></div>
      </li>
      <li class="list-row">
        <div class="list-main"><span class="list-name">Phone</span><span class="list-sub"><?php echo $user['emergency_contact_phone'] ? h($user['emergency_contact_phone']) : '—'; ?></span></div>
      </li>
    </ul>
  </div>

  <div class="panel-card reveal" style="animation-delay:0.15s">
    <div class="panel-head">
      <h2>Account</h2>
    </div>
    <ul class="list">
      <li class="list-row">
        <div class="list-main"><span class="list-name">Status</span></div>
        <span class="badge <?php echo $user['status'] === 'active' ? 'badge--success' : 'badge--error'; ?>"><?php echo h(ucfirst($user['status'])); ?></span>
      </li>
      <li class="list-row">
        <div class="list-main"><span class="list-name">Registered</span><span class="list-sub"><?php echo fmt_date($user['created_at']); ?></span></div>
      </li>
      <li class="list-row">
        <div class="list-main"><span class="list-name">Gym membership</span><span class="list-sub"><?php echo $gym ? h($gym['gym_name']) : 'Not linked'; ?></span></div>
        <?php if (!$gym): ?><a class="panel-link" href="/mini-projectTEMP/fitCo/user/link-membership/link.php">Link →</a><?php endif; ?>
      </li>
    </ul>
  </div>

</section>

<?php require __DIR__ . "/../includes/layout-bottom.php"; ?>
