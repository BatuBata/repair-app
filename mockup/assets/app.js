// Perilaku mockup: kerangka per peran, render dari data contoh, filter, format uang, baris rincian, dialog, toast.
(function () {
  const $ = (s, el = document) => el.querySelector(s);
  const $$ = (s, el = document) => Array.from(el.querySelectorAll(s));
  const icon = (n, c = "icon") => `<svg class="${c}" aria-hidden="true"><use href="#i-${n}"></use></svg>`;
  const rp = (v) => "Rp" + Math.round(v).toLocaleString("id-ID");
  const D = window.DATA;
  // Grafik batang harian untuk Laporan: satu warna teal (muda = hari lalu, pekat = hari ini), garis rata-rata, semua tanggal
  window.dailyBars = (el, days, { avg, today, rp }) => {
    const NS = "http://www.w3.org/2000/svg", mk = (t, a, p) => { const n = document.createElementNS(NS, t); for (const k in a) n.setAttribute(k, a[k]); p && p.appendChild(n); return n; };
    const short = (v) => (v >= 1e6 ? (v / 1e6).toLocaleString("id-ID", { maximumFractionDigits: 1 }) + " jt" : v >= 1e3 ? Math.round(v / 1e3) + " rb" : String(v));
    const draw = () => {
      el.innerHTML = "";
      const W = el.clientWidth, H = Math.max(200, el.clientHeight || 240), padT = 16, padB = 30;
      const max = Math.max(...days.map((d) => d.omzet)), step = 500000, top = Math.ceil((max + 1) / step) * step;
      const ticks = []; for (let v = 0; v <= top; v += step) ticks.push(v);
      const padL = 48, plotW = W - padL, band = plotW / days.length, bw = Math.min(28, band * 0.5);
      const y = (v) => padT + (H - padT - padB) * (1 - v / top), x = (i) => padL + band * i + band / 2;
      const svg = mk("svg", { width: W, height: H, viewBox: `0 0 ${W} ${H}`, role: "img", "aria-label": `Omzet per hari 1 sampai ${days.length} November, tertinggi ${rp(max)}` }, el);
      const hover = mk("rect", { x: 0, y: padT - 6, width: band, height: H - padT - padB + 6, rx: 6, fill: "#f5f7f8", opacity: 0 }, svg);
      ticks.forEach((v) => {
        mk("line", { x1: padL, x2: W, y1: y(v), y2: y(v), stroke: v === 0 ? "#cfd4d7" : "#eef1f2", "shape-rendering": "crispEdges" }, svg);
        const t = mk("text", { x: padL - 10, y: y(v) + 4, "text-anchor": "end", "font-size": 12, fill: "#5d5f60" }, svg); t.textContent = v === 0 ? "0" : short(v);
      });
      days.forEach((d, i) => {
        const isToday = d.d === today, h = y(0) - y(d.omzet), r = Math.min(3, h);
        if (d.omzet > 0) mk("path", { d: `M${x(i) - bw / 2} ${y(0)}V${y(d.omzet) + r}q0 -${r} ${r} -${r}h${bw - 2 * r}q${r} 0 ${r} ${r}V${y(0)}Z`, fill: isToday ? "#1b6c80" : "#a9cbd3" }, svg);
        const t = mk("text", { x: x(i), y: H - 10, "text-anchor": "middle", "font-size": 12, fill: isToday ? "#131416" : "#5d5f60", "font-weight": isToday ? 600 : 400 }, svg); t.textContent = d.d;
      });
      // garis rata-rata
      mk("line", { x1: padL, x2: W, y1: y(avg), y2: y(avg), stroke: "#131416", "stroke-width": 1, "stroke-dasharray": "3 4", opacity: 0.55 }, svg);
      // tooltip
      const tip = document.createElement("div"); tip.className = "chart-tip"; el.appendChild(tip);
      const hit = mk("rect", { x: padL, y: 0, width: plotW, height: H, fill: "transparent" }, svg);
      const show = (ev) => {
        const b = svg.getBoundingClientRect(), i = Math.max(0, Math.min(days.length - 1, Math.floor((ev.clientX - b.left - padL) / band))), d = days[i];
        hover.setAttribute("x", padL + band * i); hover.setAttribute("opacity", 1);
        tip.innerHTML = `<div class="t">${d.d} Nov${d.d === today ? " · hari ini" : ""}</div><div class="v">${rp(d.omzet)}</div><div class="s">Servis ${rp(d.servis)}<br>Penjualan ${rp(d.jual)}</div>`;
        const left = x(i) + bw / 2 + 8; tip.style.left = (left + tip.offsetWidth > W ? x(i) - bw / 2 - 8 - tip.offsetWidth : left) + "px"; tip.style.top = "8px"; tip.style.opacity = 1;
      };
      hit.addEventListener("pointermove", show); hit.addEventListener("pointerdown", show);
      hit.addEventListener("pointerleave", () => { tip.style.opacity = 0; hover.setAttribute("opacity", 0); });
    };
    draw(); let rw; new ResizeObserver(() => { cancelAnimationFrame(rw); rw = requestAnimationFrame(draw); }).observe(el);
  };
  const MARK = `<svg class="brand-mark" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M4 5h16a2 2 0 0 1 2 2v3a2 2 0 0 0 0 4v3a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-3a2 2 0 0 0 0-4V7a2 2 0 0 1 2-2z"/><path d="M15.5 6.5v11" stroke="#fff" stroke-width="1.5" stroke-dasharray="1.6 1.9" stroke-linecap="round"/></svg>`;
  const PEOPLE = { "Pak Arif": "#d7ebef", Rizky: "#dbe6f7", Dimas: "#f7e7cf", Agus: "#dcefdc" };
  const avatar = (name) => `<span class="avatar" style="background:${PEOPLE[name] || "#ebedee"}">${name.replace("Pak ", "")[0]}</span>`;

  if (window.__ICONS__) document.body.insertAdjacentHTML("afterbegin", window.__ICONS__);

  const params = new URLSearchParams(location.search);
  const body = document.body;
  const role = params.get("peran") || body.dataset.role;
  const active = body.dataset.nav;
  if (role) body.dataset.role = role;
  $$("[data-role-only]").forEach((el) => { if (el.dataset.roleOnly !== role) el.remove(); });
  $$("a[data-keep-role]").forEach((a) => { if (role === "teknisi") a.href += (a.href.includes("?") ? "&" : "?") + "peran=teknisi"; });

  const NAV = {
    admin: {
      side: [
        ["dashboard", "Beranda", "layout-dashboard", "dashboard.html"],
        ["tts", "Servis", "clipboard-list", "tts.html"],
        ["penjualan", "Penjualan", "shopping-bag", "#"],
        ["stok", "Stok barang", "boxes", "#"],
        ["pengeluaran", "Pengeluaran", "wallet", "#"],
        ["laporan", "Laporan", "chart-column", "laporan.html"],
      ],
      bottom: [["dashboard", "Beranda", "layout-dashboard", "dashboard.html"], ["tts", "Servis", "clipboard-list", "tts.html"], ["buat", "Buat TTS", "plus", "tts-baru.html?peran=admin", 1], ["laporan", "Laporan", "chart-column", "laporan.html"], ["menu", "Menu", "menu", "#"]],
      me: ["Pak Arif", "Admin"],
    },
    teknisi: {
      side: [["buat", "Buat TTS", "plus", "tts-baru.html"], ["tts", "Daftar TTS", "clipboard-list", "tts.html?peran=teknisi"]],
      bottom: [["buat", "Buat TTS", "plus", "tts-baru.html"], ["tts", "Daftar TTS", "clipboard-list", "tts.html?peran=teknisi"], ["profil", "Profil", "user-round", "#"]],
      me: ["Rizky", "Teknisi"],
    },
  };
  const nav = NAV[role];
  const app = $(".app");
  if (nav && app && !body.classList.contains("no-shell")) {
    const cur = (id) => (id === active ? ' aria-current="page"' : "");
    app.insertAdjacentHTML("afterbegin",
      `<nav class="sidebar" aria-label="Menu utama">
        <a class="brand" href="index.html">${MARK}<strong>Anugerah</strong></a>
        <div class="scope">${icon("store")}<span>Toko Anugerah</span></div>
        ${nav.side.map(([id, l, i, h]) => `<a class="nav-item" href="${h}"${cur(id)}>${icon(i)}${l}</a>`).join("")}
        <div class="bottom">
          ${role === "admin" ? `<a class="nav-item" href="#">${icon("settings")}Pengaturan</a>` : `<a class="nav-item" href="#">${icon("user-round")}Profil</a>`}
          <div class="me">${avatar(nav.me[0])}<div><strong>${nav.me[0]}</strong><span>${nav.me[1]}</span></div></div>
        </div>
      </nav>`);
    $(".main").insertAdjacentHTML("afterbegin",
      `<header class="topbar">
        <a class="phone-brand" href="index.html">${MARK}<span>Anugerah</span></a>
        <label class="search"><span class="sr-only">Cari</span>${icon("search")}<input type="search" placeholder="Cari nomor TTS, nama, atau HP"><kbd>/</kbd></label>
        <a class="icon-btn phone-only" href="#" aria-label="Akun ${nav.me[0]}">${avatar(nav.me[0])}</a>
      </header>`);
    body.insertAdjacentHTML("beforeend",
      `<nav class="bottomnav" aria-label="Menu utama">${nav.bottom.map(([id, l, i, h, c]) => c
        ? `<a class="create" href="${h}"${cur(id)}><span class="bubble">${icon(i)}</span>${l}</a>`
        : `<a href="${h}"${cur(id)}>${icon(i)}${l}</a>`).join("")}</nav>`);
  }

  // Toast
  body.insertAdjacentHTML("beforeend", `<div class="toast" role="status"></div>`);
  const toast = $(".toast");
  let t;
  const say = (m) => { toast.innerHTML = icon("info") + `<span>${m}</span>`; toast.classList.add("show"); clearTimeout(t); t = setTimeout(() => toast.classList.remove("show"), 2600); };
  document.addEventListener("click", (e) => {
    const tb = e.target.closest("[data-toast]");
    if (tb) { e.preventDefault(); say(tb.dataset.toast); return; }
    const a = e.target.closest('a[href="#"]');
    if (a) { e.preventDefault(); say("Layar ini belum termasuk mockup."); }
  });

  // ---------- Aksi baris: 1 aksi = tombol, lebih dari 1 = menu pilihan ----------
  let menuSeq = 0;
  const item = (it) => it.href
    ? `<a role="menuitem" href="${it.href}"${it.danger ? ' class="danger"' : ""}${it.toast ? ` data-toast="${it.toast}"` : ""}>${icon(it.icon)}${it.label}</a>`
    : `<button role="menuitem" type="button"${it.danger ? ' class="danger"' : ""} data-toast="${it.toast}">${icon(it.icon)}${it.label}</button>`;
  const actions = (subject, items) => {
    if (items.length === 1) {
      const it = items[0];
      return it.href ? `<a class="btn sm act-btn text" href="${it.href}">${it.label}</a>` : `<button class="btn sm act-btn text" type="button" data-toast="${it.toast}">${it.label}</button>`;
    }
    const id = "m" + ++menuSeq;
    return `<button class="btn sm act-btn" type="button" aria-haspopup="menu" aria-expanded="false" aria-controls="${id}" aria-label="Pilihan untuk ${subject}">${icon("ellipsis")}</button>` +
      `<div class="menu" role="menu" id="${id}" hidden>${items.map((it) => (it === "-" ? "<hr>" : item(it))).join("")}</div>`;
  };
  window.rowActions = actions;
  let openMenu = null, openBtn = null;
  const closeMenu = (focus) => { if (!openMenu) return; openMenu.hidden = true; openBtn.setAttribute("aria-expanded", "false"); if (focus) openBtn.focus(); openMenu = openBtn = null; };
  const placeMenu = (b, m) => {
    const r = b.getBoundingClientRect(); m.style.left = "0px"; m.style.top = "0px";
    let top = r.bottom + 4; if (top + m.offsetHeight > innerHeight - 8) top = Math.max(8, r.top - m.offsetHeight - 4);
    m.style.top = top + "px"; m.style.left = Math.max(8, Math.min(r.left, innerWidth - m.offsetWidth - 8)) + "px";
  };
  document.addEventListener("click", (e) => {
    const b = e.target.closest(".act-btn[aria-haspopup]");
    if (b) {
      e.preventDefault(); e.stopPropagation();
      const m = document.getElementById(b.getAttribute("aria-controls"));
      if (openMenu === m) return closeMenu();
      closeMenu(); if (m.parentElement !== document.body) document.body.appendChild(m); m.hidden = false; placeMenu(b, m); b.setAttribute("aria-expanded", "true"); openMenu = m; openBtn = b;
      m.querySelector('[role="menuitem"]')?.focus();
      return;
    }
    if (openMenu && e.target.closest('.menu [role="menuitem"]')) setTimeout(() => closeMenu(), 0);
    else if (openMenu && !e.target.closest(".menu")) closeMenu();
  }, true);
  document.addEventListener("keydown", (e) => {
    if (!openMenu) return;
    const items = $$('[role="menuitem"]', openMenu), i = items.indexOf(document.activeElement);
    if (e.key === "Escape") { e.preventDefault(); closeMenu(true); }
    else if (e.key === "ArrowDown") { e.preventDefault(); items[(i + 1) % items.length].focus(); }
    else if (e.key === "ArrowUp") { e.preventDefault(); items[(i - 1 + items.length) % items.length].focus(); }
    else if (e.key === "Tab") closeMenu();
  });
  addEventListener("scroll", () => closeMenu(), true);
  addEventListener("resize", () => closeMenu());

  // ---------- Daftar servis ----------
  const listEl = $("#tts-list");
  if (listEl) {
    const showPrice = role === "admin";
    const st = { diterima: "Diterima", dikerjakan: "Dikerjakan", selesai: "Selesai", diambil: "Diambil", batal: "Batal" };
    const detailHref = (x) => x.no === "TTS-2611-011" ? "tracking.html" : "tts-detail.html" + (role === "teknisi" ? "?peran=teknisi" : "");
    $("#tts-head").innerHTML = `<tr><th class="act"><span class="sr-only">Aksi</span></th><th>Nomor</th><th>Customer</th><th>Barang &amp; keluhan</th><th>Teknisi</th><th>Masuk</th>${showPrice ? '<th class="n">Total</th>' : ""}<th>Status</th></tr>`;
    const det = role === "teknisi" ? "tts-detail.html?peran=teknisi" : "tts-detail.html";
    const wa = (x, what) => ({ label: "Kirim WA", icon: "message-circle", href: "#", toast: `Membuka WhatsApp: ${what} ${x.no} untuk ${x.nama}…` });
    const acts = (x) => {
      if (role === "teknisi") return x.status === "diterima"
        ? [{ label: "Lihat detail", icon: "file-text", href: det }, { label: "Cetak struk TTS", icon: "printer", href: "struk.html" }]
        : [{ label: "Lihat", href: det }];
      return {
        diterima: [{ label: "Tetapkan teknisi", icon: "user-check", href: det }, wa(x, "tanda terima"), { label: "Cetak struk TTS", icon: "printer", href: "struk.html" }, "-", { label: "Batalkan TTS", icon: "ban", href: "#", toast: `${x.no} dibatalkan (contoh).`, danger: 1 }],
        dikerjakan: [{ label: "Isi rincian & nota", icon: "receipt", href: "tts-rincian.html" }, { label: "Lihat detail", icon: "file-text", href: det }, wa(x, "kabar"), "-", { label: "Batalkan TTS", icon: "ban", href: "#", toast: `${x.no} dibatalkan (contoh).`, danger: 1 }],
        selesai: [{ label: "Kabari siap diambil", icon: "message-circle", href: "#", toast: `Membuka WhatsApp: ${x.no} siap diambil…` }, { label: "Tandai sudah diambil", icon: "package-check", href: "#", toast: `${x.no} ditandai diambil (contoh).` }, { label: "Lihat nota", icon: "file-text", href: "nota-a5.html" }],
        diambil: [{ label: "Lihat nota", icon: "file-text", href: "nota-a5.html" }, { label: "Unduh PDF", icon: "download", href: "#", toast: `Mengunduh nota ${x.no}.pdf (contoh)…` }],
        batal: [{ label: "Lihat", href: det }],
      }[x.status];
    };
    listEl.innerHTML = D.tts.map((x) => {
      const total = x.lines ? rp(D.jualOf(x)) : null;
      return `<tr data-status="${x.status}">
        <td class="act">${actions(x.no, acts(x))}</td>
        <td class="c-id"><a class="rowlink id" href="${detailHref(x)}">${x.no}</a></td>
        <td class="c-who"><span class="who">${x.nama}</span></td>
        <td class="c-what">${x.kat} · ${x.keluhan}</td>
        <td class="c-meta">${x.teknisi || '<span class="dash">Belum ditetapkan</span>'}</td>
        <td class="c-meta num desktop-only">${x.masuk[0]} Nov</td>
        ${showPrice ? `<td class="c-amt n${total ? "" : " dash"}">${total || "—"}</td>` : ""}
        <td class="c-st"><span class="pill st-${x.status}">${st[x.status]}</span></td>
      </tr>`;
    }).join("");
    const tabs = $("#tts-tabs");
    const counts = D.counts;
    tabs.innerHTML = [["semua", "Semua", D.tts.length], ...Object.entries(st).map(([k, l]) => [k, l, counts[k] || 0])]
      .map(([k, l, n], i) => `<button type="button" role="tab" data-value="${k}" aria-selected="${i === 0}">${l}<span class="n">${n}</span></button>`).join("");
    const empty = $("#tts-empty"), total = $("#tts-total"), q = $("#tts-q");
    let filter = params.get("status") || "semua";
    const apply = () => {
      const term = q.value.trim().toLowerCase();
      let shown = 0;
      $$("tr", listEl).forEach((tr) => {
        const ok = (filter === "semua" || tr.dataset.status === filter) && (!term || tr.textContent.toLowerCase().includes(term));
        tr.hidden = !ok; if (ok) shown++;
      });
      $$("button", tabs).forEach((b) => b.setAttribute("aria-selected", String(b.dataset.value === filter)));
      empty.hidden = shown > 0;
      total.textContent = `${shown} TTS`;
    };
    tabs.addEventListener("click", (e) => { const b = e.target.closest("button"); if (b) { filter = b.dataset.value; apply(); } });
    q.addEventListener("input", apply);
    apply();
  }

  // ---------- Beranda ----------
  const lane = $("#lane");
  if (lane) {
    const by = (s) => D.tts.filter((x) => x.status === s);
    const age = (d) => D.TODAY - d;
    const tags = (rows, f) => rows.map((x) => { const a = age(f(x)); return `<span class="tag${a >= 2 ? " late" : ""}"><b>${x.no.slice(-3)}</b>${a === 0 ? "hari ini" : a + " hari"}</span>`; }).join("");
    const cells = [
      ["diterima", "Belum ada teknisi", by("diterima"), (x) => x.masuk[0], "perlu ditetapkan"],
      ["dikerjakan", "Dikerjakan", by("dikerjakan"), (x) => x.sejak, ""],
      ["selesai", "Siap diambil", by("selesai"), (x) => x.nota, "perlu dikabari"],
    ];
    lane.innerHTML = cells.map(([k, l, rows, f, need]) =>
      `<a href="tts.html?status=${k}">
        <span class="stage">${l}</span>
        <span class="count">${rows.length}${need && rows.length ? ` <span class="need">${need}</span>` : ""}</span>
        <span class="tags">${tags(rows, f)}</span>
      </a>`).join("") +
      `<a href="tts.html?status=diambil"><span class="stage">Diambil bulan ini</span><span class="count">${D.counts.diambil}</span><span class="note">${D.counts.batal} batal · ${D.tts.length} TTS sejak 1 Nov</span></a>`;

    const r = D.report, days = D.daily;
    const series = {
      omzet: { name: "Omzet", values: days.map((d) => d.omzet), fmt: rp },
      laba: { name: "Laba kotor", values: days.map((d) => d.laba), fmt: rp },
      nota: { name: "Nota servis", values: days.map((d) => d.nota), fmt: (v) => v + " nota" },
    };
    const kp = $("#kpi-tabs");
    kp.innerHTML = [
      ["omzet", "Omzet", `<small>Rp</small>${r.omzet.toLocaleString("id-ID")}`, `hari ini ${rp(days.at(-1).omzet)}`],
      ["laba", "Laba kotor", `<small>Rp</small>${r.labaKotor.toLocaleString("id-ID")}`, `${Math.round((r.labaKotor / r.omzet) * 100)}% dari omzet`],
      ["nota", "Nota servis", `${r.servis.count}<small style="margin-left:4px">nota</small>`, `${r.penjualan.count} penjualan barang`],
    ].map(([k, l, v, d], i) => `<button class="kpi" role="tab" type="button" data-k="${k}" aria-selected="${i === 0}"><span class="kpi-label">${l}</span><span class="kpi-value">${v}</span><span class="delta">${d}</span></button>`).join("");
    const draw = (k) => {
      const el = $("#chart"); el.innerHTML = "";
      const s = series[k];
      const big = s.values.indexOf(Math.max(...s.values));
      window.Charts.line(el, {
        labels: days.map((d) => `${d.d} Nov`), series: [{ name: s.name, values: s.values }], format: s.fmt,
        tickFormat: k === "nota" ? (v) => String(v) : (v) => (v >= 1e6 ? (v / 1e6).toLocaleString("id-ID") + " jt" : v >= 1e3 ? v / 1e3 + " rb" : String(v)),
        height: 220, step: false,
        annotations: k === "nota" ? [] : [{ at: big, label: "Printer bekas Epson L120 terjual", short: "Printer bekas" }],
      });
    };
    kp.addEventListener("click", (e) => { const b = e.target.closest(".kpi"); if (!b) return; $$(".kpi", kp).forEach((x) => x.setAttribute("aria-selected", String(x === b))); draw(b.dataset.k); });
    (document.fonts ? document.fonts.ready : Promise.resolve()).then(() => draw("omzet"));
  }

  // ---------- Laporan ----------
  const rep = $("#report");
  if (rep) {
    const r = D.report;
    rep.innerHTML = `
      <div><dt>Servis · ${r.servis.count} nota</dt><dd>${rp(r.servis.omzet)}</dd></div>
      <div><dt>Penjualan · ${r.penjualan.count} transaksi</dt><dd>${rp(r.penjualan.omzet)}</dd></div>
      <div class="sum"><dt>Omzet</dt><dd>${rp(r.omzet)}</dd></div>
      <div><dt>Modal part &amp; barang</dt><dd>−${rp(r.modal)}</dd></div>
      <div class="sum"><dt>Laba kotor</dt><dd>${rp(r.labaKotor)}</dd></div>
      <div><dt>Pengeluaran · ${D.keluar.length} catatan</dt><dd>−${rp(r.pengeluaran)}</dd></div>
      <div class="final"><dt>Laba bersih</dt><dd>${rp(r.labaBersih)}</dd></div>`;

    const days = D.daily.map((d) => {
      const servis = D.tts.filter((t) => t.nota === d.d).reduce((a, t) => a + D.jualOf(t), 0);
      return { ...d, servis, jual: d.omzet - servis };
    });
    const avg = r.omzet / days.length;
    $("#day-meta").textContent = `tertinggi ${rp(Math.max(...days.map((d) => d.omzet)))} pada ${days.reduce((a, d) => (d.omzet > a.omzet ? d : a)).d} Nov`;
    $("#day-note").innerHTML = `Garis putus-putus: rata-rata <strong style="color:var(--text);font-weight:500">${rp(avg)}</strong> per hari. Arahkan atau ketuk batang untuk rinciannya.`;
    const drawDay = () => window.dailyBars($("#day-chart"), days, { avg, today: D.TODAY, rp });
    (document.fonts ? document.fonts.ready : Promise.resolve()).then(drawDay);

    const KIND = { servis: "Servis", jual: "Penjualan", keluar: "Pengeluaran" };
    const rows = [
      ...D.tts.filter((x) => x.nota).map((x) => ({ k: "servis", tgl: x.nota, id: x.no, ket: `${x.kat} · ${x.nama}`, jual: D.jualOf(x), modal: D.modalOf(x) })),
      ...D.jual.map((x) => ({ k: "jual", tgl: x.tgl, id: x.no, ket: x.qty > 1 ? `${x.ket} × ${x.qty}` : x.ket, jual: x.qty * x.harga, modal: x.qty * x.modal })),
      ...D.keluar.map((x) => ({ k: "keluar", tgl: x.tgl, id: null, ket: `${x.kat} · ${x.ket}`, jual: -x.nominal, modal: 0 })),
    ].sort((a, b) => b.tgl - a.tgl || ["servis", "jual", "keluar"].indexOf(a.k) - ["servis", "jual", "keluar"].indexOf(b.k));
    const signed = (v) => (v < 0 ? `<span class="neg">−${rp(-v)}</span>` : rp(v));
    const acts = (x) => ({
      servis: [{ label: "Lihat nota", icon: "file-text", href: "nota-a5.html" }, { label: "Unduh PDF", icon: "download", href: "#", toast: `Mengunduh nota ${x.id}.pdf (contoh)…` }, { label: "Kirim nota lewat WA", icon: "message-circle", href: "#", toast: `Membuka WhatsApp: nota ${x.id}…` }],
      jual: [{ label: "Lihat penjualan", icon: "file-text", href: "#" }, { label: "Cetak struk", icon: "printer", href: "struk.html" }],
      keluar: [{ label: "Ubah", icon: "file-text", href: "#", toast: "Ubah pengeluaran (contoh)." }, "-", { label: "Hapus", icon: "trash-2", href: "#", toast: "Pengeluaran dihapus (contoh).", danger: 1 }],
    }[x.k]);
    $("#trx").innerHTML = rows.map((x) => `<tr data-status="${x.k}" data-jual="${x.jual}" data-modal="${x.modal}">
      <td class="act">${actions(x.id || x.ket, acts(x))}</td>
      <td class="num desktop-only">${x.tgl} Nov</td>
      <td class="c-id">${x.id ? `<span class="id">${x.id}</span>` : '<span class="dash desktop-only">—</span><span class="who phone-only">Pengeluaran</span>'}</td>
      <td class="kind desktop-only">${KIND[x.k]}</td>
      <td class="c-what">${x.ket}<span class="phone-only"> · ${x.tgl} Nov${x.k === "keluar" ? "" : " · " + KIND[x.k]}</span></td>
      <td class="c-amt n">${signed(x.jual)}</td>
      <td class="n desktop-only ${x.k === "keluar" ? "dash" : "muted"}">${x.k === "keluar" ? "—" : rp(x.modal)}</td>
      <td class="n desktop-only">${signed(x.jual - x.modal)}</td></tr>`).join("");

    const tabs = $("#trx-tabs"), more = $("#trx-more"), foot = $("#trx-foot"), count = $("#trx-count");
    let cur = "semua", all = false;
    const show = () => {
      let n = 0, jual = 0, modal = 0;
      $$("#trx tr").forEach((tr) => {
        const ok = cur === "semua" || tr.dataset.status === cur;
        if (ok) { n++; jual += +tr.dataset.jual; modal += +tr.dataset.modal; }
        tr.hidden = !ok || (!all && n > 10);
      });
      more.hidden = all || n <= 10;
      more.querySelector("button").textContent = `Tampilkan semua ${n} transaksi`;
      count.textContent = `${n} transaksi, terbaru di atas`;
      foot.innerHTML = `<tr><td class="hide-phone"></td><td class="desktop-only"></td><td colspan="3">Total ${n} transaksi</td><td class="n hide-phone">${signed(jual)}</td><td class="n desktop-only">${rp(modal)}</td><td class="n">${cur === "semua" ? "Laba bersih " : ""}${signed(jual - modal)}</td></tr>`;
    };
    tabs.addEventListener("click", (e) => {
      const b = e.target.closest("button"); if (!b) return;
      $$("button", tabs).forEach((x) => x.setAttribute("aria-selected", String(x === b)));
      cur = b.dataset.value; all = false; show();
    });
    more.addEventListener("click", () => { all = true; show(); });
    show();
    const r2 = D.report;
    console.assert(rows.reduce((a, x) => a + x.jual - x.modal, 0) === r2.labaBersih, "laba transaksi ≠ laba bersih");
  }

  // ---------- Format uang ----------
  const parse = (s) => parseInt(String(s).replace(/\D/g, ""), 10) || 0;
  const bindMoney = (root = document) => $$(".money", root).forEach((el) => {
    if (el.dataset.bound) return; el.dataset.bound = "1";
    el.addEventListener("input", () => { el.value = el.value ? parse(el.value).toLocaleString("id-ID") : ""; recalc(); });
  });

  // ---------- Rincian nota ----------
  const lines = $("#lines");
  function recalc() {
    if (!lines) return;
    let jual = 0, modal = 0;
    $$(".line", lines).forEach((line) => {
      const qty = parse($(".f-qty input", line).value), price = parse($(".f-price input", line).value);
      const cost = line.classList.contains("is-jasa") ? 0 : parse($(".f-cost input", line).value);
      jual += qty * price; modal += qty * cost;
      $(".line-sub", line).textContent = rp(qty * price);
    });
    $$("[data-total=jual]").forEach((e) => (e.textContent = rp(jual)));
    $$("[data-total=modal]").forEach((e) => (e.textContent = rp(modal)));
    $$("[data-total=laba]").forEach((e) => (e.textContent = rp(jual - modal)));
  }
  if (lines) {
    lines.addEventListener("click", (e) => {
      const seg = e.target.closest(".segmented button");
      if (seg) { const line = seg.closest(".line"); $$(".segmented button", line).forEach((b) => b.setAttribute("aria-pressed", String(b === seg))); line.classList.toggle("is-jasa", seg.dataset.type === "jasa"); recalc(); }
      const del = e.target.closest("[data-remove]");
      if (del && $$(".line", lines).length > 1) { del.closest(".line").remove(); recalc(); }
    });
    lines.addEventListener("input", recalc);
    $("#add-line")?.addEventListener("click", () => { lines.append($("#line-tpl").content.cloneNode(true)); bindMoney(lines); recalc(); $$(".line", lines).at(-1).querySelector(".f-desc input").focus(); });
  }
  bindMoney(); recalc();

  // ---------- Dialog ----------
  document.addEventListener("click", (e) => {
    const o = e.target.closest("[data-open]"); if (o) { e.preventDefault(); $("#" + o.dataset.open)?.showModal(); }
    const c = e.target.closest("[data-close]"); if (c) { e.preventDefault(); c.closest("dialog")?.close(); }
  });

  // ---------- Simpan dengan validasi ----------
  $$("form[data-go]").forEach((form) => form.addEventListener("submit", (e) => {
    e.preventDefault();
    const bad = $$("[required]", form).filter((el) => {
      const isBad = el.type === "radio" ? !$(`[name="${el.name}"]:checked`, form) : !el.value.trim();
      const f = el.closest(".field");
      if (f) { $(".error-text", f)?.remove(); if (el.type !== "radio") el.setAttribute("aria-invalid", String(isBad)); if (isBad) f.insertAdjacentHTML("beforeend", `<p class="error-text">${icon("triangle-alert")}${f.dataset.error || "Wajib diisi."}</p>`); }
      return isBad;
    });
    if (bad.length) { bad[0].focus(); return; }
    const btn = e.submitter;
    if (btn) { btn.classList.add("is-loading"); btn.innerHTML = icon("refresh-cw") + "Menyimpan…"; }
    setTimeout(() => (location.href = form.dataset.go + (role === "teknisi" && !form.dataset.go.includes("?") ? "" : "")), 600);
  }));

  document.addEventListener("keydown", (e) => { if (e.key === "/" && !/input|textarea/i.test(document.activeElement.tagName)) { const s = $(".topbar .search input"); if (s && getComputedStyle(s.closest(".search")).display !== "none") { e.preventDefault(); s.focus(); } } });
})();
