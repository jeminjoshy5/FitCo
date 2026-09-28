(function () {
  "use strict";

  var EXERCISES = window.EXERCISES || {};
  var PART_LABELS = window.PART_LABELS || {};
  var SPLIT_DEFS = window.SPLIT_DEFS || {};

  var splitTypeSelect   = document.getElementById('splitType');
  var customPicker      = document.getElementById('customPicker');
  var customCheckboxes  = document.getElementById('customCheckboxes');
  var sectionsContainer = document.getElementById('sectionsContainer');
  var saveRow           = document.getElementById('saveRow');
  var saveSplitBtn      = document.getElementById('saveSplitBtn');
  var saveStatus        = document.getElementById('saveStatus');
  var builderCard       = document.getElementById('builder');
  var successCard       = document.getElementById('successCard');
  var successSummary    = document.getElementById('successSummary');

  var overviewPanel     = document.getElementById('overviewPanel');
  var overviewSub       = document.getElementById('overviewSub');
  var overviewStats     = document.getElementById('overviewStats');
  var overviewBars      = document.getElementById('overviewBars');

  // part -> array of exercise names currently selected
  var selections = {};

  function partLabel(part) {
    return PART_LABELS[part] || part;
  }

  // ---------- Custom body-part checkboxes ----------
  (function buildCustomCheckboxes() {
    Object.keys(PART_LABELS).forEach(function (part) {
      var wrap = document.createElement('label');
      wrap.className = 'checkbox-pill';
      wrap.innerHTML =
        '<input type="checkbox" value="' + part + '"> <span>' + partLabel(part) + '</span>';
      customCheckboxes.appendChild(wrap);

      var input = wrap.querySelector('input');
      input.addEventListener('change', function () {
        wrap.classList.toggle('is-checked', input.checked);
        if (!input.checked) {
          delete selections[part];
        }
        renderCustomSections();
      });
    });
  })();

  function checkedCustomParts() {
    return Array.prototype.slice
      .call(customCheckboxes.querySelectorAll('input:checked'))
      .map(function (i) { return i.value; });
  }

  // ---------- Split type switch ----------
  splitTypeSelect.addEventListener('change', function () {
    var value = splitTypeSelect.value;
    selections = {};
    sectionsContainer.innerHTML = '';
    overviewPanel.hidden = true;

    if (value === 'custom') {
      customPicker.hidden = false;
      Array.prototype.forEach.call(customCheckboxes.querySelectorAll('input'), function (i) {
        i.checked = false;
        i.closest('.checkbox-pill').classList.remove('is-checked');
      });
      saveRow.hidden = true;
    } else {
      customPicker.hidden = true;
      var days = SPLIT_DEFS[value] || [];
      days.forEach(function (dayDef) {
        sectionsContainer.appendChild(buildDayGroup(dayDef.day, dayDef.parts));
      });
      saveRow.hidden = false;
      refreshSaveState();
      refreshOverview();
    }
  });

  function renderCustomSections() {
    var parts = checkedCustomParts();
    sectionsContainer.innerHTML = '';
    if (parts.length === 0) {
      saveRow.hidden = true;
      overviewPanel.hidden = true;
      return;
    }
    sectionsContainer.appendChild(buildDayGroup('Your Split', parts));
    saveRow.hidden = false;
    refreshSaveState();
    refreshOverview();
  }

  // ---------- Building sections ----------
  function buildDayGroup(title, parts) {
    var group = document.createElement('div');
    group.className = 'day-group';

    var heading = document.createElement('div');
    heading.className = 'day-group-title';
    heading.textContent = title;
    group.appendChild(heading);

    parts.forEach(function (part) {
      group.appendChild(buildPartSection(part));
    });

    return group;
  }

  function buildPartSection(part) {
    if (!selections[part]) selections[part] = [];

    var section = document.createElement('div');
    section.className = 'part-section';
    section.setAttribute('data-part', part);

    section.innerHTML =
      '<div class="part-head">' +
        '<h3>' + partLabel(part) + '</h3>' +
        '<span class="part-count" data-role="count">0 selected</span>' +
      '</div>' +
      '<div class="search-row">' +
        '<input type="text" class="exercise-search" placeholder="Search ' + partLabel(part).toLowerCase() + ' exercises…" autocomplete="off">' +
        '<div class="search-dropdown" hidden data-role="dropdown"></div>' +
      '</div>' +
      '<div class="chip-list" data-role="chips"></div>' +
      '<div class="distribution" data-role="distribution" hidden>' +
        '<span class="distribution-label">Muscle engagement</span>' +
        '<div class="dist-bars" data-role="dist-bars"></div>' +
      '</div>';

    var searchInput = section.querySelector('.exercise-search');
    var dropdown = section.querySelector('[data-role="dropdown"]');

    function availableExercises(query) {
      var list = EXERCISES[part] || [];
      var q = (query || '').trim().toLowerCase();
      return list.filter(function (ex) {
        if (selections[part].indexOf(ex.name) !== -1) return false;
        if (!q) return true;
        return ex.name.toLowerCase().indexOf(q) !== -1;
      });
    }

    function showDropdown(query) {
      var matches = availableExercises(query).slice(0, 8);
      dropdown.innerHTML = '';
      if (matches.length === 0) {
        var empty = document.createElement('div');
        empty.className = 'search-dropdown-empty';
        empty.textContent = query ? 'No matching exercises.' : 'All exercises added.';
        dropdown.appendChild(empty);
      } else {
        matches.forEach(function (ex) {
          var item = document.createElement('div');
          item.className = 'search-dropdown-item';
          item.textContent = ex.name;
          item.addEventListener('mousedown', function (e) {
            e.preventDefault();
            addExercise(part, ex.name, section);
            searchInput.value = '';
            dropdown.hidden = true;
          });
          dropdown.appendChild(item);
        });
      }
      dropdown.hidden = false;
    }

    searchInput.addEventListener('focus', function () { showDropdown(searchInput.value); });
    searchInput.addEventListener('input', function () { showDropdown(searchInput.value); });
    searchInput.addEventListener('blur', function () {
      setTimeout(function () { dropdown.hidden = true; }, 120);
    });

    renderChips(part, section);
    renderDistribution(part, section);

    return section;
  }

  function addExercise(part, name, section) {
    if (selections[part].indexOf(name) !== -1) return;
    selections[part].push(name);
    renderChips(part, section);
    renderDistribution(part, section);
    refreshSaveState();
    refreshOverview();
  }

  function removeExercise(part, name, section) {
    selections[part] = selections[part].filter(function (n) { return n !== name; });
    renderChips(part, section);
    renderDistribution(part, section);
    refreshSaveState();
    refreshOverview();
  }

  function renderChips(part, section) {
    var chipList = section.querySelector('[data-role="chips"]');
    var countEl = section.querySelector('[data-role="count"]');
    chipList.innerHTML = '';

    selections[part].forEach(function (name) {
      var chip = document.createElement('span');
      chip.className = 'chip';
      chip.innerHTML = '<span>' + name + '</span>';
      var removeBtn = document.createElement('button');
      removeBtn.type = 'button';
      removeBtn.className = 'chip-remove';
      removeBtn.setAttribute('aria-label', 'Remove ' + name);
      removeBtn.textContent = '\u00D7';
      removeBtn.addEventListener('click', function () { removeExercise(part, name, section); });
      chip.appendChild(removeBtn);
      chipList.appendChild(chip);
    });

    var n = selections[part].length;
    countEl.textContent = n + (n === 1 ? ' selected' : ' selected');
  }

  function renderDistribution(part, section) {
    var wrap = section.querySelector('[data-role="distribution"]');
    var barsWrap = section.querySelector('[data-role="dist-bars"]');
    var list = EXERCISES[part] || [];
    var names = selections[part];

    if (names.length === 0) {
      wrap.hidden = true;
      barsWrap.innerHTML = '';
      return;
    }

    var totals = {};
    names.forEach(function (name) {
      var ex = list.filter(function (e) { return e.name === name; })[0];
      if (!ex) return;
      Object.keys(ex.engagement).forEach(function (muscle) {
        totals[muscle] = (totals[muscle] || 0) + ex.engagement[muscle];
      });
    });

    // Average across selected exercises so the mix reads as "how this day trains you",
    // not a total that balloons with more exercises added.
    var muscles = Object.keys(totals).map(function (muscle) {
      return { muscle: muscle, pct: totals[muscle] / names.length };
    });
    muscles.sort(function (a, b) { return b.pct - a.pct; });

    barsWrap.innerHTML = '';
    muscles.forEach(function (m) {
      var row = document.createElement('div');
      row.className = 'dist-row';
      var pct = Math.min(100, Math.round(m.pct));
      row.innerHTML =
        '<span class="dist-row-label">' + m.muscle + '</span>' +
        '<span class="dist-row-track"><span class="dist-row-fill" style="width:' + pct + '%"></span></span>' +
        '<span class="dist-row-pct">' + pct + '%</span>';
      barsWrap.appendChild(row);
    });

    wrap.hidden = false;
  }

  // ---------- Overall split analysis ----------
  function totalSelected() {
    return Object.keys(selections).reduce(function (sum, part) {
      return sum + selections[part].length;
    }, 0);
  }

  function refreshOverview() {
    var partsUsed = Object.keys(selections).filter(function (p) { return selections[p].length > 0; });
    var exerciseCount = totalSelected();

    if (exerciseCount === 0) {
      overviewPanel.hidden = true;
      return;
    }

    var setCount = exerciseCount * 3; // assume ~3 working sets per exercise, for a rough weekly-volume sense
    overviewStats.innerHTML = '';
    [
      { label: 'Exercises', value: exerciseCount },
      { label: 'Body parts', value: partsUsed.length },
      { label: 'Est. weekly sets', value: setCount }
    ].forEach(function (stat) {
      var card = document.createElement('div');
      card.className = 'overview-stat';
      card.innerHTML =
        '<span class="overview-stat-value">' + stat.value + '</span>' +
        '<span class="overview-stat-label">' + stat.label + '</span>';
      overviewStats.appendChild(card);
    });

    overviewSub.textContent = partsUsed.map(partLabel).join(', ');

    // Aggregate engagement across every selected exercise, across every body part,
    // so this reads as "how does the whole split train me" rather than per-part.
    var totals = {};
    var namesCount = 0;
    partsUsed.forEach(function (part) {
      var list = EXERCISES[part] || [];
      selections[part].forEach(function (name) {
        var ex = list.filter(function (e) { return e.name === name; })[0];
        if (!ex) return;
        namesCount++;
        Object.keys(ex.engagement).forEach(function (muscle) {
          totals[muscle] = (totals[muscle] || 0) + ex.engagement[muscle];
        });
      });
    });

    var muscles = Object.keys(totals).map(function (muscle) {
      return { muscle: muscle, pct: totals[muscle] / (namesCount || 1) };
    });
    muscles.sort(function (a, b) { return b.pct - a.pct; });
    muscles = muscles.slice(0, 8);
    var maxPct = muscles.length ? muscles[0].pct : 1;

    overviewBars.innerHTML = '';
    muscles.forEach(function (m) {
      var row = document.createElement('div');
      row.className = 'dist-row';
      var widthPct = Math.min(100, Math.round((m.pct / maxPct) * 100));
      row.innerHTML =
        '<span class="dist-row-label">' + m.muscle + '</span>' +
        '<span class="dist-row-track"><span class="dist-row-fill" style="width:' + widthPct + '%"></span></span>' +
        '<span class="dist-row-pct">' + Math.round(m.pct) + '%</span>';
      overviewBars.appendChild(row);
    });

    overviewPanel.hidden = false;
  }

  function refreshSaveState() {
    saveSplitBtn.disabled = totalSelected() === 0;
    if (saveStatus.textContent && !saveStatus.classList.contains('is-error')) {
      saveStatus.textContent = '';
    }
  }
  saveSplitBtn.disabled = true;

  saveSplitBtn.addEventListener('click', function () {
    var splitType = splitTypeSelect.value;
    var payload = { split_type: splitType, exercises: {} };
    var partCount = 0, exerciseCount = 0;

    Object.keys(selections).forEach(function (part) {
      if (selections[part].length > 0) {
        payload.exercises[part] = selections[part];
        partCount++;
        exerciseCount += selections[part].length;
      }
    });

    if (exerciseCount === 0) {
      saveStatus.textContent = 'Add at least one exercise before saving.';
      saveStatus.classList.add('is-error');
      return;
    }

    saveSplitBtn.disabled = true;
    saveStatus.classList.remove('is-error');
    saveStatus.textContent = 'Saving…';

    fetch('save-split.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (data && data.success) {
          builderCard.hidden = true;
          successCard.hidden = false;
          successSummary.textContent =
            exerciseCount + (exerciseCount === 1 ? ' exercise' : ' exercises') +
            ' across ' + partCount + (partCount === 1 ? ' body part' : ' body parts') +
            ' — saved to your ' + splitLabel(splitType) + ' split.';
          successCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } else {
          saveStatus.textContent = (data && data.message) ? data.message : 'Could not save your split. Please try again.';
          saveStatus.classList.add('is-error');
          saveSplitBtn.disabled = false;
        }
      })
      .catch(function () {
        saveStatus.textContent = 'Could not reach the server. Please try again.';
        saveStatus.classList.add('is-error');
        saveSplitBtn.disabled = false;
      });
  });

  function splitLabel(value) {
    if (value === 'ppl') return 'Push / Pull / Legs';
    if (value === 'bro') return 'Bro Split';
    if (value === 'custom') return 'Custom';
    return value;
  }

})();
