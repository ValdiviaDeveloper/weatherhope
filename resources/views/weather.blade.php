<x-guest-layout>
    {{-- Usamos un contenedor principal para el fondo gradiente --}}
    <div class="relative min-h-screen bg-gradient-to-br from-blue-200 via-sky-400 to-indigo-600 dark:from-slate-900 dark:via-indigo-950 dark:to-slate-900 p-4 lg:p-0">
        <div class="flex flex-col lg:flex-row h-full max-h-screen">

            <!-- Columna del Mapa -->
            <div class="lg:w-3/5 h-[40vh] lg:h-screen rounded-2xl shadow-2xl z-10" id="map"></div>

            <!-- Columna de Información y Búsqueda -->
            <div class="lg:w-2/5 w-full lg:h-screen flex flex-col p-2 lg:p-6">
                {{-- Panel con efecto de cristal --}}
                <div class="w-full h-full bg-white/50 dark:bg-gray-900/60 backdrop-blur-xl rounded-2xl shadow-lg flex flex-col overflow-hidden dark:border dark:border-white/10">
                    
                    <!-- Cabecera y Búsqueda -->
                    <div class="p-6 border-b border-white/30 dark:border-gray-700/50 flex-shrink-0">
                        <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100 mb-1">WeatherHope</h1>
                        <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">Análisis Climático Histórico y Pronósticos</p>
                        
                        <div class="relative">
                            <label for="location-search" class="sr-only">Buscar Lugar y Fecha</label>
                            <div class="flex items-center w-full shadow-md rounded-lg">
                                <input type="text" id="location-search" class="flex-grow block w-full pl-10 pr-3 py-3 text-gray-900 dark:text-white bg-white/60 dark:bg-slate-700/70 border border-transparent rounded-l-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent sm:text-sm" placeholder="Ej: Arequipa, Perú">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L19 18.17l-1.42 1.42-4.12-4.12A7 7 0 015.05 4.05zm7.07 7.07a5 5 0 10-7.07-7.07 5 5 0 007.07 7.07z" clip-rule="evenodd" /></svg>
                                </div>
                                <input type="date" id="date-search" class="block w-auto px-3 py-3 text-gray-700 dark:text-gray-300 bg-white/60 dark:bg-slate-700/70 border-y border-transparent focus:ring-2 focus:ring-sky-500 focus:border-transparent sm:text-sm" title="Selecciona una fecha">
                                <button id="search-button" class="inline-flex items-center p-3 border border-transparent rounded-r-lg bg-sky-600 text-white hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sky-500 dark:bg-sky-500 dark:hover:bg-sky-600">
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" /></svg>
                                </button>
                            </div>
                            <div id="suggestions-box" class="absolute z-20 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md mt-1 shadow-lg hidden"></div>
                        </div>
                    </div>

                    <!-- Contenedor Principal de Información -->
                    <div class="flex-grow p-6">
                        <div id="info-container" class="w-full text-center">
                            
                            <!-- Estado de Bienvenida -->
                            <div id="welcome-panel">
                                <svg class="mx-auto h-16 w-16 text-sky-500/50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                <h3 class="mt-4 text-xl font-semibold text-gray-900 dark:text-gray-100">Dashboard Ambiental Interactivo</h3>
                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Utiliza la búsqueda o el micrófono para explorar datos climáticos de cualquier lugar del mundo.</p>
                            </div>

                            <!-- Estado de Carga -->
                            <div id="loading-spinner" class="hidden">
                                <svg class="animate-spin mx-auto h-12 w-12 text-sky-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <p id="loading-text" class="mt-4 text-sm font-medium text-gray-600 dark:text-gray-400">Obteniendo datos ambientales...</p>
                            </div>

                            <!-- Estado de Resultados con Pestañas -->
                            <div id="results-panel" class="hidden w-full">
                                <div class="border-b border-gray-300/80 dark:border-gray-700/60">
                                    <nav class="-mb-px flex space-x-4" aria-label="Tabs">
                                        <button class="tab-btn whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm" data-tab="historico">Análisis Histórico</button>
                                        <button class="tab-btn whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm" data-tab="pronostico">Pronóstico</button>
                                        <button class="tab-btn whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm" data-tab="aire">Calidad del Aire</button>
                                        <button class="tab-btn whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm" data-tab="uv">Índice UV</button>
                                    </nav>
                                </div>
                                <div class="pt-5 text-left">
                                    <div id="historico-content" class="tab-content"></div>
                                    <div id="pronostico-content" class="tab-content hidden"></div>
                                    <div id="aire-content" class="tab-content hidden"></div>
                                    <div id="uv-content" class="tab-content hidden"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Contenedor de Explicación (con scroll interno) -->
                    <div id="explanation-container" class="flex-shrink-0 p-6 border-t border-white/30 dark:border-gray-700/50 hidden">
                        <p id="explanation-text" class="text-sm text-gray-700 dark:text-sky-200 max-h-24 overflow-y-auto overflow-x-hidden"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Botón Flotante de Micrófono -->
    <button id="floating-mic-btn" class="fixed bottom-6 right-6 lg:bottom-8 lg:right-8 w-16 h-16 rounded-full bg-sky-600 text-white flex items-center justify-center shadow-xl cursor-pointer transition-all duration-300 ease-in-out z-20 hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-900 focus:ring-white">
        <svg class="w-8 h-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" /></svg>
    </button>

    @push('scripts')
    <script>
        // El JS se mantiene igual, pero definimos las clases de Tailwind aquí para que sea más fácil de manejar
        const tabClasses = {
            active: 'border-sky-500 text-sky-600 dark:text-sky-400',
            inactive: 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
        };
        const micButtonClasses = {
            default: 'bg-sky-600',
            listening: 'bg-red-600 scale-110'
        };

        // Función para crear tarjetas de información con un estilo unificado
        function createInfoCard(title, value, subtext = '', icon = '', valueColorClass = '') {
            const iconSvg = icon ? `<div class="flex-shrink-0 mr-4">${icon}</div>` : '';
            return `
                <div class="bg-white/50 dark:bg-gray-800/60 p-4 rounded-lg shadow-sm flex items-center">
                    ${iconSvg}
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">${title}</p>
                        <p class="text-2xl lg:text-3xl font-bold text-gray-900 dark:text-white ${valueColorClass}">${value}</p>
                        ${subtext ? `<p class="text-xs text-gray-500 dark:text-gray-400">${subtext}</p>` : ''}
                    </div>
                </div>
            `;
        }

        document.addEventListener('DOMContentLoaded', () => {
            const explanationContainer = document.getElementById('explanation-container');
            const explanationText = document.getElementById('explanation-text');
            const voiceButton = document.getElementById('floating-mic-btn');
            const welcomePanel = document.getElementById('welcome-panel');
            const loadingSpinner = document.getElementById('loading-spinner');
            const loadingText = document.getElementById('loading-text');
            const resultsPanel = document.getElementById('results-panel');
            const searchInput = document.getElementById('location-search');
            const dateInput = document.getElementById('date-search');
            const searchButton = document.getElementById('search-button');
            const suggestionsBox = document.getElementById('suggestions-box');
            const tabButtons = document.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.tab-content');
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            dateInput.valueAsDate = new Date();

            const map = L.map('map').setView([-9.19, -75.01], 5);
            let marker = null;
            L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>'
            }).addTo(map);

            const showState = (state, text = 'Obteniendo datos...') => {
                welcomePanel.classList.add('hidden');
                loadingSpinner.classList.add('hidden');
                resultsPanel.classList.add('hidden');
                explanationContainer.classList.add('hidden');
                loadingText.textContent = text;
                if (document.getElementById(state)) document.getElementById(state).classList.remove('hidden');
            };

            const setActiveTab = (tabName) => {
                tabButtons.forEach(btn => {
                    if (btn.dataset.tab === tabName) {
                        btn.className = `tab-btn whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm ${tabClasses.active}`;
                    } else {
                        btn.className = `tab-btn whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm ${tabClasses.inactive}`;
                    }
                });
                tabContents.forEach(content => content.classList.toggle('hidden', content.id !== `${tabName}-content`));
            };
            tabButtons.forEach(button => button.addEventListener('click', () => setActiveTab(button.dataset.tab)));

            const updateUI = (data) => {
                const processed = data.processed_data;
                const locationName = processed.location.split(',')[0];

                // --- Iconos SVG para reutilizar ---
                const tempIcon = `<svg class="h-8 w-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V4a4 4 0 10-8 0v12a6 6 0 1012 0v-3M13 6h2"/></svg>`;
                const rainIcon = `<svg class="h-8 w-8 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15zM12 19v2M8 19v2m8-2v2"/></svg>`;
                const uvIcon = `<svg class="h-8 w-8 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>`;
                const airIcon = `<svg class="h-8 w-8 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>`;

                // --- 1. Update Historical Analysis Tab ---
                document.getElementById('historico-content').innerHTML = `
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Análisis Histórico para ${locationName}</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        ${createInfoCard('Temperatura Promedio', `${processed.avg_temp_c}°C`, `Rango Histórico: ${processed.historical_temp_range_c.join(' a ')}°C`, tempIcon)}
                        ${createInfoCard('Probabilidad de Lluvia', `${processed.chance_of_rain_percent}%`, 'Basado en datos históricos', rainIcon)}
                    </div>
                `;

                // --- 2. Update Current Forecast Tab (Simulated) ---
                document.getElementById('pronostico-content').innerHTML = `
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Pronóstico para Hoy en ${locationName}</h3>
                    ${createInfoCard('Ahora Mismo', '22°C', 'Parcialmente Nublado', tempIcon)}
                `;

                // --- 3. Update Air Quality Tab (Simulated) ---
                document.getElementById('aire-content').innerHTML = `
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Calidad del Aire en ${locationName}</h3>
                    ${createInfoCard('Índice AQI', '35', 'Buena', airIcon, 'text-green-500')}
                `;

                // --- 4. Update UV Index Tab ---
                const uvCategory = processed.uv_category.toLowerCase();
                let uvColorClass = 'text-green-500';
                if (uvCategory === 'moderado') uvColorClass = 'text-yellow-500';
                if (uvCategory === 'alto') uvColorClass = 'text-orange-500';
                if (uvCategory === 'muy alto') uvColorClass = 'text-red-500';
                if (uvCategory === 'extremo') uvColorClass = 'text-purple-500';
                document.getElementById('uv-content').innerHTML = `
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Índice UV Estimado para ${locationName}</h3>
                    ${createInfoCard('Índice UV', processed.uv_index, processed.uv_category, uvIcon, uvColorClass)}
                `;

                if (processed.lat && processed.lon) {
                    if (marker) map.removeLayer(marker);
                    marker = L.marker([processed.lat, processed.lon]).addTo(map);
                    marker.bindPopup(processed.location).openPopup();
                    map.flyTo([processed.lat, processed.lon], 10);
                }
            };

            const getLikelihood = (url, body) => {
                showState('loading-spinner', 'Buscando análisis histórico del clima ...');
                fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify(body)
                })
                .then(response => response.ok ? response.json() : Promise.reject('Network response was not ok'))
                .then(data => {
                    if (data.error) {
                        alert(data.explanation || 'Lo siento, ocurrió un error.');
                        showState('welcome-panel');
                    } else {
                        updateUI(data);
                        showState('results-panel');
                        setActiveTab('probabilidad');
                        speakAndHighlight(data.explanation);
                    }
                })
                .catch(error => {
                    console.error('Error fetching likelihood data:', error);
                    alert('Lo siento, no pude procesar esa búsqueda.');
                    showState('welcome-panel');
                });
            };

            const speakAndHighlight = (text) => {
                if (!text || !window.speechSynthesis) return;
                
                explanationContainer.classList.remove('hidden');
                window.speechSynthesis.cancel();

                const words = text.split(/\s+/);
                // Using a simple span with padding to avoid layout issues. The background will highlight the word.
                explanationText.innerHTML = words.map(word => `<span class="transition-all duration-150 p-1 rounded-md">${word}</span>`).join(' ');
                const wordSpans = explanationText.querySelectorAll('span');
                
                const utterance = new SpeechSynthesisUtterance(text);
                utterance.lang = 'es-ES';
                let currentWordIndex = 0;

                const highlightClasses = ['bg-sky-200', 'dark:bg-sky-600/80', 'text-slate-900', 'dark:text-white'];

                utterance.onboundary = (event) => {
                    if (event.name === 'word') {
                        for (let i = currentWordIndex; i < wordSpans.length; i++) {
                            // A more robust check to find the correct word span
                            if (text.substring(event.charIndex).startsWith(wordSpans[i].textContent)) {
                                if (currentWordIndex > 0 && wordSpans[currentWordIndex - 1]) {
                                    wordSpans[currentWordIndex - 1].classList.remove(...highlightClasses);
                                }
                                wordSpans[i].classList.add(...highlightClasses);
                                currentWordIndex = i + 1;
                                break;
                            }
                        }
                    }
                };

                utterance.onend = () => {
                    if (wordSpans.length > 0) {
                       setTimeout(() => {
                           wordSpans.forEach(span => span.classList.remove(...highlightClasses));
                       }, 500);
                    }
                };
                
                utterance.onerror = e => console.error('SpeechSynthesis Error:', e.error);
                window.speechSynthesis.speak(utterance);
            };

            const searchLocation = () => {
                const location = searchInput.value;
                const date = dateInput.value;
                if (!location || !date) {
                    alert('Por favor, ingresa una ubicación y selecciona una fecha.');
                    return;
                }
                getLikelihood('/weather/likelihood-text', { location, date });
            };

            searchButton.addEventListener('click', searchLocation);
            searchInput.addEventListener('keypress', (e) => e.key === 'Enter' && searchLocation());
            dateInput.addEventListener('keypress', (e) => e.key === 'Enter' && searchLocation());
            
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (SpeechRecognition) {
                const recognition = new SpeechRecognition();
                recognition.lang = 'es-ES';
                recognition.interimResults = false;

                voiceButton.addEventListener('click', () => {
                    speakAndHighlight("Hola, ¿qué ubicación y fecha te gustaría consultar?");
                    setTimeout(() => {
                        try { 
                            voiceButton.className = voiceButton.className.replace(micButtonClasses.default, micButtonClasses.listening);
                            recognition.start(); 
                        } catch(e) { 
                            console.error('Error starting recognition:', e); 
                            voiceButton.className = voiceButton.className.replace(micButtonClasses.listening, micButtonClasses.default);
                        }
                    }, 1500);
                });
                
                recognition.onend = () => voiceButton.className = voiceButton.className.replace(micButtonClasses.listening, micButtonClasses.default);
                recognition.onerror = (e) => {
                    console.error('Recognition error:', e.error);
                    voiceButton.className = voiceButton.className.replace(micButtonClasses.listening, micButtonClasses.default);
                };

                recognition.onresult = (event) => {
                    const transcript = event.results[0][0].transcript.toLowerCase();
                    showState('loading-spinner', 'Procesando tu pregunta...');
                    getLikelihood('/weather/likelihood', { query: transcript });
                };
            } else {
                voiceButton.disabled = true;
            }

            let debounceTimer;
            searchInput.addEventListener('input', () => {
                clearTimeout(debounceTimer);
                const query = searchInput.value;
                if (query.length < 3) {
                    suggestionsBox.classList.add('hidden');
                    return;
                }
                debounceTimer = setTimeout(() => {
                    fetch(`https://nominatim.openstreetmap.org/search?format=json&limit=5&q=${encodeURIComponent(query)}&accept-language=es`)
                        .then(response => response.json())
                        .then(data => {
                            suggestionsBox.innerHTML = '';
                            if (data && data.length > 0) {
                                data.forEach(item => {
                                    const suggestion = document.createElement('div');
                                    suggestion.className = 'p-3 text-gray-800 dark:text-gray-200 hover:bg-sky-100 dark:hover:bg-gray-700 cursor-pointer text-sm';
                                    suggestion.textContent = item.display_name;
                                    suggestion.onclick = () => {
                                        searchInput.value = item.display_name;
                                        suggestionsBox.classList.add('hidden');
                                        searchLocation();
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
            document.addEventListener('click', (e) => {
                if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
                    suggestionsBox.classList.add('hidden');
                }
            });
            
            showState('welcome-panel');
            setActiveTab('historico');
        });
    </script>
    @endpush
</x-guest-layout>