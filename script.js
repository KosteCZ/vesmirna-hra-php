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
    vehicleHP: 100,
    vehicleCrystals: 0,
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
            this.startLoop();
            this.updateUI();
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
            tr.innerHTML = `
                <td>${i + 1}.</td>
                <td>${p.player_name}</td>
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

            // Resource Production
            this.displayEnergy += (energyProd / 10);
            const ironTick = (ironProd / 10);
            const energyNeeded = ironTick * 0.5;

            if (this.displayIron < ironLimit) {
                if (this.displayEnergy >= energyNeeded) {
                    this.displayIron += ironTick;
                    this.displayEnergy -= energyNeeded;
                } else {
                    this.displayIron += (ironTick * 0.1);
                }
            }

            // Vehicle Expedition Logic
            if (this.planet.vehicle_status === 'exploring' || this.planet.vehicle_status === 'returning') {
                const now = new Date();
                const startTime = new Date(this.planet.vehicle_start_time + " UTC");
                const secondsOut = (now - startTime) / 1000;
                
                // Damage calculation (balanced for ~120s survival at lvl 1)
                const baseDamageRate = 0.1; 
                const acceleration = 0.006;
                const totalDamage = (secondsOut * (baseDamageRate + (secondsOut * acceleration))) / (this.planet.vehicle_level || 1);
                
                this.vehicleHP = Math.max(0, 100 - totalDamage);
                
                let displaySeconds = 0;

                if (this.planet.vehicle_status === 'exploring') {
                    this.vehicleCrystals = secondsOut * 0.1;
                    displaySeconds = secondsOut;
                } else {
                    // Returning
                    const recallTime = new Date(this.planet.vehicle_recall_time + " UTC");
                    const secondsReturning = (now - recallTime) / 1000;
                    const secondsToReturn = (recallTime - startTime) / 1000;
                    
                    displaySeconds = Math.max(0, secondsToReturn - secondsReturning);

                    if (secondsReturning >= secondsToReturn) {
                        this.finishExpedition();
                    }
                }

                // Update Timer UI (M:SS)
                const mins = Math.floor(displaySeconds / 60);
                const secs = Math.floor(displaySeconds % 60);
                document.getElementById('vehicle-timer').innerText = `${mins}:${secs.toString().padStart(2, '0')}`;

                if (this.vehicleHP <= 0) {
                    this.destroyVehicle();
                }
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
        
        document.getElementById('iron-prod').innerText = this.planet.iron_production.toFixed(1);
        document.getElementById('energy-prod').innerText = this.planet.energy_production.toFixed(1);
        
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

        // Vehicle UI
        if (this.planet.vehicle_level === 0 && this.planet.vehicle_status !== 'destroyed') {
            document.getElementById('no-vehicle-view').classList.remove('hidden');
            document.getElementById('vehicle-view').classList.add('hidden');
        } else {
            document.getElementById('no-vehicle-view').classList.add('hidden');
            document.getElementById('vehicle-view').classList.remove('hidden');
            document.getElementById('vehicle-lvl').innerText = this.planet.vehicle_level;
            
            const reduction = Math.round((1 - (1 / this.planet.vehicle_level)) * 100);
            document.getElementById('vehicle-reduction').innerText = reduction;
            
            const upgradeCost = (this.planet.vehicle_level + 1) * 500;
            document.getElementById('vehicle-upgrade-cost').innerText = upgradeCost;
            document.getElementById('upgrade-vehicle-btn').disabled = this.displayIron < upgradeCost;

            document.getElementById('vehicle-idle').classList.toggle('hidden', this.planet.vehicle_status !== 'idle');
            document.getElementById('vehicle-active').classList.toggle('hidden', this.planet.vehicle_status !== 'exploring' && this.planet.vehicle_status !== 'returning');
            document.getElementById('vehicle-destroyed').classList.toggle('hidden', this.planet.vehicle_status !== 'destroyed');

            if (this.planet.vehicle_status === 'exploring' || this.planet.vehicle_status === 'returning') {
                document.getElementById('vehicle-hp-val').innerText = Math.floor(this.vehicleHP);
                document.getElementById('vehicle-hp-bar').style.width = `${this.vehicleHP}%`;
                document.getElementById('vehicle-crystals').innerText = Math.floor(this.vehicleCrystals);
                document.getElementById('vehicle-status-text').innerText = this.planet.vehicle_status === 'exploring' ? '🛰️ Probíhá průzkum hlubokého vesmíru...' : '🚀 Vozidlo se vrací na základnu...';
                document.getElementById('vehicle-hp-bar').style.background = this.vehicleHP < 30 ? '#ff4a4a' : '#28a745';
                document.getElementById('recall-btn').classList.toggle('hidden', this.planet.vehicle_status === 'returning');
            }
        }
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
            this.displayIron = this.planet.iron_amount;
            this.displayEnergy = this.planet.energy_amount;
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

    async startExpedition() {
        const res = await fetch('api.php?action=start_expedition', { method: 'POST' });
        const data = await res.json();
        if (data.success) this.fetchPlanet();
    },

    async recallVehicle() {
        const res = await fetch('api.php?action=recall_vehicle', { method: 'POST' });
        const data = await res.json();
        if (data.success) this.fetchPlanet();
    },

    async finishExpedition() {
        if (this.interval) clearInterval(this.interval);
        const res = await fetch('api.php?action=finish_expedition', { method: 'POST' });
        const data = await res.json();
        if (data.success) this.fetchPlanet();
    },

    async destroyVehicle() {
        if (this.interval) clearInterval(this.interval);
        const res = await fetch('api.php?action=destroy_vehicle', { method: 'POST' });
        const data = await res.json();
        if (data.success) this.fetchPlanet();
    },

    async upgradeVehicle() {
        const res = await fetch('api.php?action=upgrade_vehicle', { method: 'POST' });
        const data = await res.json();
        if (data.success) this.fetchPlanet();
        else alert(data.error);
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
setInterval(() => game.fetchLeaderboard(), 5000); // Update leaderboard every 5s
