export const workshopViewMethods = {
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
        
        const setVal = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.innerText = val;
        };

        if (!this.planet.research_rocket_workshop) {
            section.classList.add('hidden');
            return;
        }

        section.classList.remove('hidden');
        const inventory = this.planet.rocket_parts || {};
        const total = Object.values(inventory).reduce((sum, value) => sum + Number(value || 0), 0);
        const allCompleted = Boolean(this.planet.rocket_parts_all_completed);
        const level = this.planet.rocket_workshop_level || 1;

        setVal('rocket-workshop-lvl', level);
        setVal('rocket-parts-total', total);

        const upgradeBtn = document.getElementById('rocket-workshop-upgrade-btn');
        const partsList = document.getElementById('rocket-parts-list');
        const finishedEl = document.getElementById('rocket-workshop-finished-note');
        const crystalBuy = document.getElementById('rocket-workshop-crystal-buy');
        const crystalBuyBtn = document.getElementById('rocket-workshop-buy-crystal-btn');

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

        if (finishedEl) finishedEl.classList.toggle('hidden', !allCompleted);
        const canBuyWithCrystals = this.planet.game_state === 'SAND_STORM_COMING_2';
        if (crystalBuy) crystalBuy.classList.toggle('hidden', !canBuyWithCrystals || allCompleted);
        if (crystalBuyBtn) crystalBuyBtn.disabled = this.displayCrystal < 50000 || allCompleted;

        const isSlot1Idle = (this.planet.rocket_workshop_status || 'idle') === 'idle';
        if (upgradeBtn) {
            upgradeBtn.classList.toggle('hidden', level >= 2 || !isSlot1Idle);
            upgradeBtn.disabled = this.displayIron < 1000000;
        }

        this.updateWorkshopSlot(1, {
            status: this.planet.rocket_workshop_status,
            readyAt: this.planet.rocket_workshop_ready_at,
            duration: 28800,
            cost: 10000,
            unlocked: true,
            allCompleted,
        });

        this.updateWorkshopSlot(2, {
            status: this.planet.rocket_workshop_2_status,
            readyAt: this.planet.rocket_workshop_2_ready_at,
            duration: 57600,
            cost: 20000,
            unlocked: level >= 2,
            allCompleted,
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
            if (statusEl) {
                statusEl.innerText = 'Hotovo! Můžeš vyzvednout.';
                statusEl.style.color = '#28a745';
            }
            if (timerWrap) timerWrap.classList.add('hidden');
            if (startBtn) startBtn.classList.add('hidden');
            if (collectBtn) collectBtn.classList.remove('hidden');
        } else if (data.status === 'producing' && data.readyAt) {
            const readyAt = new Date(data.readyAt.replace(' ', 'T') + 'Z');
            const remaining = (readyAt.getTime() - Date.now()) / 1000;
            if (remaining <= 0) {
                if (statusEl) statusEl.innerText = 'Dokončování...';
                if (!this.refreshPromise) this.refreshDashboard();
                if (startBtn) startBtn.classList.add('hidden');
                if (collectBtn) collectBtn.classList.add('hidden');
            } else {
                if (statusEl) {
                    statusEl.innerText = 'Probíhá výroba...';
                    statusEl.style.color = '#888';
                }
                if (timerWrap) timerWrap.classList.remove('hidden');
                if (timerEl) timerEl.innerText = this.formatDuration(remaining);
                if (progressEl) progressEl.style.width = `${Math.min(100, ((data.duration - remaining) / data.duration) * 100)}%`;
                if (startBtn) startBtn.classList.add('hidden');
                if (collectBtn) collectBtn.classList.add('hidden');
            }
        } else {
            if (statusEl) {
                statusEl.innerText = 'Připraveno';
                statusEl.style.color = '#888';
            }
            if (timerWrap) timerWrap.classList.add('hidden');
            if (startBtn) {
                startBtn.classList.remove('hidden');
                startBtn.disabled = this.displayTubes < data.cost || data.allCompleted;
            }
            if (collectBtn) collectBtn.classList.add('hidden');
        }
    },

    updateRocketPlatformUI() {
        const section = document.getElementById('rocket-platform-section');
        if (!section || !this.planet) return;

        const visible = this.planet.game_state === 'SAND_STORM_COMING_2' || this.planet.game_state === 'WIN';
        section.classList.toggle('hidden', !visible);
        if (!visible) return;

        const totals = this.planet.alien_global_totals || {};
        const gateActive = Object.keys(this.colorNames).every((color) => Number(totals[color] || 0) >= 10000000);
        const rocketComplete = Boolean(this.planet.rocket_parts_all_completed);

        const gateImage = document.getElementById('space-gate-status-image');
        const gateText = document.getElementById('space-gate-status-text');
        const rocketImage = document.getElementById('rocket-platform-status-image');
        const rocketText = document.getElementById('rocket-platform-status-text');
        const launchBtn = document.getElementById('launch-rocket-btn');

        if (gateImage) gateImage.src = gateActive ? 'resources/space-gate-on.png' : 'resources/space-gate-off.png';
        if (gateText) {
            gateText.innerText = gateActive ? 'Aktivn\u00ed' : 'Neaktivn\u00ed';
            gateText.style.color = gateActive ? '#4ade80' : '#f87171';
        }
        if (rocketImage) rocketImage.src = rocketComplete ? 'resources/rocket-platform-with-rocket.png' : 'resources/rocket-platform-empty.png';
        if (rocketText) {
            rocketText.innerText = rocketComplete ? 'Raketa je kompletn\u00ed' : 'Raketa nen\u00ed kompletn\u00ed';
            rocketText.style.color = rocketComplete ? '#4ade80' : '#f87171';
        }
        if (launchBtn) {
            const launched = this.planet.game_state === 'WIN';
            launchBtn.disabled = launched || !gateActive || !rocketComplete;
            launchBtn.innerText = launched ? 'Raketa odstartovala' : 'Odstartovat raketu';
        }
    },
};
