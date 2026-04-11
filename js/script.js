/**
 * StockSmart Pro — script.js
 */

/* ── OPEN FOOD FACTS avec cache localStorage ─────────────────── */
const OFF_MEM = {};

async function loadProductPhoto(img) {
  const search  = img.getAttribute('data-search');
  const wrapId  = img.getAttribute('data-wrapid');
  const wrap    = wrapId ? document.getElementById(wrapId) : null;

  if (!search) return;

  img.setAttribute('data-current', search);

  const markLoaded = () => { if (wrap) wrap.classList.add('loaded'); };

  // 1. Cache localStorage
  const cacheKey = 'off_' + search;
  const cached   = localStorage.getItem(cacheKey);
  if (cached) {
    img.src = cached;
    img.style.objectFit = 'cover';
    img.style.width     = '80px';
    img.style.height    = '80px';
    markLoaded();
    return;
  }

  // 2. Cache mémoire
  if (OFF_MEM[search]) {
    img.src = OFF_MEM[search];
    img.style.objectFit = 'cover';
    img.style.width     = '80px';
    img.style.height    = '80px';
    markLoaded();
    return;
  }

  try {
    const url  = 'https://world.openfoodfacts.org/cgi/search.pl'
               + '?search_terms=' + encodeURIComponent(search)
               + '&search_simple=1&action=process&json=1&page_size=5';

    const data = await fetch(url).then(r => r.json());

    let photoUrl = null;
    for (const product of (data.products || [])) {
      const candidate = product.image_front_url || product.image_url;
      if (candidate && candidate.startsWith('http')) {
        photoUrl = candidate;
        break;
      }
    }

    if (photoUrl) {
      if (img.getAttribute('data-current') !== search) return;

      img.src = photoUrl;
      img.style.objectFit = 'cover';
      img.style.width     = '80px';
      img.style.height    = '80px';

      OFF_MEM[search] = photoUrl;
      try { localStorage.setItem(cacheKey, photoUrl); } catch(e) {}
    }

  } catch (e) {
    // Pas de reseau → avatar par defaut reste
  } finally {
    markLoaded();
  }
}

/* ── INITIALISATION ─────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function () {

  const images = document.querySelectorAll('img[data-search]');
  images.forEach(function (img, i) {
    setTimeout(function () { loadProductPhoto(img); }, i * 150);
  });

  const searchInput = document.getElementById('searchInput');
  if (searchInput) {
    searchInput.addEventListener('keyup', function () {
      const filter = this.value.toLowerCase();
      document.querySelectorAll('.product-row').forEach(function (row) {
        row.style.display = row.innerText.toLowerCase().includes(filter) ? '' : 'none';
      });
    });
  }

  document.querySelectorAll('a[href^="#"]').forEach(function (a) {
    a.addEventListener('click', function (e) {
      const target = document.querySelector(this.getAttribute('href'));
      if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth' }); }
    });
  });

});

/* ── UTILITAIRES ────────────────────────────────────────────── */
function copyToClipboard(text, btn) {
  navigator.clipboard.writeText(text).then(function () {
    if (btn) {
      var orig = btn.textContent;
      btn.textContent = 'Copie !';
      setTimeout(function () { btn.textContent = orig; }, 1500);
    }
  });
}

function confirmDelete(msg) {
  return confirm(msg || 'Confirmer la suppression ?');
}
