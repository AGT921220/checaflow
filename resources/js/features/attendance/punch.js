/**
 * Punch form: resolve GPS on load, show branch + device coords,
 * block submit when geofence requires location, optional mini-map.
 */
export function mountPunch(root) {
    if (!(root instanceof HTMLElement)) {
        return;
    }

    const form = root.querySelector('[data-punch-form]');
    const status = root.querySelector('[data-location-status]');
    const latInput = root.querySelector('[name="latitude"]');
    const lngInput = root.querySelector('[name="longitude"]');
    const deviceLat = root.querySelector('[data-device-lat]');
    const deviceLng = root.querySelector('[data-device-lng]');
    const deviceAccuracy = root.querySelector('[data-device-accuracy]');
    const deviceDistance = root.querySelector('[data-device-distance]');
    const requiresGeofence = root.dataset.requireGeofence === '1';
    const mapsKey = (root.dataset.mapsKey || '').trim();
    const branchLat = Number.parseFloat(root.dataset.branchLat || '');
    const branchLng = Number.parseFloat(root.dataset.branchLng || '');
    const branchRadius = Number.parseInt(root.dataset.branchRadius || '', 10);
    const mapCanvas = root.querySelector('[data-punch-map]');
    let mapInstance = null;
    let branchMarker = null;
    let userMarker = null;
    let geofenceCircle = null;

    const setStatus = (message, tone = 'muted') => {
        if (!(status instanceof HTMLElement)) {
            return;
        }

        status.textContent = message;
        status.dataset.tone = tone;
    };

    const setText = (node, value) => {
        if (node instanceof HTMLElement) {
            node.textContent = value;
        }
    };

    const formatCoord = (value) => value.toFixed(7);

    const distanceMeters = (fromLat, fromLng, toLat, toLng) => {
        const toRad = (deg) => (deg * Math.PI) / 180;
        const earth = 6371000;
        const dLat = toRad(toLat - fromLat);
        const dLng = toRad(toLng - fromLng);
        const a = Math.sin(dLat / 2) ** 2
            + Math.cos(toRad(fromLat)) * Math.cos(toRad(toLat)) * Math.sin(dLng / 2) ** 2;

        return earth * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    };

    const showDevice = (lat, lng, accuracy) => {
        setText(deviceLat, formatCoord(lat));
        setText(deviceLng, formatCoord(lng));

        if (Number.isFinite(accuracy)) {
            setText(deviceAccuracy, `${Math.round(accuracy)} m`);
        }

        if (Number.isFinite(branchLat) && Number.isFinite(branchLng)) {
            const meters = distanceMeters(lat, lng, branchLat, branchLng);
            setText(deviceDistance, `${Math.round(meters)} m`);
        }
    };

    const writeLocation = (lat, lng, accuracy) => {
        if (latInput instanceof HTMLInputElement) {
            latInput.value = String(lat);
        }

        if (lngInput instanceof HTMLInputElement) {
            lngInput.value = String(lng);
        }

        showDevice(lat, lng, accuracy);
        paintMap(lat, lng);
    };

    const paintMap = (userLat, userLng) => {
        if (!(mapCanvas instanceof HTMLElement) || mapsKey === '') {
            return;
        }

        const hasBranch = Number.isFinite(branchLat) && Number.isFinite(branchLng);
        const hasUser = Number.isFinite(userLat) && Number.isFinite(userLng);

        if (!hasBranch && !hasUser) {
            return;
        }

        ensureMaps(mapsKey).then((google) => {
            mapCanvas.hidden = false;

            const center = hasBranch
                ? { lat: branchLat, lng: branchLng }
                : { lat: userLat, lng: userLng };

            if (mapInstance === null) {
                mapInstance = new google.maps.Map(mapCanvas, {
                    center,
                    zoom: 16,
                    disableDefaultUI: true,
                    gestureHandling: 'cooperative',
                });
            }

            if (hasBranch && branchMarker === null) {
                branchMarker = new google.maps.Marker({
                    map: mapInstance,
                    position: { lat: branchLat, lng: branchLng },
                    title: 'Sucursal',
                });
            }

            if (hasBranch && Number.isFinite(branchRadius) && branchRadius > 0 && geofenceCircle === null) {
                geofenceCircle = new google.maps.Circle({
                    map: mapInstance,
                    center: { lat: branchLat, lng: branchLng },
                    radius: branchRadius,
                    fillColor: '#1767E2',
                    fillOpacity: 0.12,
                    strokeColor: '#1767E2',
                    strokeOpacity: 0.8,
                    strokeWeight: 2,
                });
            }

            if (hasUser) {
                const point = { lat: userLat, lng: userLng };

                if (userMarker === null) {
                    userMarker = new google.maps.Marker({
                        map: mapInstance,
                        position: point,
                        title: 'Tu ubicación',
                        icon: {
                            path: google.maps.SymbolPath.CIRCLE,
                            scale: 7,
                            fillColor: '#1767E2',
                            fillOpacity: 1,
                            strokeWeight: 2,
                            strokeColor: '#ffffff',
                        },
                    });
                }

                if (userMarker !== null) {
                    userMarker.setPosition(point);
                }

                if (hasBranch) {
                    const bounds = new google.maps.LatLngBounds();
                    bounds.extend({ lat: branchLat, lng: branchLng });
                    bounds.extend(point);
                    mapInstance.fitBounds(bounds, 48);
                }

                if (!hasBranch) {
                    mapInstance.setCenter(point);
                }
            }
        }).catch(() => {
            mapCanvas.hidden = true;
        });
    };

    if (Number.isFinite(branchLat) && Number.isFinite(branchLng) && mapsKey !== '') {
        paintMap(Number.NaN, Number.NaN);
    }

    if (!navigator.geolocation) {
        setStatus('Este navegador no comparte ubicación.', 'warn');
        setText(deviceLat, 'No disponible');
        setText(deviceLng, 'No disponible');
    }

    if (navigator.geolocation) {
        setStatus('Buscando tu ubicación…', 'muted');
        navigator.geolocation.getCurrentPosition(
            (position) => {
                writeLocation(
                    position.coords.latitude,
                    position.coords.longitude,
                    position.coords.accuracy,
                );
                setStatus('Ubicación del dispositivo lista.', 'ok');

                if (form instanceof HTMLFormElement) {
                    form.dataset.locating = 'ready';
                }
            },
            () => {
                setStatus(requiresGeofence
                    ? 'Ubicación denegada. Actívala para marcar en esta sucursal.'
                    : 'Ubicación denegada. Puedes marcar sin ella.', 'warn');
                setText(deviceLat, 'Denegada');
                setText(deviceLng, 'Denegada');

                if (form instanceof HTMLFormElement) {
                    form.dataset.locating = 'denied';
                }
            },
            { enableHighAccuracy: true, timeout: 8000, maximumAge: 0 },
        );

        navigator.geolocation.watchPosition(
            (position) => {
                writeLocation(
                    position.coords.latitude,
                    position.coords.longitude,
                    position.coords.accuracy,
                );

                if (form instanceof HTMLFormElement && form.dataset.locating !== 'done') {
                    form.dataset.locating = 'ready';
                }
            },
            () => undefined,
            { enableHighAccuracy: true, maximumAge: 5000 },
        );
    }

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    form.addEventListener('submit', (event) => {
        const hasPoint = latInput instanceof HTMLInputElement
            && lngInput instanceof HTMLInputElement
            && latInput.value !== ''
            && lngInput.value !== '';

        if (requiresGeofence && !hasPoint) {
            event.preventDefault();
            setStatus('Necesitas permitir la ubicación para marcar en esta sucursal.', 'warn');

            return;
        }

        if (form.dataset.locating === 'done' || form.dataset.locating === 'ready' || !navigator.geolocation) {
            return;
        }

        event.preventDefault();
        form.dataset.locating = 'done';
        setStatus('Confirmando ubicación…', 'muted');
        navigator.geolocation.getCurrentPosition(
            (position) => {
                writeLocation(
                    position.coords.latitude,
                    position.coords.longitude,
                    position.coords.accuracy,
                );
                form.requestSubmit();
            },
            () => {
                if (requiresGeofence) {
                    form.dataset.locating = 'denied';
                    setStatus('No se pudo obtener la ubicación. Intenta de nuevo.', 'warn');

                    return;
                }

                form.requestSubmit();
            },
            { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 },
        );
    });
}

function ensureMaps(key) {
    if (window.google?.maps?.Map) {
        return Promise.resolve(window.google);
    }

    if (window.__checaMapsPromise) {
        return window.__checaMapsPromise;
    }

    window.__checaMapsPromise = new Promise((resolve, reject) => {
        const callback = `__checaPunchMaps_${Date.now()}`;

        window[callback] = () => {
            resolve(window.google);
            delete window[callback];
        };

        const script = document.createElement('script');
        script.async = true;
        script.defer = true;
        script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(key)}&callback=${callback}`;
        script.onerror = () => reject(new Error('maps_load_failed'));
        document.head.appendChild(script);
    });

    return window.__checaMapsPromise;
}

document.querySelectorAll('[data-punch-shell]').forEach((root) => mountPunch(root));
