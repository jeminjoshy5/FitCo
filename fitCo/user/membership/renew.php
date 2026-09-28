<?php
require __DIR__ . "/../includes/bootstrap.php";

$pageTitle = 'Renew Membership';
$activeNav = 'membership';

if (!$member) {
    header("Location: /mini-projectTEMP/fitCo/user/link-membership/link.php");
    exit();
}

// The membership being renewed, if any (absent = first-time subscription).
$oldMembershipId = (int) ($_GET['membership_id'] ?? 0);
$old = null;
if ($oldMembershipId > 0) {
    $r = mysqli_query($con, "SELECT mm.*, mp.plan_name FROM memberships mm
                              JOIN membership_plans mp ON mp.plan_id = mm.plan_id
                              WHERE mm.membership_id=$oldMembershipId AND mm.member_id=$member_id");
    $old = $r ? mysqli_fetch_assoc($r) : null;
    if (!$old) {
        flash_set('error', 'Membership not found.');
        header("Location: index.php");
        exit();
    }
}

$plans = [];
$r = mysqli_query($con, "SELECT plan_id, plan_name, duration_days, price, description FROM membership_plans WHERE gym_id=$gym_id AND status='active' ORDER BY plan_name");
while ($row = mysqli_fetch_assoc($r)) { $plans[] = $row; }

require __DIR__ . "/../includes/layout-top.php";
?>

<div class="section-head reveal">
  <div>
    <h2><?php echo $old ? 'Renew membership' : 'Choose a plan'; ?></h2>
    <p class="section-sub">
      <?php if ($old): ?>
        Current plan: <?php echo h($old['plan_name']); ?>, ends <?php echo fmt_date($old['end_date']); ?>.
        Paying below starts a new period — your history is kept.
      <?php else: ?>
        Pick a plan and pay to start your membership.
      <?php endif; ?>
    </p>
  </div>
  <a class="btn" href="index.php">← Back to membership</a>
</div>

<div id="renewError" class="flash flash--error reveal" style="display:none;"></div>

<section class="panels">
  <?php foreach ($plans as $p): ?>
    <div class="panel-card reveal">
      <div class="panel-head"><h2><?php echo h($p['plan_name']); ?></h2></div>
      <ul class="list">
        <li class="list-row"><div class="list-main"><span class="list-name">Price</span><span class="list-sub"><?php echo fmt_money($p['price']); ?></span></div></li>
        <li class="list-row"><div class="list-main"><span class="list-name">Duration</span><span class="list-sub"><?php echo (int) $p['duration_days']; ?> days</span></div></li>
        <li class="list-row"><div class="list-main"><span class="list-name">Benefits</span><span class="list-sub"><?php echo $p['description'] ? h($p['description']) : 'Standard access'; ?></span></div></li>
      </ul>
      <div class="form-actions" style="margin-top:16px;">
        <button type="button" class="btn btn--primary pay-btn"
                data-plan-id="<?php echo (int) $p['plan_id']; ?>"
                data-plan-name="<?php echo h($p['plan_name']); ?>"
                data-amount="<?php echo (int) round($p['price'] * 100); ?>">
          Pay <?php echo fmt_money($p['price']); ?> &amp; <?php echo $old ? 'renew' : 'subscribe'; ?>
        </button>
      </div>
    </div>
  <?php endforeach; ?>
</section>

<div id="payModal" class="pay-modal" aria-hidden="true">
  <div class="pay-modal__backdrop" id="payModalBackdrop"></div>
  <div class="pay-modal__card" role="dialog" aria-modal="true" aria-labelledby="payModalTitle">
    <div class="pay-modal__badge">Simulated payment</div>
    <h3 id="payModalTitle">Complete your payment</h3>
    <p class="pay-modal__sub"><span id="payModalPlan"></span> — <strong id="payModalAmount"></strong></p>

    <!-- Real-credit-card visual: mirrors what's typed below, flips to show
         the CVV while that field is focused. Decorative but purposeful —
         it's the same "show which side you're editing" job a real
         checkout's card preview does. -->
    <div class="cc-stage">
      <div class="cc" id="ccFlip">
        <div class="cc__face cc__face--front">
          <div class="cc__row-top">
            <div class="cc__chip" aria-hidden="true">
              <svg viewBox="0 0 34 26" width="34" height="26"><rect x="0.5" y="0.5" width="33" height="25" rx="4" fill="url(#chipGrad)" stroke="rgba(0,0,0,0.25)"/><line x1="11" y1="0.5" x2="11" y2="25.5" stroke="rgba(0,0,0,0.25)"/><line x1="23" y1="0.5" x2="23" y2="25.5" stroke="rgba(0,0,0,0.25)"/><line x1="0.5" y1="13" x2="33.5" y2="13" stroke="rgba(0,0,0,0.25)"/><defs><linearGradient id="chipGrad" x1="0" y1="0" x2="34" y2="26"><stop offset="0" stop-color="#e9d8a6"/><stop offset="1" stop-color="#c9a86a"/></linearGradient></defs></svg>
            </div>
            <div class="cc__brand" id="ccBrand" aria-hidden="true"></div>
          </div>
          <div class="cc__number" id="ccNumberDisplay">•••• •••• •••• ••••</div>
          <div class="cc__row-bottom">
            <div class="cc__field-group">
              <span class="cc__micro">Card holder</span>
              <span class="cc__name" id="ccNameDisplay">YOUR NAME</span>
            </div>
            <div class="cc__field-group cc__field-group--right">
              <span class="cc__micro">Expires</span>
              <span class="cc__expiry" id="ccExpiryDisplay">MM/YY</span>
            </div>
          </div>
        </div>
        <div class="cc__face cc__face--back">
          <div class="cc__stripe" aria-hidden="true"></div>
          <div class="cc__cvv-row">
            <span class="cc__cvv-label">CVV</span>
            <div class="cc__cvv-box" id="ccCvvDisplay">•••</div>
          </div>
          <p class="cc__fineprint">Simulated card — for demo purposes only</p>
        </div>
      </div>
    </div>

    <form class="pay-form" id="payForm" novalidate>
      <div class="pay-modal__field" id="fgCardName">
        <label for="simCardName">Name on card</label>
        <input type="text" id="simCardName" placeholder="e.g. Priya Sharma" autocomplete="cc-name">
        <p class="field-error" id="errCardName"></p>
      </div>

      <div class="pay-modal__field" id="fgCardNumber">
        <label for="simCardNumber">Card number</label>
        <input type="text" id="simCardNumber" inputmode="numeric" placeholder="4111 1111 1111 1111" autocomplete="cc-number" maxlength="19">
        <p class="field-error" id="errCardNumber"></p>
      </div>

      <div class="pay-modal__row">
        <div class="pay-modal__field" id="fgExpiry">
          <label for="simExpiry">Expiry (MM/YY)</label>
          <input type="text" id="simExpiry" inputmode="numeric" placeholder="12/29" autocomplete="cc-exp" maxlength="5">
          <p class="field-error" id="errExpiry"></p>
        </div>
        <div class="pay-modal__field" id="fgCvv">
          <label for="simCvv">CVV</label>
          <input type="text" id="simCvv" inputmode="numeric" placeholder="123" autocomplete="cc-csc" maxlength="4">
          <p class="field-error" id="errCvv"></p>
        </div>
      </div>
    </form>

    <p class="pay-modal__note">No real card is charged — this is a local simulator standing in for a payment gateway.</p>

    <div class="pay-modal__actions">
      <button type="button" class="btn" id="payModalCancel">Cancel</button>
      <button type="button" class="btn btn--primary" id="payModalConfirm">
        <span id="payModalConfirmLabel">Pay now</span>
      </button>
    </div>
  </div>
</div>

<style>
  .pay-modal{ position:fixed; inset:0; z-index:200; display:none; align-items:center; justify-content:center; padding:20px; overflow-y:auto; }
  .pay-modal.is-open{ display:flex; }
  .pay-modal__backdrop{ position:absolute; inset:0; background:rgba(23,20,15,0.45); animation: payFade 0.18s var(--spring-fast); }
  .pay-modal__card{
    position:relative; width:100%; max-width:400px; background:var(--bg); border:1px solid var(--rule);
    border-radius:14px; padding:24px; box-shadow:0 20px 50px rgba(23,20,15,0.25);
    animation: payUp 0.22s var(--spring);
    margin:auto;
  }
  @keyframes payFade{ from{opacity:0;} to{opacity:1;} }
  @keyframes payUp{ from{opacity:0; transform:translateY(10px) scale(0.98);} to{opacity:1; transform:translateY(0) scale(1);} }
  .pay-modal__badge{
    display:inline-block; font-size:11px; font-weight:600; letter-spacing:0.04em; text-transform:uppercase;
    color:var(--muted); background:var(--surface); border:1px solid var(--rule); border-radius:999px;
    padding:3px 10px; margin-bottom:12px;
  }
  .pay-modal__card h3{ font-size:18px; margin-bottom:4px; }
  .pay-modal__sub{ color:var(--muted); font-size:14px; margin-bottom:16px; }

  /* ---------- Credit card visual ---------- */
  .cc-stage{ perspective:1000px; margin-bottom:20px; }
  .cc{
    position:relative; width:100%; height:186px; transform-style:preserve-3d;
    transition:transform 480ms cubic-bezier(0.77,0,0.175,1);
  }
  .cc.is-flipped{ transform:rotateY(180deg); }
  .cc__face{
    position:absolute; inset:0; border-radius:14px; backface-visibility:hidden;
    padding:18px 20px; color:#fff; display:flex; flex-direction:column; justify-content:space-between;
    background:
      radial-gradient(120% 140% at 100% 0%, rgba(255,255,255,0.14), transparent 55%),
      linear-gradient(135deg, #2a251c 0%, #17140f 55%, #0e0c08 100%);
    box-shadow:0 14px 30px rgba(23,20,15,0.35);
  }
  .cc__face--back{ transform:rotateY(180deg); justify-content:flex-start; padding:0; }
  .cc__row-top{ display:flex; align-items:flex-start; justify-content:space-between; }
  .cc__brand{ height:22px; opacity:0; transition:opacity 200ms ease; font-family:var(--display); font-weight:700; font-style:italic; font-size:18px; letter-spacing:-0.02em; }
  .cc__brand.is-visible{ opacity:1; }
  .cc__brand[data-brand="visa"]{ color:#fff; }
  .cc__brand[data-brand="mastercard"]{ display:flex; align-items:center; gap:0; }
  .cc__brand[data-brand="mastercard"]::before,
  .cc__brand[data-brand="mastercard"]::after{ content:""; width:20px; height:20px; border-radius:50%; display:inline-block; }
  .cc__brand[data-brand="mastercard"]::before{ background:#eb5f27; }
  .cc__brand[data-brand="mastercard"]::after{ background:#f2ab27; margin-left:-8px; mix-blend-mode:screen; }
  .cc__number{
    font-family:var(--mono); font-size:19px; letter-spacing:0.08em; white-space:nowrap;
    text-shadow:0 1px 1px rgba(0,0,0,0.3);
  }
  .cc__row-bottom{ display:flex; justify-content:space-between; align-items:flex-end; }
  .cc__field-group{ display:flex; flex-direction:column; gap:2px; }
  .cc__field-group--right{ align-items:flex-end; }
  .cc__micro{ font-size:9px; letter-spacing:0.06em; text-transform:uppercase; color:rgba(255,255,255,0.55); }
  .cc__name{ font-size:14px; letter-spacing:0.03em; text-transform:uppercase; font-family:var(--mono); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:220px; }
  .cc__expiry{ font-size:14px; font-family:var(--mono); }
  .cc__stripe{ width:100%; height:40px; background:#0a0908; margin-top:18px; }
  .cc__cvv-row{ display:flex; align-items:center; gap:10px; margin:22px 20px 0; }
  .cc__cvv-label{ font-size:10px; text-transform:uppercase; letter-spacing:0.06em; color:rgba(255,255,255,0.6); }
  .cc__cvv-box{
    margin-left:auto; background:#fff; color:var(--ink); font-family:var(--mono); font-size:14px;
    padding:6px 12px; border-radius:4px; letter-spacing:0.15em; min-width:52px; text-align:center;
  }
  .cc__fineprint{ margin:16px 20px 0; font-size:10px; color:rgba(255,255,255,0.4); }

  /* ---------- Form fields ---------- */
  .pay-form{ display:flex; flex-direction:column; }
  .pay-modal__field{ margin-bottom:14px; flex:1; }
  .pay-modal__field label{ display:block; font-size:12px; color:var(--muted); margin-bottom:5px; }
  .pay-modal__field input{
    width:100%; padding:9px 11px; border:1px solid var(--rule); border-radius:8px; background:var(--surface);
    color:var(--ink); font-family:var(--mono); font-size:14px;
    transition:border-color 140ms var(--spring-fast), box-shadow 140ms var(--spring-fast);
  }
  .pay-modal__field input:focus{ outline:none; border-color:var(--accent); box-shadow:0 0 0 3px rgba(23,20,15,0.08); }
  .pay-modal__field.has-error input{ border-color:var(--error); }
  .pay-modal__field.has-error input:focus{ box-shadow:0 0 0 3px var(--error-bg); }
  .pay-modal__field.is-valid input{ border-color:var(--success); }
  .pay-modal__row{ display:flex; gap:12px; }
  .field-error{
    font-size:11.5px; color:var(--error); margin-top:5px; height:0; overflow:hidden; opacity:0;
    transition:opacity 140ms ease-out, height 140ms ease-out;
  }
  .field-error.is-shown{ height:auto; opacity:1; }
  .shake{ animation:fieldShake 320ms cubic-bezier(0.36,0.07,0.19,0.97); }
  @keyframes fieldShake{
    10%,90%{ transform:translateX(-1px); }
    20%,80%{ transform:translateX(2px); }
    30%,50%,70%{ transform:translateX(-4px); }
    40%,60%{ transform:translateX(4px); }
  }
  .pay-modal__note{ font-size:12px; color:var(--muted); margin:4px 0 18px; }
  .pay-modal__actions{ display:flex; justify-content:flex-end; gap:10px; }
  .pay-modal__actions .btn{ min-width:96px; }
</style>

<script>
(function(){
  var oldMembershipId = <?php echo $old ? (int) $old['membership_id'] : 'null'; ?>;
  var errorBox = document.getElementById('renewError');
  var buttons  = document.querySelectorAll('.pay-btn');

  var modal          = document.getElementById('payModal');
  var modalBackdrop   = document.getElementById('payModalBackdrop');
  var modalPlan       = document.getElementById('payModalPlan');
  var modalAmount     = document.getElementById('payModalAmount');
  var modalCancelBtn  = document.getElementById('payModalCancel');
  var modalConfirmBtn = document.getElementById('payModalConfirm');
  var modalConfirmLabel = document.getElementById('payModalConfirmLabel');

  var currentOrder = null; // { orderId, planName, amountDisplay, activeBtn }

  // ---------------------------------------------------------------
  // Credit-card visual + live formatting
  // ---------------------------------------------------------------
  var ccFlip     = document.getElementById('ccFlip');
  var ccBrand    = document.getElementById('ccBrand');
  var ccNumberEl = document.getElementById('ccNumberDisplay');
  var ccNameEl   = document.getElementById('ccNameDisplay');
  var ccExpiryEl = document.getElementById('ccExpiryDisplay');
  var ccCvvEl    = document.getElementById('ccCvvDisplay');

  var nameInput   = document.getElementById('simCardName');
  var numberInput = document.getElementById('simCardNumber');
  var expiryInput = document.getElementById('simExpiry');
  var cvvInput    = document.getElementById('simCvv');

  function onlyDigits(s){ return (s || '').replace(/\D/g, ''); }

  function detectBrand(digits){
    if (/^4/.test(digits)) return 'visa';
    if (/^(5[1-5]|2[2-7])/.test(digits)) return 'mastercard';
    return '';
  }

  function luhnValid(digits){
    var sum = 0, alt = false;
    for (var i = digits.length - 1; i >= 0; i--) {
      var n = parseInt(digits.charAt(i), 10);
      if (alt) { n *= 2; if (n > 9) n -= 9; }
      sum += n;
      alt = !alt;
    }
    return digits.length > 0 && sum % 10 === 0;
  }

  // Mirrors typed digits onto the card face, four at a time, padding
  // whatever hasn't been typed yet with dots — same pattern real
  // checkout card previews use.
  function renderCardNumber(digits){
    var groups = [];
    for (var i = 0; i < 16; i += 4) {
      var chunk = digits.slice(i, i + 4);
      groups.push(chunk.length ? chunk.padEnd(4, '•') : '••••');
    }
    ccNumberEl.textContent = groups.join(' ');
  }

  numberInput.addEventListener('input', function(){
    var digits = onlyDigits(numberInput.value).slice(0, 19);
    numberInput.value = digits.replace(/(.{4})/g, '$1 ').trim();
    renderCardNumber(digits);

    var brand = detectBrand(digits);
    if (brand) {
      ccBrand.textContent = brand === 'visa' ? 'VISA' : '';
      ccBrand.setAttribute('data-brand', brand);
      ccBrand.classList.add('is-visible');
    } else {
      ccBrand.classList.remove('is-visible');
    }
    clearFieldError('fgCardNumber');
  });

  nameInput.addEventListener('input', function(){
    ccNameEl.textContent = nameInput.value.trim() ? nameInput.value.toUpperCase() : 'YOUR NAME';
    clearFieldError('fgCardName');
  });

  expiryInput.addEventListener('input', function(){
    var digits = onlyDigits(expiryInput.value).slice(0, 4);
    var formatted = digits.length > 2 ? digits.slice(0, 2) + '/' + digits.slice(2) : digits;
    expiryInput.value = formatted;
    ccExpiryEl.textContent = formatted || 'MM/YY';
    clearFieldError('fgExpiry');
  });

  cvvInput.addEventListener('input', function(){
    var digits = onlyDigits(cvvInput.value).slice(0, 4);
    cvvInput.value = digits;
    ccCvvEl.textContent = digits.length ? digits.padEnd(3, '•') : '•••';
    clearFieldError('fgCvv');
  });

  // Flip to the back while entering the CVV — the same "show what
  // you're editing" job the number/expiry fields do on the front.
  cvvInput.addEventListener('focus', function(){ ccFlip.classList.add('is-flipped'); });
  cvvInput.addEventListener('blur',  function(){ ccFlip.classList.remove('is-flipped'); });

  // ---------------------------------------------------------------
  // Validation
  // ---------------------------------------------------------------
  function fieldGroup(id){ return document.getElementById(id); }
  function errorEl(id){ return document.getElementById(id); }

  function clearFieldError(groupId){
    var group = fieldGroup(groupId);
    group.classList.remove('has-error');
    var err = group.querySelector('.field-error');
    err.classList.remove('is-shown');
  }

  function setFieldError(groupId, message){
    var group = fieldGroup(groupId);
    group.classList.remove('is-valid');
    group.classList.add('has-error');
    var err = group.querySelector('.field-error');
    err.textContent = message;
    err.classList.add('is-shown');
    group.classList.remove('shake');
    void group.offsetWidth; // restart the shake even if already showing an error
    group.classList.add('shake');
  }

  function setFieldValid(groupId){
    var group = fieldGroup(groupId);
    group.classList.remove('has-error');
    group.classList.add('is-valid');
  }

  function validatePaymentForm(){
    var firstInvalid = null;

    var name = nameInput.value.trim();
    if (name.length < 2) {
      setFieldError('fgCardName', 'Enter the name on the card.');
      firstInvalid = firstInvalid || nameInput;
    } else {
      setFieldValid('fgCardName');
    }

    var digits = onlyDigits(numberInput.value);
    if (digits.length < 13 || digits.length > 19 || !luhnValid(digits)) {
      setFieldError('fgCardNumber', 'Enter a valid card number.');
      firstInvalid = firstInvalid || numberInput;
    } else {
      setFieldValid('fgCardNumber');
    }

    var expiryMatch = /^(\d{2})\/(\d{2})$/.exec(expiryInput.value);
    var expiryOk = false;
    if (expiryMatch) {
      var month = parseInt(expiryMatch[1], 10);
      var year  = 2000 + parseInt(expiryMatch[2], 10);
      if (month >= 1 && month <= 12) {
        var expiryDate = new Date(year, month, 1); // first day of month *after* expiry
        expiryOk = expiryDate > new Date();
      }
    }
    if (!expiryOk) {
      setFieldError('fgExpiry', 'Enter a valid future date.');
      firstInvalid = firstInvalid || expiryInput;
    } else {
      setFieldValid('fgExpiry');
    }

    var cvvDigits = onlyDigits(cvvInput.value);
    if (cvvDigits.length < 3 || cvvDigits.length > 4) {
      setFieldError('fgCvv', 'Enter a valid CVV.');
      firstInvalid = firstInvalid || cvvInput;
    } else {
      setFieldValid('fgCvv');
    }

    if (firstInvalid) {
      firstInvalid.focus();
      return false;
    }
    return true;
  }

  function showError(msg){
    errorBox.textContent = msg;
    errorBox.style.display = 'block';
  }

  function resetPaymentForm(){
    ['fgCardName', 'fgCardNumber', 'fgExpiry', 'fgCvv'].forEach(function(id){
      var group = fieldGroup(id);
      group.classList.remove('has-error', 'is-valid');
      group.querySelector('.field-error').classList.remove('is-shown');
    });
    nameInput.value = '';
    numberInput.value = '';
    expiryInput.value = '';
    cvvInput.value = '';
    ccNameEl.textContent = 'YOUR NAME';
    ccExpiryEl.textContent = 'MM/YY';
    ccCvvEl.textContent = '•••';
    ccBrand.classList.remove('is-visible');
    renderCardNumber('');
    ccFlip.classList.remove('is-flipped');
  }

  function openModal(ctx){
    currentOrder = ctx;
    modalPlan.textContent = ctx.planName;
    modalAmount.textContent = ctx.amountDisplay;
    modalConfirmLabel.textContent = 'Pay now';
    modalConfirmBtn.disabled = false;
    resetPaymentForm();
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
  }

  function closeModal(reenableButtons){
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    if (reenableButtons) {
      buttons.forEach(function(b){ b.disabled = false; });
    }
    currentOrder = null;
  }

  modalBackdrop.addEventListener('click', function(){ closeModal(true); });
  modalCancelBtn.addEventListener('click', function(){ closeModal(true); });

  modalConfirmBtn.addEventListener('click', function(){
    if (!currentOrder) return;
    if (!validatePaymentForm()) return;

    modalConfirmBtn.disabled = true;
    modalConfirmLabel.textContent = 'Processing…';

    fetch('simulate-payment.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ order_id: currentOrder.orderId })
    })
    .then(function(res){ return res.json(); })
    .then(function(payment){
      if (!payment.success) {
        closeModal(true);
        showError(payment.message || 'Payment simulation failed. Please try again.');
        return;
      }

      return fetch('verify-payment.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
          razorpay_order_id: payment.razorpay_order_id,
          razorpay_payment_id: payment.razorpay_payment_id,
          razorpay_signature: payment.razorpay_signature
        })
      })
      .then(function(res){ return res.json(); })
      .then(function(result){
        if (result.success) {
          window.location.href = 'index.php';
        } else {
          closeModal(true);
          showError(result.message || 'Payment could not be verified. Contact your gym if the amount was deducted.');
        }
      });
    })
    .catch(function(){
      closeModal(true);
      showError('Something went wrong completing the payment. Please try again.');
    });
  });

  buttons.forEach(function(btn){
    btn.addEventListener('click', function(){
      errorBox.style.display = 'none';
      buttons.forEach(function(b){ b.disabled = true; });

      var planId = btn.getAttribute('data-plan-id');

      fetch('create-order.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ plan_id: planId, membership_id: oldMembershipId })
      })
      .then(function(res){ return res.json(); })
      .then(function(order){
        if (!order.success) {
          showError(order.message || 'Could not start payment. Please try again.');
          buttons.forEach(function(b){ b.disabled = false; });
          return;
        }

        openModal({
          orderId: order.order_id,
          planName: btn.getAttribute('data-plan-name') + ' membership',
          amountDisplay: '₹' + (order.amount / 100).toFixed(2)
        });
      })
      .catch(function(){
        showError('Something went wrong starting the payment. Please try again.');
        buttons.forEach(function(b){ b.disabled = false; });
      });
    });
  });
})();
</script>

<?php require __DIR__ . "/../includes/layout-bottom.php"; ?>
