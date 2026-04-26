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
        <symbol id="icon-secret-mine" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 2L2 7l10 5 10-5-10-5z" />
            <path d="M2 17l10 5 10-5" />
            <path d="M2 12l10 5 10-5" />
            <circle cx="12" cy="12" r="3" />
        </symbol>
        <symbol id="icon-copper" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="9" />
            <path d="M12 8v8M8 12h8" />
        </symbol>
        <symbol id="icon-tube" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 2v17.5A2.5 2.5 0 0 0 11.5 22h0a2.5 2.5 0 0 0 2.5-2.5V2M9 8h5M9 14h5"/>
        </symbol>
        <symbol id="icon-lab" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M10 2v7.5M14 2v7.5M8.5 13h7M7 9h10l3 13H4L7 9z"/>
        </symbol>
    </svg>

    <main>
        <header class="top-bar">
            <div class="logo">🚀 Vesmírná Kolonie</div>
            <div class="top-bar-controls">
                <div id="graphics-toggle-container" class="graphics-toggle">
                    <span class="toggle-label">Grafika</span>
                    <label class="switch">
                        <input type="checkbox" id="graphics-toggle" onclick="game.toggleGraphics()" checked>
                        <span class="slider round"></span>
                    </label>
                </div>
                <div id="user-info" class="user-pill hidden">
                    <span id="player-name"></span>
                    <button class="logout-btn" onclick="auth.logout()">✕</button>
                </div>
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
                    <div class="res-icon" id="icon-iron-container"><svg width="24" height="24"><use href="#icon-iron"/></svg></div>
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
                    <div class="res-icon" id="icon-energy-container"><svg width="24" height="24"><use href="#icon-energy"/></svg></div>
                    <div class="res-data">
                        <span class="label">Energie</span>
                        <span class="value" id="display-energy">0</span>
                        <span class="prod">+<span id="energy-prod">0</span>/s</span>
                    </div>
                </div>

                <div class="res-card crystal">
                    <div class="res-icon" id="icon-crystal-container" style="color: #bc4aff;"><svg width="24" height="24"><use href="#icon-crystal"/></svg></div>
                    <div class="res-data">
                        <span class="label">Krystaly</span>
                        <span class="value" id="display-crystal">0</span>
                        <span class="prod hidden" id="crystal-prod-container">+<span id="crystal-prod">0</span>/min</span>
                    </div>
                </div>

                <div id="res-copper-card" class="res-card copper hidden" style="border-color: #b87333;">
                    <div class="res-icon" id="icon-copper-container" style="color: #b87333;"><svg width="24" height="24"><use href="#icon-copper"/></svg></div>
                    <div class="res-data">
                        <span class="label">Měď</span>
                        <span class="value"><span id="display-copper">0</span> <small>/ <span id="display-copper-limit">0</span></small></span>
                        <div class="progress-bg">
                            <div id="copper-progress" class="progress-bar" style="width: 0%; background: #b87333;"></div>
                        </div>
                        <span class="prod">+<span id="copper-prod">0</span>/s</span>
                    </div>
                </div>

                <div id="res-tubes-card" class="res-card tubes hidden" style="border-color: #00d2ff;">
                    <div class="res-icon" id="icon-tubes-container" style="color: #00d2ff;"><svg width="24" height="24"><use href="#icon-tube"/></svg></div>
                    <div class="res-data">
                        <span class="label">Zkumavky</span>
                        <span class="value"><span id="display-tubes">0</span> <small>/ <span id="display-tubes-limit">0</span></small></span>
                        <div class="progress-bg">
                            <div id="tubes-progress" class="progress-bar" style="width: 0%; background: #00d2ff;"></div>
                        </div>
                        <span class="prod">+<span id="tube-prod-val">0.00</span>/s</span>
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
                    <div id="icon-mine-container"><svg width="40" height="40"><use href="#icon-mine"/></svg></div>
                    <h3>Důl na železo</h3>
                    <p class="lvl">Úroveň <span id="mine-lvl">0</span></p>
                    <p class="desc">Produkuje železo (stojí energii).</p>
                    <button onclick="game.upgrade('mine')" id="upgrade-mine">
                        Vylepšit <svg width="14" height="14"><use href="#icon-upgrade"/></svg> <span id="mine-cost"></span>
                    </button>
                </div>

                <div class="building-card">
                    <div id="icon-solar-container"><svg width="40" height="40"><use href="#icon-solar"/></svg></div>
                    <h3>Solární elektrárna</h3>
                    <p class="lvl">Úroveň <span id="solar-lvl">0</span></p>
                    <p class="desc">Vyrábí energii pro provoz budov.</p>
                    <button onclick="game.upgrade('solar')" id="upgrade-solar">
                        Vylepšit <svg width="14" height="14"><use href="#icon-upgrade"/></svg> <span id="solar-cost"></span>
                    </button>
                </div>

                <div class="building-card">
                    <div id="icon-warehouse-container"><svg width="40" height="40"><use href="#icon-warehouse"/></svg></div>
                    <h3>Sklad železa</h3>
                    <p class="lvl">Úroveň <span id="warehouse-lvl">0</span></p>
                    <p class="desc">Zvyšuje maximální kapacitu železa.</p>
                    <button onclick="game.upgrade('warehouse')" id="upgrade-warehouse">
                        Vylepšit <svg width="14" height="14"><use href="#icon-upgrade"/></svg> <span id="warehouse-cost"></span>
                    </button>
                    <button onclick="game.upgradeWarehouseCopperEff()" id="upgrade-warehouse-copper-eff" class="hidden" style="background: #b87333; margin-top: 5px; font-size: 0.8rem;">
                        Efektivní vylepšení <svg width="14" height="14"><use href="#icon-upgrade"/></svg> (<span id="warehouse-copper-cost-eff"></span>)
                    </button>
                </div>
            </section>

            <section id="copper-buildings" class="buildings hidden" style="margin-top: 20px;">
                <div class="building-card" style="border-color: #b87333;">
                    <div id="icon-mine-copper-container"><svg width="40" height="40" style="color: #b87333;"><use href="#icon-mine"/></svg></div>
                    <h3>Důl na měď</h3>
                    <p class="lvl">Úroveň <span id="mine-copper-lvl">0</span></p>
                    <p class="desc">Produkuje měď pro pokročilé stavby.</p>
                    <button onclick="game.upgradeCopperMine()" id="upgrade-mine-copper">
                        Vylepšit <svg width="14" height="14"><use href="#icon-upgrade"/></svg> <span id="mine-copper-cost"></span>
                    </button>
                </div>

                <div class="building-card" style="border-color: #b87333;">
                    <div id="icon-warehouse-copper-container"><svg width="40" height="40" style="color: #b87333;"><use href="#icon-warehouse"/></svg></div>
                    <h3>Sklad mědi</h3>
                    <p class="lvl">Úroveň <span id="warehouse-copper-lvl">0</span></p>
                    <p class="desc">Zvyšuje maximální kapacitu mědi.</p>
                    <button onclick="game.upgradeCopperWarehouse()" id="upgrade-warehouse-copper">
                        Vylepšit <svg width="14" height="14"><use href="#icon-upgrade"/></svg> <span id="warehouse-copper-cost"></span>
                    </button>
                </div>
            </section>

            <!-- Advanced Lab Buildings -->
            <section id="lab-buildings" class="buildings hidden" style="margin-top: 20px;">
                <div class="building-card" style="border-color: #00d2ff;">
                    <div id="icon-lab-container"><svg width="40" height="40" style="color: #00d2ff;"><use href="#icon-lab"/></svg></div>
                    <h3>Pokročilá laboratoř</h3>
                    <p class="lvl">Úroveň <span id="lab-lvl">0</span></p>
                    <p class="desc">Vyrábí zkumavky pro vědecké účely.</p>
                    <button onclick="game.upgradeLab()" id="upgrade-lab-btn">
                        Vylepšit <svg width="14" height="14"><use href="#icon-upgrade"/></svg> <span id="lab-upgrade-cost"></span>
                    </button>
                </div>

                <div class="building-card" style="border-color: #00d2ff;">
                    <div id="icon-lab-storage-container"><svg width="40" height="40" style="color: #00d2ff;"><use href="#icon-warehouse"/></svg></div>
                    <h3>Sklad zkumavek</h3>
                    <p class="lvl">Úroveň <span id="lab-storage-lvl">0</span></p>
                    <p class="desc">Zvětšuje prostor pro hotové zkumavky.</p>
                    <button onclick="game.upgradeLabStorage()" id="upgrade-lab-storage-btn">
                        Vylepšit <svg width="14" height="14"><use href="#icon-upgrade"/></svg> <span id="lab-storage-upgrade-cost"></span>
                    </button>
                </div>

                <!-- Secret Crystal Mine -->
                <div id="secret-mine-building" class="building-card hidden" style="border-color: #38bdf8;">
                    <div id="icon-secret-mine-container"></div>
                    <h3>Skrytý důl na krystaly</h3>
                    <p class="lvl">Úroveň <span id="secret-mine-lvl">0</span></p>
                    <p class="desc">Těží krystaly z hlubin. Produkce závisí na počtu hráčů, kteří důl objevili. <br><small id="secret-mine-discovered-count-label" style="color: #38bdf8;"></small></p>
                    <button onclick="game.upgradeSecretMine()" id="upgrade-secret-mine-btn">
                        Vylepšit <svg width="14" height="14"><use href="#icon-upgrade"/></svg> <span id="secret-mine-upgrade-cost"></span>
                    </button>
                </div>
            </section>

            <section id="rocket-workshop-section" class="card hidden" style="margin-bottom: 30px;">
                <h3 style="display: flex; align-items: center; gap: 10px;">
                    <span id="icon-rocket-workshop-container" style="display: inline-flex; vertical-align: middle;">🚀</span>
                    Raketov&aacute; d&iacute;lna
                </h3>
                <p style="font-size: 0.9rem; color: #888; margin-top: 0;">
                    Experiment&aacute;ln&iacute; d&iacute;lna pro kompletaci raketov&yacute;ch sou&#269;&aacute;stek. Ka&#382;dou &#269;&aacute;st lze vyrobit maxim&aacute;ln&#283; 10x.
                </p>
                <div style="display: flex; flex-wrap: wrap; gap: 20px; align-items: flex-start;">
                    <div style="flex: 1 1 300px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                            <p class="lvl" style="margin: 0;">&Uacute;rove&#328; <span id="rocket-workshop-lvl">0</span></p>
                            <button id="rocket-workshop-upgrade-btn" onclick="game.upgradeRocketWorkshop()" class="hidden" style="width: auto; background: #007bff; padding: 5px 15px; font-size: 0.8rem;">
                                Vylep&scaron;it d&iacute;lnu (1M Fe)
                            </button>
                        </div>

                        <!-- Slot 1: Běžná -->
                        <div class="workshop-slot card" style="padding: 15px; margin-bottom: 15px; border-color: #ff704333;">
                            <p style="margin: 0 0 10px; font-weight: bold; color: #ff7043;">B&#283;&#382;n&aacute; v&yacute;roba (8h)</p>
                            <p id="ws-slot1-status" style="font-size: 0.85rem; margin-bottom: 10px; color: #888;">P&#345;ipraveno</p>
                            <div id="ws-slot1-timer-wrap" class="hidden" style="margin-bottom: 10px;">
                                <div class="progress-bg" style="height: 6px; margin-bottom: 5px;">
                                    <div id="ws-slot1-progress" class="progress-bar" style="background: #ff7043; width: 0%;"></div>
                                </div>
                                <p style="font-size: 0.8rem; color: #00d2ff; margin: 0;">Zb&yacute;v&aacute;: <span id="ws-slot1-timer">00:00:00</span></p>
                            </div>
                            <button id="ws-slot1-start-btn" onclick="game.startRocketWorkshopProduction(1)" style="background: #ff7043; font-size: 0.85rem;">Spustit (10k zkum.)</button>
                            <button id="ws-slot1-collect-btn" onclick="game.collectRocketWorkshopProduct(1)" class="hidden" style="background: #28a745; font-size: 0.85rem;">Vyzvednout (1 ks)</button>
                        </div>

                        <!-- Slot 2: Těžká -->
                        <div id="ws-slot2-container" class="workshop-slot card hidden" style="padding: 15px; border-color: #e64a1933;">
                            <p style="margin: 0 0 10px; font-weight: bold; color: #e64a19;">T&#283;&#382;k&aacute; v&yacute;roba (16h)</p>
                            <p id="ws-slot2-status" style="font-size: 0.85rem; margin-bottom: 10px; color: #888;">P&#345;ipraveno</p>
                            <div id="ws-slot2-timer-wrap" class="hidden" style="margin-bottom: 10px;">
                                <div class="progress-bg" style="height: 6px; margin-bottom: 5px;">
                                    <div id="ws-slot2-progress" class="progress-bar" style="background: #e64a19; width: 0%;"></div>
                                </div>
                                <p style="font-size: 0.8rem; color: #00d2ff; margin: 0;">Zb&yacute;v&aacute;: <span id="ws-slot2-timer">00:00:00</span></p>
                            </div>
                            <button id="ws-slot2-start-btn" onclick="game.startRocketWorkshopProduction(2)" style="background: #e64a19; font-size: 0.85rem;">Spustit (20k zkum.)</button>
                            <button id="ws-slot2-collect-btn" onclick="game.collectRocketWorkshopProduct(2)" class="hidden" style="background: #28a745; font-size: 0.85rem;">Vyzvednout (2 ks)</button>
                        </div>

                        <p id="rocket-workshop-finished-note" class="hidden" style="margin: 12px 0 0; color: #28a745; font-size: 0.85rem;">
                            V&#353;echny druhy sou&#269;&aacute;stek u&#382; m&aacute;&scaron; vyroben&eacute; 10x.
                        </p>
                    </div>

                    <div style="flex: 1 1 320px;">
                        <p style="margin: 0 0 10px;">
                            Hotov&eacute; d&iacute;ly: <strong><span id="rocket-parts-total">0</span> / 100</strong>
                        </p>
                        <div id="rocket-parts-list" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 8px;">
                            <!-- Filled by JS -->
                        </div>
                    </div>
                </div>
            </section>

            <!-- Alien Buildings Section (Dynamic) -->
            <section id="alien-buildings" class="buildings alien-bld hidden">
                <!-- Filled by JS -->
            </section>


            <!-- Expedition Section -->
            <section class="expedition card" style="margin-bottom: 30px;">
                <h3 style="display: flex; align-items: center; gap: 10px;">
                    <span id="icon-hangar-container" style="display: inline-flex; vertical-align: middle;">🛰️</span> 
                    Hangáry a expedice
                </h3>
                
                <!-- Hangar 1: Iron Vehicle -->
                <div style="margin-bottom: 25px; padding-bottom: 20px; border-bottom: 1px solid #30363d;">
                    <p style="margin-top: 0; color: #888; font-size: 0.85rem;">PRŮZKUMNÉ VOZIDLO I (Železo)</p>
                    <div id="no-vehicle-view" class="hidden">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div id="no-vehicle-icon-container" style="background: #333; padding: 10px; border-radius: 50%; opacity: 0.5;">
                                <svg width="30" height="30"><use href="#icon-vehicle"/></svg>
                            </div>
                            <div style="flex-grow: 1;">
                                <p style="margin: 0;"><strong>Vozidlo I není postaveno</strong></p>
                                <p style="margin: 0; font-size: 0.8rem; color: #888;">Vyžaduje železo pro konstrukci.</p>
                            </div>
                            <button onclick="game.buyVehicle()" style="width: auto; background: #bc4aff; padding: 5px 15px;">Postavit (500 Fe)</button>
                        </div>
                    </div>
                    
                    <div id="vehicle-view" class="hidden" style="display: flex; gap: 20px; align-items: center;">
                        <div style="flex: 1; text-align: center;">
                            <div id="vehicle-icon-container">
                                <svg width="50" height="50" style="color: #bc4aff;"><use href="#icon-vehicle"/></svg>
                            </div>
                            <p style="font-size: 0.85rem; margin: 5px 0;"><strong>Pancíř Lvl <span id="vehicle-lvl">1</span></strong></p>
                            <p style="font-size: 0.75rem; color: #888; margin: 0;">Senzory Lvl <span id="vehicle-sensor-lvl">1</span></p>
                        </div>
                        
                        <div style="flex: 2;">
                            <div id="vehicle-idle" class="hidden">
                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                    <button onclick="game.startExpedition()" style="background: #28a745; padding: 5px;">Vyslat na expedici</button>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                                        <button onclick="game.upgradeVehicle()" id="upgrade-vehicle-btn" style="font-size: 0.8rem;">Pancíř (<span id="vehicle-upgrade-cost"></span> Fe)</button>
                                        <button onclick="game.upgradeVehicleSensors()" id="upgrade-sensors-btn" style="font-size: 0.8rem;">Senzory (<span id="vehicle-sensor-cost"></span> Fe)</button>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="vehicle-active" class="hidden">
                                <p id="vehicle-status-text" style="font-size: 0.85rem; margin-top: 0;">Probíhá průzkum...</p>
                                <div class="progress-bg" style="height: 8px; margin-bottom: 5px;">
                                    <div id="vehicle-hp-bar" class="progress-bar" style="background: #28a745; width: 100%;"></div>
                                </div>
                                <p style="font-size: 0.8rem; margin: 0;">
                                    ⏱️ <span id="vehicle-timer">0:00</span> | ❤️ <span id="vehicle-hp-val">100</span>% | 💎 <span id="vehicle-crystals">0</span>
                                </p>
                                <button id="recall-btn" onclick="game.recallVehicle()" style="background: #ffc107; color: black; padding: 3px 10px; margin-top: 8px; font-size: 0.8rem;">Odvolat</button>
                            </div>

                            <div id="vehicle-destroyed" class="hidden">
                                <p style="color: #ff4a4a; margin: 0; font-size: 0.85rem;"><strong>⚠️ Vozidlo bylo zničeno!</strong></p>
                                <button onclick="game.buyVehicle()" style="background: #bc4aff; margin-top: 5px; padding: 5px 10px; font-size: 0.85rem;">Postavit nové (500 Fe)</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hangar 2: Copper Vehicle -->
                <div id="hangar2-section" class="hidden" style="margin-bottom: 25px; padding-bottom: 20px; border-bottom: 1px solid #30363d;">
                    <p style="margin-top: 0; color: #b87333; font-size: 0.85rem;">PRŮZKUMNÉ VOZIDLO II (Měď)</p>
                    <div id="no-vehicle2-view" class="hidden">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div id="no-vehicle2-icon-container" style="background: #b8733333; padding: 10px; border-radius: 50%; opacity: 0.5; color: #b87333;">
                                <svg width="30" height="30"><use href="#icon-vehicle"/></svg>
                            </div>
                            <div style="flex-grow: 1;">
                                <p style="margin: 0;"><strong>Vozidlo II není postaveno</strong></p>
                                <p style="margin: 0; font-size: 0.8rem; color: #888;">Vyžaduje měď pro konstrukci.</p>
                            </div>
                            <button onclick="game.buyVehicle2()" style="width: auto; background: #b87333; padding: 5px 15px;">Postavit (500 Cu)</button>
                        </div>
                    </div>
                    
                    <div id="vehicle2-view" class="hangar-view hidden">
                        <div style="flex: 1; text-align: center;">
                            <div id="vehicle2-icon-container">
                                <svg width="50" height="50" style="color: #b87333;"><use href="#icon-vehicle"/></svg>
                            </div>
                            <p style="font-size: 0.85rem; margin: 5px 0;"><strong>Pancíř Lvl <span id="vehicle2-lvl">1</span></strong></p>
                            <p style="font-size: 0.75rem; color: #888; margin: 0;">Senzory Lvl <span id="vehicle2-sensor-lvl">1</span></p>
                        </div>
                        
                        <div style="flex: 2;">
                            <div id="vehicle2-idle" class="hidden">
                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                    <button onclick="game.startExpedition2()" style="background: #28a745; padding: 5px;">Vyslat na expedici</button>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                                        <button onclick="game.upgradeVehicle2Armor()" id="upgrade-vehicle2-btn" style="font-size: 0.8rem;">Pancíř (<span id="vehicle2-upgrade-cost"></span> Cu)</button>
                                        <button onclick="game.upgradeVehicle2Sensors()" id="upgrade-sensors2-btn" style="font-size: 0.8rem;">Senzory (<span id="vehicle2-sensor-cost"></span> Cu)</button>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="vehicle2-active" class="hidden">
                                <p id="vehicle2-status-text" style="font-size: 0.85rem; margin-top: 0;">Probíhá průzkum...</p>
                                <div class="progress-bg" style="height: 8px; margin-bottom: 5px;">
                                    <div id="vehicle2-hp-bar" class="progress-bar" style="background: #28a745; width: 100%;"></div>
                                </div>
                                <p style="font-size: 0.8rem; margin: 0;">
                                    ⏱️ <span id="vehicle2-timer">0:00</span> | ❤️ <span id="vehicle2-hp-val">100</span>% | 💎 <span id="vehicle2-crystals">0</span>
                                </p>
                                <button id="recall2-btn" onclick="game.recallVehicle2()" style="background: #ffc107; color: black; padding: 3px 10px; margin-top: 8px; font-size: 0.8rem;">Odvolat</button>
                            </div>

                            <div id="vehicle2-destroyed" class="hidden">
                                <p style="color: #ff4a4a; margin: 0; font-size: 0.85rem;"><strong>⚠️ Vozidlo II bylo zničeno!</strong></p>
                                <button onclick="game.buyVehicle2()" style="background: #b87333; margin-top: 5px; padding: 5px 10px; font-size: 0.85rem;">Postavit nové (500 Cu)</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Drone Sub-section -->
                <div id="drone-section" style="margin-top: 25px; padding-top: 20px; border-top: 1px solid #30363d;">
                    <div id="no-drone-view" class="hidden" style="display: flex; align-items: center; gap: 15px;">
                        <div id="no-drone-icon-container" style="background: #333; padding: 10px; border-radius: 50%; opacity: 0.5;">
                            <svg width="24" height="24"><use href="#icon-drone"/></svg>
                        </div>
                        <div style="flex-grow: 1;">
                            <p style="margin: 0;"><strong>Těžební dron</strong></p>
                            <p style="margin: 0; font-size: 0.8rem; color: #888;">Automaticky těží 1 krystal každých 5 minut.</p>
                        </div>
                        <button onclick="game.buyDrone()" style="width: auto; background: #bc4aff; padding: 5px 15px;">Koupit drona (250 Kryst.)</button>
                    </div>

                    <div id="drone-view" class="hidden" style="display: flex; align-items: center; gap: 15px;">
                        <div id="drone-icon-container" style="background: #bc4aff1a; padding: 10px; border-radius: 50%; color: #bc4aff;">
                            <svg width="24" height="24"><use href="#icon-drone"/></svg>
                        </div>
                        <div style="flex-grow: 1;">
                            <p style="margin: 0;"><strong>Těžební dron (Aktivní)</strong></p>
                            <p style="margin: 0; font-size: 0.85rem;">
                                Zásoba: <span id="drone-storage-val">0</span> / <span id="drone-storage-limit">100</span> 💎
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

                <div id="copper-research-container" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #333;">
                    <p><strong>Pokročilá metalurgie</strong></p>
                    <p id="copper-research-desc" style="font-size: 0.9rem; color: #888;">Umožňuje těžbu a skladování mědi. Vyžaduje 2000 jednoho druhu barevného materiálu.</p>
                    <button id="research-copper-btn" onclick="game.researchCopper()" style="background: #b87333; margin-top: 10px;">
                        Vyzkoumat Měď (50000 Fe, 50 Kryst.)
                    </button>
                </div>

                <div id="drone-research-container" class="hidden" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #333;">
                    <p><strong>Pokročilá robotika I</strong></p>
                    <p id="drone-research-desc" style="font-size: 0.9rem; color: #888;">Zvyšuje produkci a kapacitu drona 5x.</p>
                    <button id="research-drone-btn" onclick="game.researchDroneUpgrade()" style="background: #bc4aff; margin-top: 10px;">
                        Vylepšit drony (100 Mědi)
                    </button>
                </div>

                <div id="drone-research-2-container" class="hidden" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #333;">
                    <p><strong>Pokročilá robotika II</strong></p>
                    <p id="drone-research-2-desc" style="font-size: 0.9rem; color: #888;">Zvyšuje produkci a kapacitu drona na 25x základní hodnoty. Vyžaduje 2 barvy.</p>
                    <button id="research-drone-2-btn" onclick="game.researchDroneUpgrade2()" style="background: #bc4aff; margin-top: 10px;">
                        Vylepšit drony II (500 Mědi)
                    </button>
                </div>

                <div id="lab-research-container" class="hidden" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #333;">
                    <p><strong>Pokročilá laboratoř</strong></p>
                    <p id="lab-research-desc" style="font-size: 0.9rem; color: #888;">Odemkne výrobu zkumavek pro pokročilý výzkum. Vyžaduje 2 barvy a dosažení součtu všech barevných materiálů v hodnotě 10 000.</p>
                    <button id="research-lab-btn" onclick="game.researchAdvancedLab()" style="background: #00d2ff; color: black; margin-top: 10px;">
                        Postavit laboratoř (5000 Mědi)
                    </button>
                </div>

                <!-- Post-Lab Researches -->
                <div id="research-wh-copper-container" class="hidden" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #333;">
                    <p><strong>Metalurgické sklady</strong></p>
                    <p style="font-size: 0.9rem; color: #888;">Umožňuje vylepšovat Sklad železa pomocí Mědi (5x efektivnější). Vyžaduje Sklad železa Lvl 200.</p>
                    <button id="research-wh-copper-btn" onclick="game.researchWarehouseCopper()" style="background: #00d2ff; color: black; margin-top: 10px;">
                        Vyzkoumat (2500 Zkumavek)
                    </button>
                </div>

                <div id="research-drone-3-container" class="hidden" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #333;">
                    <p><strong>Kvantová robotika (Dron III)</strong></p>
                    <p style="font-size: 0.9rem; color: #888;">Zvyšuje efektivitu i limit dronů 4x (celkem 100x oproti základu).</p>
                    <button id="research-drone-3-btn" onclick="game.researchDroneUpgrade3()" style="background: #00d2ff; color: black; margin-top: 10px;">
                        Vyzkoumat (5000 Zkumavek)
                    </button>
                </div>

                <div id="research-auto-recall-container" class="hidden" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #333;">
                    <p><strong>Automatický návratový systém</strong></p>
                    <p style="font-size: 0.9rem; color: #888;">Vozidla se automaticky vrátí z expedice, pokud jejich zdraví klesne na 90 %.</p>
                    <button id="research-auto-recall-btn" onclick="game.researchAutoRecall()" style="background: #00d2ff; color: black; margin-top: 10px;">
                        Vyzkoumat (7500 Zkumavek)
                    </button>
                </div>
                <div id="research-rocket-workshop-container" class="hidden" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #333;">
                    <p><strong>Raketov&aacute; d&iacute;lna</strong></p>
                    <p style="font-size: 0.9rem; color: #888;">Za 15000 zkumavek odemkne a rovnou postav&iacute; novou budovu pro osmihodinovou v&yacute;robu raketov&yacute;ch sou&#269;&aacute;stek.</p>
                    <button id="research-rocket-workshop-btn" onclick="game.researchRocketWorkshop()" style="background: #ff7043; color: black; margin-top: 10px;">
                        Vyzkoumat a postavit (15000 zkumavek)
                    </button>
                </div>

                <div id="research-alien-slot-3-container" class="hidden" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #333;">
                    <p><strong>3. d&#367;l na mimozemsk&eacute; materi&aacute;ly</strong></p>
                    <p style="font-size: 0.9rem; color: #888;">Umo&#382;n&iacute; t&#283;&#382;it t&#345;et&iacute; druh mimozemsk&eacute;ho materi&aacute;lu sou&#269;asn&#283;.</p>
                    <p style="font-size: 0.8rem; color: #aaa;">Vy&#382;aduje: 2 st&aacute;vaj&iacute;c&iacute; doly Lvl 50+, Raketov&aacute; d&iacute;lna.</p>
                    <button id="research-alien-slot-3-btn" onclick="game.researchAlienSlot3()" style="background: #bc4aff; color: black; margin-top: 10px;">
                        Vyzkoumat (25k zkum., 2M Fe, 25k Cu)
                    </button>
                </div>

                <div id="research-secret-mine-container" class="hidden" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #333;">
                    <p><strong>Skrytý důl na krystaly</strong></p>
                    <p style="font-size: 0.9rem; color: #888;">Odemkne stavbu speciálního dolu na krystaly. Produkce roste s počtem velitelů, kteří jej objevili. <br><small id="secret-mine-note-text" style="color: #38bdf8;"></small></p>
                    <button id="research-secret-mine-btn" onclick="game.researchSecretMine()" style="background: #00d2ff; color: black; margin-top: 10px;">
                        Vyzkoumat (30 000 Zkumavek)
                    </button>
                </div>
            </section>

            <!-- Leaderboard Section -->
            <section class="leaderboard card" style="margin-bottom: 30px;">
                <h3>🏆 Top Průzkumníci</h3>
                <table>
                    <thead>
                        <tr><th>Pozice</th><th>Velitel</th><th>Důl na železo</th><th>Přihlášen</th></tr>
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

    <footer style="text-align: center; padding: 20px; color: #444; font-size: 0.7rem; font-family: monospace;">
        v2026.04.26.2107
    </footer>

    <!-- Workshop Item Modal -->
    <div id="workshop-modal" class="modal hidden">
        <div class="modal-content card">
            <h3 id="modal-title">Nový díl získán!</h3>
            <div id="modal-image-container" style="margin: 20px 0; text-align: center;">
                <!-- Image will be injected here -->
            </div>
            <p id="modal-text" style="font-size: 1.1rem; font-weight: bold; margin-bottom: 25px;"></p>
            <button onclick="document.getElementById('workshop-modal').classList.add('hidden')" style="background: #28a745;">Skvělé!</button>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>
