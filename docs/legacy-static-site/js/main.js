/* =========================================================
   SRM FINTECH SOLUTIONS — shared site script
   ========================================================= */
(function () {
  const C = window.SRM_CONFIG;
  const P = C.p2p;
  const page = document.body.dataset.page || "";
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const inr = (n, d = 2) => "₹" + Number(n).toLocaleString("en-IN", { minimumFractionDigits: d, maximumFractionDigits: d });
  const num = (n, d = 2) => Number(n).toLocaleString("en-IN", { minimumFractionDigits: d, maximumFractionDigits: d });
  const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
  const waLink = (msg) => `https://wa.me/${C.whatsapp}?text=${encodeURIComponent(msg)}`;

  const rates = { buy: P.buyRate, sell: P.sellRate, btc: null, eth: null, live: false };

  /* ---------------- Header / Footer ---------------- */
  const links = [
    ["index.html", "Home", "home"],
    ["p2p.html", "P2P USDT", "p2p"],
    ["services.html", "Services", "services"],
    ["insights.html", "Market Insights", "insights"],
    ["about.html", "About", "about"],
    ["contact.html", "Contact", "contact"]
  ];

  const header = `
  <div class="ticker"><div class="ticker-track" id="tickerTrack"></div></div>
  <header class="header">
    <nav class="container nav">
      <a href="index.html" class="logo" aria-label="SRM Fintech Solutions home">
        <span class="logo-mark">SRM</span>
        <span class="full">SRM FINTECH<small>SOLUTIONS</small></span>
      </a>
      <ul class="nav-links" id="navLinks">
        ${links.map(([h, t, k]) => `<li><a href="${h}" class="${k === page ? "active" : ""} ${k === "p2p" ? "p2p-link" : ""}">${t}</a></li>`).join("")}
      </ul>
      <div class="nav-cta">
        <a href="contact.html" class="btn btn-ghost btn-sm">Talk to us</a>
        <a href="p2p.html#trade" class="btn btn-primary btn-sm">Buy / Sell USDT</a>
        <button class="burger" id="burger" aria-label="Menu">☰</button>
      </div>
    </nav>
  </header>`;

  const footer = `
  <footer class="footer">
    <div class="container">
      <div class="footer-grid">
        <div>
          <a href="index.html" class="logo"><span class="logo-mark">SRM</span><span>SRM FINTECH<small>SOLUTIONS</small></span></a>
          <p class="muted" style="margin-top:16px;font-size:14px;max-width:340px">${C.tagline}</p>
          <p class="muted" style="margin-top:10px;font-size:13px">Digital Marketing · Trading &amp; Market Insights · Digital Asset Services</p>
          <div class="socials">
            <a href="${waLink("Hi SRM Fintech Solutions")}" target="_blank" rel="noopener" aria-label="WhatsApp">WA</a>
            <a href="${C.instagram}" target="_blank" rel="noopener" aria-label="Instagram">IG</a>
            <a href="mailto:${C.email}" aria-label="Email">@</a>
          </div>
        </div>
        <div><h5>P2P Desk</h5><ul>
          <li><a href="p2p.html#trade">Buy USDT</a></li>
          <li><a href="p2p.html#trade">Sell USDT</a></li>
          <li><a href="p2p.html#rates">Rates &amp; Limits</a></li>
          <li><a href="p2p.html#kyc">KYC Requirements</a></li>
          <li><a href="p2p.html#faq">P2P FAQ</a></li>
        </ul></div>
        <div><h5>Quick Links</h5><ul>
          <li><a href="index.html">Home</a></li>
          <li><a href="about.html">About Us</a></li>
          <li><a href="services.html">Our Services</a></li>
          <li><a href="insights.html">Market Insights</a></li>
          <li><a href="contact.html">Contact Us</a></li>
        </ul></div>
        <div><h5>Legal</h5><ul>
          <li><a href="legal.html#privacy">Privacy Policy</a></li>
          <li><a href="legal.html#terms">Terms &amp; Conditions</a></li>
          <li><a href="legal.html#p2p-terms">P2P Transaction Terms</a></li>
          <li><a href="legal.html#risk">Risk Disclosure</a></li>
          <li><a href="legal.html#disclaimer">Disclaimers</a></li>
        </ul></div>
      </div>
      <div class="footer-bottom">
        <p><b>Risk Disclaimer:</b> Financial markets, forex, commodities and cryptocurrency involve substantial risk. Content on this website is for general information only and is not investment advice. Digital asset services are subject to applicable Indian laws, eligibility and customer due diligence. SRM Fintech Solutions does not claim to be an RBI-authorised bank, stock exchange, cryptocurrency exchange or investment adviser.</p>
        <p>SRM FINTECH SOLUTIONS · Sole Proprietorship · ${C.location}</p>
        <p>© ${new Date().getFullYear()} SRM Fintech Solutions. All Rights Reserved.</p>
      </div>
    </div>
  </footer>
  <a class="wa-float" href="${waLink("Hi SRM Fintech Solutions, I have an enquiry.")}" target="_blank" rel="noopener" aria-label="Chat on WhatsApp">
    <svg viewBox="0 0 32 32"><path d="M16 3C9 3 3.3 8.6 3.3 15.6c0 2.2.6 4.4 1.7 6.3L3 29l7.3-1.9c1.8 1 3.8 1.5 5.8 1.5 7 0 12.7-5.6 12.7-12.6S23 3 16 3zm0 23.2c-1.9 0-3.7-.5-5.3-1.4l-.4-.2-4.3 1.1 1.2-4.2-.3-.4c-1-1.6-1.5-3.5-1.5-5.4C5.4 9.8 10.2 5.1 16 5.1s10.6 4.7 10.6 10.5S21.8 26.2 16 26.2zm5.8-7.9c-.3-.2-1.9-.9-2.2-1-.3-.1-.5-.2-.7.2-.2.3-.8 1-1 1.2-.2.2-.4.2-.7.1-.3-.2-1.4-.5-2.6-1.6-1-.9-1.6-1.9-1.8-2.2-.2-.3 0-.5.1-.7l.5-.6c.2-.2.2-.3.3-.6.1-.2 0-.4 0-.6l-1-2.4c-.3-.6-.5-.5-.7-.5h-.6c-.2 0-.6.1-.9.4-.3.3-1.2 1.1-1.2 2.8s1.2 3.2 1.4 3.5c.2.2 2.4 3.6 5.7 5 .8.3 1.4.5 1.9.7.8.3 1.5.2 2.1.1.6-.1 1.9-.8 2.2-1.5.3-.7.3-1.4.2-1.5-.1-.2-.3-.3-.6-.4z"/></svg>
  </a>`;

  document.body.insertAdjacentHTML("afterbegin", header);
  document.body.insertAdjacentHTML("beforeend", footer);
  $("#burger").addEventListener("click", () => $("#navLinks").classList.toggle("open"));

  /* ---------------- Ticker ---------------- */
  function renderTicker() {
    const items = [
      `USDT Buy <b>${inr(rates.buy)}</b>`,
      `USDT Sell <b>${inr(rates.sell)}</b>`,
      rates.btc ? `BTC/USD <b>$${Number(rates.btc).toLocaleString("en-US")}</b>` : `BTC/USD <b>—</b>`,
      rates.eth ? `ETH/USD <b>$${Number(rates.eth).toLocaleString("en-US")}</b>` : `ETH/USD <b>—</b>`,
      `Avg. completion <b>${P.avgCompletion}</b>`,
      `Networks <b>${P.networks.map((n) => n.id).join(" · ")}</b>`,
      `Payments <b>${P.paymentMethods.join(" · ")}</b>`,
      `Desk hours <b>${P.hours}</b>`
    ];
    const html = items.map((i) => `<span>${i}</span>`).join("");
    $("#tickerTrack").innerHTML = html + html;
  }

  /* ---------------- Rates ---------------- */
  async function loadRates() {
    try {
      const r = await fetch("https://api.coingecko.com/api/v3/simple/price?ids=tether,bitcoin,ethereum&vs_currencies=inr,usd");
      if (!r.ok) throw 0;
      const d = await r.json();
      rates.btc = d.bitcoin && d.bitcoin.usd;
      rates.eth = d.ethereum && d.ethereum.usd;
      if (P.rateMode === "live" && d.tether && d.tether.inr) {
        rates.buy = +(d.tether.inr + P.buySpread).toFixed(2);
        rates.sell = +(d.tether.inr - P.sellSpread).toFixed(2);
        rates.live = true;
      }
    } catch (e) { /* keep configured rates */ }
    renderTicker();
    document.dispatchEvent(new Event("rates:update"));
  }

  /* ---------------- P2P Widget ---------------- */
  const widgetHTML = (id) => `
    <div class="widget" id="${id}">
      <div class="tabs">
        <button class="tab buy active" data-side="buy">Buy USDT</button>
        <button class="tab sell" data-side="sell">Sell USDT</button>
      </div>
      <div class="field">
        <label><span data-l="pay">You pay</span><span data-l="min"></span></label>
        <div class="field-row"><input type="text" inputmode="decimal" data-in="top" placeholder="0.00" autocomplete="off"><span class="coin" data-coin="top"></span></div>
      </div>
      <div class="swap"><span>⇅</span></div>
      <div class="field">
        <label><span data-l="get">You receive (approx.)</span><span></span></label>
        <div class="field-row"><input type="text" inputmode="decimal" data-in="bot" placeholder="0.00" autocomplete="off"><span class="coin" data-coin="bot"></span></div>
      </div>
      <div class="err" data-err></div>
      <select class="select" data-net aria-label="Network">${P.networks.map((n) => `<option value="${n.id}">${n.name}</option>`).join("")}</select>
      <select class="select" data-pay aria-label="Payment method">${P.paymentMethods.map((m) => `<option>${m}</option>`).join("")}</select>
      <div class="rate-box" data-box></div>
      <button class="btn btn-primary btn-block" data-go>Buy USDT</button>
      <p class="widget-note">🔒 KYC-verified transactions only · Own-account payments only</p>
    </div>`;

  const COIN = { USDT: `<i class="usdt">₮</i>USDT`, INR: `<i class="inr">₹</i>INR` };

  function initWidget(root) {
    let side = "buy";
    let lastEdited = "top";
    const top = $('[data-in="top"]', root), bot = $('[data-in="bot"]', root);
    const err = $("[data-err]", root), box = $("[data-box]", root), go = $("[data-go]", root);
    const net = $("[data-net]", root), pay = $("[data-pay]", root);

    function netFee() { return (P.networks.find((n) => n.id === net.value) || {}).fee || 0; }
    function clean(v) { return parseFloat(String(v).replace(/[^0-9.]/g, "")) || 0; }

    // Buy: top = INR, bot = USDT.  Sell: top = USDT, bot = INR.
    function compute(from) {
      const fee = netFee();
      const rate = side === "buy" ? rates.buy : rates.sell;
      const tdsPct = side === "sell" && P.applyTdsOnSell ? P.tdsPercent / 100 : 0;
      let inrAmt, usdt, out;
      if (side === "buy") {
        if (from === "top") { inrAmt = clean(top.value); usdt = Math.max(inrAmt / rate - fee, 0); bot.value = inrAmt ? usdt.toFixed(2) : ""; }
        else { usdt = clean(bot.value); inrAmt = (usdt + fee) * rate; top.value = usdt ? inrAmt.toFixed(2) : ""; }
        out = usdt;
      } else {
        if (from === "top") { usdt = clean(top.value); inrAmt = usdt * rate * (1 - tdsPct); bot.value = usdt ? inrAmt.toFixed(2) : ""; }
        else { inrAmt = clean(bot.value); usdt = inrAmt / (1 - tdsPct) / rate; top.value = inrAmt ? usdt.toFixed(2) : ""; }
        out = inrAmt;
      }
      const gross = side === "buy" ? inrAmt : usdt * rate;
      const tds = side === "sell" ? usdt * rate * tdsPct : 0;

      let e = "";
      if (gross && gross < P.minInr) e = `Minimum order is ${inr(P.minInr, 0)}`;
      else if (gross > P.maxInr) e = `Maximum per order is ${inr(P.maxInr, 0)}. Contact us for larger volumes.`;
      err.textContent = e;

      box.innerHTML = side === "buy"
        ? `<div><span>Rate</span><b>1 USDT = ${inr(rate)}</b></div>
           <div><span>Network fee (${net.value})</span><b>${num(fee)} USDT</b></div>
           <div><span>Payment via</span><b>${esc(pay.value)}</b></div>
           <div class="total"><span>You receive</span><b>${num(out || 0)} USDT</b></div>`
        : `<div><span>Rate</span><b>1 USDT = ${inr(rate)}</b></div>
           ${tdsPct ? `<div><span>TDS @ ${P.tdsPercent}% (Sec. 194S)</span><b>− ${inr(tds)}</b></div>` : ""}
           <div><span>Send on network</span><b>${net.value}</b></div>
           <div class="total"><span>You receive</span><b>${inr(out || 0)}</b></div>`;

      return { side, rate, inrAmt: gross, usdt, out, fee, tds, network: net.value, payment: pay.value, valid: !!gross && !e };
    }

    function setSide(s) {
      side = s;
      $$(".tab", root).forEach((t) => t.classList.toggle("active", t.dataset.side === s));
      $('[data-coin="top"]', root).innerHTML = s === "buy" ? COIN.INR : COIN.USDT;
      $('[data-coin="bot"]', root).innerHTML = s === "buy" ? COIN.USDT : COIN.INR;
      $('[data-l="pay"]', root).textContent = s === "buy" ? "You pay" : "You sell";
      $('[data-l="min"]', root).textContent = `Min ${inr(P.minInr, 0)} · Max ${inr(P.maxInr, 0)}`;
      go.textContent = s === "buy" ? "Buy USDT" : "Sell USDT";
      go.className = "btn btn-block " + (s === "buy" ? "btn-primary" : "btn-sell");
      top.value = s === "buy" ? "10000" : "100";
      bot.value = "";
      lastEdited = "top";
      compute("top");
    }

    $$(".tab", root).forEach((t) => t.addEventListener("click", () => setSide(t.dataset.side)));
    top.addEventListener("input", () => { lastEdited = "top"; compute("top"); });
    bot.addEventListener("input", () => { lastEdited = "bot"; compute("bot"); });
    net.addEventListener("change", () => compute(lastEdited));
    pay.addEventListener("change", () => compute(lastEdited));
    document.addEventListener("rates:update", () => compute(lastEdited));
    go.addEventListener("click", () => {
      const q = compute(lastEdited);
      if (!q.valid) { err.textContent = err.textContent || "Enter an amount to continue."; return; }
      openOrder(q);
    });
    setSide("buy");
  }

  $$("[data-p2p-widget]").forEach((el, i) => { el.innerHTML = widgetHTML("w" + i); initWidget($("#w" + i)); });

  /* ---------------- Order modal ---------------- */
  const modal = document.createElement("div");
  modal.className = "modal";
  modal.innerHTML = `<div class="modal-box" role="dialog" aria-modal="true"><button class="modal-close" aria-label="Close">×</button><div data-body></div></div>`;
  document.body.appendChild(modal);
  const mBody = $("[data-body]", modal);
  const closeModal = () => modal.classList.remove("open");
  $(".modal-close", modal).addEventListener("click", closeModal);
  modal.addEventListener("click", (e) => { if (e.target === modal) closeModal(); });
  document.addEventListener("keydown", (e) => { if (e.key === "Escape") closeModal(); });

  let order = {};

  function openOrder(q) {
    order = { ...q };
    stepDetails();
    modal.classList.add("open");
  }

  const prog = (n) => `<div class="progress">${[1, 2, 3].map((i) => `<span class="${i <= n ? "on" : ""}"></span>`).join("")}</div>`;
  const quoteSummary = () => `
    <div class="summary">
      <div><span>Order</span><b>${order.side === "buy" ? "Buy" : "Sell"} USDT · ${order.network}</b></div>
      <div><span>${order.side === "buy" ? "You pay" : "You send"}</span><b>${order.side === "buy" ? inr(order.inrAmt) : num(order.usdt) + " USDT"}</b></div>
      <div><span>You receive</span><b>${order.side === "buy" ? num(order.out) + " USDT" : inr(order.out)}</b></div>
      <div><span>Rate</span><b>${inr(order.rate)}</b></div>
    </div>`;

  function stepDetails() {
    mBody.innerHTML = `
      <h3>Your details</h3><p class="muted" style="font-size:14px">Step 1 of 3 · KYC is mandatory for every transaction.</p>
      ${prog(1)}${quoteSummary()}
      <form class="form" id="f1" novalidate>
        <div><label>Full name (as per PAN)</label><input class="input" name="name" required value="${esc(order.name || "")}"></div>
        <div class="form-row">
          <div><label>Mobile (WhatsApp)</label><input class="input" name="phone" inputmode="tel" required value="${esc(order.phone || "")}"></div>
          <div><label>Email</label><input class="input" name="email" type="email" required value="${esc(order.email || "")}"></div>
        </div>
        <div><label>PAN number</label><input class="input" name="pan" maxlength="10" style="text-transform:uppercase" required value="${esc(order.pan || "")}"></div>
        <div class="err" id="e1"></div>
        <button class="btn btn-primary btn-block">Continue</button>
      </form>`;
    $("#f1").addEventListener("submit", (e) => {
      e.preventDefault();
      const f = Object.fromEntries(new FormData(e.target));
      f.pan = f.pan.trim().toUpperCase();
      let m = "";
      if (f.name.trim().length < 3) m = "Enter your full name.";
      else if (!/^[6-9]\d{9}$/.test(f.phone.replace(/\D/g, "").slice(-10))) m = "Enter a valid 10-digit Indian mobile number.";
      else if (!/^\S+@\S+\.\S+$/.test(f.email)) m = "Enter a valid email address.";
      else if (!/^[A-Z]{5}[0-9]{4}[A-Z]$/.test(f.pan)) m = "Enter a valid PAN (e.g. ABCDE1234F).";
      if (m) return ($("#e1").textContent = m);
      Object.assign(order, f);
      stepPayout();
    });
  }

  function stepPayout() {
    const buy = order.side === "buy";
    mBody.innerHTML = `
      <h3>${buy ? "Receiving wallet" : "Payout details"}</h3>
      <p class="muted" style="font-size:14px">Step 2 of 3 · ${buy ? `USDT will be sent to this ${order.network} address.` : "INR will be paid only to an account in your own name."}</p>
      ${prog(2)}
      <form class="form" id="f2" novalidate>
        ${buy
          ? `<div><label>Your USDT wallet address (${order.network})</label><input class="input" name="wallet" required value="${esc(order.wallet || "")}" placeholder="${order.network === "TRC20" ? "T..." : "0x..."}"></div>
             <div><label>Payment method</label><input class="input" value="${esc(order.payment)}" disabled></div>`
          : `<div><label>Payout method</label><select name="payoutType" class="input"><option>UPI</option><option>Bank Account (IMPS/NEFT)</option></select></div>
             <div><label>UPI ID or Account No. + IFSC</label><input class="input" name="payout" required value="${esc(order.payout || "")}" placeholder="name@bank  or  0000000000 / SBIN0000000"></div>`}
        <label class="check"><input type="checkbox" name="own" required> I confirm the ${buy ? "payment will be made from a bank account / UPI" : "payout account is"} in my own name, and the funds are from legitimate sources.</label>
        <label class="check"><input type="checkbox" name="terms" required> I agree to the <a href="legal.html#p2p-terms" target="_blank" class="green">P2P Transaction Terms</a> and <a href="legal.html#risk" target="_blank" class="green">Risk Disclosure</a>.</label>
        <div class="err" id="e2"></div>
        <div class="form-row"><button type="button" class="btn btn-ghost" id="back">Back</button><button class="btn btn-primary">Create order</button></div>
      </form>`;
    $("#back").addEventListener("click", stepDetails);
    $("#f2").addEventListener("submit", (e) => {
      e.preventDefault();
      const fd = new FormData(e.target);
      const f = Object.fromEntries(fd);
      let m = "";
      if (buy) {
        const w = (f.wallet || "").trim();
        const ok = order.network === "TRC20" ? /^T[1-9A-HJ-NP-Za-km-z]{33}$/.test(w) : /^0x[a-fA-F0-9]{40}$/.test(w);
        if (!ok) m = `Enter a valid ${order.network} wallet address.`;
        f.wallet = w;
      } else if ((f.payout || "").trim().length < 6) m = "Enter your UPI ID or bank account details.";
      if (!m && (!fd.get("own") || !fd.get("terms"))) m = "Please accept both confirmations.";
      if (m) return ($("#e2").textContent = m);
      Object.assign(order, f);
      order.id = "SRM" + Date.now().toString(36).toUpperCase().slice(-6) + Math.floor(Math.random() * 90 + 10);
      order.time = new Date().toISOString();
      saveOrder(order);
      stepDone();
    });
  }

  function orderMessage(o) {
    const buy = o.side === "buy";
    return [
      `*New P2P Order — ${o.id}*`,
      `Type: ${buy ? "BUY" : "SELL"} USDT (${o.network})`,
      buy ? `Pay: ${inr(o.inrAmt)} via ${o.payment}` : `Send: ${num(o.usdt)} USDT`,
      buy ? `Receive: ${num(o.out)} USDT` : `Receive: ${inr(o.out)}${o.tds ? ` (after TDS ${inr(o.tds)})` : ""}`,
      `Rate: ${inr(o.rate)} / USDT`,
      ``,
      `Name: ${o.name}`,
      `Mobile: ${o.phone}`,
      `Email: ${o.email}`,
      `PAN: ${o.pan}`,
      buy ? `Wallet: ${o.wallet}` : `Payout (${o.payoutType}): ${o.payout}`,
      ``,
      `Please share payment details and KYC steps.`
    ].join("\n");
  }

  function stepDone() {
    const msg = orderMessage(order);
    mBody.innerHTML = `
      <h3>Order created ✅</h3>
      <p class="muted" style="font-size:14px">Step 3 of 3 · Send this order to our desk to lock your rate.</p>
      ${prog(3)}
      <p class="muted" style="font-size:13px">Order ID</p>
      <div class="order-id">${order.id}</div>
      ${quoteSummary()}
      <div class="notice" style="margin-bottom:16px"><b>Next:</b> Our desk will verify your KYC and share ${order.side === "buy" ? "payment details" : "our wallet address"} on WhatsApp. <b>Do not pay or send USDT</b> to any details that are not confirmed from our official number ${esc(C.phoneDisplay)}. Rates are indicative until confirmed.</div>
      <a class="btn btn-primary btn-block" href="${waLink(msg)}" target="_blank" rel="noopener">Send order on WhatsApp</a>
      <a class="btn btn-ghost btn-block" style="margin-top:10px" href="mailto:${C.email}?subject=${encodeURIComponent("P2P Order " + order.id)}&body=${encodeURIComponent(msg)}">Send by email instead</a>`;
    renderOrders();
  }

  /* ---------------- My orders (browser only) ---------------- */
  const KEY = "srm_orders";
  function getOrders() { try { return JSON.parse(localStorage.getItem(KEY)) || []; } catch (e) { return []; } }
  function saveOrder(o) {
    try {
      const list = getOrders();
      list.unshift({ id: o.id, side: o.side, network: o.network, inrAmt: o.inrAmt, usdt: o.usdt, out: o.out, rate: o.rate, time: o.time });
      localStorage.setItem(KEY, JSON.stringify(list.slice(0, 20)));
    } catch (e) {}
  }
  function renderOrders() {
    const el = $("#myOrders");
    if (!el) return;
    const list = getOrders();
    if (!list.length) { el.innerHTML = `<p class="muted center">No orders yet. Orders you create on this device appear here.</p>`; return; }
    el.innerHTML = list.map((o) => `
      <div class="order-item">
        <div><b>${esc(o.id)}</b><br><small class="muted">${new Date(o.time).toLocaleString("en-IN")}</small></div>
        <div><span class="badge ${o.side === "buy" ? "ok" : "warn"}">${o.side.toUpperCase()}</span> ${num(o.usdt)} USDT · ${esc(o.network)}</div>
        <div>${inr(o.inrAmt)}</div>
        <a class="btn btn-ghost btn-sm" target="_blank" rel="noopener" href="${waLink("Status update for order " + o.id)}">Check status</a>
      </div>`).join("");
  }
  renderOrders();

  /* ---------------- Rates table on P2P page ---------------- */
  function renderRateTable() {
    $$("[data-rate=buy]").forEach((e) => (e.textContent = inr(rates.buy)));
    $$("[data-rate=sell]").forEach((e) => (e.textContent = inr(rates.sell)));
    $$("[data-rate=mode]").forEach((e) => (e.textContent = rates.live ? "Live market-linked rate" : "Desk rate · updated by our team"));
    const t = $("#rateTable");
    if (!t) return;
    t.innerHTML = P.networks.map((n) => `
      <tr>
        <td><b>USDT</b> <span class="muted">· ${n.name}</span></td>
        <td class="green"><b>${inr(rates.buy)}</b></td>
        <td class="red"><b>${inr(rates.sell)}</b></td>
        <td>${num(n.fee)} USDT</td>
        <td>${inr(P.minInr, 0)} – ${inr(P.maxInr, 0)}</td>
        <td><span class="badge ok">Available</span></td>
      </tr>`).join("");
  }
  document.addEventListener("rates:update", renderRateTable);
  renderRateTable();

  /* ---------------- Config-driven text ---------------- */
  $$("[data-cfg]").forEach((el) => {
    const k = el.dataset.cfg;
    const v = { email: C.email, phone: C.phoneDisplay, location: C.location, hours: P.hours, completion: P.avgCompletion,
      min: inr(P.minInr, 0), max: inr(P.maxInr, 0) }[k];
    if (v) el.textContent = v;
  });
  $$("[data-wa]").forEach((el) => (el.href = waLink(el.dataset.wa || "Hi SRM Fintech Solutions")));
  $$("[data-mail]").forEach((el) => (el.href = "mailto:" + C.email));
  $$("[data-ig]").forEach((el) => (el.href = C.instagram));

  /* ---------------- Contact form ---------------- */
  const cf = $("#contactForm");
  if (cf) {
    cf.addEventListener("submit", (e) => {
      e.preventDefault();
      const f = Object.fromEntries(new FormData(cf));
      const out = $("#contactMsg");
      if (!f.name || !f.email || !f.phone || !f.message) { out.textContent = "Please fill all fields."; out.className = "err"; return; }
      const msg = `*Website Enquiry*\nName: ${f.name}\nEmail: ${f.email}\nPhone: ${f.phone}\nService: ${f.service}\n\n${f.message}`;
      window.open(waLink(msg), "_blank", "noopener");
      out.className = "green";
      out.textContent = "Thank you! Your enquiry has opened in WhatsApp — press send to reach our team.";
      cf.reset();
    });
  }

  /* ---------------- Insights filter ---------------- */
  const filt = $("#filters");
  if (filt) {
    filt.addEventListener("click", (e) => {
      const b = e.target.closest("button");
      if (!b) return;
      $$("button", filt).forEach((x) => x.classList.toggle("active", x === b));
      const c = b.dataset.f;
      $$("[data-cat]").forEach((p) => (p.style.display = c === "all" || p.dataset.cat === c ? "" : "none"));
    });
  }

  /* ---------------- Reveal on scroll ---------------- */
  const io = "IntersectionObserver" in window ? new IntersectionObserver((es) => es.forEach((en) => { if (en.isIntersecting) { en.target.classList.add("in"); io.unobserve(en.target); } }), { threshold: 0.12 }) : null;
  $$(".reveal").forEach((el) => (io ? io.observe(el) : el.classList.add("in")));

  renderTicker();
  loadRates();
})();
