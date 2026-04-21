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
        
        // Sync graphics toggle - default to true if not set
        const stored = localStorage.getItem('game_use_images');
        const useImages = stored === null ? true : stored === 'true';
        document.getElementById('graphics-toggle').checked = useImages;
        game.useImages = useImages;

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
    displayTubes: 0, 
    displayAlien: {}, 
    displayDrone: 0, 
    vehicleHP: 100,
    vehicleCrystals: 0,
    vehicle2HP: 100,
    vehicle2Crystals: 0,
    interval: null,
    refreshPromise: null,
    recallPending: false,
    recallVehicle2Pending: false,
    useImages: true,

    toggleGraphics() {
        this.useImages = document.getElementById('graphics-toggle').checked;
        localStorage.setItem('game_use_images', this.useImages);
        this.refreshIcons();
        this.updateUI();
        this.updateAlienUI();
        this.updateResearchUI();
        this.updateRocketWorkshopUI();
    },

    refreshIcons() {
        if (!this.planet) return;
        const setIcon = (id, symbol, img, size = 24) => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = this.renderIcon(symbol, img, size);
        };

        setIcon('icon-iron-container', 'icon-iron', 'iron.png');
        setIcon('icon-energy-container', 'icon-energy', 'electricity.png');
        setIcon('icon-crystal-container', 'icon-crystal', 'crystal.png');
        setIcon('icon-copper-container', 'icon-copper', 'copper.png');
        setIcon('icon-tubes-container', 'icon-tube', 'test-tube.png');

        setIcon('icon-mine-container', 'icon-mine', 'mines-iron.png', 40);
        setIcon('icon-solar-container', 'icon-solar', 'solar-power-plant.png', 40);
        setIcon('icon-warehouse-container', 'icon-warehouse', 'storage-iron.png', 40);
        setIcon('icon-mine-copper-container', 'icon-mine', 'mines-copper.png', 40);
        setIcon('icon-warehouse-copper-container', 'icon-warehouse', 'storage-copper.png', 40);
        setIcon('icon-lab-container', 'icon-lab', 'microscope.png', 40);
        setIcon('icon-lab-storage-container', 'icon-warehouse', 'storage-test-tubes.png', 40);
        
        setIcon('icon-hangar-container', 'icon-vehicle', 'hangar.png', 28);
        setIcon('icon-rocket-workshop-container', 'icon-lab', 'space-workshop.png', 28);
    },

    renderIcon(symbolId, imagePath = null, size = 24) {
        if (this.useImages && imagePath) {
            return `<img src="resources/${imagePath}" width="${size}" height="${size}" alt="icon">`;
        }
        return `<svg width="${size}" height="${size}"><use href="#${symbolId}"/></svg>`;
    },

    showDashboard(user) {
        document.getElementById('auth-section').classList.add('hidden');
        document.getElementById('user-info').classList.remove('hidden');
        document.getElementById('player-name').innerText = user.player_name;
        document.getElementById('dashboard-section').classList.remove('hidden');
        this.fetchPlanet();
        this.fetchLeaderboard();
        this.fetchGlobalStats();
    },

    async refreshDashboard() {
        if (this.refreshPromise) {
            return this.refreshPromise;
        }

        this.refreshPromise = (async () => {
            await this.fetchPlanet();
            await Promise.all([
                this.fetchLeaderboard(),
                this.fetchGlobalStats()
            ]);
        })();

        try {
            await this.refreshPromise;
        } finally {
            this.refreshPromise = null;
        }
    },

    async submitAction(url, body = null) {
        const res = await fetch(url, {
            method: 'POST',
            body
        });
        const data = await res.json();

        if (!res.ok || data.error) {
            alert(data.error || 'Akci se nepodarilo dokoncit.');
            return null;
        }

        await this.refreshDashboard();
        return data;
    },

    async fetchPlanet() {
        const res = await fetch('api.php?action=get_planet');
        this.planet = await res.json();
        if (this.planet) {
            this.displayIron = this.planet.iron_amount;
            this.displayEnergy = this.planet.energy_amount;
            this.displayCrystal = this.planet.crystal_amount;
            this.displayCopper = this.planet.res_copper || 0;
            this.displayTubes = this.planet.res_tubes || 0;
            this.displayDrone = this.planet.drone_storage || 0;
            this.vehicleHP = this.planet.vehicle_hp || 100;
            this.vehicle2HP = this.planet.vehicle2_hp || 100;
            this.recallPending = false;
            this.recallVehicle2Pending = false;
            
            // Sync alien resources
            this.displayAlien = {};
            if (this.planet.alien_resources) {
                for (const color in this.planet.alien_resources) {
                    this.displayAlien[color] = this.planet.alien_resources[color].amount;
                }
            }
            
            this.startLoop();
            this.refreshIcons();
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
                const icon = this.renderIcon('icon-alien-res', `am-${color}.png`, 16);
                iconsHtml += `<span style="color: ${colorCode}; margin-left: 5px; display: inline-flex;">${icon}</span>`;
            });

            tr.innerHTML = `
                <td>${i + 1}.</td>
                <td>${p.player_name}${iconsHtml}</td>
                <td>Lvl ${p.mine_level}</td>
                <td>${this.formatUtcDateTime(p.last_login)}</td>
            `;
            body.appendChild(tr);
        });
    },

    formatUtcDateTime(value) {
        if (!value) return '---';

        const parsed = new Date(value.replace(' ', 'T') + 'Z');
        if (Number.isNaN(parsed.getTime())) return value;

        return new Intl.DateTimeFormat(undefined, {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        }).format(parsed);
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
            if (this.planet.research_advanced_lab) {
                extraEnergyNeeded += (this.planet.lab_level * 1.5);
            }

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

            // Advanced Lab Production (Test Tubes)
            if (this.planet.research_advanced_lab && this.displayTubes < this.planet.tube_storage_limit) {
                const tubeProd = this.planet.tube_production;
                this.displayTubes += (tubeProd / 10) * prodFactor;
            }

            // Drone Production (1 crystal / 300 seconds base)
            if (this.planet.has_drone) {
                let multiplier = 1;
                if (this.planet.research_drone_upgrade_3) multiplier = 100; // 25 * 4
                else if (this.planet.research_drone_upgrade_2) multiplier = 25;
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
                let damageSeconds = secondsOut;
                
                const baseDamageRate = 0.1; 
                const acceleration = 0.003;
                const armorFactor = Math.pow(this.planet.vehicle_level || 1, 1.2);
                let displaySeconds = 0;

                if (this.planet.vehicle_status === 'exploring') {
                    const sensorLvl = this.planet.vehicle_sensor_lvl || 1;
                    const timeBonus = 1 + (secondsOut * 0.0005);
                    const crystalRate = 0.1 * (1 + (sensorLvl - 1) * 0.05) * timeBonus;
                    this.vehicleCrystals = secondsOut * crystalRate;
                    displaySeconds = secondsOut;

                    // Auto-Recall
                    if (this.planet.research_auto_recall && this.vehicleHP <= 90 && !this.recallPending && !this.refreshPromise) {
                        this.recallVehicle();
                    }
                } else {
                    const recallTime = new Date(this.planet.vehicle_recall_time + " UTC");
                    const secondsReturning = (now - recallTime) / 1000;
                    const secondsToReturn = (recallTime - startTime) / 1000;
                    const missionCompleteAfter = secondsToReturn * 2;
                    damageSeconds = Math.min(secondsOut, missionCompleteAfter);
                    displaySeconds = Math.max(0, secondsToReturn - secondsReturning);
                    if (secondsReturning >= secondsToReturn) {
                        this.refreshDashboard();
                        return;
                    }
                }

                const totalDamage = (damageSeconds * (baseDamageRate + (damageSeconds * acceleration))) / armorFactor;
                this.vehicleHP = Math.max(0, 100 - totalDamage);

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
                let damageSeconds = secondsOut;
                
                const baseDamageRate = 0.1; 
                const acceleration = 0.003;
                // Armor is 2x more effective (level counts double for the bonus)
                const effectiveLevel = 1 + ((this.planet.vehicle2_level || 1) - 1) * 2;
                const armorFactor = Math.pow(effectiveLevel, 1.2);
                let displaySeconds = 0;

                if (this.planet.vehicle2_status === 'exploring') {
                    const sensorLvl = this.planet.vehicle2_sensor_lvl || 1;
                    const timeBonus = 1 + (secondsOut * 0.0005);
                    // Sensors are 2x more effective (10% bonus instead of 5%)
                    const crystalRate = 0.2 * (1 + (sensorLvl - 1) * 0.10) * timeBonus;
                    this.vehicle2Crystals = secondsOut * crystalRate;
                    displaySeconds = secondsOut;

                    // Auto-Recall
                    if (this.planet.research_auto_recall && this.vehicle2HP <= 90 && !this.recallVehicle2Pending && !this.refreshPromise) {
                        this.recallVehicle2();
                    }
                } else {
                    const recallTime = new Date(this.planet.vehicle2_recall_time + " UTC");
                    const secondsReturning = (now - recallTime) / 1000;
                    const secondsToReturn = (recallTime - startTime) / 1000;
                    const missionCompleteAfter = secondsToReturn * 2;
                    damageSeconds = Math.min(secondsOut, missionCompleteAfter);
                    displaySeconds = Math.max(0, secondsToReturn - secondsReturning);
                    if (secondsReturning >= secondsToReturn) {
                        this.refreshDashboard();
                        return;
                    }
                }

                const totalDamage = (damageSeconds * (baseDamageRate + (damageSeconds * acceleration))) / armorFactor;
                this.vehicle2HP = Math.max(0, 100 - totalDamage);

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
        const researched = this.planet.researched_colors || [];
        
        const ironLimit = this.planet.iron_storage_limit;
        document.getElementById('display-iron').innerText = Math.floor(this.displayIron);
        document.getElementById('display-limit').innerText = ironLimit;
        document.getElementById('display-energy').innerText = Math.floor(this.displayEnergy);
        document.getElementById('display-crystal').innerText = Math.floor(this.displayCrystal);
        
        document.getElementById('iron-prod').innerText = this.planet.iron_production.toFixed(2);
        document.getElementById('energy-prod').innerText = this.planet.energy_production.toFixed(2);
        
        const progress = (this.displayIron / ironLimit) * 100;
        document.getElementById('iron-progress').style.width = `${Math.min(100, progress)}%`;
        
        // Test Tubes Card
        const tubesCard = document.getElementById('res-tubes-card');
        if (this.planet.research_advanced_lab) {
            tubesCard.classList.remove('hidden');
            const tubeLimit = this.planet.tube_storage_limit;
            document.getElementById('display-tubes').innerText = Math.floor(this.displayTubes);
            document.getElementById('display-tubes-limit').innerText = tubeLimit;
            document.getElementById('tube-prod-val').innerText = (this.planet.tube_production || 0).toFixed(2);
            
            const tubeProgress = (this.displayTubes / tubeLimit) * 100;
            document.getElementById('tubes-progress').style.width = `${Math.min(100, tubeProgress)}%`;
        } else {
            tubesCard.classList.add('hidden');
        }

        const mineCost = 100 * this.planet.mine_level;
        const solarCost = 100 * this.planet.solar_plant_level;
        const warehouseCost = 100 * this.planet.warehouse_level;
        
        document.getElementById('mine-lvl').innerText = this.planet.mine_level;
        document.getElementById('solar-lvl').innerText = this.planet.solar_plant_level;
        document.getElementById('warehouse-lvl').innerText = this.planet.warehouse_level;

        document.getElementById('mine-cost').innerText = `(${mineCost} Fe)`;
        document.getElementById('solar-cost').innerText = `(${solarCost} Fe)`;
        
        const upgradeWarehouseBtn = document.getElementById('upgrade-warehouse');
        if (this.planet.warehouse_level >= 200) {
            upgradeWarehouseBtn.classList.add('hidden');
        } else {
            upgradeWarehouseBtn.classList.remove('hidden');
            document.getElementById('warehouse-cost').innerText = `(${warehouseCost} Fe)`;
            upgradeWarehouseBtn.disabled = this.displayIron < warehouseCost;
        }
        
        document.getElementById('upgrade-mine').disabled = this.displayIron < mineCost;
        document.getElementById('upgrade-solar').disabled = this.displayIron < solarCost;

        // Copper-based Warehouse Efficiency
        const warehouseCopperBtn = document.getElementById('upgrade-warehouse-copper-eff');
        if (this.planet.research_warehouse_copper) {
            warehouseCopperBtn.classList.remove('hidden');
            const cost = (this.planet.warehouse_level + 1) * 10;
            document.getElementById('warehouse-copper-cost-eff').innerText = `${cost} Cu`;
            warehouseCopperBtn.disabled = this.displayCopper < cost;
        } else {
            warehouseCopperBtn.classList.add('hidden');
        }

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

        // Advanced Lab Research UI
        const labResContainer = document.getElementById('lab-research-container');
        if (this.planet && !this.planet.research_advanced_lab) {
            let totalColored = 0;
            for (const color in this.displayAlien) {
                totalColored += this.displayAlien[color];
            }
            const researchedCount = (this.planet.researched_colors || []).length;
            
            if (researchedCount >= 2) {
                labResContainer.classList.remove('hidden');
                const btn = document.getElementById('research-lab-btn');
                const cost = 5000;
                btn.disabled = this.displayCopper < cost || totalColored < 10000;
                
                const labDesc = document.getElementById('lab-research-desc');
                if (totalColored < 10000) {
                    labDesc.innerText = `Vyžaduje 10 000 barevného materiálu (máš ${Math.floor(totalColored)}).`;
                    labDesc.style.color = '#ff4a4a';
                } else {
                    labDesc.innerText = 'Odemkne výrobu zkumavek pro pokročilý výzkum. Vyžaduje 2 barvy a dosažení součtu všech barevných materiálů v hodnotě 10 000.';
                    labDesc.style.color = '#888';
                }
            } else {
                labResContainer.classList.add('hidden');
            }
        } else {
            labResContainer.classList.add('hidden');
        }

        // Advanced Lab Buildings UI
        const labBuildings = document.getElementById('lab-buildings');
        if (this.planet && this.planet.research_advanced_lab) {
            labBuildings.classList.remove('hidden');
            
            const labLvl = this.planet.lab_level;
            const labStorageLvl = this.planet.lab_storage_level;
            
            document.getElementById('lab-lvl').innerText = labLvl;
            document.getElementById('lab-storage-lvl').innerText = labStorageLvl;
            
            const labIronCost = (labLvl + 1) * 5000;
            const labCrystalCost = (labLvl + 1) * 100;
            const storageIronCost = (labStorageLvl + 1) * 8000;
            const storageCrystalCost = (labStorageLvl + 1) * 150;
            
            document.getElementById('lab-upgrade-cost').innerText = `(${labIronCost} Fe, ${labCrystalCost} Kryst.)`;
            document.getElementById('lab-storage-upgrade-cost').innerText = `(${storageIronCost} Fe, ${storageCrystalCost} Kryst.)`;
            
            document.getElementById('upgrade-lab-btn').disabled = this.displayIron < labIronCost || this.displayCrystal < labCrystalCost;
            document.getElementById('upgrade-lab-storage-btn').disabled = this.displayIron < storageIronCost || this.displayCrystal < storageCrystalCost;
        } else {
            labBuildings.classList.add('hidden');
        }

        // --- Post-Lab Researches ---
        if (this.planet && this.planet.research_advanced_lab) {
            // 1. Warehouse Copper Research
            const resWhCopper = document.getElementById('research-wh-copper-container');
            if (!this.planet.research_warehouse_copper && this.planet.warehouse_level >= 200) {
                resWhCopper.classList.remove('hidden');
                document.getElementById('research-wh-copper-btn').disabled = this.displayTubes < 2500;
            } else {
                resWhCopper.classList.add('hidden');
            }

            // 2. Drone Upgrade III
            const resDrone3 = document.getElementById('research-drone-3-container');
            if (!this.planet.research_drone_upgrade_3 && this.planet.research_drone_upgrade_2) {
                resDrone3.classList.remove('hidden');
                document.getElementById('research-drone-3-btn').disabled = this.displayTubes < 5000;
            } else {
                resDrone3.classList.add('hidden');
            }

            // 3. Auto-Recall
            const resAutoRecall = document.getElementById('research-auto-recall-container');
            if (!this.planet.research_auto_recall) {
                resAutoRecall.classList.remove('hidden');
                document.getElementById('research-auto-recall-btn').disabled = this.displayTubes < 7500;
            } else {
                resAutoRecall.classList.add('hidden');
            }

            // 4. Rocket Workshop
            const resRocketWorkshop = document.getElementById('research-rocket-workshop-container');
            if (!this.planet.research_rocket_workshop) {
                resRocketWorkshop.classList.remove('hidden');
                document.getElementById('research-rocket-workshop-btn').disabled = this.displayTubes < 15000;
            } else {
                resRocketWorkshop.classList.add('hidden');
            }

            // 5. 3rd Alien Slot
            const resAlienSlot3 = document.getElementById('research-alien-slot-3-container');
            if (this.planet.research_rocket_workshop && !this.planet.research_alien_slot_3) {
                resAlienSlot3.classList.remove('hidden');
                
                // Check precondition: 2 mines at Lvl 50
                let minesAt50 = 0;
                researched.forEach(color => {
                   if ((this.planet.alien_resources[color]?.lvl || 0) >= 50) minesAt50++;
                });

                const canAfford = this.displayTubes >= 25000 && this.displayIron >= 2000000 && this.displayCopper >= 25000;
                const btn = document.getElementById('research-alien-slot-3-btn');
                btn.disabled = !canAfford || minesAt50 < 2;
                
                if (minesAt50 < 2) {
                    btn.innerText = 'Vyzkoumat (Vyžaduje 2 doly Lvl 50)';
                } else {
                    btn.innerText = 'Vyzkoumat (25k zkum., 2M Fe, 25k Cu)';
                }
            } else {
                resAlienSlot3.classList.add('hidden');
            }
        } else {
            document.getElementById('research-wh-copper-container').classList.add('hidden');
            document.getElementById('research-drone-3-container').classList.add('hidden');
            document.getElementById('research-auto-recall-container').classList.add('hidden');
            document.getElementById('research-rocket-workshop-container').classList.add('hidden');
            document.getElementById('research-alien-slot-3-container').classList.add('hidden');
        }

        this.updateRocketWorkshopUI();

        // Drone Upgrade Research UI
        const droneResContainer = document.getElementById('drone-research-container');
        const droneRes2Container = document.getElementById('drone-research-2-container');
        
        if (this.planet && this.planet.research_copper) {
            // Upgrade 1
            if (droneResContainer) {
                if (!this.planet.research_drone_upgrade) {
                    droneResContainer.classList.remove('hidden');
                    const btn = document.getElementById('research-drone-btn');
                    if (btn) btn.disabled = this.displayCopper < 100;
                } else {
                    droneResContainer.classList.add('hidden');
                }
            }

            // Upgrade 2 (Requires 2 colors and Upgrade 1)
            if (droneRes2Container) {
                const researchedCount = (this.planet.researched_colors || []).length;
                if (this.planet.research_drone_upgrade && !this.planet.research_drone_upgrade_2 && researchedCount >= 2) {
                    droneRes2Container.classList.remove('hidden');
                    const btn2 = document.getElementById('research-drone-2-btn');
                    if (btn2) btn2.disabled = this.displayCopper < 500;
                } else {
                    droneRes2Container.classList.add('hidden');
                }
            }
        } else {
            if (droneResContainer) droneResContainer.classList.add('hidden');
            if (droneRes2Container) droneRes2Container.classList.add('hidden');
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
        if (this.planet && (this.planet.vehicle_level === 0 && this.planet.vehicle_status !== 'destroyed')) {
            document.getElementById('no-vehicle-view').classList.remove('hidden');
            document.getElementById('vehicle-view').classList.add('hidden');
            document.getElementById('no-vehicle-icon-container').innerHTML = this.renderIcon('icon-vehicle', 'vehicle-1.png', 30);
        } else if (this.planet) {
            document.getElementById('no-vehicle-view').classList.add('hidden');
            document.getElementById('vehicle-view').classList.remove('hidden');
            document.getElementById('vehicle-icon-container').innerHTML = this.renderIcon('icon-vehicle', 'vehicle-1.png', 50);
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
        if (this.planet && this.planet.research_copper) {
            document.getElementById('hangar2-section').classList.remove('hidden');
            if (this.planet.vehicle2_level === 0 && this.planet.vehicle2_status !== 'destroyed') {
                document.getElementById('no-vehicle2-view').classList.remove('hidden');
                document.getElementById('vehicle2-view').classList.add('hidden');
                document.getElementById('no-vehicle2-icon-container').innerHTML = this.renderIcon('icon-vehicle', 'vehicle-2.png', 30);
            } else {
                document.getElementById('no-vehicle2-view').classList.add('hidden');
                document.getElementById('vehicle2-view').classList.remove('hidden');
                document.getElementById('vehicle2-icon-container').innerHTML = this.renderIcon('icon-vehicle', 'vehicle-2.png', 50);
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
        } else if (this.planet) {
            document.getElementById('hangar2-section').classList.add('hidden');
        }

        // Drone UI
        if (this.planet && this.planet.has_drone) {
            document.getElementById('no-drone-view').classList.add('hidden');
            document.getElementById('drone-view').classList.remove('hidden');
            document.getElementById('drone-icon-container').innerHTML = this.renderIcon('icon-drone', 'dron-crystals.png', 24);
            const limit = this.planet.drone_storage_limit || 100;

            document.getElementById('drone-storage-val').innerText = Math.floor(this.displayDrone);
            const limitEl = document.getElementById('drone-storage-limit');
            if (limitEl) limitEl.innerText = limit;

            const progress = (this.displayDrone / limit) * 100;
            document.getElementById('drone-progress-bar').style.width = `${Math.min(100, progress)}%`;
            document.getElementById('collect-drone-btn').disabled = this.displayDrone < 1;
        } else if (this.planet) {
            document.getElementById('no-drone-view').classList.remove('hidden');
            document.getElementById('no-drone-icon-container').innerHTML = this.renderIcon('icon-drone', 'dron-crystals.png', 24);
            document.getElementById('drone-view').classList.add('hidden');
        }
    },

    async researchDroneUpgrade() {
        await this.submitAction('api.php?action=research_drone_upgrade');
    },

    async researchDroneUpgrade2() {
        await this.submitAction('api.php?action=research_drone_upgrade_2');
    },

    async researchDroneUpgrade3() {
        await this.submitAction('api.php?action=research_drone_upgrade_3');
    },

    async researchWarehouseCopper() {
        await this.submitAction('api.php?action=research_warehouse_copper');
    },

    async researchAutoRecall() {
        await this.submitAction('api.php?action=research_auto_recall');
    },

    async upgradeWarehouseCopperEff() {
        await this.submitAction('api.php?action=upgrade_warehouse_copper_eff');
    },

    async upgrade(type) {
        const formData = new FormData();
        formData.append('type', type);

        await this.submitAction('api.php?action=upgrade', formData);
    },

    async buyVehicle() {
        if (this.displayIron < 500) return alert("Nedostatek železa!");
        await this.submitAction('api.php?action=buy_vehicle');
    },

    async buyVehicle2() {
        if (this.displayCopper < 500) return alert("Nedostatek mědi (500 Cu)!");
        await this.submitAction('api.php?action=buy_vehicle2');
    },

    async startExpedition() {
        await this.submitAction('api.php?action=start_expedition');
    },

    async startExpedition2() {
        await this.submitAction('api.php?action=start_expedition2');
    },

    async recallVehicle() {
        if (this.recallPending) return;

        this.recallPending = true;
        try {
            const res = await fetch('api.php?action=recall_vehicle', { method: 'POST' });
            const data = await res.json();

            if (data.success || data.error === 'Vozidlo zrovna neni na pruzkumu!') {
                await this.refreshDashboard();
            } else {
                alert(data.error || 'Akci se nepodarilo dokoncit.');
            }
        } finally {
            this.recallPending = false;
        }
    },

    async recallVehicle2() {
        if (this.recallVehicle2Pending) return;

        this.recallVehicle2Pending = true;
        try {
            const res = await fetch('api.php?action=recall_vehicle2', { method: 'POST' });
            const data = await res.json();

            if (data.success || data.error === 'Druhe vozidlo zrovna neni na pruzkumu!') {
                await this.refreshDashboard();
            } else {
                alert(data.error || 'Akci se nepodarilo dokoncit.');
            }
        } finally {
            this.recallVehicle2Pending = false;
        }
    },

    async finishExpedition() {
        if (this.interval) clearInterval(this.interval);
        await this.submitAction('api.php?action=finish_expedition');
    },

    async finishExpedition2() {
        if (this.interval) clearInterval(this.interval);
        await this.submitAction('api.php?action=finish_expedition2');
    },

    async destroyVehicle() {
        if (this.interval) clearInterval(this.interval);
        await this.submitAction('api.php?action=destroy_vehicle');
    },

    async destroyVehicle2() {
        if (this.interval) clearInterval(this.interval);
        await this.submitAction('api.php?action=destroy_vehicle2');
    },

    async upgradeVehicle() {
        await this.submitAction('api.php?action=upgrade_vehicle');
    },

    async upgradeVehicleSensors() {
        await this.submitAction('api.php?action=upgrade_vehicle_sensors');
    },

    async upgradeVehicle2Armor() {
        await this.submitAction('api.php?action=upgrade_vehicle2_armor');
    },

    async upgradeVehicle2Sensors() {
        await this.submitAction('api.php?action=upgrade_vehicle2_sensors');
    },

    async researchCopper() {
        await this.submitAction('api.php?action=research_copper');
    },

    async upgradeCopperMine() {
        await this.submitAction('api.php?action=upgrade_copper_mine');
    },

    async upgradeCopperWarehouse() {
        await this.submitAction('api.php?action=upgrade_copper_warehouse');
    },

    async researchAdvancedLab() {
        await this.submitAction('api.php?action=research_advanced_lab');
    },

    async upgradeLab() {
        await this.submitAction('api.php?action=upgrade_lab');
    },

    async upgradeLabStorage() {
        await this.submitAction('api.php?action=upgrade_lab_storage');
    },

    async researchRocketWorkshop() {
        await this.submitAction('api.php?action=research_rocket_workshop');
    },

    async researchAlienSlot3() {
        await this.submitAction('api.php?action=research_alien_slot_3');
    },

    async upgradeRocketWorkshop() {
        if (this.displayIron < 1000000) return alert("Nedostatek železa (1 000 000 Fe)!");
        await this.submitAction('api.php?action=upgrade_rocket_workshop');
    },

    async startRocketWorkshopProduction(mode = 1) {
        const formData = new FormData();
        formData.append('mode', mode);
        await this.submitAction('api.php?action=start_rocket_workshop_production', formData);
    },

    async collectRocketWorkshopProduct(slot = 1) {
        const formData = new FormData();
        formData.append('slot', slot);
        const data = await this.submitAction('api.php?action=collect_rocket_workshop_product', formData);
        if (data && data.part_label) {
            const modal = document.getElementById('workshop-modal');
            const modalTitle = document.getElementById('modal-title');
            const modalText = document.getElementById('modal-text');
            const modalImgContainer = document.getElementById('modal-image-container');
            
            const isMultiple = Array.isArray(data.parts) && data.parts.length > 1;
            modalTitle.innerText = isMultiple ? 'Nové díly získány!' : 'Nový díl získán!';
            modalText.innerText = data.part_label;
            
            modalImgContainer.innerHTML = '';
            modalImgContainer.style.display = 'flex';
            modalImgContainer.style.justifyContent = 'center';
            modalImgContainer.style.gap = '15px';
            modalImgContainer.style.flexWrap = 'wrap';

            const partsToShow = Array.isArray(data.parts) ? data.parts : [data.part_key];
            
            partsToShow.forEach(pName => {
                // Find key by label
                const imageKey = Object.keys(this.rocketPartNames).find(k => this.rocketPartNames[k] === pName) || pName;
                const imgPath = this.rocketPartImages[imageKey] || 'workshop-items/unknown.png';
                const img = document.createElement('img');
                img.src = `resources/${imgPath}`;
                img.alt = pName;
                img.style.maxWidth = isMultiple ? '100px' : '150px';
                modalImgContainer.appendChild(img);
            });
            
            modal.classList.remove('hidden');
        }
    },

    async buyDrone() {
        if (this.displayCrystal < 250) return alert("Nedostatek krystalů!");
        await this.submitAction('api.php?action=buy_drone');
    },

    async collectDrone() {
        await this.submitAction('api.php?action=collect_drone');
    },

    // --- Alien Content Methods ---
    rocketPartNames: {
        rocket_tip: '\u0160pi\u010dka rakety',
        rocket_body: 'Trup rakety',
        fuel_tank: 'Palivov\u00e9 n\u00e1dr\u017ee',
        jet_engine: 'Tryskov\u00fd motor',
        satellite: 'Satelit',
        solar_panel: 'Sol\u00e1rn\u00ed panel',
        seat: 'Sedadlo',
        fuel_canister: 'Kanystr s palivem',
        electronics: 'Elektronick\u00e9 za\u0159\u00edzen\u00ed',
        tools: 'N\u00e1\u0159ad\u00ed'
    },

    rocketPartImages: {
        rocket_tip: 'workshop-items/rocket-cabin.png',
        rocket_body: 'workshop-items/rocket-body.png',
        fuel_tank: 'workshop-items/rocket-fuel-tanks.png',
        jet_engine: 'workshop-items/rocket-engine.png',
        satellite: 'workshop-items/rocket-satelite.png',
        solar_panel: 'workshop-items/rocket-solar-panel.png',
        seat: 'workshop-items/rocket-chair.png',
        fuel_canister: 'workshop-items/rocket-fuel.png',
        electronics: 'workshop-items/rocket-device.png',
        tools: 'workshop-items/tools.png'
    },

    colorNames: {
        yellow: 'Žlutý', red: 'Červený', blue: 'Modrý',
        green: 'Zelený', orange: 'Oranžový', purple: 'Fialový'
    },

    updateResearchUI() {
        const researched = this.planet.researched_colors || [];
        const info = document.getElementById('research-info');
        const options = document.getElementById('color-options');
        
        const count = researched.length;
        const maxSlots = this.planet.research_alien_slot_3 ? 3 : 2;

        if (count >= maxSlots) {
            info.innerHTML = `<p style="color: #28a745;"><strong>Všechny výzkumné sloty (${count}/${maxSlots}) jsou obsazeny.</strong></p>`;
        } else {
            const cost = count === 0 ? 100 : (count === 1 ? 2000 : 10000);
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
            } else if (count >= maxSlots) {
                btn.disabled = true;
            } else {
                const cost = count === 0 ? 100 : (count === 1 ? 2000 : 10000);
                btn.onclick = () => this.researchColor(color);
                if (this.displayCrystal < cost) btn.style.opacity = '0.5';
            }
            options.appendChild(btn);
        }
    },

    async researchColor(color) {
        const formData = new FormData();
        formData.append('color', color);
        await this.submitAction('api.php?action=research_color', formData);
    },

    formatDuration(totalSeconds) {
        const safeSeconds = Math.max(0, Math.floor(totalSeconds));
        const hours = Math.floor(safeSeconds / 3600);
        const minutes = Math.floor((safeSeconds % 3600) / 60);
        const seconds = safeSeconds % 60;
        return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    },

    updateRocketWorkshopUI() {
        const section = document.getElementById('rocket-workshop-section');
        if (!section || !this.planet) return;

        if (!this.planet.research_rocket_workshop) {
            section.classList.add('hidden');
            return;
        }

        section.classList.remove('hidden');

        const inventory = this.planet.rocket_parts || {};
        const total = Object.values(inventory).reduce((sum, value) => sum + Number(value || 0), 0);
        const allCompleted = Boolean(this.planet.rocket_parts_all_completed);
        const level = this.planet.rocket_workshop_level || 1;
        
        document.getElementById('rocket-workshop-lvl').innerText = level;
        document.getElementById('rocket-parts-total').innerText = total;

        const upgradeBtn = document.getElementById('rocket-workshop-upgrade-btn');
        const partsList = document.getElementById('rocket-parts-list');
        const finishedEl = document.getElementById('rocket-workshop-finished-note');

        if (partsList) {
            partsList.innerHTML = '';
            Object.entries(this.rocketPartNames).forEach(([key, label]) => {
                const count = Number(inventory[key] || 0);
                const item = document.createElement('div');
                item.style.padding = '8px 10px';
                item.style.border = '1px solid #30363d';
                item.style.borderRadius = '10px';
                item.style.background = count >= 10 ? '#123524' : '#161b22';
                item.style.display = 'flex';
                item.style.alignItems = 'center';
                item.style.gap = '10px';

                const icon = this.renderIcon('icon-upgrade', this.rocketPartImages[key], 32);
                item.innerHTML = `
                    <div style="flex-shrink: 0; display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; background: #0003; border-radius: 5px;">
                        ${icon}
                    </div>
                    <div>
                        <strong>${label}</strong><br>
                        <span style="color: ${count >= 10 ? '#7ee787' : '#9fb3c8'};">${count} / 10</span>
                    </div>
                `;
                partsList.appendChild(item);
            });
        }

        finishedEl.classList.toggle('hidden', !allCompleted);
        
        // Upgrade button logic: only show at level 1 and not producing in Slot 1 (to keep it simple)
        const isSlot1Idle = (this.planet.rocket_workshop_status || 'idle') === 'idle';
        upgradeBtn.classList.toggle('hidden', level >= 2 || !isSlot1Idle);
        upgradeBtn.disabled = this.displayIron < 1000000;

        // --- SLOT 1 (Běžná) ---
        this.updateWorkshopSlot(1, {
            status: this.planet.rocket_workshop_status,
            readyAt: this.planet.rocket_workshop_ready_at,
            duration: 28800,
            cost: 10000,
            unlocked: true,
            allCompleted: allCompleted
        });

        // --- SLOT 2 (Těžká) ---
        this.updateWorkshopSlot(2, {
            status: this.planet.rocket_workshop_2_status,
            readyAt: this.planet.rocket_workshop_2_ready_at,
            duration: 57600,
            cost: 20000,
            unlocked: level >= 2,
            allCompleted: allCompleted
        });
    },

    updateWorkshopSlot(id, data) {
        const container = document.getElementById(`ws-slot${id}-container`);
        if (container) container.classList.toggle('hidden', !data.unlocked);
        
        const statusEl = document.getElementById(`ws-slot${id}-status`);
        const timerWrap = document.getElementById(`ws-slot${id}-timer-wrap`);
        const timerEl = document.getElementById(`ws-slot${id}-timer`);
        const progressEl = document.getElementById(`ws-slot${id}-progress`);
        const startBtn = document.getElementById(`ws-slot${id}-start-btn`);
        const collectBtn = document.getElementById(`ws-slot${id}-collect-btn`);

        if (data.status === 'ready') {
            statusEl.innerText = 'Hotovo! Můžeš vyzvednout.';
            statusEl.style.color = '#28a745';
            timerWrap.classList.add('hidden');
            startBtn.classList.add('hidden');
            collectBtn.classList.remove('hidden');
        } else if (data.status === 'producing' && data.readyAt) {
            const readyAt = new Date(data.readyAt.replace(' ', 'T') + 'Z');
            const remaining = (readyAt.getTime() - Date.now()) / 1000;
            
            if (remaining <= 0) {
                statusEl.innerText = 'Dokončování...';
                if (!this.refreshPromise) this.refreshDashboard();
                startBtn.classList.add('hidden');
                collectBtn.classList.add('hidden');
            } else {
                statusEl.innerText = 'Probíhá výroba...';
                statusEl.style.color = '#888';
                timerWrap.classList.remove('hidden');
                timerEl.innerText = this.formatDuration(remaining);
                const percent = Math.min(100, ((data.duration - remaining) / data.duration) * 100);
                progressEl.style.width = `${percent}%`;
                startBtn.classList.add('hidden');
                collectBtn.classList.add('hidden');
            }
        } else {
            statusEl.innerText = 'Připraveno';
            statusEl.style.color = '#888';
            timerWrap.classList.add('hidden');
            startBtn.classList.remove('hidden');
            startBtn.disabled = this.displayTubes < data.cost || data.allCompleted;
            collectBtn.classList.add('hidden');
        }
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
            const resIcon = this.renderIcon('icon-alien-res', `am-${color}.png`, 24);
            resCard.innerHTML = `
                <div class="res-icon" style="color: ${colorCode}">${resIcon}</div>
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
            const bldIcon = this.renderIcon('icon-alien-res', `am-${color}.png`, 40);
            const ironCost = (data.lvl + 1) * 500;
            const crystalCost = (data.lvl + 1) * 50;
            
            bldCard.innerHTML = `
                <div style="color: ${colorCode}">${bldIcon}</div>
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
        await this.submitAction('api.php?action=upgrade_alien_mine', formData);
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
            const icon = this.renderIcon('icon-alien-res', `am-${color}.png`, 14);

            const item = document.createElement('div');
            item.innerHTML = `
                <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 5px;">
                    <span style="display: flex; align-items: center;"><span style="color: ${colorCode}; vertical-align: middle; margin-right: 5px; display: inline-flex;">${icon}</span> <strong>${this.colorNames[color]} materiál</strong></span>
                    <span>${Math.floor(amount).toLocaleString()} / ${target.toLocaleString()}</span>
                </div>                <div class="progress-bg" style="height: 12px;">
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
