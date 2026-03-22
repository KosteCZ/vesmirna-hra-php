<?php
session_start();
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🚀 Vesmírná Kolonie (PHP)</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- SVG Icons Definition -->
    <svg style="display: none;">
        <symbol id="icon-iron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 10h16M4 14h16M4 18h16M4 6h16M21 6V18a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V6a3 3 0 0 1 3-3h12a3 3 0 0 1 3 3z" />
        </symbol>
        <symbol id="icon-energy" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" />
        </symbol>
        <symbol id="icon-mine" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 2v10M18 9l-6 3-6-3M6 15l6 3 6-3" />
        </symbol>
        <symbol id="icon-solar" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
            <path d="M3 9h18M3 15h18M9 3v18M15 3v18" />
        </symbol>
        <symbol id="icon-warehouse" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
            <polyline points="9 22 9 12 15 12 15 22" />
        </symbol>
        <symbol id="icon-upgrade" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="17 11 12 6 7 11" />
            <polyline points="17 18 12 13 7 18" />
        </symbol>
        <symbol id="icon-crystal" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" />
        </symbol>
        <symbol id="icon-vehicle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 2L2 22h20L12 2z" />
            <path d="M12 18l-3-3m6 0l-3 3" />
        </symbol>
        <symbol id="icon-alien-res" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="5" y="5" width="14" height="14" rx="2" />
        </symbol>
        <symbol id="icon-drone" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 2v20M2 12h20" />
            <circle cx="12" cy="12" r="3" />
            <path d="M18 6l-4 4M6 18l4-4M6 6l4 4M18 18l-4-4" />
        </symbol>
    </svg>

    <main>
        <header class="top-bar">
            <div class="logo">🚀 Vesmírná Kolonie</div>
            <div id="user-info" class="user-pill hidden">
                <span id="player-name"></span>
                <button class="logout-btn" onclick="auth.logout()">✕</button>
            </div>
        </header>

        <!-- Auth Section -->
        <section id="auth-section" class="auth-container card hidden">
            <h2 id="auth-title">Přihlášení na palubu</h2>
            <form id="auth-form">
                <div class="input-group">
                    <label for="email">E-mail</label>
                    <input id="email" type="email" required placeholder="velitel@vesmir.cz">
                </div>
                <div class="input-group">
                    <label for="password">Heslo</label>
                    <input id="password" type="password" required placeholder="********">
                </div>
                <div id="playerName-group" class="input-group hidden">
                    <label for="playerName">Jméno velitele</label>
                    <input id="playerName" type="text" placeholder="Např. Captain Solo">
                </div>
                <button type="submit" id="auth-submit">Vstoupit do hry</button>
            </form>
            <p>
                <span id="auth-switch-text">Ještě nemáš kolonii?</span>
                <button class="link-btn" onclick="auth.toggleMode()" id="auth-switch-btn">Zaregistrovat se</button>
            </p>
        </section>

        <!-- Loading Section -->
        <div id="loading-section" class="loader-container">
            <div class="loader">Skenuji orbitu...</div>
        </div>

        <!-- Dashboard Section -->
        <div id="dashboard-section" class="dashboard hidden">
            <!-- Resource Section -->
            <section class="resources">
                <div class="res-card iron">
                    <div class="res-icon"><svg width="24" height="24"><use href="#icon-iron"/></svg></div>
                    <div class="res-data">
                        <span class="label">Železo</span>
                        <span class="value"><span id="display-iron">0</span> <small>/ <span id="display-limit">0</span></small></span>
                        <div class="progress-bg">
                            <div id="iron-progress" class="progress-bar" style="width: 0%"></div>
                        </div>
                        <span class="prod">+<span id="iron-prod">0</span>/s</span>
                    </div>
                </div>

                <div class="res-card energy">
                    <div class="res-icon"><svg width="24" height="24"><use href="#icon-energy"/></svg></div>
                    <div class="res-data">
                        <span class="label">Energie</span>
                        <span class="value" id="display-energy">0</span>
                        <span class="prod">+<span id="energy-prod">0</span>/s</span>
                    </div>
                </div>

                <div class="res-card crystal">
                    <div class="res-icon" style="color: #bc4aff;"><svg width="24" height="24"><use href="#icon-crystal"/></svg></div>
                    <div class="res-data">
                        <span class="label">Krystaly</span>
                        <span class="value" id="display-crystal">0</span>
                    </div>
                </div>
            </section>

            <!-- Alien Resource Section (Dynamic) -->
            <section id="alien-resources" class="resources alien-res hidden">
                <!-- Filled by JS -->
            </section>

            <!-- Building Section -->
            <section class="buildings">
                <div class="building-card">
                    <svg width="40" height="40"><use href="#icon-mine"/></svg>
                    <h3>Důl na železo</h3>
                    <p class="lvl">Úroveň <span id="mine-lvl">0</span></p>
                    <p class="desc">Produkuje železo (stojí energii).</p>
                    <button onclick="game.upgrade('mine')" id="upgrade-mine">
                        Vylepšit <svg width="14" height="14"><use href="#icon-upgrade"/></svg> <span id="mine-cost"></span>
                    </button>
                </div>

                <div class="building-card">
                    <svg width="40" height="40"><use href="#icon-solar"/></svg>
                    <h3>Solární elektrárna</h3>
                    <p class="lvl">Úroveň <span id="solar-lvl">0</span></p>
                    <p class="desc">Vyrábí energii pro provoz budov.</p>
                    <button onclick="game.upgrade('solar')" id="upgrade-solar">
                        Vylepšit <svg width="14" height="14"><use href="#icon-upgrade"/></svg> <span id="solar-cost"></span>
                    </button>
                </div>

                <div class="building-card">
                    <svg width="40" height="40"><use href="#icon-warehouse"/></svg>
                    <h3>Sklad surovin</h3>
                    <p class="lvl">Úroveň <span id="warehouse-lvl">0</span></p>
                    <p class="desc">Zvyšuje maximální kapacitu železa.</p>
                    <button onclick="game.upgrade('warehouse')" id="upgrade-warehouse">
                        Vylepšit <svg width="14" height="14"><use href="#icon-upgrade"/></svg> <span id="warehouse-cost"></span>
                    </button>
                </div>
            </section>

            <!-- Alien Buildings Section (Dynamic) -->
            <section id="alien-buildings" class="buildings alien-bld hidden">
                <!-- Filled by JS -->
            </section>

            <!-- Expedition Section -->
            <section class="expedition card" style="margin-bottom: 30px;">
                <h3>🛰️ Hangár a expedice</h3>
                <div id="no-vehicle-view" class="hidden">
                    <p>Nemáš žádné průzkumné vozidlo.</p>
                    <button onclick="game.buyVehicle()" style="background: #bc4aff;">Koupit vozidlo (500 Fe)</button>
                </div>
                
                <div id="vehicle-view" class="hidden" style="display: flex; gap: 20px; align-items: center;">
                    <div style="flex: 1; text-align: center;">
                        <svg width="60" height="60" style="color: #bc4aff;"><use href="#icon-vehicle"/></svg>
                        <p><strong>Úroveň pancíře: <span id="vehicle-lvl">1</span></strong></p>
                        <p style="font-size: 0.8rem; color: #888;">Ochrana: <span id="vehicle-reduction">0</span>%</p>
                        <hr style="border: 0; border-top: 1px solid #333; margin: 10px 0;">
                        <p><strong>Senzory: Lvl <span id="vehicle-sensor-lvl">1</span></strong></p>
                        <p style="font-size: 0.8rem; color: #888;">Bonus: <span id="vehicle-sensor-bonus">0</span>%</p>
                    </div>
                    
                    <div style="flex: 2;">
                        <div id="vehicle-idle" class="hidden">
                            <p>Vozidlo je připraveno v hangáru.</p>
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                <button onclick="game.startExpedition()" style="background: #28a745;">Vyslat na expedici</button>
                                <div style="display: flex; gap: 10px;">
                                    <button onclick="game.upgradeVehicle()" id="upgrade-vehicle-btn" style="flex: 1;">Vylepšit pancíř (<span id="vehicle-upgrade-cost"></span> Fe)</button>
                                    <button onclick="game.upgradeVehicleSensors()" id="upgrade-sensors-btn" style="flex: 1;">Vylepšit senzory (<span id="vehicle-sensor-cost"></span> Fe)</button>
                                </div>
                            </div>
                        </div>
                        
                        <div id="vehicle-active" class="hidden">
                            <p id="vehicle-status-text">Probíhá průzkum...</p>
                            <div class="progress-bg" style="height: 10px; margin-bottom: 10px;">
                                <div id="vehicle-hp-bar" class="progress-bar" style="background: #28a745; width: 100%;"></div>
                            </div>
                            <p style="font-size: 0.9rem; margin-bottom: 5px;">
                                ⏱️ Čas: <span id="vehicle-timer">0:00</span> | 
                                Stav pancíře: <span id="vehicle-hp-val">100</span>%
                            </p>
                            <p style="font-size: 0.9rem; margin-bottom: 10px;">Nalezeno krystalů: <span id="vehicle-crystals">0</span></p>
                            <button id="recall-btn" onclick="game.recallVehicle()" style="background: #ffc107; color: black;">Odvolat vozidlo</button>
                        </div>

                        <div id="vehicle-destroyed" class="hidden">
                            <p style="color: #ff4a4a;"><strong>⚠️ Vozidlo bylo při průzkumu zničeno!</strong></p>
                            <button onclick="game.buyVehicle()" style="background: #bc4aff;">Postavit nové vozidlo (500 Fe)</button>
                        </div>
                    </div>
                </div>

                <!-- Drone Sub-section -->
                <div id="drone-section" style="margin-top: 25px; padding-top: 20px; border-top: 1px solid #30363d;">
                    <div id="no-drone-view" class="hidden" style="display: flex; align-items: center; gap: 15px;">
                        <div style="background: #333; padding: 10px; border-radius: 50%; opacity: 0.5;">
                            <svg width="24" height="24"><use href="#icon-drone"/></svg>
                        </div>
                        <div style="flex-grow: 1;">
                            <p style="margin: 0;"><strong>Těžební dron</strong></p>
                            <p style="margin: 0; font-size: 0.8rem; color: #888;">Automaticky těží 1 krystal každých 5 minut.</p>
                        </div>
                        <button onclick="game.buyDrone()" style="width: auto; background: #bc4aff; padding: 5px 15px;">Koupit drona (250 Kryst.)</button>
                    </div>

                    <div id="drone-view" class="hidden" style="display: flex; align-items: center; gap: 15px;">
                        <div style="background: #bc4aff1a; padding: 10px; border-radius: 50%; color: #bc4aff;">
                            <svg width="24" height="24"><use href="#icon-drone"/></svg>
                        </div>
                        <div style="flex-grow: 1;">
                            <p style="margin: 0;"><strong>Těžební dron (Aktivní)</strong></p>
                            <p style="margin: 0; font-size: 0.85rem;">
                                Zásoba: <span id="drone-storage-val">0</span> / 100 💎
                            </p>
                            <div class="progress-bg" style="height: 6px; width: 150px; margin-top: 5px;">
                                <div id="drone-progress-bar" class="progress-bar" style="background: #bc4aff; width: 0%;"></div>
                            </div>
                        </div>
                        <button onclick="game.collectDrone()" id="collect-drone-btn" style="width: auto; background: #28a745; padding: 5px 15px;">Vybrat krystaly</button>
                    </div>
                </div>
            </section>

            <!-- Research Lab -->
            <section id="research-lab" class="card" style="margin-bottom: 30px;">
                <h3>🔬 Vědecká laboratoř</h3>
                <div id="research-info">
                    <!-- Research status message -->
                </div>
                <div id="color-options" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 10px; margin-top: 15px;">
                    <!-- Research buttons -->
                </div>
            </section>

            <!-- Leaderboard Section -->
            <section class="leaderboard card" style="margin-bottom: 30px;">
                <h3>🏆 Top Průzkumníci</h3>
                <table>
                    <thead>
                        <tr><th>Pozice</th><th>Velitel</th><th>Důl na železo</th><th>Sklad Fe</th></tr>
                    </thead>
                    <tbody id="leaderboard-body">
                        <!-- Filled by JS -->
                    </tbody>
                </table>
            </section>

            <!-- Game Goal Section -->
            <section id="game-goal" class="card">
                <h3>🌌 Společný cíl: Megaprojekt "Nová naděje"</h3>
                <p style="font-size: 0.9rem; color: #888; margin-bottom: 20px;">
                    Spojte síly se všemi veliteli v galaxii a nashromážděte dostatek mimozemských materiálů pro stavbu intergalaktické brány.
                </p>
                <div id="global-progress-container" style="display: flex; flex-direction: column; gap: 15px;">
                    <!-- Filled by JS -->
                </div>
            </section>
        </div>
    </main>

    <script src="script.js"></script>
</body>
</html>
