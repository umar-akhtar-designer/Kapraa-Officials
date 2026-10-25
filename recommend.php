<?php include 'include/head.php'; ?>
<?php include 'include/navbar.php'; ?>

<style>
.rec-hero{background:linear-gradient(135deg,#1a0533 0%,#6d28d9 50%,#be185d 100%);padding:60px 0 44px;text-align:center;color:#fff;}
.rec-hero .ai-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);border-radius:50px;padding:6px 18px;font-size:13px;font-weight:600;margin-bottom:14px;}
.rec-hero h1{font-size:clamp(1.5rem,3.5vw,2.4rem);font-weight:800;margin-bottom:8px;}
.rec-hero p{font-size:14px;opacity:.8;max-width:500px;margin:0 auto 24px;}
.rec-tabs{display:flex;justify-content:center;gap:10px;flex-wrap:wrap;}
.rec-tab{padding:10px 26px;border-radius:50px;font-size:13px;font-weight:700;cursor:pointer;border:2px solid rgba(255,255,255,.4);color:rgba(255,255,255,.75);background:transparent;transition:all .25s;}
.rec-tab.active,.rec-tab:hover{background:#fff;color:#6d28d9;border-color:#fff;}

.rec-section{padding:48px 0 60px;background:#f8f8f8;}
.rec-panel{display:none;}
.rec-panel.active{display:block;}

/* Occasion cards */
.occ-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:12px;margin-bottom:28px;}
.occ-card{border-radius:14px;padding:20px 8px;text-align:center;cursor:pointer;transition:all .25s;border:2px solid #e8e8e8;background:#fff;box-shadow:0 1px 6px rgba(0,0,0,.05);}
.occ-card:hover,.occ-card.selected{border-color:#7c3aed;box-shadow:0 4px 18px rgba(124,58,237,.18);transform:translateY(-3px);}
.occ-card.selected{background:linear-gradient(135deg,#f5f3ff,#fdf4ff);}
.occ-icon{font-size:2rem;margin-bottom:7px;display:block;}
.occ-label{font-size:12px;font-weight:700;color:#374151;}

/* Culture cards */
.cult-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(145px,1fr));gap:14px;margin-bottom:28px;}
.cult-card{border-radius:14px;overflow:hidden;cursor:pointer;transition:all .25s;border:3px solid transparent;background:#fff;box-shadow:0 2px 10px rgba(0,0,0,.07);}
.cult-card:hover,.cult-card.selected{border-color:#7c3aed;transform:translateY(-4px);box-shadow:0 8px 24px rgba(124,58,237,.22);}
.cult-emoji{width:100%;height:110px;display:flex;align-items:center;justify-content:center;font-size:3rem;}
.cult-card[data-culture=punjabi]  .cult-emoji{background:linear-gradient(135deg,#fef3c7,#fde68a);}
.cult-card[data-culture=sindhi]   .cult-emoji{background:linear-gradient(135deg,#ecfdf5,#6ee7b7);}
.cult-card[data-culture=balochi]  .cult-emoji{background:linear-gradient(135deg,#fce7f3,#f9a8d4);}
.cult-card[data-culture=pashtun]  .cult-emoji{background:linear-gradient(135deg,#eff6ff,#93c5fd);}
.cult-card[data-culture=kashmiri] .cult-emoji{background:linear-gradient(135deg,#f5f3ff,#c4b5fd);}
.cult-info{padding:10px;text-align:center;}
.cult-name{font-size:13px;font-weight:800;color:#1f2937;}
.cult-sub{font-size:11px;color:#9ca3af;}

/* Recommend btn */
.btn-rec{background:linear-gradient(135deg,#7c3aed,#be185d);color:#fff;border:none;padding:13px 38px;border-radius:50px;font-size:14px;font-weight:700;cursor:pointer;display:block;margin:0 auto;transition:opacity .2s;}
.btn-rec:hover{opacity:.88;}
.btn-rec:disabled{opacity:.45;cursor:not-allowed;}

/* AI advice */
.ai-box{background:linear-gradient(135deg,#f5f3ff,#fdf4ff);border:1px solid #ddd6fe;border-radius:14px;padding:18px 22px;margin-bottom:24px;display:none;}
.ai-box.show{display:block;}
.ai-box .ai-head{display:flex;align-items:center;gap:7px;font-size:13px;font-weight:700;color:#7c3aed;margin-bottom:7px;}
.ai-box p{font-size:13px;color:#374151;line-height:1.75;margin:0;}

/* Results header */
.res-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;}
.res-header h5{font-size:15px;font-weight:800;color:#1f2937;margin:0;}
.res-badge{background:#7c3aed;color:#fff;border-radius:50px;padding:3px 12px;font-size:11px;font-weight:700;}

/* Loading */
.rec-loading{text-align:center;padding:44px 0;display:none;}
.rec-loading.show{display:block;}
.rec-spin{width:48px;height:48px;border:5px solid #e9d5ff;border-top-color:#7c3aed;border-radius:50%;animation:rspin .8s linear infinite;margin:0 auto 12px;}
@keyframes rspin{to{transform:rotate(360deg);}}
.rec-loading p{color:#7c3aed;font-weight:600;font-size:13px;}
.rec-empty{text-align:center;padding:40px 0;display:none;color:#9ca3af;font-size:14px;}
.rec-empty.show{display:block;}

/* Section heading */
.sec-head{text-align:center;margin-bottom:32px;}
.sec-head h4{font-size:1.3rem;font-weight:800;color:#1f2937;margin-bottom:6px;}
.sec-head p{font-size:13px;color:#6b7280;}

/* Ensure product grid layout matches products.php */
#occ-products .item, #cult-products .item { margin-bottom: 24px; }
</style>

<!-- Hero -->
<section class="rec-hero">
  <div class="container">
    <div class="ai-badge">✨ Powered by Gemini AI</div>
    <h1>Smart AI Fashion Recommender</h1>
    <p>Get personalized outfit suggestions for every occasion — or explore Pakistan's beautiful cultural fashion heritage</p>
    <div class="rec-tabs">
      <button class="rec-tab active" onclick="switchTab('occasion',this)">🎉 Occasion-Based</button>
      <button class="rec-tab"        onclick="switchTab('cultural',this)">🇵🇰 Cultural Dresses</button>
    </div>
  </div>
</section>

<!-- Content -->
<section class="rec-section">
  <div class="container">

    <!-- OCCASION PANEL -->
    <div class="rec-panel active" id="panel-occasion">
      <div class="sec-head">
        <h4>What's the Occasion?</h4>
        <p>Select an occasion and Gemini AI will recommend the perfect outfit from our collection</p>
      </div>
      <div class="occ-grid">
        <div class="occ-card" data-occasion="wedding" onclick="selectOcc(this)"><span class="occ-icon">💍</span><span class="occ-label">Wedding / Shadi</span></div>
        <div class="occ-card" data-occasion="eid"     onclick="selectOcc(this)"><span class="occ-icon">🌙</span><span class="occ-label">Eid</span></div>
        <div class="occ-card" data-occasion="casual"  onclick="selectOcc(this)"><span class="occ-icon">☕</span><span class="occ-label">Casual / Daily</span></div>
        <div class="occ-card" data-occasion="office"  onclick="selectOcc(this)"><span class="occ-icon">💼</span><span class="occ-label">Office / Formal</span></div>
        <div class="occ-card" data-occasion="party"   onclick="selectOcc(this)"><span class="occ-icon">🎊</span><span class="occ-label">Party</span></div>
        <div class="occ-card" data-occasion="winter"  onclick="selectOcc(this)"><span class="occ-icon">❄️</span><span class="occ-label">Winter</span></div>
        <div class="occ-card" data-occasion="summer"  onclick="selectOcc(this)"><span class="occ-icon">☀️</span><span class="occ-label">Summer / Lawn</span></div>
        <div class="occ-card" data-occasion="sports"  onclick="selectOcc(this)"><span class="occ-icon">🏃</span><span class="occ-label">Sports / Active</span></div>
      </div>
      <button class="btn-rec" id="btnOcc" onclick="getOccRec()" disabled>✨ Get AI Recommendations</button>
      <div style="margin-top:36px;">
        <div class="rec-loading" id="occ-loading"><div class="rec-spin"></div><p>Gemini AI is styling your outfit…</p></div>
        <div class="ai-box" id="occ-advice"></div>
        <div class="res-header" id="occ-hdr" style="display:none;"><h5 id="occ-title">Recommended Outfits</h5><span class="res-badge" id="occ-count">0</span></div>
        <div class="row grid-products" id="occ-products"></div>
        <div class="rec-empty" id="occ-empty">No products found for this occasion. Please add products with occasion tags from admin.</div>
      </div>
    </div>

    <!-- CULTURAL PANEL -->
    <div class="rec-panel" id="panel-cultural">
      <div class="sec-head">
        <h4>Explore Cultural Fashion</h4>
        <p>Discover the rich traditional clothing from Pakistan's diverse provinces and cultures</p>
      </div>
      <div class="cult-grid">
        <div class="cult-card" data-culture="punjabi"  onclick="selectCult(this)"><div class="cult-emoji">🌾</div><div class="cult-info"><div class="cult-name">Punjabi</div><div class="cult-sub">Punjab, Pakistan</div></div></div>
        <div class="cult-card" data-culture="sindhi"   onclick="selectCult(this)"><div class="cult-emoji">🏺</div><div class="cult-info"><div class="cult-name">Sindhi</div><div class="cult-sub">Sindh, Pakistan</div></div></div>
        <div class="cult-card" data-culture="balochi"  onclick="selectCult(this)"><div class="cult-emoji">🪡</div><div class="cult-info"><div class="cult-name">Balochi</div><div class="cult-sub">Balochistan</div></div></div>
        <div class="cult-card" data-culture="pashtun"  onclick="selectCult(this)"><div class="cult-emoji">🏔️</div><div class="cult-info"><div class="cult-name">Pashtun</div><div class="cult-sub">KPK, Pakistan</div></div></div>
        <div class="cult-card" data-culture="kashmiri" onclick="selectCult(this)"><div class="cult-emoji">🌸</div><div class="cult-info"><div class="cult-name">Kashmiri</div><div class="cult-sub">Azad Kashmir</div></div></div>
      </div>
      <button class="btn-rec" id="btnCult" onclick="getCultRec()" disabled>🇵🇰 Show Cultural Outfits</button>
      <div style="margin-top:36px;">
        <div class="rec-loading" id="cult-loading"><div class="rec-spin"></div><p>Gemini AI is finding cultural outfits…</p></div>
        <div class="ai-box" id="cult-advice"></div>
        <div class="res-header" id="cult-hdr" style="display:none;"><h5 id="cult-title">Cultural Outfits</h5><span class="res-badge" id="cult-count">0</span></div>
        <div class="row grid-products" id="cult-products"></div>
        <div class="rec-empty" id="cult-empty">No products found. Try another culture.</div>
      </div>
    </div>

  </div>
</section>

<?php include 'include/footer.php'; ?>

<script>
const AJAX = '<?= $url_domain ?>/recommend_ajax.php';
const IBASE = '<?= $url_domain ?>/admin/images/product/';
let selOcc = null, selCult = null;

function switchTab(tab, el) {
  document.querySelectorAll('.rec-tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  document.querySelectorAll('.rec-panel').forEach(p => p.classList.remove('active'));
  document.getElementById('panel-' + tab).classList.add('active');
}
function selectOcc(el) {
  document.querySelectorAll('.occ-card').forEach(c => c.classList.remove('selected'));
  el.classList.add('selected'); selOcc = el.dataset.occasion;
  document.getElementById('btnOcc').disabled = false;
}
function selectCult(el) {
  document.querySelectorAll('.cult-card').forEach(c => c.classList.remove('selected'));
  el.classList.add('selected'); selCult = el.dataset.culture;
  document.getElementById('btnCult').disabled = false;
}

// Render — identical structure to products.php cards
function renderProducts(products, containerId) {
  const c = document.getElementById(containerId);
  if (!products || !products.length) { c.innerHTML = ''; return false; }
  const WAIT = '<?= $url_domain ?>/images/waiting images.jpeg';
  c.innerHTML = products.map(p => {
    const img1  = p.img1 ? IBASE + p.img1 : WAIT;
    const img2  = p.img2 ? IBASE + p.img2 : img1;
    const url   = '<?= $url_domain ?>/product/' + encodeURIComponent(p.slug);
    const fmt   = n => 'RS. ' + parseFloat(n).toLocaleString('en-PK', {minimumFractionDigits:2, maximumFractionDigits:2});
    const priceHtml = p.hasDiscount
      ? `<span class="old-price">${fmt(p.price)}</span><span class="price">${fmt(p.final)}</span>`
      : `<span class="price">${fmt(p.final)}</span>`;
    const saleLabel = p.sale ? `<div class="product-labels rectangular"><span class="lbl pr-label1">${p.sale}</span></div>` : '';
    return `<div class="col-6 col-sm-6 col-md-3 col-lg-3 item">
      <div class="product-image">
        <a href="${url}">
          <img class="primary blur-up lazyload" src="${img1}" alt="${p.name}" title="product" onerror="this.src='${WAIT}'">
          <img class="hover blur-up lazyload"   src="${img2}" alt="${p.name}" title="product" onerror="this.src='${WAIT}'">
          ${saleLabel}
        </a>
        <div class="button-set d-none d-md-block">
          <div class="quickview-btn">
            <a href="${url}" class="btn quick-view rounded"><span>Quick View</span></a>
          </div>
        </div>
      </div>
      <div class="product-details text-center">
        <div class="product-name"><a href="${url}">${p.name}</a></div>
        <div class="product-price">${priceHtml}</div>
      </div>
    </div>`;
  }).join('');
  return true;
}

function showAdvice(id, text) {
  const el = document.getElementById(id);
  if (!text) { el.classList.remove('show'); return; }
  el.innerHTML = `<div class="ai-head"><span>🤖</span> AI Style Advice</div><p>${text}</p>`;
  el.classList.add('show');
}

function getOccRec() {
  if (!selOcc) return;
  document.getElementById('occ-loading').classList.add('show');
  document.getElementById('occ-advice').classList.remove('show');
  document.getElementById('occ-hdr').style.display='none';
  document.getElementById('occ-products').innerHTML='';
  document.getElementById('occ-empty').classList.remove('show');
  document.getElementById('btnOcc').disabled=true;
  const fd=new FormData(); fd.append('action','occasion'); fd.append('occasion',selOcc);
  fetch(AJAX,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
    document.getElementById('occ-loading').classList.remove('show');
    document.getElementById('btnOcc').disabled=false;
    if(!d.success||!d.products.length){document.getElementById('occ-empty').classList.add('show');return;}
    const labels={wedding:'Wedding / Shadi Outfits',eid:'Eid Collection',casual:'Casual Wear',office:'Office / Formal',party:'Party Wear',winter:'Winter Collection',summer:'Summer / Lawn',sports:'Sports Wear'};
    document.getElementById('occ-title').textContent=labels[selOcc]||'Recommended Outfits';
    document.getElementById('occ-count').textContent=d.products.length+' items';
    document.getElementById('occ-hdr').style.display='flex';
    showAdvice('occ-advice',d.advice);
    renderProducts(d.products,'occ-products');
    document.getElementById('occ-hdr').scrollIntoView({behavior:'smooth',block:'start'});
  }).catch(()=>{
    document.getElementById('occ-loading').classList.remove('show');
    document.getElementById('btnOcc').disabled=false;
    document.getElementById('occ-empty').classList.add('show');
  });
}

function getCultRec() {
  if (!selCult) return;
  document.getElementById('cult-loading').classList.add('show');
  document.getElementById('cult-advice').classList.remove('show');
  document.getElementById('cult-hdr').style.display='none';
  document.getElementById('cult-products').innerHTML='';
  document.getElementById('cult-empty').classList.remove('show');
  document.getElementById('btnCult').disabled=true;
  const fd=new FormData(); fd.append('action','cultural'); fd.append('culture',selCult);
  fetch(AJAX,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
    document.getElementById('cult-loading').classList.remove('show');
    document.getElementById('btnCult').disabled=false;
    if(!d.success||!d.products.length){document.getElementById('cult-empty').classList.add('show');return;}
    const cName=d.info?d.info.name:selCult;
    document.getElementById('cult-title').textContent=cName+' Traditional Fashion';
    document.getElementById('cult-count').textContent=d.products.length+' items';
    document.getElementById('cult-hdr').style.display='flex';
    showAdvice('cult-advice',d.advice);
    renderProducts(d.products,'cult-products');
    document.getElementById('cult-hdr').scrollIntoView({behavior:'smooth',block:'start'});
  }).catch(()=>{
    document.getElementById('cult-loading').classList.remove('show');
    document.getElementById('btnCult').disabled=false;
    document.getElementById('cult-empty').classList.add('show');
  });
}
</script>
