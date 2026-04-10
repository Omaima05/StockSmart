/**
 * StockSmart Pro — Auto-photo via Open Food Facts
 * Inclure ce fichier dans produits.php ET dashboard.php
 * Usage : <img class="off-img" data-search="Jus Orange Tropicana" ...>
 */

// Cache en mémoire pour éviter les doublons
const OFF_CACHE = {};

async function fetchProductPhoto(searchTerm, imgElement) {
    if (!searchTerm || !imgElement) return;

    // Déjà en cache ?
    if (OFF_CACHE[searchTerm]) {
        imgElement.src = OFF_CACHE[searchTerm];
        return;
    }

    try {
        const url = `https://world.openfoodfacts.org/cgi/search.pl?search_terms=${encodeURIComponent(searchTerm)}&search_simple=1&action=process&json=1&page_size=3`;
        const res  = await fetch(url);
        const data = await res.json();

        // Chercher la première image valide dans les 3 résultats
        let photoUrl = null;
        for (const product of (data.products || [])) {
            const img = product.image_front_url || product.image_url;
            if (img && img.startsWith('http')) {
                photoUrl = img;
                break;
            }
        }

        if (photoUrl) {
            imgElement.src = photoUrl;
            imgElement.style.objectFit = 'contain';
            OFF_CACHE[searchTerm] = photoUrl; // mise en cache
        }
        // Si rien trouvé → on garde l'avatar par défaut, pas d'erreur

    } catch (e) {
        // Pas de connexion ou erreur API → avatar par défaut reste
    }
}

// Lancer automatiquement sur tous les éléments avec data-search
document.addEventListener('DOMContentLoaded', () => {
    const images = document.querySelectorAll('img[data-search]');

    images.forEach((img, index) => {
        const search = img.getAttribute('data-search');
        if (!search) return;

        // Délai progressif pour ne pas flood l'API (50ms entre chaque)
        setTimeout(() => {
            fetchProductPhoto(search, img);
        }, index * 80);
    });
});
