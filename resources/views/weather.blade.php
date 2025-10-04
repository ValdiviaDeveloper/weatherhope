<x-guest-layout>
    <div class="flex flex-col md:flex-row h-screen bg-gray-100">
        <!-- Map Column -->
        <div class="md:w-3/4 h-1/2 md:h-full" id="map"></div>

        <!-- Info & Search Column -->
        <div class="md:w-1/4 w-full p-6 bg-white shadow-lg overflow-y-auto flex flex-col">
            <div class="flex-shrink-0">
                <h2 class="text-3xl font-bold text-gray-800 mb-4">WeatherHope</h2>
                <div class="mb-6 relative">
                    <label for="location-search" class="block text-sm font-medium text-gray-700">Buscar Ubicación</label>
                    <div class="mt-1 flex rounded-md shadow-sm">
                        <input type="text" id="location-search" class="text-gray-900 flex-1 block w-full rounded-none rounded-l-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Ej: Arequipa, Perú">
                        <button id="search-button" class="inline-flex items-center rounded-r-md border border-l-0 border-gray-300 bg-gray-50 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">Buscar</button>
                    </div>
                    <div id="suggestions-box" class="absolute z-10 w-full bg-white border border-gray-300 rounded-md mt-1 shadow-lg hidden"></div>
                </div>
            </div>

            <!-- Main Info Container -->
            <div class="flex-grow flex items-center justify-center">
                <div id="info-container" class="w-full text-center">
                    <!-- Welcome State -->
                    <div id="welcome-panel">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        <h3 class="mt-2 text-lg font-medium text-gray-900">Dashboard Ambiental</h3>
                        <p class="mt-1 text-sm text-gray-500">Haz clic en el mapa o busca una ubicación para ver los datos.</p>
                    </div>

                    <!-- Loading State -->
                    <div id="loading-spinner" class="hidden">
                        <svg class="animate-spin mx-auto h-10 w-10 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <p class="mt-2 text-sm text-gray-600">Obteniendo datos ambientales...</p>
                    </div>

                    <!-- Results State with Tabs -->
                    <div id="results-panel" class="hidden w-full">
                        <div class="border-b border-gray-200">
                            <nav class="-mb-px flex space-x-4" aria-label="Tabs">
                                <button class="tab-btn whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm" data-tab="clima">Clima</button>
                                <button class="tab-btn whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm" data-tab="aire">Calidad de Aire</button>
                                <button class="tab-btn whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm" data-tab="hora">Por Hora</button>
                                <button class="tab-btn whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm" data-tab="suelo">Humedad de Suelo</button>
                            </nav>
                        </div>
                        <div class="pt-4">
                            <div id="clima-content" class="tab-content"></div>
                            <div id="aire-content" class="tab-content hidden"><p class="text-gray-500">Datos de calidad de aire próximamente.</p></div>
                            <div id="hora-content" class="tab-content hidden"></div>
                            <div id="suelo-content" class="tab-content hidden"><p class="text-gray-500">Datos de humedad de suelo próximamente.</p></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        #map { z-index: 0; }
        .tab-btn.active { border-color: #3b82f6; color: #3b82f6; }
        .tab-btn:not(.active) { border-color: transparent; color: #6b7280; }
    </style>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // UI Elements
            const welcomePanel = document.getElementById('welcome-panel');
            const loadingSpinner = document.getElementById('loading-spinner');
            const resultsPanel = document.getElementById('results-panel');
            const searchInput = document.getElementById('location-search');
            const searchButton = document.getElementById('search-button');
            const suggestionsBox = document.getElementById('suggestions-box');
            const tabButtons = document.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.tab-content');

            // Map
            const map = L.map('map').setView([-9.19, -75.01], 5);
            let marker = null;
            let debounceTimer;
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '© OpenStreetMap' }).addTo(map);

            // State Management
            const showState = (state) => {
                welcomePanel.classList.add('hidden');
                loadingSpinner.classList.add('hidden');
                resultsPanel.classList.add('hidden');
                if (document.getElementById(state)) {
                    document.getElementById(state).classList.remove('hidden');
                }
            };

            // Tab Management
            const setActiveTab = (tabName) => {
                tabButtons.forEach(btn => {
                    btn.classList.toggle('active', btn.dataset.tab === tabName);
                });
                tabContents.forEach(content => {
                    content.classList.toggle('hidden', content.id !== `${tabName}-content`);
                });
            };

            tabButtons.forEach(button => {
                button.addEventListener('click', () => setActiveTab(button.dataset.tab));
            });

            // UI Updates
            const updateWeatherUI = (data) => {
                document.getElementById('clima-content').innerHTML = `
                    <div class="flex items-center justify-center mb-4">
                        <svg class="w-16 h-16 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V6a1 1 0 011-1h2a1 1 0 011 1v10a1 1 0 01-1 1h-2a1 1 0 01-1-1z"></path></svg>
                        <span class="text-6xl font-bold text-gray-800">${data.forecast.temperature}°C</span>
                    </div>
                    <div class="flex items-center justify-center mb-4">
                        <svg class="w-10 h-10 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v.01"></path></svg>
                        <span class="text-2xl font-semibold text-gray-700">${data.forecast.precipitation_chance}% de probabilidad</span>
                    </div>
                    <p class="mt-4 text-sm text-gray-600">${data.full_text}</p>
                `;
            };

            const updateAirQualityUI = (data) => {
                const aqi = data.air_quality.aqi;
                let aqiColorClass = '';
                let aqiText = '';

                switch (aqi) {
                    case 1: aqiColorClass = 'text-green-500'; aqiText = 'Buena'; break;
                    case 2: aqiColorClass = 'text-yellow-500'; aqiText = 'Justa'; break;
                    case 3: aqiColorClass = 'text-orange-500'; aqiText = 'Moderada'; break;
                    case 4: aqiColorClass = 'text-red-500'; aqiText = 'Pobre'; break;
                    case 5: aqiColorClass = 'text-purple-500'; aqiText = 'Muy Pobre'; break;
                    default: aqiColorClass = 'text-gray-500'; aqiText = 'Desconocida'; break;
                }

                document.getElementById('aire-content').innerHTML = `
                    <div class="flex items-center justify-center mb-4">
                        <span class="text-6xl font-bold ${aqiColorClass}">${aqi}</span>
                    </div>
                    <p class="mt-2 text-lg font-semibold text-gray-800">Calidad del Aire: ${aqiText}</p>
                    <p class="mt-4 text-sm text-gray-600">${data.full_text}</p>
                    <div class="mt-4 text-xs text-gray-500">
                        <p>CO: ${data.air_quality.components.co} μg/m³</p>
                        <p>NO2: ${data.air_quality.components.no2} μg/m³</p>
                        <p>O3: ${data.air_quality.components.o3} μg/m³</p>
                        <p>SO2: ${data.air_quality.components.so2} μg/m³</p>
                        <p>PM2.5: ${data.air_quality.components.pm2_5} μg/m³</p>
                        <p>PM10: ${data.air_quality.components.pm10} μg/m³</p>
                    </div>
                `;
            };

            const updateHourlyWeatherUI = (data) => {
                let hourlyHtml = '<h3 class="text-lg font-medium text-gray-900 mb-4">Pronóstico por Hora (Próximas 8h)</h3><div class="grid grid-cols-2 gap-4">';
                data.hourly_forecast.forEach(hour => {
                    hourlyHtml += `
                        <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg shadow-sm">
                            <img src="https://openweathermap.org/img/wn/${hour.icon}.png" alt="${hour.description}" class="w-10 h-10">
                            <div>
                                <p class="text-sm font-medium text-gray-900">${hour.time}</p>
                                <p class="text-lg font-bold text-gray-800">${hour.temp}°C</p>
                                <p class="text-xs text-gray-600">${hour.description}</p>
                            </div>
                        </div>
                    `;
                });
                hourlyHtml += '</div>';
                document.getElementById('hora-content').innerHTML = hourlyHtml;
            };

            const speak = (text) => {
                speechSynthesis.cancel();
                const utterance = new SpeechSynthesisUtterance(text);
                utterance.lang = 'es-ES';
                speechSynthesis.speak(utterance);
            };

            // Data Fetching
            const getEnvironmentalData = (lat, lon, locationName = '') => {
                showState('loading-spinner');
                suggestionsBox.classList.add('hidden');

                if (marker) map.removeLayer(marker);
                marker = L.marker([lat, lon]).addTo(map);
                if (locationName) marker.bindPopup(locationName).openPopup();
                map.flyTo([lat, lon], 13);

                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                // Fetch Weather Data
                const fetchWeather = fetch('/api/weather', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ latitude: lat, longitude: lon })
                })
                .then(response => response.ok ? response.json() : Promise.reject('Error fetching weather data.'))
                .then(weatherData => {
                    updateWeatherUI(weatherData);
                    speak(weatherData.full_text);
                    return weatherData; // Pass data for further processing if needed
                });

                // Fetch Air Quality Data
                const fetchAirQuality = fetch('/api/air-quality', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ latitude: lat, longitude: lon })
                })
                .then(response => response.ok ? response.json() : Promise.reject('Error fetching air quality data.'))
                .then(airQualityData => {
                    updateAirQualityUI(airQualityData);
                    return airQualityData; // Pass data for further processing if needed
                });

                // Fetch Hourly Weather Data
                const fetchHourlyWeather = fetch('/api/hourly-weather', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ latitude: lat, longitude: lon })
                })
                .then(response => response.ok ? response.json() : Promise.reject('Error fetching hourly weather data.'))
                .then(hourlyWeatherData => {
                    updateHourlyWeatherUI(hourlyWeatherData);
                    return hourlyWeatherData; // Pass data for further processing if needed
                });

                Promise.allSettled([fetchWeather, fetchAirQuality, fetchHourlyWeather])
                    .then(results => {
                        const weatherResult = results[0];
                        const airQualityResult = results[1];
                        const hourlyWeatherResult = results[2];

                        if (weatherResult.status === 'fulfilled') {
                            updateWeatherUI(weatherResult.value);
                            speak(weatherResult.value.full_text);
                        } else {
                            console.error('Error fetching weather data:', weatherResult.reason);
                            document.getElementById('clima-content').innerHTML = '<p class="text-red-500">No se pudo obtener el pronóstico del clima.</p>';
                        }

                        if (airQualityResult.status === 'fulfilled') {
                            updateAirQualityUI(airQualityResult.value);
                        } else {
                            console.error('Error fetching air quality data:', airQualityResult.reason);
                            document.getElementById('aire-content').innerHTML = '<p class="text-red-500">No se pudo obtener la calidad del aire.</p>';
                        }

                        if (hourlyWeatherResult.status === 'fulfilled') {
                            updateHourlyWeatherUI(hourlyWeatherResult.value);
                        } else {
                            console.error('Error fetching hourly weather data:', hourlyWeatherResult.reason);
                            document.getElementById('hora-content').innerHTML = '<p class="text-red-500">No se pudo obtener el pronóstico por hora.</p>';
                        }

                        showState('results-panel');
                        setActiveTab('clima'); // Default to clima tab
                    });
            };

            // Search & Geolocation Logic (similar to before)
            const searchLocation = () => {
                const query = searchInput.value;
                if (!query) return;
                // The search button now triggers the geocoding directly via the input event listener
                // No direct action needed here as the input listener handles it.
            };
            
            searchInput.addEventListener('input', () => {
                clearTimeout(debounceTimer);
                const query = searchInput.value;
                if (query.length < 3) { suggestionsBox.classList.add('hidden'); return; }
                debounceTimer = setTimeout(() => {
                    fetch(`https://nominatim.openstreetmap.org/search?format=json&limit=5&q=${encodeURIComponent(query)}`)
                        .then(response => response.json())
                        .then(data => {
                            suggestionsBox.innerHTML = '';
                            if (data && data.length > 0) {
                                data.forEach(item => {
                                    const suggestion = document.createElement('div');
                                    suggestion.className = 'p-2 text-gray-800 hover:bg-gray-100 cursor-pointer';
                                    suggestion.textContent = item.display_name;
                                    suggestion.onclick = () => {
                                        searchInput.value = item.display_name.split(',')[0];
                                        getEnvironmentalData(item.lat, item.lon, item.display_name);
                                    };
                                    suggestionsBox.appendChild(suggestion);
                                });
                                suggestionsBox.classList.remove('hidden');
                            } else {
                                suggestionsBox.classList.add('hidden');
                            }
                        });
                }, 300);
            });

            map.on('click', (e) => getEnvironmentalData(e.latlng.lat, e.latlng.lng, 'Punto seleccionado'));
            searchButton.addEventListener('click', searchLocation);
            searchInput.addEventListener('keypress', (e) => e.key === 'Enter' && searchLocation());
            document.addEventListener('click', (e) => { if (!searchInput.contains(e.target)) { suggestionsBox.classList.add('hidden'); } });

            navigator.geolocation.getCurrentPosition(
                (position) => getEnvironmentalData(position.coords.latitude, position.coords.longitude, 'Tu ubicación actual'),
                () => showState('welcome-panel')
            );
        });
    </script>
    @endpush
</x-guest-layout>