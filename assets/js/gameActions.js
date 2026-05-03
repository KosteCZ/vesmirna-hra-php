export const gameActionMethods = {
    showDashboard(user) {
        const authSect = document.getElementById('auth-section');
        if (authSect) authSect.classList.add('hidden');
        const userInfo = document.getElementById('user-info');
        if (userInfo) userInfo.classList.remove('hidden');
        const playerName = document.getElementById('player-name');
        if (playerName) playerName.innerText = user.player_name;
        const dashSect = document.getElementById('dashboard-section');
        if (dashSect) dashSect.classList.remove('hidden');
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

        this.checkGameStateEvents();
        this.updateGlobalAlert();
        this.startLoop();
        this.refreshIcons();
        this.updateUI();
        this.updateResearchUI();
        this.updateAlienUI();
    },

    updateGlobalAlert() {
        const bar = document.getElementById('global-alert-bar');
        const textEl = document.getElementById('storm-countdown-text');
        const escalationMessageBtn = document.getElementById('storm-escalation-message-btn');
        if (!bar || !this.planet) return;

        const state = this.planet.game_state;
        const etaStr = this.planet.sand_storm_eta;

        if ((state === 'SAND_STORM_COMING_1' || state === 'SAND_STORM_COMING_2') && etaStr) {
            bar.classList.remove('hidden');
            const eta = new Date(etaStr.replace(' ', 'T') + 'Z');
            const updateTimer = () => {
                if (!textEl) return;
                const diff = eta.getTime() - Date.now();
                if (diff <= 0) {
                    textEl.innerText = 'Bouře právě probíhá!';
                    return;
                }
                const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const mins = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const secs = Math.floor((diff % (1000 * 60)) / 1000);
                textEl.innerText = `Bouře za ${days}d ${hours.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
            };
            updateTimer();
            if (this.globalTimerInterval) clearInterval(this.globalTimerInterval);
            this.globalTimerInterval = setInterval(updateTimer, 1000);
            if (escalationMessageBtn) escalationMessageBtn.classList.toggle('hidden', state !== 'SAND_STORM_COMING_2');
        } else {
            bar.classList.add('hidden');
            if (escalationMessageBtn) escalationMessageBtn.classList.add('hidden');
            if (this.globalTimerInterval) clearInterval(this.globalTimerInterval);
        }
    },

    showSandStormEvent() {
        this.showEventModal(
            'Blížící se písečná bouře',
            'resources/events/event-sand-storm.png',
            'Radary zjistily, že se k tvojí základně pomalu blíží písečná bouře. Pokuste se s ostatními veliteli co nejdřív dokončit výstavbu vesmírné brány a zároveň vyrob všechny potřebné části pro výstavbu rakety, aby bylo možné opustit tuto planetu a proletět bránou zpátky domů.'
        );
    },

    showSandStormEscalationEvent() {
        this.showEventModal(
            'P\u00edse\u010dn\u00e1 bou\u0159e zrychluje',
            'resources/events/event-sand-storm-2.png',
            'Nov\u00e1 m\u011b\u0159en\u00ed ukazuj\u00ed, \u017ee se p\u00edse\u010dn\u00e1 bou\u0159e bl\u00ed\u017e\u00ed mnohem rychleji, ne\u017e jsme \u010dekali. \u010casu na dokon\u010den\u00ed vesm\u00edrn\u00e9 br\u00e1ny a p\u0159\u00edpravu rakety zb\u00fdv\u00e1 m\u00e9n\u011b. V\u0161ichni velitel\u00e9 mus\u00ed okam\u017eit\u011b zrychlit pr\u00e1ce.'
        );
    },

    checkGameStateEvents() {
        if (!this.planet || !this.planet.game_state) return;

        const state = this.planet.game_state;
        const seenStates = JSON.parse(localStorage.getItem('seen_game_states') || '[]');

        if (state === 'SAND_STORM_COMING_1' && !seenStates.includes('SAND_STORM_COMING_1')) {
            this.showEventModal(
                'Blížící se písečná bouře',
                'resources/events/event-sand-storm.png',
                'Radary zjistily, že se k tvojí základně pomalu blíží písečná bouře. Pokuste se s ostatními veliteli co nejdřív dokončit výstavbu vesmírné brány a zároveň vyrob všechny potřebné části pro výstavbu rakety, aby bylo možné opustit tuto planetu a proletět bránou zpátky domů.'
            );
            seenStates.push('SAND_STORM_COMING_1');
            localStorage.setItem('seen_game_states', JSON.stringify(seenStates));
        }

        if (state === 'SAND_STORM_COMING_2' && !seenStates.includes('SAND_STORM_COMING_2')) {
            this.showSandStormEscalationEvent();
            seenStates.push('SAND_STORM_COMING_2');
            localStorage.setItem('seen_game_states', JSON.stringify(seenStates));
        }
    },

    showEventModal(title, image, text) {
        const titleEl = document.getElementById('event-title');
        const imgEl = document.getElementById('event-image');
        const textEl = document.getElementById('event-text');
        const modal = document.getElementById('event-modal');

        if (titleEl) titleEl.innerText = title;
        if (imgEl) imgEl.src = image;
        if (textEl) textEl.innerText = text;
        if (modal) modal.classList.remove('hidden');
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
                    <span style="display: flex; align-items: center;"><span style="color: ${colorCode}; vertical-align: middle; margin-right: 5px; display: inline-flex;">${icon}</span> <strong>${this.colorNames[color]} materiál</strong></span>
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
    async buyVehicle() { if (this.displayIron < 500) return alert('Nedostatek železa!'); await this.postAction('buy_vehicle'); },
    async buyVehicle2() { if (this.displayCopper < 500) return alert('Nedostatek mědi (500 Cu)!'); await this.postAction('buy_vehicle2'); },
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
    async buyDrone() { if (this.displayCrystal < 250) return alert('Nedostatek krystalů!'); await this.postAction('buy_drone'); },
    async collectDrone() { await this.postAction('collect_drone'); },

    async upgrade(type) {
        const formData = new FormData();
        formData.append('type', type);
        await this.postAction('upgrade', formData);
    },

    async upgradeRocketWorkshop() {
        if (this.displayIron < 1000000) return alert('Nedostatek železa (1 000 000 Fe)!');
        await this.postAction('upgrade_rocket_workshop');
    },

    async startRocketWorkshopProduction(mode = 1) {
        const formData = new FormData();
        formData.append('mode', mode);
        await this.postAction('start_rocket_workshop_production', formData);
    },

    async buyRocketWorkshopPart() {
        if (this.displayCrystal < 50000) return alert('Nedostatek krystalĹŻ (50 000)!');
        const data = await this.postAction('buy_rocket_workshop_part');
        this.showRocketPartModal(data);
    },

    async launchRocket() {
        const data = await this.postAction('launch_rocket');
        if (data && data.success) alert('Raketa odstartovala!');
    },

    async collectRocketWorkshopProduct(slot = 1) {
        const formData = new FormData();
        formData.append('slot', slot);
        const data = await this.postAction('collect_rocket_workshop_product', formData);
        this.showRocketPartModal(data);
    },

    showRocketPartModal(data) {
        if (!data || !data.part_label) return;

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
