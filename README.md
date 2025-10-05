# WeatherHope 🌦️

**WeatherHope: Tu Máquina del Tiempo Climática para Planificar el Futuro.**

WeatherHope es una aplicación web interactiva diseñada para el [NASA International Space Apps Challenge 2025](https://www.spaceappschallenge.org/). A diferencia de las aplicaciones de pronóstico del tiempo tradicionales que miran días hacia el futuro, WeatherHope utiliza décadas de datos de observación de la Tierra de la NASA para analizar el pasado y ofrecer probabilidades climáticas históricas.

Nuestra misión es empoderar a planificadores de eventos, agricultores, turistas y cualquier persona que necesite tomar decisiones a largo plazo, respondiendo a la pregunta: *"¿Cómo suele ser el clima en este lugar, en esta fecha específica?"*

---

## 🎯 El Problema: El Vacío en la Planificación a Largo Plazo

Planificar eventos importantes (bodas, vacaciones, siembras agrícolas) con meses de antelación es un ejercicio de incertidumbre. Los pronósticos meteorológicos solo cubren 1-2 semanas, dejando un vacío de información crítico. Esto obliga a tomar decisiones a ciegas, sin una base de datos sólida que responda a preguntas clave como: *"¿Qué tan probable es que llueva para mi boda en julio?"* o *"¿Debería preocuparme por el calor extremo en mis vacaciones de agosto?"*.

## ✨ La Solución: Traducir la Ciencia de la NASA en Respuestas Claras

WeatherHope llena este vacío al actuar como un **traductor entre los complejos datos históricos de la NASA y el lenguaje cotidiano del usuario**. Nuestra aplicación no predice el futuro, sino que analiza décadas de datos de observación de la Tierra para ofrecer un análisis de probabilidad claro y accionable.

A través de una interfaz visualmente atractiva y, fundamentalmente, un **potente asistente de voz**, democratizamos el acceso a la ciencia. Transformamos datos científicos en respuestas directas a preguntas prácticas, permitiendo que cualquier persona pueda planificar su futuro con la confianza que solo décadas de datos de la NASA pueden ofrecer.

---

## 🚀 Características Principales

*   **🗺️ Mapa Interactivo:** Selecciona cualquier punto del planeta para obtener datos climáticos.
*   **📊 Análisis Histórico Detallado:** Consulta la temperatura promedio, el rango histórico y la probabilidad de precipitación basada en décadas de datos.
*   **🗣️ Interfaz Controlada por Voz:** Realiza consultas complejas en lenguaje natural y recibe respuestas habladas y resaltadas en tiempo real.
*   **☀️ Datos Múltiples:** Accede a información sobre la calidad del aire y el índice UV estimado.
*   **📱 Diseño Moderno y Responsivo:** Una experiencia de usuario fluida y atractiva en cualquier dispositivo.

---

## 🛠️ Pila Tecnológica

*   **Backend:** Laravel (PHP)
*   **Frontend:** Blade, Tailwind CSS, JavaScript
*   **APIs y Datos:**
    *   **NASA POWER:** Para datos históricos de temperatura y precipitación.
    *   **OpenWeatherMap:** Para datos complementarios de calidad del aire y pronóstico.
    *   **Nominatim (OpenStreetMap):** Para geocodificación de búsquedas de lugares.
*   **Librerías Clave:**
    *   **Leaflet.js:** Para el mapa interactivo.
    *   **Web Speech API (JavaScript):** Para el reconocimiento y síntesis de voz.

---

## ⚙️ Instalación y Configuración Local

Sigue estos pasos para ejecutar WeatherHope en tu propio entorno:

1.  **Clonar el repositorio:**
    ```bash
    git clone https://github.com/tu-usuario/weatherhope.git
    cd weatherhope
    ```

2.  **Instalar dependencias:**
    ```bash
    composer install
    npm install
    ```

3.  **Configurar el entorno:**
    *   Copia el archivo de ejemplo: `cp .env.example .env`
    *   Genera la clave de la aplicación: `php artisan key:generate`
    *   Abre el archivo `.env` y añade tus claves de API:
        ```
        NASA_API_KEY=TU_API_KEY_DE_LA_NASA
        OPENWEATHER_API_KEY=TU_API_KEY_DE_OPENWEATHER
        ```

4.  **Base de datos:**
    ```bash
    php artisan migrate
    ```

5.  **Compilar assets y ejecutar el servidor:**
    ```bash
    npm run dev
    php artisan serve
    ```

¡La aplicación estará disponible en `http://127.0.0.1:8000`!

---

## NASA Data in Action

Este proyecto utiliza la API **POWER (Prediction Of Worldwide Energy Resources)** de la NASA. Específicamente, accedemos a los siguientes parámetros para cualquier coordenada geográfica:

*   **T2M:** Temperatura del Aire a 2 Metros.
*   **PRECTOTCORR:** Precipitación Total Corregida.

Estos conjuntos de datos, que se remontan a décadas, son la columna vertebral de nuestro motor de análisis histórico, permitiéndonos calcular promedios, rangos y probabilidades con una base científica sólida.