/* SRM Fintech Solutions — public site + panel helpers */
(function () {
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const S = window.SRM || {};
  const inr = (n, d = 2) => "₹" + Number(n || 0).toLocaleString("en-IN", { minimumFractionDigits: d, maximumFractionDigits: d });
  const num = (n, d = 2) => Number(n || 0).toLocaleString("en-IN", { minimumFractionDigits: d, maximumFractionDigits: d });

  // Mobile menus
  const burger = $("#burger");
  if (burger) burger.addEventListener("click", () => $("#navLinks").classList.toggle("open"));
  const st = $("#sideToggle");
  if (st) st.addEventListener("click", (e) => { e.stopPropagation(); $("#side").classList.toggle("open"); });
  document.addEventListener("click", (e) => { const sd = $("#side"); if (sd && sd.classList.contains("open") && !sd.contains(e.target)) sd.classList.remove("open"); });

  // Copy buttons
  $$("[data-copy]").forEach((b) => b.addEventListener("click", () => {
    navigator.clipboard && navigator.clipboard.writeText(b.dataset.copy).then(() => { const t = b.textContent; b.textContent = "Copied"; setTimeout(() => (b.textContent = t), 1200); });
  }));

  // Confirm dialogs
  $$("form[data-confirm]").forEach((f) => f.addEventListener("submit", (e) => { if (!confirm(f.dataset.confirm)) e.preventDefault(); }));

  // Countdown timers
  $$("[data-expires]").forEach((el) => {
    const end = new Date(el.dataset.expires).getTime();
    const tick = () => {
      const s = Math.max(0, Math.floor((end - Date.now()) / 1000));
      el.textContent = String(Math.floor(s / 60)).padStart(2, "0") + ":" + String(s % 60).padStart(2, "0");
      if (s === 0) { clearInterval(iv); setTimeout(() => location.reload(), 1500); }
    };
    const iv = setInterval(tick, 1000); tick();
  });

  // Reveal on scroll
  const io = "IntersectionObserver" in window ? new IntersectionObserver((es) => es.forEach((en) => { if (en.isIntersecting) { en.target.classList.add("in"); io.unobserve(en.target); } }), { threshold: 0.1 }) : null;
  $$(".reveal").forEach((el) => (io ? io.observe(el) : el.classList.add("in")));

  // Fiat formatting for any currency
  const fiat = (n, c, d = 2) => (c ? c.symbol : "") + Number(n || 0).toLocaleString("en-IN", { minimumFractionDigits: d, maximumFractionDigits: d });
  const defCur = () => S.pricing && S.pricing.currencies ? S.pricing.currencies[S.pricing.currency] : null;

  // Ticker with BTC/ETH prices
  const track = $("#tickerTrack");
  // Market reference prices for USDT in many currencies (public CoinGecko data — not our desk rates).
  const MARKET_CUR = ["usd", "eur", "gbp", "aed", "sar", "sgd", "myr", "aud", "cad", "jpy", "thb", "php", "ngn", "pkr", "bdt"];
  const MARKET_SYM = { usd: "$", eur: "€", gbp: "£", aed: "AED ", sar: "SAR ", sgd: "S$", myr: "RM", aud: "A$", cad: "C$", jpy: "¥", thb: "฿", php: "₱", ngn: "₦", pkr: "Rs ", bdt: "৳" };
  let lastMarket = {};
  function renderTicker(market) {
    if (!track || !S.pricing) return;
    market = market || lastMarket;
    lastMarket = market;
    const curs = Object.values(S.pricing.currencies || {});
    const btc = market.bitcoin && market.bitcoin.usd, eth = market.ethereum && market.ethereum.usd, usdt = market.tether || {};
    const desk = curs.map((c) => c.code.toLowerCase());
    const narrow = window.innerWidth < 768;
    // Phones get fewer market prices: a very wide moving strip can exceed the phone GPU's
    // texture limit (~8000px) and the whole bar disappears.
    const marketList = MARKET_CUR.filter((k) => usdt[k] && !desk.includes(k)).slice(0, narrow ? 4 : MARKET_CUR.length);
    const items = [
      ...curs.flatMap((c) => [`USDT/${c.code} Buy <b>${fiat(c.buy_rate, c)}</b>`, `USDT/${c.code} Sell <b>${fiat(c.sell_rate, c)}</b>`]),
      `BTC/USD <b>${btc ? "$" + Number(btc).toLocaleString("en-US") : "—"}</b>`,
      `ETH/USD <b>${eth ? "$" + Number(eth).toLocaleString("en-US") : "—"}</b>`,
      ...marketList.map((k) =>
        `USDT/${k.toUpperCase()} <small class="tk-mkt">market</small> <b>${MARKET_SYM[k] || ""}${Number(usdt[k]).toLocaleString("en-US", { maximumFractionDigits: usdt[k] < 10 ? 3 : 2 })}</b>`),
      `Avg. completion <b>${S.completion || ""}</b>`,
      ...(narrow ? [] : [`Networks <b>${S.pricing.networks.map((n) => n.code).join(" · ")}</b>`, `Desk hours <b>${S.hours || ""}</b>`])
    ];
    const paint = () => { const html = items.map((i) => `<span>${i}</span>`).join(""); track.innerHTML = html + html; };
    paint();
    // Safety net: keep the doubled strip under ~7000px by dropping market prices (last first).
    for (let i = items.length - 1; track.scrollWidth > 7000 && i >= 0; i--) {
      if (items[i].includes("tk-mkt")) { items.splice(i, 1); paint(); }
    }
    // Constant, readable speed (~50px/s) whatever the content length; restart cleanly.
    const half = track.scrollWidth / 2;
    track.style.animation = "none";
    void track.offsetWidth;
    track.style.animation = `tick ${Math.max(20, Math.round(half / 50))}s linear infinite`;
  }
  if (track) {
    renderTicker();
    fetch("https://api.coingecko.com/api/v3/simple/price?ids=bitcoin,ethereum,tether&vs_currencies=" + MARKET_CUR.join(","))
      .then((r) => r.json()).then(renderTicker).catch(() => {});
    let wasNarrow = window.innerWidth < 768;
    window.addEventListener("resize", () => {
      const n = window.innerWidth < 768;
      if (n !== wasNarrow) { wasNarrow = n; renderTicker(); }
    });
  }

  // Recent-orders feed — REAL completed orders only. Newest first, pages of 3, live relative times,
  // and a poll every 30s so newly completed orders appear as "Just now".
  const feed = document.getElementById("activityFeed");
  if (feed && Array.isArray(window.SRM_ACTIVITY)) {
    let all = window.SRM_ACTIVITY.slice();
    let page = 0;
    const PER = 3;
    const reduce = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
    const ago = (iso) => {
      const s = Math.max(0, Math.floor((Date.now() - new Date(iso).getTime()) / 1000));
      if (s < 10) return "Just now";
      if (s < 60) return s + " sec ago";
      const m = Math.floor(s / 60); if (m < 60) return m + (m === 1 ? " min ago" : " mins ago");
      const h = Math.floor(m / 60); if (h < 24) return h + (h === 1 ? " hour ago" : " hours ago");
      const d = Math.floor(h / 24); return d + (d === 1 ? " day ago" : " days ago");
    };
    const row = (a, fresh) => `<div class="activity-item${fresh ? " act-in" : ""}"><img src="https://flagcdn.com/w40/${esc(a.cc)}.png" alt="">` +
      `<div><b class="act-nick">${esc(a.nick)}</b> from ${esc(a.country)} ${esc(a.verb)} <b>${esc(a.usdt)} USDT</b>` +
      `<small data-ts="${esc(a.ts)}">${ago(a.ts)}</small></div></div>`;
    const tick = () => feed.querySelectorAll("[data-ts]").forEach((el) => (el.textContent = ago(el.dataset.ts)));
    const pages = () => Math.max(1, Math.ceil(all.length / PER));
    function render(animate) {
      const items = all.slice(page * PER, page * PER + PER);
      if (!items.length) return;
      const swap = () => { feed.innerHTML = items.map((a) => row(a, animate)).join(""); feed.classList.remove("act-fade"); };
      if (animate && !reduce) { feed.classList.add("act-fade"); setTimeout(swap, 300); } else swap();
    }
    setInterval(tick, 5000);
    if (all.length > PER) setInterval(() => { if (document.hidden) return; page = (page + 1) % pages(); render(true); }, 6000);
    // live: fetch latest completed orders; if there's a new one, jump back to the newest page
    const src = feed.dataset.src;
    if (src) setInterval(() => {
      if (document.hidden) return;
      fetch(src, { headers: { Accept: "application/json" } }).then((r) => r.json()).then((list) => {
        if (!Array.isArray(list) || !list.length) return;
        const isNew = !all.length || list[0].id !== all[0].id;
        all = list;
        if (isNew) { page = 0; render(true); }
      }).catch(() => {});
    }, 30000);
  }

  /* ---------- Calculator (public widget + trade page) ---------- */
  function initCalc(root) {
    const P = S.pricing;
    if (!P || !defCur()) return;
    const mode = root.dataset.mode || "widget"; // widget | trade
    let side = root.dataset.side || "buy";
    let C = defCur();
    const top = $('[data-in="top"]', root), bot = $('[data-in="bot"]', root);
    const net = $("[data-net]", root), box = $("[data-box]", root), err = $("[data-err]", root), go = $("[data-go]", root);
    const curSel = $("[data-cur]", root), limits = $("[data-limits]", root);
    const amountField = $('[name="amount"]', root);
    let last = "top";

    const clean = (v) => parseFloat(String(v).replace(/[^0-9.]/g, "")) || 0;
    const netObj = () => P.networks.find((n) => n.code === net.value) || { fee: 0 };
    const f = (n, d) => fiat(n, C, d);

    function compute(from) {
      const fee = side === "buy" ? netObj().fee : 0;
      const rate = side === "buy" ? C.buy_rate : C.sell_rate;
      const tdsPct = side === "sell" ? (C.tds || 0) / 100 : 0;
      let amt = 0, usdt = 0, out = 0;
      if (side === "buy") {
        if (from === "top") { amt = clean(top.value); usdt = Math.max(Math.floor((amt / rate - fee) * 100) / 100, 0); bot.value = amt ? usdt.toFixed(2) : ""; }
        else { usdt = clean(bot.value); amt = Math.ceil((usdt + fee) * rate * 100) / 100; top.value = usdt ? amt.toFixed(2) : ""; }
        out = usdt;
      } else {
        if (from === "top") { usdt = clean(top.value); amt = usdt * rate; out = amt * (1 - tdsPct); bot.value = usdt ? out.toFixed(2) : ""; }
        else { out = clean(bot.value); usdt = Math.ceil(out / (1 - tdsPct) / rate * 100) / 100; amt = usdt * rate; top.value = out ? usdt.toFixed(2) : ""; }
      }
      const tds = side === "sell" ? amt * tdsPct : 0;
      let e = "";
      if (amt && C.min > 0 && amt < C.min) e = `Minimum order value is ${f(C.min, 0)}`;
      else if (C.max > 0 && amt > C.max) e = `Maximum order value is ${f(C.max, 0)}`;
      else if (side === "buy" && amt && usdt <= 0) e = "Amount too small to cover network fee";
      err.textContent = e;
      if (amountField) amountField.value = side === "buy" ? (amt ? amt.toFixed(2) : "") : (usdt ? usdt.toFixed(2) : "");
      if (limits) limits.textContent = (C.min > 0 ? `Min ${f(C.min, 0)}` : "") + (C.max > 0 ? ` · Max ${f(C.max, 0)}` : "");
      const scope = root.closest("form") || root;
      const info = $("[data-info]", scope);
      if (info) info.innerHTML = `<span>1 USDT ≈ ${f(rate)}</span><span>${net.value}${side === "buy" ? " · Network fee " + num(fee) + " USDT" : ""}</span>`;
      const total = $("[data-total]", scope);
      if (total) total.innerHTML = side === "buy" ? `You pay <b>${f(amt)}</b> including fees` : `You receive <b>${f(out)}</b>${tdsPct ? " after TDS" : ""}`;

      box.innerHTML = side === "buy"
        ? `<div><span>Rate</span><b>1 USDT = ${f(rate)}</b></div>
           <div><span>Network fee (${net.value})</span><b>${num(fee)} USDT</b></div>
           <div class="total"><span>You receive</span><b>${num(out)} USDT</b></div>`
        : `<div><span>Rate</span><b>1 USDT = ${f(rate)}</b></div>
           <div><span>Gross value</span><b>${f(amt)}</b></div>
           ${tdsPct ? `<div><span>TDS @ ${C.tds}%${C.code === "INR" ? " (Sec. 194S)" : ""}</span><b>− ${f(tds)}</b></div>` : ""}
           <div class="total"><span>You receive</span><b>${f(out)}</b></div>`;
      return { ok: !!amt && !e, amount: side === "buy" ? amt : usdt };
    }

    const coinUsdt = `<i class="usdt">₮</i>USDT`;
    const coinFiat = () => `<i class="inr">${C.symbol.trim().slice(0, 2)}</i>${C.code}`;
    function paintCoins() {
      $('[data-coin="top"]', root).innerHTML = side === "buy" ? coinFiat() : coinUsdt;
      $('[data-coin="bot"]', root).innerHTML = side === "buy" ? coinUsdt : coinFiat();
    }
    function setSide(s, keep) {
      side = s;
      $$(".tab", root).forEach((t) => t.classList.toggle("active", t.dataset.side === s));
      paintCoins();
      $('[data-l="pay"]', root).textContent = s === "buy" ? "You pay" : "You sell";
      // only networks enabled for this side
      [...net.options].forEach((o) => { const n = P.networks.find((x) => x.code === o.value); o.disabled = n && !n[s]; });
      if (net.selectedOptions[0] && net.selectedOptions[0].disabled) { const ok = [...net.options].find((o) => !o.disabled); if (ok) net.value = ok.value; }
      if (go) { go.textContent = (s === "buy" ? "Buy" : "Sell") + " USDT"; go.className = "btn btn-block " + (s === "buy" ? "btn-primary" : "btn-sell"); }
      const sideInput = $('[name="side"]', root); if (sideInput) sideInput.value = s;
      $$("[data-only]", root.closest("form") || root).forEach((el) => (el.style.display = el.dataset.only === s ? "" : "none"));
      if (!keep) { top.value = s === "buy" ? String(Math.max(C.min || 0, Math.round((C.buy_rate || 1) * 100))) : "100"; bot.value = ""; last = "top"; }
      compute(last);
    }

    $$(".tab", root).forEach((t) => t.addEventListener("click", (e) => { e.preventDefault(); setSide(t.dataset.side); }));
    top.addEventListener("input", () => { last = "top"; compute("top"); });
    bot.addEventListener("input", () => { last = "bot"; compute("bot"); });
    net.addEventListener("change", () => compute(last));
    if (curSel) curSel.addEventListener("change", () => { C = P.currencies[curSel.value] || C; setSide(side); });

    if (mode === "widget" && go) {
      go.addEventListener("click", (e) => {
        e.preventDefault();
        const q = compute(last);
        if (!q.ok) { err.textContent = err.textContent || "Enter an amount to continue."; return; }
        location.href = `${S.tradeUrl}?side=${side}&amount=${q.amount.toFixed(2)}&network=${encodeURIComponent(net.value)}`;
      });
    }
    const pre = parseFloat(root.dataset.amount);
    setSide(side, !!pre);
    if (pre) { top.value = pre; compute("top"); }
  }
  $$("[data-calc]").forEach(initCalc);

  // Theme toggle (light default)
  $$("[data-theme-toggle]").forEach((b) => b.addEventListener("click", () => {
    const next = document.documentElement.dataset.theme === "dark" ? "light" : "dark";
    document.documentElement.dataset.theme = next;
    try { localStorage.setItem("srm-theme", next); } catch (e) {}
  }));

  // Drawer menu (app card)
  const drawer = $("#drawer");
  $$("[data-drawer-open]").forEach((b) => b.addEventListener("click", () => drawer && drawer.classList.add("open")));
  $$("[data-drawer-close]").forEach((b) => b.addEventListener("click", () => drawer && drawer.classList.remove("open")));

  // Collapsible sections
  $$("[data-toggle]").forEach((b) => b.addEventListener("click", () => {
    const t = document.getElementById(b.dataset.toggle);
    if (t) { t.hidden = !t.hidden; b.setAttribute("aria-expanded", String(!t.hidden)); }
  }));

  // Bottom sheets: trigger [data-sheet-open=id]; options .sheet-opt (label with radio + [data-label])
  $$("[data-sheet-open]").forEach((b) => b.addEventListener("click", () => { const s = document.getElementById(b.dataset.sheetOpen); if (s) s.classList.add("open"); }));
  $$(".sheet-backdrop").forEach((bd) => {
    bd.addEventListener("click", (e) => { if (e.target === bd || e.target.closest("[data-sheet-close]")) bd.classList.remove("open"); });
    const sync = () => {
      const checked = $("input[type=radio]:checked", bd);
      $$(".sheet-opt", bd).forEach((o) => o.classList.toggle("selected", !!checked && o.contains(checked)));
      const trig = $(`[data-sheet-open="${bd.id}"] [data-sheet-label]`);
      if (trig && checked) { const lab = $("[data-label]", checked.closest(".sheet-opt")); if (lab) trig.innerHTML = lab.innerHTML; }
    };
    $$("input[type=radio]", bd).forEach((r) => r.addEventListener("change", () => { sync(); bd.classList.remove("open"); }));
    sync();
  });

  // Country picker
  const cmodal = $("#countryModal");
  let activePicker = null;
  $$("[data-country-picker] .cpick-btn").forEach((btn) => btn.addEventListener("click", () => {
    activePicker = btn.closest("[data-country-picker]");
    if (!cmodal) return;
    cmodal.classList.add("open");
    const s = $(".cmodal-search", cmodal); s.value = ""; s.dispatchEvent(new Event("input")); setTimeout(() => s.focus(), 50);
  }));
  if (cmodal) {
    const close = () => cmodal.classList.remove("open");
    cmodal.addEventListener("click", (e) => { if (e.target === cmodal || e.target.closest("[data-close]")) close(); });
    $(".cmodal-search", cmodal).addEventListener("input", (e) => {
      const q = e.target.value.trim().toLowerCase().replace(/^\+/, "");
      $$(".clist li", cmodal).forEach((li) => { const b = $("button", li); li.hidden = !!q && !(b.dataset.name.toLowerCase().includes(q) || b.dataset.dial.startsWith(q) || b.dataset.code.toLowerCase() === q); });
    });
    $$(".clist button", cmodal).forEach((b) => b.addEventListener("click", () => {
      if (activePicker) {
        $("input[type=hidden]", activePicker).value = b.dataset.code;
        $("[data-flag]", activePicker).src = `https://flagcdn.com/w40/${b.dataset.code.toLowerCase()}.png`;
        $("[data-dial]", activePicker).textContent = "+" + b.dataset.dial;
        const n = $("[data-cname]", activePicker); if (n) n.textContent = b.dataset.name;
      }
      close();
    }));
    document.addEventListener("keydown", (e) => { if (e.key === "Escape") close(); });
  }

  // Insights category filter
  const filt = $("#filters");
  if (filt) filt.addEventListener("click", (e) => {
    const b = e.target.closest("button[data-f]");
    if (!b) return;
    $$("button", filt).forEach((x) => x.classList.toggle("active", x === b));
    $$("[data-cat]").forEach((p) => (p.style.display = b.dataset.f === "all" || p.dataset.cat === b.dataset.f ? "" : "none"));
  });

  // Payout/deposit type toggles (bank vs upi)
  $$("[data-type-toggle]").forEach((sel) => {
    const form = sel.closest("form");
    // An option may carry data-kind (e.g. a payout method id whose kind is "bank"); fields list one or more kinds.
    const apply = () => {
      const opt = sel.selectedOptions[0];
      const kind = (opt && opt.dataset.kind) || sel.value;
      $$("[data-type]", form).forEach((el) => (el.style.display = el.dataset.type.split(" ").includes(kind) ? "" : "none"));
    };
    sel.addEventListener("change", apply); apply();
  });
})();
