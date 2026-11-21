const DEFAULT_CENTER = { lat: 19.4326, lng: -99.1332 };
const DEFAULT_RADIUS = 100;

/**
 * Branch form map: click/drag pin, geofence circle, Places autocomplete.
 * Root needs data-maps-key (may be empty) and a form with lat/lng/radius/address.
 */
export function mountBranchMap(root) {
    if (!(root instanceof HTMLElement)) {
        return;
    }

    if (root.dataset.mapMounted === '1') {
        return;
    }

    root.dataset.mapMounted = '1';

    const key = (root.dataset.mapsKey || '').trim();
    const form = root.closest('form') ?? document.querySelector(root.dataset.form || 'form');
    const canvas = root.querySelector('[data-branch-map-canvas]');
    const notice = root.querySelector('[data-branch-map-notice]');

    if (!(form instanceof HTMLFormElement) || !(canvas instanceof HTMLElement)) {
        return;
    }

    if (key === '') {
        if (notice instanceof HTMLElement) {
            notice.hidden = false;
            notice.textContent = 'Configura GOOGLE_MAPS_API_KEY para usar el mapa. Puedes capturar latitud, longitud y radio a mano.';
        }

        canvas.hidden = true;

        return;
    }

    if (notice instanceof HTMLElement) {
        notice.hidden = true;
    }

    loadMaps(key).then((google) => bindMap(google, form, canvas)).catch(() => {
        if (notice instanceof HTMLElement) {
            notice.hidden = false;
            notice.textContent = 'No se pudo cargar Google Maps. Usa latitud, longitud y radio a mano.';
        }

        canvas.hidden = true;
    });
}

function loadMaps(key) {
    if (window.google?.maps?.Map) {
        return Promise.resolve(window.google);
    }

    if (window.__checaMapsPromise) {
        return window.__checaMapsPromise;
    }

    window.__checaMapsPromise = new Promise((resolve, reject) => {
        const callback = `__checaMapsReady_${Date.now()}`;

        window[callback] = () => {
            resolve(window.google);
            delete window[callback];
        };

        const script = document.createElement('script');
        script.async = true;
        script.defer = true;
        script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(key)}&libraries=places&callback=${callback}`;
        script.onerror = () => reject(new Error('maps_load_failed'));
        document.head.appendChild(script);
    });

    return window.__checaMapsPromise;
}

function bindMap(google, form, canvas) {
    const latInput = form.querySelector('[name="latitude"]');
    const lngInput = form.querySelector('[name="longitude"]');
    const radiusInput = form.querySelector('[name="geofence_radius_meters"]');
    const addressInput = form.querySelector('[name="address"]');

    const start = readPoint(latInput, lngInput) ?? DEFAULT_CENTER;
    const map = new google.maps.Map(canvas, {
        center: start,
        zoom: 16,
        mapTypeControl: false,
        streetViewControl: false,
        fullscreenControl: false,
    });

    const marker = new google.maps.Marker({
        map,
        position: start,
        draggable: true,
    });

    let circle = null;

    const redrawCircle = () => {
        const radius = readRadius(radiusInput);
        const position = marker.getPosition();

        if (circle !== null) {
            circle.setMap(null);
            circle = null;
        }

        if (position === null || radius === null) {
            return;
        }

        circle = new google.maps.Circle({
            map,
            center: position,
            radius,
            fillColor: '#1767E2',
            fillOpacity: 0.15,
            strokeColor: '#1767E2',
            strokeOpacity: 0.7,
            strokeWeight: 2,
        });
    };

    const writePoint = (point) => {
        if (latInput instanceof HTMLInputElement) {
            latInput.value = point.lat.toFixed(7);
        }

        if (lngInput instanceof HTMLInputElement) {
            lngInput.value = point.lng.toFixed(7);
        }

        marker.setPosition(point);
        map.panTo(point);
        redrawCircle();
    };

    map.addListener('click', (event) => {
        if (event.latLng === undefined || event.latLng === null) {
            return;
        }

        writePoint({ lat: event.latLng.lat(), lng: event.latLng.lng() });
    });

    marker.addListener('dragend', () => {
        const position = marker.getPosition();

        if (position === null) {
            return;
        }

        writePoint({ lat: position.lat(), lng: position.lng() });
    });

    if (radiusInput instanceof HTMLInputElement) {
        radiusInput.addEventListener('input', redrawCircle);
        radiusInput.addEventListener('change', redrawCircle);
    }

    if (addressInput instanceof HTMLInputElement && google.maps.places?.Autocomplete) {
        const autocomplete = new google.maps.places.Autocomplete(addressInput, {
            fields: ['formatted_address', 'geometry'],
        });

        autocomplete.addListener('place_changed', () => {
            const place = autocomplete.getPlace();
            const location = place.geometry?.location;

            if (location === undefined || location === null) {
                return;
            }

            if (place.formatted_address) {
                addressInput.value = place.formatted_address;
            }

            writePoint({ lat: location.lat(), lng: location.lng() });
        });
    }

    if (latInput instanceof HTMLInputElement && lngInput instanceof HTMLInputElement) {
        const syncFromInputs = () => {
            const point = readPoint(latInput, lngInput);

            if (point === null) {
                return;
            }

            writePoint(point);
        };

        latInput.addEventListener('change', syncFromInputs);
        lngInput.addEventListener('change', syncFromInputs);
    }

    if (readPoint(latInput, lngInput) === null && radiusInput instanceof HTMLInputElement && radiusInput.value === '') {
        radiusInput.value = String(DEFAULT_RADIUS);
    }

    redrawCircle();
}

function readPoint(latInput, lngInput) {
    if (!(latInput instanceof HTMLInputElement) || !(lngInput instanceof HTMLInputElement)) {
        return null;
    }

    const lat = Number.parseFloat(latInput.value);
    const lng = Number.parseFloat(lngInput.value);

    if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
        return null;
    }

    return { lat, lng };
}

function readRadius(radiusInput) {
    if (!(radiusInput instanceof HTMLInputElement)) {
        return null;
    }

    const radius = Number.parseInt(radiusInput.value, 10);

    if (!Number.isFinite(radius) || radius < 1) {
        return null;
    }

    return radius;
}

function mountVisibleMaps(scope = document) {
    scope.querySelectorAll('[data-branch-map]').forEach((root) => {
        if (!(root instanceof HTMLElement)) {
            return;
        }

        const modal = root.closest('[data-modal]');

        if (modal instanceof HTMLElement && modal.hidden) {
            return;
        }

        mountBranchMap(root);
    });
}

mountVisibleMaps();

document.addEventListener('cf:modal-open', (event) => {
    if (!(event.target instanceof HTMLElement)) {
        return;
    }

    mountVisibleMaps(event.target);
});
