export const gameActionMethods = {
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
        if (this.refreshPromise) return this.refreshPromise;
        this.refreshPromise = (async () => {
            await this.fetchPlanet();
            await Promise.all([this.fetchLeaderboard(), this.fetchGlobalStats()]);
        })();
        try {
            await this.refreshPromise;
        } finally {
            this.refreshPromise = null;
        }
    },

    async submitAction(url, body = null) {
        const res = await fetch(url, { method: 'POST', body });
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
        if (!this.planet) return;

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
    },

    async fetchLeaderboard() {
        const res = await fetch('api.php?action=leaderboard');
        const data = await res.json();
        const body = document.getElementById('leaderboard-body');
        body.innerHTML = '';
        data.forEach((p, i) => {
            const tr = document.createElement('tr');
            if (p.player_name === document.getElementById('player-name').innerText) tr.className = 'highlight';
            const researched = p.researched_colors ? p.researched_colors.split(',') : [];
            let iconsHtml = '';
            researched.forEach((color) => {
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
            second: '2-digit',
        }).format(parsed);
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
                    <span style="display: flex; align-items: center;"><span style="color: ${colorCode}; vertical-align: middle; margin-right: 5px; display: inline-flex;">${icon}</span> <strong>${this.colorNames[color]} materiĂˇl</strong></span>
                    <span>${Math.floor(amount).toLocaleString()} / ${target.toLocaleString()}</span>
                </div>
                <div class="progress-bg" style="height: 12px;">
                    <div class="progress-bar" style="width: ${percent}%; background: ${colorCode}; box-shadow: 0 0 10px ${colorCode}66;"></div>
                </div>
            `;
            container.appendChild(item);
        }
    },

    async postAction(action, body = null) {
        return this.submitAction(`api.php?action=${action}`, body);
    },

    async researchDroneUpgrade() { await this.postAction('research_drone_upgrade'); },
    async researchDroneUpgrade2() { await this.postAction('research_drone_upgrade_2'); },
    async researchDroneUpgrade3() { await this.postAction('research_drone_upgrade_3'); },
    async researchWarehouseCopper() { await this.postAction('research_warehouse_copper'); },
    async researchAutoRecall() { await this.postAction('research_auto_recall'); },
    async upgradeWarehouseCopperEff() { await this.postAction('upgrade_warehouse_copper_eff'); },
    async buyVehicle() { if (this.displayIron < 500) return alert('Nedostatek Ĺľeleza!'); await this.postAction('buy_vehicle'); },
    async buyVehicle2() { if (this.displayCopper < 500) return alert('Nedostatek mÄ›di (500 Cu)!'); await this.postAction('buy_vehicle2'); },
    async startExpedition() { await this.postAction('start_expedition'); },
    async startExpedition2() { await this.postAction('start_expedition2'); },
    async finishExpedition() { if (this.interval) clearInterval(this.interval); await this.postAction('finish_expedition'); },
    async finishExpedition2() { if (this.interval) clearInterval(this.interval); await this.postAction('finish_expedition2'); },
    async destroyVehicle() { if (this.interval) clearInterval(this.interval); await this.postAction('destroy_vehicle'); },
    async destroyVehicle2() { if (this.interval) clearInterval(this.interval); await this.postAction('destroy_vehicle2'); },
    async upgradeVehicle() { await this.postAction('upgrade_vehicle'); },
    async upgradeVehicleSensors() { await this.postAction('upgrade_vehicle_sensors'); },
    async upgradeVehicle2Armor() { await this.postAction('upgrade_vehicle2_armor'); },
    async upgradeVehicle2Sensors() { await this.postAction('upgrade_vehicle2_sensors'); },
    async researchCopper() { await this.postAction('research_copper'); },
    async upgradeCopperMine() { await this.postAction('upgrade_copper_mine'); },
    async upgradeCopperWarehouse() { await this.postAction('upgrade_copper_warehouse'); },
    async researchAdvancedLab() { await this.postAction('research_advanced_lab'); },
    async upgradeLab() { await this.postAction('upgrade_lab'); },
    async upgradeLabStorage() { await this.postAction('upgrade_lab_storage'); },
    async researchRocketWorkshop() { await this.postAction('research_rocket_workshop'); },
    async researchAlienSlot3() { await this.postAction('research_alien_slot_3'); },
    async researchSecretMine() { await this.postAction('research_secret_crystal_mine'); },
    async upgradeSecretMine() { await this.postAction('upgrade_secret_crystal_mine'); },
    async buyDrone() { if (this.displayCrystal < 250) return alert('Nedostatek krystalĹŻ!'); await this.postAction('buy_drone'); },
    async collectDrone() { await this.postAction('collect_drone'); },

    async upgrade(type) {
        const formData = new FormData();
        formData.append('type', type);
        await this.postAction('upgrade', formData);
    },

    async upgradeRocketWorkshop() {
        if (this.displayIron < 1000000) return alert('Nedostatek Ĺľeleza (1 000 000 Fe)!');
        await this.postAction('upgrade_rocket_workshop');
    },

    async startRocketWorkshopProduction(mode = 1) {
        const formData = new FormData();
        formData.append('mode', mode);
        await this.postAction('start_rocket_workshop_production', formData);
    },

    async collectRocketWorkshopProduct(slot = 1) {
        const formData = new FormData();
        formData.append('slot', slot);
        const data = await this.postAction('collect_rocket_workshop_product', formData);
        if (!data || !data.part_label) return;

        const modal = document.getElementById('workshop-modal');
        const modalTitle = document.getElementById('modal-title');
        const modalText = document.getElementById('modal-text');
        const modalImgContainer = document.getElementById('modal-image-container');
        const isMultiple = Array.isArray(data.parts) && data.parts.length > 1;
        modalTitle.innerText = isMultiple ? 'NovĂ© dĂ­ly zĂ­skĂˇny!' : 'NovĂ˝ dĂ­l zĂ­skĂˇn!';
        modalText.innerText = data.part_label;
        modalImgContainer.innerHTML = '';
        modalImgContainer.style.display = 'flex';
        modalImgContainer.style.justifyContent = 'center';
        modalImgContainer.style.gap = '15px';
        modalImgContainer.style.flexWrap = 'wrap';
        const partsToShow = Array.isArray(data.parts) ? data.parts : [data.part_key];
        partsToShow.forEach((pName) => {
            const imageKey = Object.keys(this.rocketPartNames).find((key) => this.rocketPartNames[key] === pName) || pName;
            const img = document.createElement('img');
            img.src = `resources/${this.rocketPartImages[imageKey] || 'workshop-items/unknown.png'}`;
            img.alt = pName;
            img.style.maxWidth = isMultiple ? '100px' : '150px';
            modalImgContainer.appendChild(img);
        });
        modal.classList.remove('hidden');
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

    async upgradeAlienMine(color) {
        const formData = new FormData();
        formData.append('color', color);
        await this.postAction('upgrade_alien_mine', formData);
    },

    async researchColor(color) {
        const formData = new FormData();
        formData.append('color', color);
        await this.postAction('research_color', formData);
    },
};
