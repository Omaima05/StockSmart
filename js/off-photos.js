/**
 * StockSmart Pro — JS Global pour les photos
 * CORRECTION : Gère l'effet 'contain' pour ne pas zoomer l'image
 */

const OFF_CACHE = {};

async function fetchPhoto(query, element) {
    if (!query || query.length < 3) return;

    // Déjà en cache ?
    if (OFF_CACHE[query]) {
        element.src = OFF_CACHE[query];
        // --- FORCE LE CADRAGE ICI ---
        element.style.objectFit = 'contain';
        element.style.display = 'block';
        return;
    }

    try {
        const res = await fetch(`https://world.openfoodfacts.org/cgi/search.pl?search_terms=${encodeURIComponent(query)}&search_simple=1&action=process&json=1&page_size=1`);
        const data = await res.json();

        if (data.products && data.products[0]) {
            const url = data.products[0].image_front_url || data.products[0].image_url;
            if (url) {
                element.src = url;
                // --- FORCE LE CADRAGE ICI AUSSI ---
                element.style.objectFit = 'contain';
                element.style.display = 'block';
                OFF_CACHE[query] = url;
                localStorage.setItem('off_' + query, url);
            }
        }
    } catch (e) {
        console.error("Erreur API OFF:", e);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // 1. Charger les photos existantes dans le tableau
    document.querySelectorAll('img[data-search]').forEach((img, i) => {
        const q = img.getAttribute('data-search');
        if (!q) return;

        const cached = localStorage.getItem('off_' + q);
        if (cached) {
            img.src = cached;
            img.style.objectFit = 'contain';
        } else {
            // Petit délai pour ne pas flood l'API
            setTimeout(() => fetchPhoto(q, img), i * 120);
        }
    });

    // 2. Gérer l'aperçu en direct pendant l'ajout
    const iNom = document.getElementById('input_nom');
    const iMarq = document.getElementById('input_marque');
    const pImg = document.getElementById('live-preview-img');
    const pTxt = document.getElementById('preview-text');

    if (iNom && iMarq && pImg) {
        const update = () => {
            const q = (iMarq.value + " " + iNom.value).trim();
            if (q.length > 3) {
                fetchPhoto(q, pImg);
                if (pTxt) pTxt.style.display = 'none';
            }
        };
        // On déclenche la recherche quand on clique ailleurs
        iNom.addEventListener('blur', update);
        iMarq.addEventListener('blur', update);
    }
});
