mapboxgl.accessToken = 'pk.eyJ1IjoiZWxpZWwwNiIsImEiOiJjbWRqMjJsMHAwYmxuMmpzNW1xbmlldXA1In0.7S97Hn4TRZp-q6X3TW2UuQ';

let map, marker;
let suggestions = [];
let selectedIndex = -1;
let searchTimeout;

// Déterminer la page actuelle
const currentPage = window.location.search.includes('page=details') ? 'details' : 
                   window.location.search.includes('page=creation-evenement') ? 'creation' : 'other';

// Éléments pour la page de création d'événement
const input = document.getElementById('event-location');
const suggestionsContainer = document.getElementById('geocoder-container');
const latitudeInput = document.querySelector('input[name="latitude"]');
const longitudeInput = document.querySelector('input[name="longitude"]');

// Position par défaut à Abidjan si géolocalisation échoue
const defaultCenter = [-4.0267, 5.3364]; // Abidjan, Côte d'Ivoire

// Vérifier si nous sommes sur une page avec une carte
const mapElement = document.getElementById('map');
if (mapElement) {
    // Initialisation avec géolocalisation (priorité à la position actuelle)
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(successLocation, errorLocation, {
            enableHighAccuracy: true,
            timeout: 15000,
            maximumAge: 600000 // Cache de 10 minutes
        });
    } else {
        setupMap(defaultCenter);
    }
}

function successLocation(position) {
    const lng = position.coords.longitude;
    const lat = position.coords.latitude;

    // Utiliser directement la position actuelle de l'utilisateur
    setupMap([lng, lat]);
}

function errorLocation() {
    // Si géolocalisation échoue, utiliser Abidjan
    setupMap(defaultCenter);
}

function setupMap(center) {
    // Vérifier si l'élément de carte existe
    const mapContainer = document.getElementById('map');
    if (!mapContainer) return;

    map = new mapboxgl.Map({
        container: 'map',
        style: 'mapbox://styles/mapbox/streets-v12',
        center: center,
        zoom: 14
    });

    // Configurer la carte en fonction de la page
    if (currentPage === 'details') {
        // Pour la page de détails, récupérer les coordonnées de l'événement
        const eventLatLng = getEventCoordinates();
        if (eventLatLng) {
            // Marqueur non déplaçable pour la page de détails
            marker = new mapboxgl.Marker()
                .setLngLat(eventLatLng)
                .addTo(map);

            // Centrer la carte sur l'événement
            map.setCenter(eventLatLng);

            // Ajouter le bouton d'itinéraire
            setupDirectionsButton(eventLatLng);
        }
    } else {
        // Pour la page de création d'événement
        // Ajouter le marqueur déplaçable
        marker = new mapboxgl.Marker({draggable: true})
            .setLngLat(center)
            .addTo(map);

        // Stocke la position initiale si les champs existent
        if (longitudeInput && latitudeInput) {
            longitudeInput.value = center[0];
            latitudeInput.value = center[1];

            // Event listener pour le drag du marqueur
            marker.on('dragend', () => {
                const lngLat = marker.getLngLat();
                longitudeInput.value = lngLat.lng;
                latitudeInput.value = lngLat.lat;
                if (input) {
                    reverseGeocode(lngLat.lng, lngLat.lat);
                }
            });
        }
    }
}

// Fonction pour récupérer les coordonnées de l'événement sur la page de détails
function getEventCoordinates() {
    // Essayer de récupérer les coordonnées depuis les attributs data
    const mapContainer = document.getElementById('map');
    if (mapContainer) {
        // Chercher les coordonnées dans l'URL (pour la démo)
        const urlParams = new URLSearchParams(window.location.search);
        const eventId = urlParams.get('id') || urlParams.get('info-event');

        // Récupérer les coordonnées depuis le script PHP
        const lat = mapContainer.getAttribute('data-lat');
        const lng = mapContainer.getAttribute('data-lng');

        if (lat && lng) {
            return [parseFloat(lng), parseFloat(lat)];
        }
    }
    return null;
}

// Fonction pour configurer le bouton d'itinéraire
function setupDirectionsButton(destination) {
    const directionsButton = document.getElementById('get-directions');
    const directionsContainer = document.getElementById('directions-container');
    const directionsInfo = document.getElementById('directions-info');

    if (directionsButton && directionsContainer && directionsInfo) {
        directionsButton.addEventListener('click', () => {
            if (navigator.geolocation) {
                directionsButton.textContent = 'Chargement...';
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const origin = [position.coords.longitude, position.coords.latitude];
                        getDirections(origin, destination, directionsInfo);
                        directionsContainer.style.display = 'block';
                        directionsButton.textContent = 'Obtenir l\'itinéraire';
                    },
                    () => {
                        directionsInfo.innerHTML = '<p>Impossible d\'obtenir votre position actuelle.</p>';
                        directionsContainer.style.display = 'block';
                        directionsButton.textContent = 'Obtenir l\'itinéraire';
                    }
                );
            } else {
                directionsInfo.innerHTML = '<p>La géolocalisation n\'est pas supportée par votre navigateur.</p>';
                directionsContainer.style.display = 'block';
            }
        });
    }
}

// Fonction pour obtenir les directions
function getDirections(origin, destination, container) {
    const url = `https://api.mapbox.com/directions/v5/mapbox/driving/${origin[0]},${origin[1]};${destination[0]},${destination[1]}?steps=true&geometries=geojson&access_token=${mapboxgl.accessToken}&language=fr`;

    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.routes && data.routes.length > 0) {
                const route = data.routes[0];
                const distance = (route.distance / 1000).toFixed(1);
                const duration = Math.round(route.duration / 60);

                let html = `
                    <div class="directions-summary">
                        <p><strong>Distance:</strong> ${distance} km</p>
                        <p><strong>Durée estimée:</strong> ${duration} minutes</p>
                    </div>
                    <div class="directions-steps">
                        <h5>Instructions:</h5>
                        <ol>
                `;

                route.legs[0].steps.forEach(step => {
                    html += `<li>${step.maneuver.instruction}</li>`;
                });

                html += `
                        </ol>
                    </div>
                    <div class="directions-link">
                        <a href="https://www.mapbox.com/directions/?destination=${destination[1]},${destination[0]}" target="_blank" class="btn-text">
                            Ouvrir dans Mapbox
                        </a>
                    </div>
                `;

                container.innerHTML = html;

                // Dessiner l'itinéraire sur la carte
                if (map.getSource('route')) {
                    map.getSource('route').setData({
                        'type': 'Feature',
                        'properties': {},
                        'geometry': route.geometry
                    });
                } else {
                    map.addSource('route', {
                        'type': 'geojson',
                        'data': {
                            'type': 'Feature',
                            'properties': {},
                            'geometry': route.geometry
                        }
                    });

                    map.addLayer({
                        'id': 'route',
                        'type': 'line',
                        'source': 'route',
                        'layout': {
                            'line-join': 'round',
                            'line-cap': 'round'
                        },
                        'paint': {
                            'line-color': '#3887be',
                            'line-width': 5,
                            'line-opacity': 0.75
                        }
                    });
                }

                // Ajuster la vue pour voir tout l'itinéraire
                const bounds = new mapboxgl.LngLatBounds()
                    .extend(origin)
                    .extend(destination);

                map.fitBounds(bounds, {
                    padding: 50
                });
            } else {
                container.innerHTML = '<p>Impossible de trouver un itinéraire.</p>';
            }
        })
        .catch(error => {
            console.error('Erreur lors de la récupération de l\'itinéraire:', error);
            container.innerHTML = '<p>Erreur lors de la récupération de l\'itinéraire.</p>';
        });
}

// Event listeners pour l'input (seulement si l'input existe)
if (input) {
    input.addEventListener('input', (e) => {
        const query = e.target.value.trim();

        if (query.length < 2) {
            hideSuggestions();
            return;
        }

        // Debounce la recherche
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            searchAddresses(query);
        }, 300);
    });

    input.addEventListener('keydown', (e) => {
        if (!suggestionsContainer || !suggestionsContainer.style.display || suggestionsContainer.style.display === 'none') {
            return;
        }

        switch(e.key) {
            case 'ArrowDown':
                e.preventDefault();
                selectedIndex = Math.min(selectedIndex + 1, suggestions.length - 1);
                updateHighlight();
                break;
            case 'ArrowUp':
                e.preventDefault();
                selectedIndex = Math.max(selectedIndex - 1, -1);
                updateHighlight();
                break;
            case 'Enter':
                e.preventDefault();
                if (selectedIndex >= 0 && suggestions[selectedIndex]) {
                    selectSuggestion(suggestions[selectedIndex]);
                }
                break;
            case 'Escape':
                hideSuggestions();
                break;
        }
    });
}

// Cacher les suggestions quand on clique ailleurs
document.addEventListener('click', (e) => {
    if (!e.target.closest('.form-group')) {
        hideSuggestions();
    }
});

async function searchAddresses(query) {
    try {
        showLoading();

        const abidjanCoords = '-4.0267,5.3364';

        // Recherche complète comme Google Maps - sans restriction de pays ni de types
        const urls = [
            // Recherche 1: Terme exact avec proximité CI
            `https://api.mapbox.com/geocoding/v5/mapbox.places/${encodeURIComponent(query)}.json?access_token=${mapboxgl.accessToken}&limit=10&language=fr&proximity=${abidjanCoords}&bbox=-8.6,4.2,-2.5,10.8`,

            // Recherche 2: Avec "Côte d'Ivoire" ajouté
            `https://api.mapbox.com/geocoding/v5/mapbox.places/${encodeURIComponent(query + ' Côte d\'Ivoire')}.json?access_token=${mapboxgl.accessToken}&limit=8&language=fr`,

            // Recherche 3: Avec "Abidjan" pour les lieux locaux
            `https://api.mapbox.com/geocoding/v5/mapbox.places/${encodeURIComponent(query + ' Abidjan')}.json?access_token=${mapboxgl.accessToken}&limit=8&language=fr`,

            // Recherche 4: Avec "CI" ajouté
            `https://api.mapbox.com/geocoding/v5/mapbox.places/${encodeURIComponent(query + ' CI')}.json?access_token=${mapboxgl.accessToken}&limit=6&language=fr&proximity=${abidjanCoords}`,

            // Recherche 5: Recherche globale sans restriction
            `https://api.mapbox.com/geocoding/v5/mapbox.places/${encodeURIComponent(query)}.json?access_token=${mapboxgl.accessToken}&limit=8&language=fr`
        ];

        // Exécuter toutes les recherches en parallèle
        const responses = await Promise.allSettled(
            urls.map(url => fetch(url))
        );

        let allFeatures = [];

        // Traiter chaque réponse
        for (const response of responses) {
            if (response.status === 'fulfilled' && response.value.ok) {
                try {
                    const data = await response.value.json();
                    if (data.features && Array.isArray(data.features)) {
                        allFeatures = allFeatures.concat(data.features);
                    }
                } catch (parseError) {
                    console.warn('Erreur parsing JSON:', parseError);
                }
            }
        }

        console.log(`Recherche "${query}": ${allFeatures.length} résultats trouvés`);
        console.log('Types:', [...new Set(allFeatures.flatMap(f => f.place_type || []))]);

        // Éliminer les doublons avec une méthode plus robuste
        const uniqueFeatures = [];
        const seenPlaces = new Set();

        allFeatures.forEach(feature => {
            // Créer une clé unique basée sur nom + coordonnées
            const coords = feature.center ? feature.center.join(',') : '';
            const name = feature.place_name || feature.text || '';
            const uniqueKey = `${name}|${coords}`;

            if (!seenPlaces.has(uniqueKey) && feature.center) {
                seenPlaces.add(uniqueKey);
                uniqueFeatures.push(feature);
            }
        });

        // Filtrer pour prioriser les résultats de Côte d'Ivoire tout en gardant le reste
        const ivoirianResults = uniqueFeatures.filter(feature =>
            isFromCoteDivoire(feature)
        );

        const otherResults = uniqueFeatures.filter(feature =>
            !isFromCoteDivoire(feature)
        ).slice(0, 5); // Limiter les résultats non-ivoiriens

        // Combiner en priorisant la CI
        const combinedResults = [...ivoirianResults, ...otherResults];

        // Trier les résultats par pertinence
        suggestions = sortSuggestionsByRelevance(combinedResults, query).slice(0, 15);
        selectedIndex = -1;

        await new Promise(resolve => setTimeout(resolve, 100));
        displaySuggestions();

    } catch (error) {
        console.error('Erreur lors de la recherche:', error);
        showError();
    }
}

function isFromCoteDivoire(feature) {
    const placeName = (feature.place_name || '').toLowerCase();
    const context = feature.context || [];

    // Vérifier dans le nom du lieu
    if (placeName.includes('côte d\'ivoire') ||
        placeName.includes('cote d\'ivoire') ||
        placeName.includes('ivory coast') ||
        placeName.includes('abidjan') ||
        placeName.includes('yamoussoukro') ||
        placeName.includes('bouaké') ||
        placeName.includes('daloa') ||
        placeName.includes('korhogo') ||
        placeName.includes('san-pédro') ||
        placeName.includes('man') ||
        placeName.includes('divo') ||
        placeName.includes('gagnoa')) {
        return true;
    }

    // Vérifier dans le contexte
    return context.some(ctx => {
        const ctxText = (ctx.text || '').toLowerCase();
        return ctxText.includes('côte d\'ivoire') ||
            ctxText.includes('cote d\'ivoire') ||
            ctxText.includes('ivory coast') ||
            ctx.short_code === 'ci';
    });
}

function sortSuggestionsByRelevance(features, query) {
    const queryLower = query.toLowerCase();

    return features.sort((a, b) => {
        const textA = (a.text || a.place_name || '').toLowerCase();
        const textB = (b.text || b.place_name || '').toLowerCase();
        const placeNameA = (a.place_name || '').toLowerCase();
        const placeNameB = (b.place_name || '').toLowerCase();

        // Priorité 1: Correspondance exacte avec le terme de recherche
        const exactMatchA = textA === queryLower;
        const exactMatchB = textB === queryLower;
        if (exactMatchA && !exactMatchB) return -1;
        if (!exactMatchA && exactMatchB) return 1;

        // Priorité 2: Commence par le terme de recherche
        const startsWithA = textA.startsWith(queryLower);
        const startsWithB = textB.startsWith(queryLower);
        if (startsWithA && !startsWithB) return -1;
        if (!startsWithA && startsWithB) return 1;

        // Priorité 3: Résultats de Côte d'Ivoire
        const isIvoirianA = isFromCoteDivoire(a);
        const isIvoirianB = isFromCoteDivoire(b);
        if (isIvoirianA && !isIvoirianB) return -1;
        if (!isIvoirianA && isIvoirianB) return 1;

        // Priorité 4: Résultats d'Abidjan (dans les résultats ivoiriens)
        if (isIvoirianA && isIvoirianB) {
            const isAbidjanA = placeNameA.includes('abidjan');
            const isAbidjanB = placeNameB.includes('abidjan');
            if (isAbidjanA && !isAbidjanB) return -1;
            if (!isAbidjanA && isAbidjanB) return 1;
        }

        // Priorité 5: Contient le terme de recherche
        const containsA = textA.includes(queryLower) || placeNameA.includes(queryLower);
        const containsB = textB.includes(queryLower) || placeNameB.includes(queryLower);
        if (containsA && !containsB) return -1;
        if (!containsA && containsB) return 1;

        // Priorité 6: Distance de correspondance (plus le terme est proche du début, mieux c'est)
        const indexA = Math.max(textA.indexOf(queryLower), placeNameA.indexOf(queryLower));
        const indexB = Math.max(textB.indexOf(queryLower), placeNameB.indexOf(queryLower));
        if (indexA !== indexB && indexA >= 0 && indexB >= 0) {
            return indexA - indexB;
        }

        // Priorité 7: Type de lieu (POI et addresses en premier)
        const typeOrderA = getTypeOrder(a.place_type);
        const typeOrderB = getTypeOrder(b.place_type);
        if (typeOrderA !== typeOrderB) return typeOrderA - typeOrderB;

        // Priorité 8: Longueur du nom (plus court = plus spécifique)
        return textA.length - textB.length;
    });
}

function getTypeOrder(placeTypes) {
    if (!placeTypes || !placeTypes.length) return 99;

    const order = {
        'poi': 1,
        'address': 2,
        'place': 3,
        'locality': 4,
        'neighborhood': 5,
        'district': 6,
        'region': 7,
        'country': 8
    };

    return order[placeTypes[0]] || 10;
}

function showLoading() {
    if (suggestionsContainer) {
        suggestionsContainer.innerHTML = '<div class="loading">🔍 Recherche en cours...</div>';
        suggestionsContainer.style.display = 'block';
    }
}

function showError() {
    if (suggestionsContainer) {
        suggestionsContainer.innerHTML = '<div class="no-results">❌ Erreur lors de la recherche. Réessayez.</div>';
    }
}

function displaySuggestions() {
    if (!suggestionsContainer) return;

    if (suggestions.length === 0) {
        suggestionsContainer.innerHTML = '<div class="no-results">🔍 Aucun lieu trouvé. Essayez avec un autre terme.</div>';
        return;
    }

    const html = suggestions.map((suggestion, index) => {
        const icon = getPlaceIcon(suggestion.place_type);
        const mainText = suggestion.text || suggestion.place_name.split(',')[0];
        const subText = suggestion.place_name.replace(mainText, '').replace(/^,\s*/, '');

        // Marquer les résultats d'Abidjan avec un badge
        const isAbidjan = suggestion.place_name.toLowerCase().includes('abidjan');
        const badge = isAbidjan ? '<span style="color: var(--text-highlight, #0066cc); font-size: 10px; margin-left: 4px;">📍 ABJ</span>' : '';

        // Marquer les POI avec un badge spécial
        const isPOI = suggestion.place_type && suggestion.place_type.includes('poi');
        const poiBadge = isPOI ? '<span style="color: #ff6b35; font-size: 10px; margin-left: 4px;">🏢</span>' : '';

        return `
            <div class="suggestion-item" data-index="${index}">
                <span class="suggestion-icon">${icon}</span>
                <div class="suggestion-text">
                    <div class="suggestion-main">${mainText} ${badge} ${poiBadge}</div>
                    ${subText ? `<div class="suggestion-sub">${subText}</div>` : ''}
                </div>
            </div>
        `;
    }).join('');

    suggestionsContainer.innerHTML = html;
    suggestionsContainer.style.display = 'block';

    // Ajouter les event listeners aux suggestions
    suggestionsContainer.querySelectorAll('.suggestion-item').forEach(item => {
        item.addEventListener('click', () => {
            const index = parseInt(item.dataset.index);
            selectSuggestion(suggestions[index]);
        });
    });
}

function updateHighlight() {
    if (!suggestionsContainer) return;

    const items = suggestionsContainer.querySelectorAll('.suggestion-item');
    items.forEach((item, index) => {
        item.classList.toggle('highlighted', index === selectedIndex);
    });
}

function selectSuggestion(suggestion) {
    const coords = suggestion.center;

    // Mettre à jour l'input
    input.value = suggestion.place_name;

    // Déplacer le marqueur et la carte
    marker.setLngLat(coords);
    map.flyTo({center: coords, zoom: 16});

    // Mettre à jour les champs cachés
    longitudeInput.value = coords[0];
    latitudeInput.value = coords[1];

    // Cacher les suggestions
    hideSuggestions();
}

function hideSuggestions() {
    if (suggestionsContainer) {
        suggestionsContainer.style.display = 'none';
    }
    selectedIndex = -1;
}

function getPlaceIcon(placeTypes) {
    return '📍';
}

async function reverseGeocode(lng, lat) {
    try {
        const url = `https://api.mapbox.com/geocoding/v5/mapbox.places/${lng},${lat}.json?access_token=${mapboxgl.accessToken}&language=fr&country=ci&types=poi,address,place,locality,neighborhood`;
        const response = await fetch(url);

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.features && data.features.length > 0) {
            input.value = data.features[0].place_name;
        }
    } catch (error) {
        console.error('Erreur lors du géocodage inverse:', error);
        // En cas d'erreur, on garde la valeur actuelle
    }
}
