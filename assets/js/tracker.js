(function () {
  const cfg = window.CT;
  const panel = document.getElementById('panel');
  let currentCode = null;
  let data = {};

  const mk = (tag, text, cls) => {
    const n = document.createElement(tag);
    if (text != null) n.textContent = text;
    if (cls) n.className = cls;
    return n;
  };

  function render(code, r, fallbackName) {
    currentCode = code;
    panel.replaceChildren();
    if (!r) {
      panel.append(mk('h3', fallbackName || code), mk('p', 'No climate data for this country yet.', 'mute'));
      return;
    }
    const i = api.info(code);
    const year = api.getYear();
    panel.append(mk('h3', r.name + ' – Climate Impact Overview'));
    const badge = mk('span', i.label, 'badge');
    badge.style.background = i.color;
    panel.append(badge);
    if (i.score !== null) {
      const bar = mk('div', null, 'bar');
      const fill = document.createElement('i');
      fill.style.width = i.score + '%';
      fill.style.background = i.color;
      bar.append(fill);
      panel.append(bar, mk('p', 'Score' + (year ? ' in ' + year : '') + ': ' + i.score + ' / 100'));
    } else if (year) {
      panel.append(mk('p', 'No data for ' + year + '.', 'mute'));
    }
    panel.append(mk('p', r.continent || '', 'mute'), mk('p', r.summary || 'No summary written yet.'));
    const a = mk('a', 'Full country dashboard →');
    a.href = cfg.countryUrl + encodeURIComponent(code);
    panel.append(a);
  }

  const api = initClimateGlobe({
    el: document.getElementById('globe'),
    geoUrl: cfg.geo,
    apiUrl: cfg.api,
    scoresUrl: cfg.scores,
    onSelect: render,
  });

  function setupSlider(meta) {
    const box = document.getElementById('yearBox');
    const slider = document.getElementById('yearSlider');
    const label = document.getElementById('yearLabel');
    const play = document.getElementById('playBtn');
    if (!box || !slider) return;

    slider.min = meta.years[0];
    slider.max = meta.years[meta.years.length - 1];
    slider.value = meta.base || slider.max;
    box.hidden = false;

    let timer = null;
    function apply() {
      const y = Number(slider.value);
      label.textContent = y;
      api.setYear(y);
      if (currentCode) render(currentCode, data[currentCode]);
    }
    function stop() {
      if (timer) { clearInterval(timer); timer = null; }
      play.textContent = '▶';
      play.setAttribute('aria-label', 'Play timeline');
    }
    function start() {
      if (Number(slider.value) >= Number(slider.max)) slider.value = slider.min;
      play.textContent = '⏸';
      play.setAttribute('aria-label', 'Pause timeline');
      timer = setInterval(() => {
        if (Number(slider.value) >= Number(slider.max)) { stop(); return; }
        slider.value = Number(slider.value) + 1;
        apply();
      }, 500);
    }

    slider.addEventListener('input', () => { stop(); apply(); });
    play.addEventListener('click', () => (timer ? stop() : start()));
    apply();
  }

  api.ready.then(d => {
    document.getElementById('globeLoading')?.remove();
    if (!d) return;
    data = d;
    const list = document.getElementById('countryList');
    const entries = Object.entries(d).sort((a, b) => a[1].name.localeCompare(b[1].name));
    entries.forEach(([, r]) => {
      const o = document.createElement('option');
      o.value = r.name;
      list.append(o);
    });

    const search = document.getElementById('countrySearch');
    search.addEventListener('change', () => {
      const q = search.value.trim().toLowerCase();
      const hit = entries.find(([, r]) => r.name.toLowerCase() === q);
      if (hit) api.select(hit[0]);
    });

    if (api.meta && api.meta.years && api.meta.years.length) setupSlider(api.meta);
    if (cfg.preselect && d[cfg.preselect]) api.select(cfg.preselect);
  });
})();
