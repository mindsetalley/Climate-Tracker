function initClimateGlobe(opts) {
    const el = opts.el;
    const state = { data: {}, features: {}, hover: null, selected: null };
    const iso = d => d.properties.ADM0_A3;
    const rec = d => state.data[iso(d)];
    const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  
    const g = Globe()(el)
      .backgroundColor('#07110c')
      .atmosphereColor('#3fcf8e')
      .atmosphereAltitude(0.18)
      .polygonsTransitionDuration(250)
      // 1. polygonCapColor
      .polygonCapColor(d => (opts.plain ? '#2b4a3a' : rec(d) ? rec(d).color : '#33443b'))      .polygonSideColor(() => 'rgba(0,0,0,0.25)')
      .polygonStrokeColor(d => (d === state.selected ? '#ffffff' : '#0b1a12'))
      .polygonAltitude(d => (d === state.selected ? 0.07 : d === state.hover ? 0.035 : 0.01))
      // 2. polygonLabel: add this as the first line inside the arrow function
      .polygonLabel(d => {
        if (opts.plain) return '<b>' + esc(d.properties.ADMIN) + '</b>';
        const r = rec(d);
        const name = esc(r ? r.name : d.properties.ADMIN);
        const info = r ? esc(r.label) + (r.score !== null ? ' · ' + r.score : '') : 'No data yet';
        return '<b>' + name + '</b><br>' + info;
      })
      .onPolygonHover(d => { state.hover = d; refresh(); })
      // 3. onPolygonClick
      .onPolygonClick(d => { if (!opts.plain) select(iso(d)); });  
    g.globeMaterial().color.set('#0f2a3d');
    g.controls().autoRotate = opts.autoRotate !== false;
    g.controls().autoRotateSpeed = 0.5;
    if (opts.zoom === false) g.controls().enableZoom = false;
    el.addEventListener('pointerdown', () => { g.controls().autoRotate = false; });
  
    // Re-assigning the same accessors makes Globe.gl re-evaluate them
    function refresh() {
      g.polygonAltitude(g.polygonAltitude());
      g.polygonStrokeColor(g.polygonStrokeColor());
    }
  
    function fit() { g.width(el.clientWidth).height(el.clientHeight); }
    fit();
    new ResizeObserver(fit).observe(el);
  
    function select(code) {
      const f = state.features[code];
      if (!f) return;
      state.selected = f;
      refresh();
      const r = state.data[code];
      g.controls().autoRotate = false;
      if (r && r.lat !== null) g.pointOfView({ lat: r.lat, lng: r.lng, altitude: 1.6 }, 900);
      if (opts.onSelect) opts.onSelect(code, r, f.properties.ADMIN);
    }
  
    const ready = Promise.all([
      fetch(opts.geoUrl).then(r => r.json()),
      fetch(opts.apiUrl).then(r => r.json()),
    ]).then(([geo, data]) => {
      state.data = data;
      geo.features.forEach(f => { state.features[iso(f)] = f; });
      g.polygonsData(geo.features);
      return data;
    }).catch(err => {
      console.error(err);
      el.textContent = 'Could not load globe data.';
    });
  
    return { globe: g, select, ready };
  }
