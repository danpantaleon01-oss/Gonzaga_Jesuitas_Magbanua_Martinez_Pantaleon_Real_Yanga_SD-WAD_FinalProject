<?php
// Fetches weather and holiday data from API
function getWeatherData() {
    $api_url = BASE_URL . '/api/weather.php';
    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'header' => 'User-Agent: CampusEventSystem/1.0'
        ],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
    ]);

    $response = @file_get_contents($api_url, false, $context);
    if ($response !== false) {
        return json_decode($response, true);
    }
    return null;
}
?>

<div class="weather-widget card">
    <div class="card-header">
        <div>
            <h3 style="margin: 0;">🌤️ Weather & Holidays</h3>
            <span style="font-size: 0.8rem; color: var(--text-tertiary);">URS Morong, Rizal</span>
        </div>
        <span class="badge badge-info" id="weatherTime">Loading...</span>
    </div>
    <div class="card-body">
        <div class="weather-grid">
            <!-- Current Weather -->
            <div class="weather-current">
                <div class="weather-icon-container">
                    <span class="weather-icon" id="weatherIcon">--</span>
                    <span class="weather-temp" id="weatherTemp">--°C</span>
                </div>
                <div class="weather-details">
                    <div class="weather-detail">
                        <span class="weather-detail-label">Condition</span>
                        <span class="weather-detail-value" id="weatherCondition">--</span>
                    </div>
                    <div class="weather-detail">
                        <span class="weather-detail-label">Feels Like</span>
                        <span class="weather-detail-value" id="weatherFeelsLike">--°C</span>
                    </div>
                    <div class="weather-detail">
                        <span class="weather-detail-label">Humidity</span>
                        <span class="weather-detail-value" id="weatherHumidity">--%</span>
                    </div>
                    <div class="weather-detail">
                        <span class="weather-detail-label">Wind</span>
                        <span class="weather-detail-value" id="weatherWind">-- km/h</span>
                    </div>
                </div>
            </div>

            <!-- Holidays & Forecast -->
            <div class="weather-sidebar">
                <!-- Today's Holiday -->
                <div class="weather-holiday" id="holidayToday">
                    <div class="holiday-icon">📅</div>
                    <div class="holiday-info">
                        <span class="holiday-label">Today</span>
                        <span class="holiday-name">No holiday today</span>
                    </div>
                </div>

                <!-- Upcoming Holiday -->
                <div class="weather-holiday upcoming" id="holidayUpcoming">
                    <div class="holiday-icon">🔔</div>
                    <div class="holiday-info">
                        <span class="holiday-label">Next Holiday</span>
                        <span class="holiday-name">Loading...</span>
                    </div>
                </div>

                <!-- Mini 3-day forecast -->
                <div class="weather-mini-forecast" id="miniForecast">
                    <div class="mini-day">
                        <span class="mini-day-name">--</span>
                        <span class="mini-day-icon">--</span>
                        <span class="mini-day-temp">--°</span>
                    </div>
                    <div class="mini-day">
                        <span class="mini-day-name">--</span>
                        <span class="mini-day-icon">--</span>
                        <span class="mini-day-temp">--°</span>
                    </div>
                    <div class="mini-day">
                        <span class="mini-day-name">--</span>
                        <span class="mini-day-icon">--</span>
                        <span class="mini-day-temp">--°</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.weather-widget {
    margin-bottom: 32px;
    border: none;
    overflow: hidden;
    background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-tertiary) 100%);
}

.weather-widget .card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 16px 24px;
}

.weather-widget .card-header h3 {
    color: white;
    font-weight: 700;
    font-size: 1.1rem;
}

.weather-widget .card-header .badge {
    background: rgba(255,255,255,0.2);
    color: white;
    border: 1px solid rgba(255,255,255,0.3);
}

.weather-widget .card-body {
    padding: 20px;
}

.weather-grid {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 20px;
}

.weather-current {
    display: flex;
    align-items: center;
    gap: 24px;
}

.weather-icon-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-width: 120px;
}

.weather-icon {
    font-size: 3.5rem;
    line-height: 1;
    animation: weatherIconPulse 3s ease-in-out infinite;
}

@keyframes weatherIconPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.weather-temp {
    font-size: 2rem;
    font-weight: 800;
    color: var(--text-primary);
    margin-top: 4px;
}

.weather-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    flex: 1;
}

.weather-detail {
    display: flex;
    flex-direction: column;
    padding: 12px;
    background: var(--bg-secondary);
    border-radius: var(--radius);
    border: 1px solid var(--border-color);
}

.weather-detail-label {
    font-size: 0.75rem;
    color: var(--text-tertiary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}

.weather-detail-value {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--text-primary);
    margin-top: 4px;
}

.weather-sidebar {
    display: flex;
    flex-direction: column;
    gap: 12px;
    border-left: 1px solid var(--border-color);
    padding-left: 20px;
}

.weather-holiday {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: var(--bg-secondary);
    border-radius: var(--radius);
    border: 1px solid var(--border-color);
    transition: var(--transition);
}

.weather-holiday.is-today {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border-color: #f59e0b;
}

.dark-mode .weather-holiday.is-today {
    background: linear-gradient(135deg, #422006 0%, #713f12 100%);
}

.holiday-icon {
    font-size: 1.5rem;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--bg-tertiary);
    border-radius: 50%;
    flex-shrink: 0;
}

.holiday-info {
    display: flex;
    flex-direction: column;
}

.holiday-label {
    font-size: 0.7rem;
    color: var(--text-tertiary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}

.holiday-name {
    font-size: 0.85rem;
    color: var(--text-primary);
    font-weight: 600;
}

.weather-mini-forecast {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
    margin-top: 4px;
}

.mini-day {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 10px 4px;
    background: var(--bg-secondary);
    border-radius: var(--radius);
    border: 1px solid var(--border-color);
    text-align: center;
    gap: 4px;
}

.mini-day-name {
    font-size: 0.7rem;
    color: var(--text-tertiary);
    font-weight: 600;
}

.mini-day-icon {
    font-size: 1.2rem;
}

.mini-day-temp {
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--text-primary);
}

@media (max-width: 900px) {
    .weather-grid {
        grid-template-columns: 1fr;
    }

    .weather-sidebar {
        border-left: none;
        padding-left: 0;
        border-top: 1px solid var(--border-color);
        padding-top: 20px;
    }

    .weather-current {
        flex-direction: column;
        text-align: center;
    }
}

@media (max-width: 600px) {
    .weather-details {
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }

    .weather-detail {
        padding: 8px;
    }

    .weather-icon {
        font-size: 2.5rem;
    }

    .weather-temp {
        font-size: 1.5rem;
    }

    .weather-mini-forecast {
        gap: 6px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    fetchWeatherData();
});

function fetchWeatherData() {
    fetch('../api/weather.php')
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                console.error('Weather API error:', data.error);
                return;
            }

            const weather = data.weather;
            const current = weather.current;
            const forecast = weather.forecast;

            document.getElementById('weatherIcon').textContent = current.icon || '⛅';
            document.getElementById('weatherTemp').textContent = current.temperature + '°C';
            document.getElementById('weatherCondition').textContent = current.condition || '--';
            document.getElementById('weatherFeelsLike').textContent = current.feels_like + '°C';
            document.getElementById('weatherHumidity').textContent = current.humidity + '%';
            document.getElementById('weatherWind').textContent = current.wind_speed + ' km/h';
            document.getElementById('weatherTime').textContent = 'Updated ' + new Date().toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit' });

            // Today's holiday
            if (data.holidays && data.holidays.today) {
                const todayEl = document.getElementById('holidayToday');
                todayEl.classList.add('is-today');
                todayEl.querySelector('.holiday-icon').textContent = '🎉';
                todayEl.querySelector('.holiday-name').textContent = data.holidays.today.name;
            }

            // Upcoming holiday
            if (data.holidays && data.holidays.upcoming) {
                const upcoming = data.holidays.upcoming;
                const upcomingEl = document.getElementById('holidayUpcoming');
                upcomingEl.querySelector('.holiday-name').textContent =
                    upcoming.name + ' (' + upcoming.days_until + ' days)';
            } else if (data.holidays && data.holidays.today === null) {
                document.getElementById('holidayUpcoming').querySelector('.holiday-name').textContent = 'No upcoming holidays';
            }

            // Mini 3-day forecast
            if (forecast && forecast.length >= 3) {
                const miniForecast = document.getElementById('miniForecast');
                miniForecast.innerHTML = '';
                for (let i = 0; i < 3; i++) {
                    const day = forecast[i];
                    const dayName = day.day.substring(0, 3);
                    miniForecast.innerHTML +=
                        '<div class="mini-day">' +
                            '<span class="mini-day-name">' + dayName + '</span>' +
                            '<span class="mini-day-icon">' + day.icon + '</span>' +
                            '<span class="mini-day-temp">' + day.temp_min + '°-' + day.temp_max + '°</span>' +
                        '</div>';
                }
            }
        })
        .catch(err => {
            console.error('Weather fetch error:', err);
        });
}
</script>
