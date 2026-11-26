function initClimateGlobe(opts) {
  const el = opts.el;
  const NONE = '#33443b';
  const state = { data: {}, features: {}, hover: null, selected: null, selectedCode: null,
                  scores: null, bands: [], year: null };
  const iso = d => d.properties.ADM0_A3;
  const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  // Score, colour and label for a country in the current view (a year, or the stored score)
  function info(code) {
    const r = state.data[code];
    if (!r) return null;
    if (state.year === null || !state.scores) return { score: r.score, color: r.color, label: r.label };
    const byYear = state.scores[code];
    const s = byYear ? byYear[state.year] : undefined;
    if (s === undefined) return { score: null, color: NONE, label: 'No data' };
    const b = state.bands.find(x => s <= x.max) || state.bands[state.bands.length - 1];
    return { score: s, color: b.color, label: b.label };
  }

  function tip(code, name) {
    const i = info(code);
    if (!i) return '<b>' + esc(name) + '</b><br>No data yet';
    return '<b>' + esc(name) + '</b><br>' + esc(i.label) + (i.score !== null ? ' · ' + i.score : '');
  }

  const g = Globe()(el)
    .backgroundColor('#07110c')
    .atmosphereColor('#3fcf8e')
    .atmosphereAltitude(0.18)
    .polygonsTransitionDuration(250)
    .polygonCapColor(d => {
      if (opts.plain) return '#2b4a3a';
      const i = info(iso(d));
      return i ? i.color : NONE;
    })
    .polygonSideColor(() => 'rgba(0,0,0,0.25)')
    .polygonStrokeColor(d => (d === state.selected ? '#ffffff' : '#0b1a12'))
    .polygonAltitude(d => (d === state.selected ? 0.07 : d === state.hover ? 0.035 : 0.01))
    .polygonLabel(d => (opts.plain ? '<b>' + esc(d.properties.ADMIN) + '</b>' : tip(iso(d), d.properties.ADMIN)))
    .onPolygonHover(d => { state.hover = d; refresh(); })
    .onPolygonClick(d => { if (!opts.plain) select(iso(d)); });

  g.globeMaterial().color.set('#0f2a3d');
  const calm = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  g.controls().autoRotate = opts.autoRotate !== false && !calm;  g.controls().autoRotateSpeed = 0.5;
  if (opts.zoom === false) g.controls().enableZoom = false;
  el.addEventListener('pointerdown', () => { g.controls().autoRotate = false; });

  // Re-assigning the same accessors makes Globe.gl re-evaluate them
  function refresh() {
    g.polygonAltitude(g.polygonAltitude());
    g.polygonStrokeColor(g.polygonStrokeColor());
    if (!opts.plain) { g.pointAltitude(g.pointAltitude()); g.pointRadius(g.pointRadius()); }
  }
  function recolor() {
    g.polygonCapColor(g.polygonCapColor());
    if (!opts.plain) g.pointColor(g.pointColor());
  }

  function fit() { g.width(el.clientWidth).height(el.clientHeight); }
  fit();
  new ResizeObserver(fit).observe(el);

  function select(code) {
    const f = state.features[code];
    const r = state.data[code];
    if (!f && !r) return;
    state.selected = f || null;
    state.selectedCode = code;
    refresh();
    g.controls().autoRotate = false;
    if (r && r.lat !== null) g.pointOfView({ lat: r.lat, lng: r.lng, altitude: f ? 1.6 : 1.2 }, 900);
    if (opts.onSelect) opts.onSelect(code, r, f ? f.properties.ADMIN : (r ? r.name : code));
  }

  function setYear(y) { state.year = y; recolor(); }

  const api = { globe: g, select, setYear, info, getYear: () => state.year, meta: {} };

  api.ready = Promise.all([
    fetch(opts.geoUrl).then(r => r.json()),
    fetch(opts.apiUrl).then(r => r.json()),
    opts.scoresUrl ? fetch(opts.scoresUrl).then(r => r.json()).catch(() => null) : Promise.resolve(null),
  ]).then(([geo, data, sc]) => {
    state.data = data;
    if (sc && sc.years && sc.years.length) { state.scores = sc.scores; state.bands = sc.bands; api.meta = sc; }
    geo.features.forEach(f => { state.features[iso(f)] = f; });
    g.polygonsData(geo.features);

    if (!opts.plain) {
      // Countries too small for the map outline get a coloured dot
      const extra = Object.entries(data)
        .filter(([code, r]) => !state.features[code] && r.lat !== null)
        .map(([code, r]) => ({ code, name: r.name, lat: r.lat, lng: r.lng }));
      g.pointsData(extra).pointLat('lat').pointLng('lng')
        .pointColor(d => { const i = info(d.code); return i ? i.color : NONE; })
        .pointAltitude(d => (d.code === state.selectedCode ? 0.06 : 0.02))
        .pointRadius(d => (d.code === state.selectedCode ? 0.9 : 0.55))
        .pointLabel(d => tip(d.code, d.name))
        .onPointClick(d => select(d.code));
    }
    return data;
  }).catch(err => {
    console.error(err);
    el.textContent = 'Could not load globe data.';
  });

  return api;
}
