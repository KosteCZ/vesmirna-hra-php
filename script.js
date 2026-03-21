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
            this.updateUI();
        }, 100);
    },

    updateUI() {
        if (!this.planet) return;
        
        const ironLimit = this.planet.iron_storage_limit;
        document.getElementById('display-iron').innerText = Math.floor(this.displayIron);
        document.getElementById('display-limit').innerText = ironLimit;
        document.getElementById('display-energy').innerText = Math.floor(this.displayEnergy);
        
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
