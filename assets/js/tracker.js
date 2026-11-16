(function () {
    const cfg = window.CT;
    const panel = document.getElementById('panel');
    const mk = (tag, text, cls) => {
      const n = document.createElement(tag);
      if (text != null) n.textContent = text;
      if (cls) n.className = cls;
      return n;
    };
  
    function render(code, r, fallbackName) {
      panel.replaceChildren();
      if (!r) {
        panel.append(mk('h3', fallbackName || code), mk('p', 'No climate data for this country yet.', 'mute'));
        return;
      }
      panel.append(mk('h3', r.name + ' – Climate Impact Overview'));
      const badge = mk('span', r.label, 'badge');
      badge.style.background = r.color;
      panel.append(badge);
      if (r.score !== null) {
        const bar = mk('div', null, 'bar');
        const fill = document.createElement('i');
        fill.style.width = r.score + '%';
        fill.style.background = r.color;
        bar.append(fill);
        panel.append(bar, mk('p', 'Score: ' + r.score + ' / 100'));
      }
      panel.append(mk('p', r.continent, 'mute'), mk('p', r.summary || 'No summary written yet.'));
      const a = mk('a', 'Full country dashboard →');
      a.href = cfg.countryUrl + encodeURIComponent(code);
      panel.append(a);
    }
  
    const api = initClimateGlobe({
      el: document.getElementById('globe'),
      geoUrl: cfg.geo,
      apiUrl: cfg.api,
      onSelect: render,
    });
  
    api.ready.then(data => {
      if (!data) return;
      const list = document.getElementById('countryList');
      const entries = Object.entries(data).sort((a, b) => a[1].name.localeCompare(b[1].name));
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
  
      if (cfg.preselect && data[cfg.preselect]) api.select(cfg.preselect);
    });
  })();
