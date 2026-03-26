/**
 * script.js - Game frontend logic
 */

const auth = {
    isSignUp: false,
    
    toggleMode() {
        this.isSignUp = !this.isSignUp;
        document.getElementById('auth-title').innerText = this.isSignUp ? 'Registrace do Kolonie' : 'Přihlášení na Palubu';
        document.getElementById('auth-submit').innerText = this.isSignUp ? 'Založit kolonii' : 'Vstoupit do hry';
        document.getElementById('auth-switch-text').innerText = this.isSignUp ? 'Už máš účet?' : 'Ještě nemáš kolonii?';
        document.getElementById('auth-switch-btn').innerText = this.isSignUp ? 'Přihlásit se' : 'Zaregistrovat se';
        document.getElementById('playerName-group').classList.toggle('hidden', !this.isSignUp);
    },

    async init() {
        const res = await fetch('auth.php?action=status');
        const data = await res.json();
        
        document.getElementById('loading-section').classList.add('hidden');
        
        if (data.authenticated) {
            game.showDashboard(data.user);
        } else {
            document.getElementById('auth-section').classList.remove('hidden');
        }
    },

    async logout() {
        await fetch('auth.php?action=logout');
        location.reload();
    }
};

const game = {
    planet: null,
    displayIron: 0,
    displayEnergy: 0,
    displayCrystal: 0,
    displayCopper: 0,
    displayAlien: {}, // Tracks alien resource amounts locally
    displayDrone: 0, // Tracks drone storage locally
    vehicleHP: 100,
    vehicleCrystals: 0,
    vehicle2HP: 100,
    vehicle2Crystals: 0,
    interval: null,

    showDashboard(user) {
        document.getElementById('auth-section').classList.add('hidden');
        document.getElementById('user-info').classList.remove('hidden');
        document.getElementById('player-name').innerText = user.player_name;
        document.getElementById('dashboard-section').classList.remove('hidden');
        this.fetchPlanet();
        this.fetchLeaderboard();
    },

    async fetchPlanet() {
        const res = await fetch('api.php?action=get_planet');
        this.planet = await res.json();
        if (this.planet) {
            this.displayIron = this.planet.iron_amount;
            this.displayEnergy = this.planet.energy_amount;
            this.displayCrystal = this.planet.crystal_amount;
            this.displayCopper = this.planet.res_copper || 0;
            this.displayDrone = this.planet.drone_storage || 0;
            this.vehicleHP = this.planet.vehicle_hp || 100;
            this.vehicle2HP = this.planet.vehicle2_hp || 100;
            
            // Sync alien resources
            this.displayAlien = {};
            for (const color in this.planet.alien_resources) {
                this.displayAlien[color] = this.planet.alien_resources[color].amount;
            }
            
            this.startLoop();
            this.updateUI();
            this.updateResearchUI();
            this.updateAlienUI();
        }
    },

    async fetchLeaderboard() {
        const res = await fetch('api.php?action=leaderboard');
        const data = await res.json();
        const body = document.getElementById('leaderboard-body');
        body.innerHTML = '';
        data.forEach((p, i) => {
            const tr = document.createElement('tr');
            if (p.player_name === document.getElementById('player-name').innerText) tr.className = 'highlight';
            
            // Parse researched colors for the leaderboard
            const researched = p.researched_colors ? p.researched_colors.split(',') : [];
            let iconsHtml = '';
            researched.forEach(color => {
                const colorCode = this.getColorCode(color);
                iconsHtml += `<svg width="16" height="16" style="color: ${colorCode}; margin-left: 5px; vertical-align: middle;"><use href="#icon-alien-res"/></svg>`;
            });

            tr.innerHTML = `
                <td>${i + 1}.</td>
                <td>${p.player_name}${iconsHtml}</td>
                <td>Lvl ${p.mine_level}</td>
                <td>${Math.floor(p.iron_amount)}</td>
            `;
            body.appendChild(tr);
        });
    },

    startLoop() {
        if (this.interval) clearInterval(this.interval);
        
        this.interval = setInterval(() => {
            const ironProd = this.planet.iron_production;
            const energyProd = this.planet.energy_production;
            const ironLimit = this.planet.iron_storage_limit;

            // Calculate total energy needed (Sync with db.php logic)
            let extraEnergyNeeded = 0;
            for (const color in this.planet.alien_resources) {
                extraEnergyNeeded += (this.planet.alien_resources[color].lvl * 0.3);
            }
            extraEnergyNeeded += (this.planet.mine_copper_lvl * 0.5);

            const totalEnergyNeeded = (ironProd * 0.5) + extraEnergyNeeded;

            // Resource Production
            const energyTick = (energyProd / 10);
            const energyNeededTick = (totalEnergyNeeded / 10);
            
            let prodFactor = 1.0;
            if (this.displayEnergy < energyNeededTick) {
                prodFactor = 0.1; // 90% drop without energy
            }

            this.displayEnergy += (energyTick - energyNeededTick);
            this.displayEnergy = Math.max(0, this.displayEnergy);

            if (this.displayIron < ironLimit) {
                this.displayIron += (ironProd / 10) * prodFactor;
            }

            // Copper Production
            if (this.planet.research_copper && this.displayCopper < this.planet.copper_storage_limit) {
                this.displayCopper += (this.planet.copper_production / 10) * prodFactor;
            }

            // Alien Production
            for (const color in this.planet.alien_resources) {
                const prod = this.planet.alien_resources[color].prod;
                this.displayAlien[color] += (prod / 10) * prodFactor;
            }

            // Drone Production (1 crystal / 300 seconds base)
            if (this.planet.has_drone) {
                let multiplier = 1;
                if (this.planet.research_drone_upgrade_2) multiplier = 25;
                else if (this.planet.research_drone_upgrade) multiplier = 5;

                const prod = (1 / 3000) * multiplier; // 1/300 per sec, loop is 0.1s
                const limit = 100 * multiplier;
                this.displayDrone = Math.min(limit, this.displayDrone + prod);
            }

            // Vehicle 1 Expedition Logic
            if (this.planet.vehicle_status === 'exploring' || this.planet.vehicle_status === 'returning') {
                const now = new Date();
                const startTime = new Date(this.planet.vehicle_start_time + " UTC");
                const secondsOut = (now - startTime) / 1000;
                
                const baseDamageRate = 0.1; 
                const acceleration = 0.003;
                const armorFactor = Math.pow(this.planet.vehicle_level || 1, 1.2);
                const totalDamage = (secondsOut * (baseDamageRate + (secondsOut * acceleration))) / armorFactor;
                
                this.vehicleHP = Math.max(0, 100 - totalDamage);
                let displaySeconds = 0;

                if (this.planet.vehicle_status === 'exploring') {
                    const sensorLvl = this.planet.vehicle_sensor_lvl || 1;
                    const timeBonus = 1 + (secondsOut * 0.0005);
                    const crystalRate = 0.1 * (1 + (sensorLvl - 1) * 0.05) * timeBonus;
                    this.vehicleCrystals = secondsOut * crystalRate;
                    displaySeconds = secondsOut;
                } else {
                    const recallTime = new Date(this.planet.vehicle_recall_time + " UTC");
                    const secondsReturning = (now - recallTime) / 1000;
                    const secondsToReturn = (recallTime - startTime) / 1000;
                    displaySeconds = Math.max(0, secondsToReturn - secondsReturning);
                    if (secondsReturning >= secondsToReturn) this.finishExpedition();
                }

                const mins = Math.floor(displaySeconds / 60);
                const secs = Math.floor(displaySeconds % 60);
                const timerEl = document.getElementById('vehicle-timer');
                if (timerEl) timerEl.innerText = `${mins}:${secs.toString().padStart(2, '0')}`;

                if (this.vehicleHP <= 0) this.destroyVehicle();
            }

            // Vehicle 2 Expedition Logic
            if (this.planet.vehicle2_status === 'exploring' || this.planet.vehicle2_status === 'returning') {
                const now = new Date();
                const startTime = new Date(this.planet.vehicle2_start_time + " UTC");
                const secondsOut = (now - startTime) / 1000;
                
                const baseDamageRate = 0.1; 
                const acceleration = 0.003;
                // Armor is 2x more effective (level counts double for the bonus)
                const effectiveLevel = 1 + ((this.planet.vehicle2_level || 1) - 1) * 2;
                const armorFactor = Math.pow(effectiveLevel, 1.2);
                const totalDamage = (secondsOut * (baseDamageRate + (secondsOut * acceleration))) / armorFactor;
                
                this.vehicle2HP = Math.max(0, 100 - totalDamage);
                let displaySeconds = 0;

                if (this.planet.vehicle2_status === 'exploring') {
                    const sensorLvl = this.planet.vehicle2_sensor_lvl || 1;
                    const timeBonus = 1 + (secondsOut * 0.0005);
                    // Sensors are 2x more effective (10% bonus instead of 5%)
                    const crystalRate = 0.2 * (1 + (sensorLvl - 1) * 0.10) * timeBonus;
                    this.vehicle2Crystals = secondsOut * crystalRate;
                    displaySeconds = secondsOut;
                } else {
                    const recallTime = new Date(this.planet.vehicle2_recall_time + " UTC");
                    const secondsReturning = (now - recallTime) / 1000;
                    const secondsToReturn = (recallTime - startTime) / 1000;
                    displaySeconds = Math.max(0, secondsToReturn - secondsReturning);
                    if (secondsReturning >= secondsToReturn) this.finishExpedition2();
                }

                const mins = Math.floor(displaySeconds / 60);
                const secs = Math.floor(displaySeconds % 60);
                const timerEl = document.getElementById('vehicle2-timer');
                if (timerEl) timerEl.innerText = `${mins}:${secs.toString().padStart(2, '0')}`;

                if (this.vehicle2HP <= 0) this.destroyVehicle2();
            }

            this.updateUI();
        }, 100);
    },

    updateUI() {
        if (!this.planet) return;
        
        const ironLimit = this.planet.iron_storage_limit;
        document.getElementById('display-iron').innerText = Math.floor(this.displayIron);
        document.getElementById('display-limit').innerText = ironLimit;
        document.getElementById('display-energy').innerText = Math.floor(this.displayEnergy);
        document.getElementById('display-crystal').innerText = Math.floor(this.displayCrystal);
        
        document.getElementById('iron-prod').innerText = this.planet.iron_production.toFixed(2);
        document.getElementById('energy-prod').innerText = this.planet.energy_production.toFixed(2);
        
        const progress = (this.displayIron / ironLimit) * 100;
        document.getElementById('iron-progress').style.width = `${Math.min(100, progress)}%`;
        
        document.getElementById('mine-lvl').innerText = this.planet.mine_level;
        document.getElementById('solar-lvl').innerText = this.planet.solar_plant_level;
        document.getElementById('warehouse-lvl').innerText = this.planet.warehouse_level;
        
        const mineCost = 100 * this.planet.mine_level;
        const solarCost = 100 * this.planet.solar_plant_level;
        const warehouseCost = 100 * this.planet.warehouse_level;
        
        document.getElementById('mine-cost').innerText = `(${mineCost} Fe)`;
        document.getElementById('solar-cost').innerText = `(${solarCost} Fe)`;
        document.getElementById('warehouse-cost').innerText = `(${warehouseCost} Fe)`;
        
        document.getElementById('upgrade-mine').disabled = this.displayIron < mineCost;
        document.getElementById('upgrade-solar').disabled = this.displayIron < solarCost;
        document.getElementById('upgrade-warehouse').disabled = this.displayIron < warehouseCost;

        // Copper UI
        if (this.planet.research_copper) {
            document.getElementById('res-copper-card').classList.remove('hidden');
            document.getElementById('copper-buildings').classList.remove('hidden');
            document.getElementById('copper-research-container').classList.add('hidden');
            
            const copperLimit = this.planet.copper_storage_limit;
            document.getElementById('display-copper').innerText = Math.floor(this.displayCopper);
            document.getElementById('display-copper-limit').innerText = copperLimit;
            document.getElementById('copper-prod').innerText = this.planet.copper_production.toFixed(2);
            
            const copperProgress = (this.displayCopper / copperLimit) * 100;
            document.getElementById('copper-progress').style.width = `${Math.min(100, copperProgress)}%`;
            
            const copperMineLvl = this.planet.mine_copper_lvl;
            const copperWarehouseLvl = this.planet.warehouse_copper_lvl;
            
            document.getElementById('mine-copper-lvl').innerText = copperMineLvl;
            document.getElementById('warehouse-copper-lvl').innerText = copperWarehouseLvl;
            
            const copperMineIronCost = (copperMineLvl + 1) * 1000;
            const copperMineCrystalCost = (copperMineLvl + 1) * 10;
            const copperWarehouseIronCost = (copperWarehouseLvl + 1) * 2000;
            const copperWarehouseCrystalCost = (copperWarehouseLvl + 1) * 20;
            
            document.getElementById('mine-copper-cost').innerText = `(${copperMineIronCost} Fe, ${copperMineCrystalCost} Kryst.)`;
            document.getElementById('warehouse-copper-cost').innerText = `(${copperWarehouseIronCost} Fe, ${copperWarehouseCrystalCost} Kryst.)`;
            
            document.getElementById('upgrade-mine-copper').disabled = this.displayIron < copperMineIronCost || this.displayCrystal < copperMineCrystalCost;
            document.getElementById('upgrade-warehouse-copper').disabled = this.displayIron < copperWarehouseIronCost || this.displayCrystal < copperWarehouseCrystalCost;
        } else {
            document.getElementById('res-copper-card').classList.add('hidden');
            document.getElementById('copper-buildings').classList.add('hidden');
            document.getElementById('copper-research-container').classList.remove('hidden');
            
            const ironCost = 50000;
            const crystalCost = 50;
            
            let hasEnoughMaterial = false;
            for (const color in this.displayAlien) {
                if (this.displayAlien[color] >= 2000) hasEnoughMaterial = true;
            }
            
            const btn = document.getElementById('research-copper-btn');
            if (btn) {
                btn.disabled = this.displayIron < ironCost || this.displayCrystal < crystalCost || !hasEnoughMaterial;
            }
            
            const desc = document.getElementById('copper-research-desc');
            if (desc) {
                desc.style.color = hasEnoughMaterial ? '#888' : '#ff4a4a';
            }
        }

        // Drone Upgrade Research UI
        const droneResContainer = document.getElementById('drone-research-container');
        const droneRes2Container = document.getElementById('drone-research-2-container');
        
        if (this.planet.research_copper) {
            // Upgrade 1
            if (!this.planet.research_drone_upgrade) {
                droneResContainer.classList.remove('hidden');
                const btn = document.getElementById('research-drone-btn');
                if (btn) btn.disabled = this.displayCopper < 100;
            } else {
                droneResContainer.classList.add('hidden');
            }

            // Upgrade 2 (Requires 2 colors and Upgrade 1)
            const researchedCount = (this.planet.researched_colors || []).length;
            if (this.planet.research_drone_upgrade && !this.planet.research_drone_upgrade_2 && researchedCount >= 2) {
                droneRes2Container.classList.remove('hidden');
                const btn2 = document.getElementById('research-drone-2-btn');
                if (btn2) btn2.disabled = this.displayCopper < 500;
            } else {
                droneRes2Container.classList.add('hidden');
            }
        } else {
            droneResContainer.classList.add('hidden');
            droneRes2Container.classList.add('hidden');
        }

        // Update Alien Resource Values in UI
        for (const color in this.displayAlien) {
            const el = document.getElementById(`display-res-${color}`);
            if (el) el.innerText = Math.floor(this.displayAlien[color]);
            
            const btn = document.getElementById(`upgrade-alien-mine-${color}`);
            if (btn) {
                const lvl = this.planet.alien_resources[color].lvl;
                const ironCost = (lvl + 1) * 500;
                const crystalCost = (lvl + 1) * 50;
                btn.disabled = this.displayIron < ironCost || this.displayCrystal < crystalCost;
            }
        }

        // Vehicle I UI (Iron)
        if (this.planet.vehicle_level === 0 && this.planet.vehicle_status !== 'destroyed') {
            document.getElementById('no-vehicle-view').classList.remove('hidden');
            document.getElementById('vehicle-view').classList.add('hidden');
        } else {
            document.getElementById('no-vehicle-view').classList.add('hidden');
            document.getElementById('vehicle-view').classList.remove('hidden');
            document.getElementById('vehicle-lvl').innerText = this.planet.vehicle_level;

            const sensorLvl = this.planet.vehicle_sensor_lvl || 1;
            document.getElementById('vehicle-sensor-lvl').innerText = sensorLvl;
            
            const upgradeCost = (this.planet.vehicle_level + 1) * 500;
            const sensorCost = sensorLvl * 1000;

            document.getElementById('vehicle-upgrade-cost').innerText = upgradeCost;
            document.getElementById('vehicle-sensor-cost').innerText = sensorCost;

            document.getElementById('upgrade-vehicle-btn').disabled = this.displayIron < upgradeCost;
            document.getElementById('upgrade-sensors-btn').disabled = this.displayIron < sensorCost;

            document.getElementById('vehicle-idle').classList.toggle('hidden', this.planet.vehicle_status !== 'idle');
            document.getElementById('vehicle-active').classList.toggle('hidden', this.planet.vehicle_status !== 'exploring' && this.planet.vehicle_status !== 'returning');
            document.getElementById('vehicle-destroyed').classList.toggle('hidden', this.planet.vehicle_status !== 'destroyed');

            if (this.planet.vehicle_status === 'exploring' || this.planet.vehicle_status === 'returning') {
                document.getElementById('vehicle-hp-val').innerText = Math.floor(this.vehicleHP);
                document.getElementById('vehicle-hp-bar').style.width = `${this.vehicleHP}%`;
                document.getElementById('vehicle-crystals').innerText = Math.floor(this.vehicleCrystals);
                document.getElementById('vehicle-status-text').innerText = this.planet.vehicle_status === 'exploring' ? '🛰️ Probíhá průzkum...' : '🚀 Vozidlo se vrací...';
                document.getElementById('vehicle-hp-bar').style.background = this.vehicleHP < 30 ? '#ff4a4a' : '#28a745';
                document.getElementById('recall-btn').classList.toggle('hidden', this.planet.vehicle_status === 'returning');
            }
        }

        // Vehicle II UI (Copper)
        if (this.planet.research_copper) {
            document.getElementById('hangar2-section').classList.remove('hidden');
            if (this.planet.vehicle2_level === 0 && this.planet.vehicle2_status !== 'destroyed') {
                document.getElementById('no-vehicle2-view').classList.remove('hidden');
                document.getElementById('vehicle2-view').classList.add('hidden');
            } else {
                document.getElementById('no-vehicle2-view').classList.add('hidden');
                document.getElementById('vehicle2-view').classList.remove('hidden');
                document.getElementById('vehicle2-lvl').innerText = this.planet.vehicle2_level;

                const sensor2Lvl = this.planet.vehicle2_sensor_lvl || 1;
                document.getElementById('vehicle2-sensor-lvl').innerText = sensor2Lvl;
                
                const upgrade2Cost = (this.planet.vehicle2_level + 1) * 100;
                const sensor2Cost = sensor2Lvl * 150;

                document.getElementById('vehicle2-upgrade-cost').innerText = upgrade2Cost;
                document.getElementById('vehicle2-sensor-cost').innerText = sensor2Cost;

                document.getElementById('upgrade-vehicle2-btn').disabled = this.displayCopper < upgrade2Cost;
                document.getElementById('upgrade-sensors2-btn').disabled = this.displayCopper < sensor2Cost;

                document.getElementById('vehicle2-idle').classList.toggle('hidden', this.planet.vehicle2_status !== 'idle');
                document.getElementById('vehicle2-active').classList.toggle('hidden', this.planet.vehicle2_status !== 'exploring' && this.planet.vehicle2_status !== 'returning');
                document.getElementById('vehicle2-destroyed').classList.toggle('hidden', this.planet.vehicle2_status !== 'destroyed');

                if (this.planet.vehicle2_status === 'exploring' || this.planet.vehicle2_status === 'returning') {
                    document.getElementById('vehicle2-hp-val').innerText = Math.floor(this.vehicle2HP);
                    document.getElementById('vehicle2-hp-bar').style.width = `${this.vehicle2HP}%`;
                    document.getElementById('vehicle2-crystals').innerText = Math.floor(this.vehicle2Crystals);
                    document.getElementById('vehicle2-status-text').innerText = this.planet.vehicle2_status === 'exploring' ? '🛰️ Probíhá průzkum...' : '🚀 Vozidlo se vrací...';
                    document.getElementById('vehicle2-hp-bar').style.background = this.vehicle2HP < 30 ? '#ff4a4a' : '#28a745';
                    document.getElementById('recall2-btn').classList.toggle('hidden', this.planet.vehicle2_status === 'returning');
                }
            }
        } else {
            document.getElementById('hangar2-section').classList.add('hidden');
        }

        // Drone UI
        if (this.planet.has_drone) {
            document.getElementById('no-drone-view').classList.add('hidden');
            document.getElementById('drone-view').classList.remove('hidden');
            const limit = this.planet.drone_storage_limit || 100;

            document.getElementById('drone-storage-val').innerText = Math.floor(this.displayDrone);
            const limitEl = document.getElementById('drone-storage-limit');
            if (limitEl) limitEl.innerText = limit;

            const progress = (this.displayDrone / limit) * 100;
            document.getElementById('drone-progress-bar').style.width = `${Math.min(100, progress)}%`;
            document.getElementById('collect-drone-btn').disabled = this.displayDrone < 1;
        } else {            document.getElementById('no-drone-view').classList.remove('hidden');
            document.getElementById('drone-view').classList.add('hidden');
        }
    },

    async researchDroneUpgrade() {
        const res = await fetch('api.php?action=research_drone_upgrade', { method: 'POST' });
        const data = await res.json();
        if (data.success) this.fetchPlanet();
        else alert(data.error);
    },

    async researchDroneUpgrade2() {
        const res = await fetch('api.php?action=research_drone_upgrade_2', { method: 'POST' });
        const data = await res.json();
        if (data.success) this.fetchPlanet();
        else alert(data.error);
    },

    async upgrade(type) {
        const formData = new FormData();
        formData.append('type', type);
        
        const res = await fetch('api.php?action=upgrade', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        
        if (data.success) {
            this.planet = data.planet;
            // Sync ALL resources to ensure UI is immediately correct
            this.displayIron = this.planet.iron_amount;
            this.displayEnergy = this.planet.energy_amount;
            this.displayCrystal = this.planet.crystal_amount;
            this.displayCopper = this.planet.res_copper || 0;
            this.displayDrone = this.planet.drone_storage || 0;
            
            this.updateUI(); // Immediate update
            this.startLoop();
            this.fetchLeaderboard();
        } else {
            alert(data.error);
        }
    },

    async buyVehicle() {
        if (this.displayIron < 500) return alert("Nedostatek železa!");
        const res = await fetch('api.php?action=buy_vehicle', { method: 'POST' });
        const data = await res.json();
        if (data.success) this.fetchPlanet();
    },

    async buyVehicle2() {
        if (this.displayCopper < 500) return alert("Nedostatek mědi (500 Cu)!");
        const res = await fetch('api.php?action=buy_vehicle2', { method: 'POST' });
        const data = await res.json();
        if (data.success) this.fetchPlanet();
        else alert(data.error);
    },

    async startExpedition() {
        const res = await fetch('api.php?action=start_expedition', { method: 'POST' });
        const data = await res.json();
        if (data.success) this.fetchPlanet();
    },

    async startExpedition2() {
        const res = await fetch('api.php?action=start_expedition2', { method: 'POST' });
        const data = await res.json();
        if (data.success) this.fetchPlanet();
        else alert(data.error);
    },

    async recallVehicle() {
        const res = await fetch('api.php?action=recall_vehicle', { method: 'POST' });
        const data = await res.json();
        if (data.success) this.fetchPlanet();
    },

    async recallVehicle2() {
        const res = await fetch('api.php?action=recall_vehicle2', { method: 'POST' });
        const data = await res.json();
        if (data.success) this.fetchPlanet();
        else alert(data.error);
    },

    async finishExpedition() {
        if (this.interval) clearInterval(this.interval);
        const res = await fetch('api.php?action=finish_expedition', { method: 'POST' });
        const data = await res.json();
        if (data.success) this.fetchPlanet();
    },

    async finishExpedition2() {
        if (this.interval) clearInterval(this.interval);
        const res = await fetch('api.php?action=finish_expedition2', { method: 'POST' });
        const data = await res.json();
        if (data.success) this.fetchPlanet();
    },

    async destroyVehicle() {
        if (this.interval) clearInterval(this.interval);
        const res = await fetch('api.php?action=destroy_vehicle', { method: 'POST' });
        const data = await res.json();
        if (data.success) this.fetchPlanet();
    },

    async destroyVehicle2() {
        if (this.interval) clearInterval(this.interval);
        const res = await fetch('api.php?action=destroy_vehicle2', { method: 'POST' });
        const data = await res.json();
        if (data.success) this.fetchPlanet();
    }
,

    async upgradeVehicle() {
        const res = await fetch('api.php?action=upgrade_vehicle', { method: 'POST' });
        const data = await res.json();
        if (data.success) this.fetchPlanet();
        else alert(data.error);
    },

    async upgradeVehicleSensors() {
        const res = await fetch('api.php?action=upgrade_vehicle_sensors', { method: 'POST' });
        const data = await res.json();
        if (data.success) this.fetchPlanet();
        else alert(data.error);
    },

    async upgradeVehicle2Armor() {
        const res = await fetch('api.php?action=upgrade_vehicle2_armor', { method: 'POST' });
        const data = await res.json();
        if (data.success) this.fetchPlanet();
        else alert(data.error);
    },

    async upgradeVehicle2Sensors() {
        const res = await fetch('api.php?action=upgrade_vehicle2_sensors', { method: 'POST' });
        const data = await res.json();
        if (data.success) this.fetchPlanet();
        else alert(data.error);
    },

    async researchCopper() {
        const res = await fetch('api.php?action=research_copper', { method: 'POST' });
        const data = await res.json();
        if (data.success) this.fetchPlanet();
        else alert(data.error);
    },

    async upgradeCopperMine() {
        const res = await fetch('api.php?action=upgrade_copper_mine', { method: 'POST' });
        const data = await res.json();
        if (data.success) this.fetchPlanet();
        else alert(data.error);
    },

    async upgradeCopperWarehouse() {
        const res = await fetch('api.php?action=upgrade_copper_warehouse', { method: 'POST' });
        const data = await res.json();
        if (data.success) this.fetchPlanet();
        else alert(data.error);
    },

    async buyDrone() {
        if (this.displayCrystal < 250) return alert("Nedostatek krystalů!");
        const res = await fetch('api.php?action=buy_drone', { method: 'POST' });
        const data = await res.json();
        if (data.success) this.fetchPlanet();
        else alert(data.error);
    },

    async collectDrone() {
        const res = await fetch('api.php?action=collect_drone', { method: 'POST' });
        const data = await res.json();
        if (data.success) this.fetchPlanet();
        else alert(data.error);
    },

    // --- Alien Content Methods ---
    colorNames: {
        yellow: 'Žlutý', red: 'Červený', blue: 'Modrý',
        green: 'Zelený', orange: 'Oranžový', purple: 'Fialový'
    },

    updateResearchUI() {
        const researched = this.planet.researched_colors || [];
        const info = document.getElementById('research-info');
        const options = document.getElementById('color-options');
        
        const count = researched.length;
        if (count >= 2) {
            info.innerHTML = `<p style="color: #28a745;"><strong>Všechny výzkumné sloty (2/2) jsou obsazeny.</strong></p>`;
        } else {
            const cost = count === 0 ? 100 : 2000;
            info.innerHTML = `<p>K dispozici máš slot č. <strong>${count + 1}</strong> za <strong>${cost} krystalů</strong>.</p>`;
        }

        options.innerHTML = '';
        for (const color in this.colorNames) {
            const isResearched = researched.includes(color);
            const btn = document.createElement('button');
            btn.className = `research-btn color-${color}`;
            btn.innerText = this.colorNames[color];
            
            if (isResearched) {
                btn.disabled = true;
                btn.innerText += ' (Vyzkoumáno)';
            } else if (count >= 2) {
                btn.disabled = true;
            } else {
                const cost = count === 0 ? 100 : 2000;
                btn.onclick = () => this.researchColor(color);
                if (this.displayCrystal < cost) btn.style.opacity = '0.5';
            }
            options.appendChild(btn);
        }
    },

    async researchColor(color) {
        const formData = new FormData();
        formData.append('color', color);
        const res = await fetch('api.php?action=research_color', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) this.fetchPlanet();
        else alert(data.error);
    },

    updateAlienUI() {
        const researched = this.planet.researched_colors || [];
        const resContainer = document.getElementById('alien-resources');
        const bldContainer = document.getElementById('alien-buildings');
        
        if (researched.length === 0) {
            resContainer.classList.add('hidden');
            bldContainer.classList.add('hidden');
            return;
        }

        resContainer.classList.remove('hidden');
        bldContainer.classList.remove('hidden');
        
        resContainer.innerHTML = '';
        bldContainer.innerHTML = '';

        researched.forEach(color => {
            const data = this.planet.alien_resources[color];
            const colorCode = this.getColorCode(color);
            
            // Add resource card
            const resCard = document.createElement('div');
            resCard.className = `res-card ${color}`;
            resCard.innerHTML = `
                <div class="res-icon" style="color: ${colorCode}"><svg width="24" height="24"><use href="#icon-alien-res"/></svg></div>
                <div class="res-data">
                    <span class="label">${this.colorNames[color]} materiál</span>
                    <span class="value" id="display-res-${color}">${Math.floor(this.displayAlien[color])}</span>
                    <span class="prod">+${(data.prod).toFixed(2)}/s</span>
                </div>
            `;
            resContainer.appendChild(resCard);

            // Add building card
            const bldCard = document.createElement('div');
            bldCard.className = 'building-card';
            const ironCost = (data.lvl + 1) * 500;
            const crystalCost = (data.lvl + 1) * 50;
            
            bldCard.innerHTML = `
                <div style="color: ${colorCode}"><svg width="40" height="40"><use href="#icon-alien-res"/></svg></div>
                <h3>${this.colorNames[color]} důl</h3>
                <p class="lvl">Úroveň ${data.lvl}</p>
                <p class="desc">Produkuje vzácný ${this.colorNames[color].toLowerCase()} materiál.</p>
                <button onclick="game.upgradeAlienMine('${color}')" id="upgrade-alien-mine-${color}">
                    Vylepšit <span>(${ironCost} Fe, ${crystalCost} Kryst.)</span>
                </button>
            `;
            bldContainer.appendChild(bldCard);
        });
    },

    async upgradeAlienMine(color) {
        const formData = new FormData();
        formData.append('color', color);
        const res = await fetch('api.php?action=upgrade_alien_mine', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) this.fetchPlanet();
        else alert(data.error);
    },

    getColorCode(color) {
        const codes = { yellow: '#ffeb3b', red: '#f44336', blue: '#2196f3', green: '#4caf50', orange: '#ff9800', purple: '#9c27b0' };
        return codes[color] || '#fff';
    },

    async fetchGlobalStats() {
        const res = await fetch('api.php?action=global_stats');
        const data = await res.json();
        const container = document.getElementById('global-progress-container');
        if (!container) return;

        container.innerHTML = '';
        const target = 10000000;
        
        for (const color in this.colorNames) {
            const amount = parseFloat(data[color] || 0);
            const percent = Math.min(100, (amount / target) * 100);
            const colorCode = this.getColorCode(color);
            
            const item = document.createElement('div');
            item.innerHTML = `
                <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 5px;">
                    <span><svg width="14" height="14" style="color: ${colorCode}; vertical-align: middle; margin-right: 5px;"><use href="#icon-alien-res"/></svg> <strong>${this.colorNames[color]} materiál</strong></span>
                    <span>${Math.floor(amount).toLocaleString()} / ${target.toLocaleString()}</span>
                </div>
                <div class="progress-bg" style="height: 12px;">
                    <div class="progress-bar" style="width: ${percent}%; background: ${colorCode}; box-shadow: 0 0 10px ${colorCode}66;"></div>
                </div>
            `;
            container.appendChild(item);
        }
    }
};

// Auth form listener
document.getElementById('auth-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const playerName = document.getElementById('playerName').value;
    
    const formData = new FormData();
    formData.append('email', email);
    formData.append('password', password);
    if (auth.isSignUp) formData.append('playerName', playerName);
    
    const action = auth.isSignUp ? 'register' : 'login';
    const res = await fetch(`auth.php?action=${action}`, {
        method: 'POST',
        body: formData
    });
    const data = await res.json();
    
    if (data.success) {
        auth.init();
    } else {
        alert(data.error);
    }
});

// Initialize
auth.init();
setInterval(() => {
    game.fetchLeaderboard();
    game.fetchGlobalStats();
}, 5000); // Update every 5s
