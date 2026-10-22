(function () {
  'use strict';
  const $$ = (s, r) => Array.from((r || document).querySelectorAll(s));

  // RUT: formato y validación módulo 11
  function rutClean(v) { return (v || '').replace(/[^0-9kK]/g, '').toUpperCase(); }
  function rutValid(v) {
    const c = rutClean(v); if (c.length < 2) return false;
    let num = parseInt(c.slice(0, -1), 10), dv = c.slice(-1), s = 1, m = 0;
    if (isNaN(num) || num < 100000) return false;
    for (; num; num = Math.floor(num / 10)) s = (s + num % 10 * (9 - m++ % 6)) % 11;
    return (s ? String(s - 1) : 'K') === dv;
  }
  function rutFormat(v) {
    const c = rutClean(v); if (c.length < 2) return v;
    return c.slice(0, -1).replace(/\B(?=(\d{3})+(?!\d))/g, '.') + '-' + c.slice(-1);
  }
  $$('input[data-rut]').forEach(inp => {
    inp.addEventListener('blur', () => {
      if (!inp.value) { inp.setCustomValidity(''); return; }
      inp.value = rutFormat(inp.value);
      const ok = rutValid(inp.value);
      inp.setCustomValidity(ok ? '' : 'RUT inválido (dígito verificador)');
      inp.style.borderColor = ok ? '' : '#B42318';
      const msg = inp.parentElement.querySelector('.rut-msg');
      if (msg) { msg.textContent = ok ? '✔ RUT válido' : '✘ RUT inválido'; msg.style.color = ok ? '#137A4B' : '#B42318'; }
    });
  });

  // Números con separador de miles
  $$('input[data-num]').forEach(inp => {
    inp.setAttribute('inputmode', 'numeric');
    inp.addEventListener('input', () => {
      const d = inp.value.replace(/\D/g, '');
      inp.value = d ? d.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
    });
  });

  // Mostrar/ocultar
  document.addEventListener('click', e => {
    const b = e.target.closest('[data-open]');
    if (b) { const t = document.getElementById(b.dataset.open); if (t) t.classList.toggle('hidden'); }
  });

  // Confirmaciones
  $$('form[data-confirm]').forEach(f => f.addEventListener('submit', e => { if (!confirm(f.dataset.confirm)) e.preventDefault(); }));
  $$('button[data-confirm]').forEach(b => b.addEventListener('click', e => { if (!confirm(b.dataset.confirm)) e.preventDefault(); }));

  // Sección visible con inputs habilitados
  function showSection(el, on) {
    el.classList.toggle('hidden', !on);
    $$('input,select,textarea', el).forEach(i => { i.disabled = !on || i.hasAttribute('data-keep-disabled'); });
  }

  // Formulario de sociedad: régimen y poderes
  const cf = document.getElementById('companyForm');
  if (cf) {
    const upd = () => {
      const r = (cf.querySelector('input[name=regimen]:checked') || {}).value;
      $$('.reg-block', cf).forEach(b => b.classList.toggle('hidden', b.dataset.reg !== r));
      const p = cf.querySelector('#poderesFuente').value;
      $$('.pod', cf).forEach(l => l.classList.toggle('hidden', p && !l.classList.contains('pod-' + p)));
    };
    cf.addEventListener('change', upd); upd();
    $$('input[type=color][data-sync]', cf).forEach(c => {
      const t = document.getElementById(c.dataset.sync);
      c.addEventListener('input', () => { t.value = c.value.toUpperCase(); });
      t.addEventListener('input', () => { if (/^#[0-9a-fA-F]{6}$/.test(t.value)) c.value = t.value; });
    });
  }

  // Formulario de persona: natural / jurídica
  const pf = document.getElementById('personForm');
  if (pf) {
    const sel = pf.querySelector('[name=tipo]');
    const upd = () => {
      const t = sel.value;
      $$('.t-natural', pf).forEach(s => showSection(s, t === 'NATURAL'));
      $$('.t-juridica', pf).forEach(s => showSection(s, t === 'JURIDICA'));
    };
    if (sel) { sel.addEventListener('change', upd); upd(); }
  }

  // Beneficiarios finales
  const addBen = document.getElementById('addBen');
  if (addBen) addBen.addEventListener('click', () => {
    const tb = document.querySelector('#benTable tbody'); const i = tb.rows.length;
    const tr = document.createElement('tr');
    tr.innerHTML = '<td><input name="ben_nombre[]"></td><td><input name="ben_rut[]"></td><td><input name="ben_pct[]" style="width:80px"></td><td><input type="checkbox" name="ben_pep[' + i + ']" value="1"></td>';
    tb.appendChild(tr);
  });

  // Series de acciones (alta de sociedad)
  const addSerie = document.getElementById('addSerie');
  if (addSerie) addSerie.addEventListener('click', () => {
    const tb = document.querySelector('#seriesTable tbody');
    const letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const tr = document.createElement('tr');
    tr.innerHTML = '<td><input name="serie_nombre[]" maxlength="60" value="Serie ' + (letters[tb.rows.length] || '') + '"></td><td><input name="serie_votos[]" value="1"></td><td><input name="serie_autorizadas[]" inputmode="numeric"></td><td><input name="serie_preferencias[]"></td><td><button type="button" class="btn sm ghost" data-del-row title="Quitar">✕</button></td>';
    tb.appendChild(tr);
    const n = tr.querySelector('input[name="serie_autorizadas[]"]');
    n.addEventListener('input', () => { const d = n.value.replace(/\D/g, ''); n.value = d ? d.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''; });
  });
  document.addEventListener('click', e => {
    const b = e.target.closest('[data-del-row]');
    if (b) { const tb = b.closest('tbody'); if (tb.rows.length > 1) b.closest('tr').remove(); }
  });

  // Movimiento de acciones
  const mf = document.getElementById('movForm');
  if (mf) {
    const vend = mf.querySelector('#vendSel'), serie = mf.querySelector('#serieSel'), info = mf.querySelector('#vendSaldo');
    const upd = () => {
      const t = (mf.querySelector('input[name=tipo]:checked') || {}).value;
      $$('.only-transfer', mf).forEach(s => showSection(s, t === 'TRANSFERENCIA'));
      $$('.only-emision', mf).forEach(s => { s.classList.toggle('hidden', t !== 'EMISION'); });
      if (vend) vend.required = t === 'TRANSFERENCIA';
      if (vend && info) {
        const o = vend.selectedOptions[0];
        if (o && o.dataset.bal) {
          const b = JSON.parse(o.dataset.bal); const q = b[serie.value] || 0;
          info.textContent = 'Saldo en la serie seleccionada: ' + String(q).replace(/\B(?=(\d{3})+(?!\d))/g, '.') + ' acciones';
        } else info.textContent = '';
        const gw = mf.querySelector('#vendGrav');
        if (gw) {
          const o2 = vend.selectedOptions[0];
          const g = o2 && o2.dataset.grav ? JSON.parse(o2.dataset.grav) : {};
          const gq = Array.isArray(g) ? 0 : (g[serie.value] || 0);
          gw.textContent = (t === 'TRANSFERENCIA' && gq) ? '⚠ Tiene gravámenes vigentes sobre ' + String(gq).replace(/\B(?=(\d{3})+(?!\d))/g, '.') + ' acciones de esta serie. La venta no se bloquea: revise el registro de gravámenes.' : '';
        }
      }
      const rp = mf.querySelector('#resPend');
      if (rp) $$('.cert-comp', mf).forEach(l => { l.classList.toggle('hidden', rp.checked); $$('input', l).forEach(i => { i.disabled = rp.checked; }); });
    };
    mf.addEventListener('change', upd); upd();
  }
})();
