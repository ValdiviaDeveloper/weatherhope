<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>WeatherHope</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

        <!-- Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            .hero-bg {
                background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('https://images.unsplash.com/photo-1451187580459-43490279c0fa?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2072&q=80') no-repeat center center;
                background-size: cover;
                transition: background-image 1s ease-in-out;
            }
            #subtitle-modal {
                transition: opacity 0.5s ease-in-out, transform 0.5s ease-in-out;
            }
            .highlight {
                color: #3b82f6; /* Tailwind's blue-500 */
            }
        </style>
    </head>
    <body class="antialiased">
        <div id="hero-section" class="relative min-h-screen flex flex-col items-center justify-center hero-bg text-white">
            
            <!-- Header Navigation -->
            <header class="absolute top-0 right-0 p-6">
                @if (Route::has('login'))
                    <nav class="flex flex-1 justify-end gap-4">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="rounded-md px-4 py-2 ring-1 ring-transparent transition hover:text-white/70 focus:outline-none focus-visible:ring-[#FF2D20]">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="rounded-md px-4 py-2 ring-1 ring-transparent transition hover:text-white/70 focus:outline-none focus-visible:ring-[#FF2D20]">Log in</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="rounded-md px-4 py-2 ring-1 ring-transparent transition hover:text-white/70 focus:outline-none focus-visible:ring-[#FF2D20]">Register</a>
                            @endif
                        @endauth
                    </nav>
                @endif
            </header>

            <!-- Main Content -->
            <main class="text-center p-6">
                <h1 class="text-5xl lg:text-7xl font-bold mb-4 animate-fade-in-down">WeatherHope</h1>
                <p id="apod-title" class="text-lg lg:text-xl max-w-3xl mx-auto mb-8 animate-fade-in-up font-semibold"></p>
                <p class="text-lg lg:text-xl max-w-3xl mx-auto mb-8 animate-fade-in-up">
                    Utilizando datos históricos de la NASA, te ayudamos a planificar tu futuro. Descubre la probabilidad de las condiciones climáticas para cualquier lugar y fecha del año.
                </p>
                
                <div id="interaction-container">
                    <!-- This button will be replaced by the mic button after the voice intro -->
                    <a href="{{ route('weather') }}" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-lg text-lg transition duration-300 ease-in-out transform hover:scale-105 mb-4">
                        Explorar Mapa Interactivo
                    </a>
                    <button id="start-voice-btn" class="block mx-auto bg-transparent border border-white hover:bg-white hover:text-black text-white font-bold py-3 px-8 rounded-lg text-lg transition duration-300 ease-in-out">
                        Iniciar Experiencia de Voz
                    </button>
                </div>

                <div id="listening-indicator" class="hidden mt-6 text-lg animate-pulse"><p>🎤 Escuchando...</p></div>
            </main>
        </div>

        <!-- Subtitle Modal -->
        <div id="subtitle-modal" class="fixed bottom-10 left-1/2 -translate-x-1/2 w-11/12 max-w-4xl bg-black bg-opacity-70 text-white p-4 rounded-lg text-center text-2xl opacity-0 transform translate-y-10 pointer-events-none">
            <p id="subtitle-text"></p>
        </div>

        <script>
            const fetchNasaImage = async () => {
                const heroSection = document.getElementById('hero-section');
                const apodTitle = document.getElementById('apod-title');
                const apiKey = '{{ config('services.nasa.api_key') }}';
                const url = `https://api.nasa.gov/planetary/apod?api_key=${apiKey}`;
                try {
                    const response = await fetch(url);
                    const data = await response.json();
                    if (data.media_type === 'image') {
                        heroSection.style.backgroundImage = `linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('${data.hdurl || data.url}')`;
                        apodTitle.textContent = data.title;
                    } else {
                        apodTitle.textContent = 'Imagen del Día de la NASA';
                    }
                } catch (error) {
                    console.error('Error fetching NASA APOD data:', error);
                    apodTitle.textContent = 'Explora el Cosmos';
                }
            };

            function startVoiceExperience() {
                // Hide the start button
                document.getElementById('interaction-container').style.display = 'none';

                const textToSpeak = "Hola, bienvenido a WeatherHope. ¿Quieres navegar al mapa interactivo?";
                const words = textToSpeak.split(' ');
                const modal = document.getElementById('subtitle-modal');
                const subtitleText = document.getElementById('subtitle-text');
                const interactionContainer = document.getElementById('interaction-container');

                const utterance = new SpeechSynthesisUtterance(textToSpeak);
                utterance.lang = 'es-ES';

                utterance.onstart = () => {
                    modal.classList.remove('opacity-0', 'translate-y-10');
                    subtitleText.innerHTML = words.map(word => `<span>${word}</span>`).join(' ');
                };

                utterance.onboundary = (event) => {
                    if (event.name === 'word') {
                        const wordIndex = event.charIndex;
                        let currentIndex = 0;
                        for (let i = 0; i < words.length; i++) {
                            if (wordIndex >= currentIndex && wordIndex < currentIndex + words[i].length) {
                                const spans = subtitleText.querySelectorAll('span');
                                spans.forEach(span => span.classList.remove('highlight'));
                                spans[i].classList.add('highlight');
                                break;
                            }
                            currentIndex += words[i].length + 1;
                        }
                    }
                };

                utterance.onend = () => {
                    modal.classList.add('opacity-0', 'translate-y-10');
                    // Show the interaction container again to place the mic button
                    interactionContainer.style.display = 'block';
                    interactionContainer.innerHTML = `
                        <button id="mic-btn" class="bg-blue-600 hover:bg-blue-700 text-white font-bold p-4 rounded-full transition duration-300 ease-in-out transform hover:scale-110 animate-fade-in-up">
                            <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M7 4a3 3 0 016 0v4a3 3 0 11-6 0V4zm4 10.93A7.001 7.001 0 0017 8h-1a6 6 0 11-12 0H3a7.001 7.001 0 006 6.93V17H7v1h6v-1h-2v-2.07z" clip-rule="evenodd"></path></svg>
                        </button>
                    `;
                    document.getElementById('mic-btn').onclick = startRecognition;
                };
                
                window.speechSynthesis.speak(utterance);
            }

            window.onload = () => {
                fetchNasaImage();
                const startBtn = document.getElementById('start-voice-btn');
                
                if (sessionStorage.getItem('welcomeMessagePlayed')) {
                    startBtn.style.display = 'none';
                } else {
                    startBtn.onclick = () => {
                        sessionStorage.setItem('welcomeMessagePlayed', 'true');
                        startVoiceExperience();
                    };
                }
            };

            function startRecognition() {
                if ('webkitSpeechRecognition' in window) {
                    const recognition = new webkitSpeechRecognition();
                    const indicator = document.getElementById('listening-indicator');
                    const interactionContainer = document.getElementById('interaction-container');
                    
                    recognition.continuous = false;
                    recognition.interimResults = false;
                    recognition.lang = 'es-ES';

                    recognition.onstart = () => {
                        indicator.classList.remove('hidden');
                        interactionContainer.style.display = 'none';
                    };

                    recognition.onend = () => {
                        indicator.classList.add('hidden');
                        interactionContainer.style.display = 'block';
                    };

                    recognition.onresult = (event) => {
                        const transcript = event.results[0][0].transcript.toLowerCase().trim();
                        const affirmativeResponses = ['sí', 'si', 'yes', 'claro', 'acepto', 'ok', 'dale'];
                        if (affirmativeResponses.some(response => transcript.includes(response))) {
                            window.location.href = "{{ route('weather') }}";
                        }
                    };

                    recognition.onerror = (event) => {
                        console.error('Error en el reconocimiento de voz:', event.error);
                        indicator.classList.add('hidden');
                    };
                    
                    recognition.start();
                } else {
                    alert('La API de reconocimiento de voz no es compatible con este navegador.');
                }
            }
        </script>
    </body>
</html>