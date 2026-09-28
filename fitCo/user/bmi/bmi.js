(function () {
  "use strict";

  // ---------- Config ----------
  var HEIGHT_MIN = 100, HEIGHT_MAX = 220; // cm
  var WEIGHT_MIN = 20,  WEIGHT_MAX = 200; // kg

  // ---------- Elements ----------
  var heightInput = document.getElementById('height');
  var heightRange = document.getElementById('heightRange');
  var weightInput = document.getElementById('weight');
  var weightRange = document.getElementById('weightRange');

  var rulerTrack   = document.getElementById('rulerTrack');
  var rulerTicksEl = document.getElementById('rulerTicks');
  var rulerMarker  = document.getElementById('rulerMarker');

  var dialTicksEl  = document.getElementById('dialTicks');
  var dialNeedle   = document.getElementById('dialNeedle');
  var weightReadout = document.getElementById('weightReadout');

  var calcBtn = document.getElementById('calcBtn');
  var resultSection = document.getElementById('resultSection');
  var bmiValueEl = document.getElementById('bmiValue');
  var bmiCategoryEl = document.getElementById('bmiCategory');
  var bmiDescEl = document.getElementById('bmiDesc');
  var bmiRangeMarker = document.getElementById('bmiRangeMarker');
  var saveStatus = document.getElementById('saveStatus');
  var nextBtn = document.getElementById('nextBtn');

  // ---------- Build ruler ticks (every 10cm, labelled every 20cm) ----------
  (function buildRuler() {
    var span = HEIGHT_MAX - HEIGHT_MIN;
    for (var cm = HEIGHT_MIN; cm <= HEIGHT_MAX; cm += 10) {
      var fracFromTop = 1 - (cm - HEIGHT_MIN) / span;
      var topPct = fracFromTop * 100;
      var isMajor = (cm % 20 === 0);

      var tick = document.createElement('div');
      tick.className = 'ruler-tick ' + (isMajor ? 'major' : 'minor');
      tick.style.top = topPct + '%';
      rulerTicksEl.appendChild(tick);

      if (isMajor) {
        var label = document.createElement('span');
        label.className = 'ruler-tick-label';
        label.style.top = topPct + '%';
        label.textContent = cm;
        rulerTicksEl.appendChild(label);
      }
    }
  })();

  // ---------- Build dial ticks (every 20kg around the gauge) ----------
  (function buildDial() {
    var cx = 110, cy = 118, rOuter = 96, rInnerMinor = 84, rInnerMajor = 80;
    var span = WEIGHT_MAX - WEIGHT_MIN;
    var svgNS = "http://www.w3.org/2000/svg";

    for (var kg = WEIGHT_MIN; kg <= WEIGHT_MAX; kg += 20) {
      var frac = (kg - WEIGHT_MIN) / span;       // 0..1
      var thetaDeg = 180 - frac * 180;             // 180 (left) -> 0 (right)
      var theta = thetaDeg * Math.PI / 180;
      var isMajor = ((kg - WEIGHT_MIN) % 40 === 0);
      var rInner = isMajor ? rInnerMajor : rInnerMinor;

      var x1 = cx + rOuter * Math.cos(theta);
      var y1 = cy - rOuter * Math.sin(theta);
      var x2 = cx + rInner * Math.cos(theta);
      var y2 = cy - rInner * Math.sin(theta);

      var line = document.createElementNS(svgNS, 'line');
      line.setAttribute('x1', x1.toFixed(2));
      line.setAttribute('y1', y1.toFixed(2));
      line.setAttribute('x2', x2.toFixed(2));
      line.setAttribute('y2', y2.toFixed(2));
      line.setAttribute('class', 'dial-tick' + (isMajor ? ' major' : ''));
      dialTicksEl.appendChild(line);
    }
  })();

  // ---------- Live visual updates ----------
  function clamp(v, min, max) { return Math.max(min, Math.min(max, v)); }

  function updateRuler(height) {
    var h = clamp(height, HEIGHT_MIN, HEIGHT_MAX);
    var frac = (h - HEIGHT_MIN) / (HEIGHT_MAX - HEIGHT_MIN);
    var topPct = (1 - frac) * 100;
    rulerMarker.style.top = topPct + '%';
  }

  function updateDial(weight) {
    var w = clamp(weight, WEIGHT_MIN, WEIGHT_MAX);
    var frac = (w - WEIGHT_MIN) / (WEIGHT_MAX - WEIGHT_MIN);
    var angle = -90 + frac * 180; // -90deg at min, +90deg at max
    dialNeedle.style.transform = 'rotate(' + angle + 'deg)';
    weightReadout.textContent = w.toFixed(1);
  }

  // Sensible defaults so the visuals aren't sitting at zero before the user types
  updateRuler(Number(heightRange.value));
  updateDial(Number(weightRange.value));

  function syncFromNumber(numInput, rangeInput, updateFn) {
    numInput.addEventListener('input', function () {
      var v = parseFloat(numInput.value);
      if (!isNaN(v)) {
        rangeInput.value = v;
        updateFn(v);
      }
      refreshCalcState();
    });
  }

  function syncFromRange(rangeInput, numInput, updateFn) {
    rangeInput.addEventListener('input', function () {
      var v = parseFloat(rangeInput.value);
      numInput.value = v;
      updateFn(v);
      refreshCalcState();
    });
  }

  syncFromNumber(heightInput, heightRange, updateRuler);
  syncFromRange(heightRange, heightInput, updateRuler);
  syncFromNumber(weightInput, weightRange, updateDial);
  syncFromRange(weightRange, weightInput, updateDial);

  function refreshCalcState() {
    var h = parseFloat(heightInput.value);
    var w = parseFloat(weightInput.value);
    var valid = !isNaN(h) && !isNaN(w) && h >= HEIGHT_MIN && h <= HEIGHT_MAX && w >= WEIGHT_MIN && w <= WEIGHT_MAX;
    calcBtn.disabled = !valid;
  }

  // ---------- BMI logic ----------
  function categorize(bmi) {
    if (bmi < 18.5) {
      return {
        cat: 'Underweight',
        desc: "Your BMI falls in the underweight range. Building strength through resistance training and eating enough to fuel your workouts can help."
      };
    } else if (bmi < 25) {
      return {
        cat: 'Normal weight',
        desc: "Your BMI falls in the normal weight range for most adults. Keep up your current activity and eating habits."
      };
    } else if (bmi < 30) {
      return {
        cat: 'Overweight',
        desc: "Your BMI falls in the overweight range. A mix of regular exercise and balanced meals can help bring it down over time."
      };
    } else {
      return {
        cat: 'Obese',
        desc: "Your BMI falls in the obese range. A gradual, sustainable routine works best here — consider talking to a healthcare provider about a plan that fits you."
      };
    }
  }

  function positionOnRangeBar(bmi) {
    // Bar spans 15 -> 35 BMI
    var min = 15, max = 35;
    var frac = clamp((bmi - min) / (max - min), 0, 1);
    return frac * 100;
  }

  calcBtn.addEventListener('click', function () {
    var height = parseFloat(heightInput.value);
    var weight = parseFloat(weightInput.value);
    if (isNaN(height) || isNaN(weight)) return;

    var meters = height / 100;
    var bmi = weight / (meters * meters);
    bmi = Math.round(bmi * 10) / 10;

    var info = categorize(bmi);

    bmiValueEl.textContent = bmi.toFixed(1);
    bmiCategoryEl.textContent = info.cat;
    bmiDescEl.textContent = info.desc;
    bmiRangeMarker.style.left = positionOnRangeBar(bmi) + '%';

    resultSection.hidden = false;
    resultSection.classList.remove('is-visible');
    void resultSection.offsetWidth;
    resultSection.classList.add('is-visible');

    nextBtn.hidden = true;
    saveStatus.textContent = 'Saving…';
    saveStatus.classList.remove('is-error');

    fetch('save-bmi.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'height=' + encodeURIComponent(height) +
            '&weight=' + encodeURIComponent(weight) +
            '&bmi=' + encodeURIComponent(bmi) +
            '&category=' + encodeURIComponent(info.cat)
    })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (data && data.success) {
          saveStatus.textContent = 'Saved to your profile.';
          nextBtn.hidden = false;
        } else {
          saveStatus.textContent = (data && data.message) ? data.message : 'Could not save right now — you can still continue.';
          saveStatus.classList.add('is-error');
          nextBtn.hidden = false; // don't block the flow on a save hiccup
        }
      })
      .catch(function () {
        saveStatus.textContent = '';
        nextBtn.hidden = false;
      });

    resultSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  });

  nextBtn.addEventListener('click', function () {
    window.location.href = '../split/split.php';
  });

})();
