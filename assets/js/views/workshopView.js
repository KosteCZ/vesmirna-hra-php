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
        const isSlot1Idle = (this.planet.rocket_workshop_status || 'idle') === 'idle';
        upgradeBtn.classList.toggle('hidden', level >= 2 || !isSlot1Idle);
        upgradeBtn.disabled = this.displayIron < 1000000;

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
            statusEl.innerText = 'Hotovo! MĹŻĹľeĹˇ vyzvednout.';
            statusEl.style.color = '#28a745';
            timerWrap.classList.add('hidden');
            startBtn.classList.add('hidden');
            collectBtn.classList.remove('hidden');
        } else if (data.status === 'producing' && data.readyAt) {
            const readyAt = new Date(data.readyAt.replace(' ', 'T') + 'Z');
            const remaining = (readyAt.getTime() - Date.now()) / 1000;
            if (remaining <= 0) {
                statusEl.innerText = 'DokonÄŤovĂˇnĂ­...';
                if (!this.refreshPromise) this.refreshDashboard();
                startBtn.classList.add('hidden');
                collectBtn.classList.add('hidden');
            } else {
                statusEl.innerText = 'ProbĂ­hĂˇ vĂ˝roba...';
                statusEl.style.color = '#888';
                timerWrap.classList.remove('hidden');
                timerEl.innerText = this.formatDuration(remaining);
                progressEl.style.width = `${Math.min(100, ((data.duration - remaining) / data.duration) * 100)}%`;
                startBtn.classList.add('hidden');
                collectBtn.classList.add('hidden');
            }
        } else {
            statusEl.innerText = 'PĹ™ipraveno';
            statusEl.style.color = '#888';
            timerWrap.classList.add('hidden');
            startBtn.classList.remove('hidden');
            startBtn.disabled = this.displayTubes < data.cost || data.allCompleted;
            collectBtn.classList.add('hidden');
        }
    },
};
