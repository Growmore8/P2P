/* =========================================================
   SRM FINTECH SOLUTIONS — SITE CONFIG
   Edit this file to update contact details, rates & limits.
   ========================================================= */
window.SRM_CONFIG = {
  brand: "SRM FINTECH SOLUTIONS",
  tagline: "Empowering Digital Growth. Connecting Global Markets.",

  // ---- Contact (replace placeholders) ----
  whatsapp: "910000000000",          // country code + number, no "+" or spaces
  phoneDisplay: "+91 00000 00000",
  email: "support@yourdomain.com",
  instagram: "https://instagram.com/",
  location: "Nallampalli, Dharmapuri District, Tamil Nadu, India",

  // ---- P2P Desk ----
  p2p: {
    // "manual" = use buyRate/sellRate below
    // "live"   = fetch USDT/INR market price and apply spreads
    rateMode: "manual",
    buyRate: 93.50,      // INR per USDT the customer PAYS when buying
    sellRate: 91.00,     // INR per USDT the customer RECEIVES when selling
    buySpread: 4.0,      // live mode: market + buySpread
    sellSpread: 1.5,     // live mode: market - sellSpread

    minInr: 1000,
    maxInr: 500000,
    applyTdsOnSell: true, // 1% TDS u/s 194S of the Income-tax Act on VDA transfers
    tdsPercent: 1,

    networks: [
      { id: "TRC20", name: "Tron (TRC20)",  fee: 1.0 },
      { id: "BEP20", name: "BNB Chain (BEP20)", fee: 0.5 },
      { id: "ERC20", name: "Ethereum (ERC20)", fee: 5.0 },
      { id: "POLYGON", name: "Polygon", fee: 0.5 }
    ],
    paymentMethods: ["UPI", "IMPS", "NEFT / RTGS", "Bank Transfer"],
    avgCompletion: "15–30 min",
    hours: "10:00 AM – 9:00 PM IST, Mon–Sat"
  }
};
