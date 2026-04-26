export const gameSimulationMethods = {
    startLoop() {
        if (this.interval) clearInterval(this.interval);

        this.interval = setInterval(() => {
            const ironProd = this.planet.iron_production;
            const energyProd = this.planet.energy_production;
            const ironLimit = this.planet.iron_storage_limit;

            let extraEnergyNeeded = 0;
            for (const color in this.planet.alien_resources) {
                extraEnergyNeeded += (this.planet.alien_resources[color].lvl * 0.3);
            }
            extraEnergyNeeded += (this.planet.mine_copper_lvl * 0.5);
            if (this.planet.research_advanced_lab) extraEnergyNeeded += (this.planet.lab_level * 1.5);

            const totalEnergyNeeded = (ironProd * 0.5) + extraEnergyNeeded;
            const energyTick = energyProd / 10;
            const energyNeededTick = totalEnergyNeeded / 10;
            let prodFactor = 1.0;
            if (this.displayEnergy < energyNeededTick) prodFactor = 0.1;

            this.displayEnergy = Math.max(0, this.displayEnergy + (energyTick - energyNeededTick));
            if (this.displayIron < ironLimit) this.displayIron += (ironProd / 10) * prodFactor;
            if (this.planet.research_secret_crystal_mine && this.planet.secret_crystal_mine_level > 0) this.displayCrystal += (this.planet.secret_mine_production / 10) * prodFactor;
            if (this.planet.research_copper && this.displayCopper < this.planet.copper_storage_limit) this.displayCopper += (this.planet.copper_production / 10) * prodFactor;
            for (const color in this.planet.alien_resources) {
                this.displayAlien[color] += (this.planet.alien_resources[color].prod / 10) * prodFactor;
            }
            if (this.planet.research_advanced_lab && this.displayTubes < this.planet.tube_storage_limit) this.displayTubes += (this.planet.tube_production / 10) * prodFactor;

            if (this.planet.has_drone) {
                let multiplier = 1;
                if (this.planet.research_drone_upgrade_3) multiplier = 100;
                else if (this.planet.research_drone_upgrade_2) multiplier = 25;
                else if (this.planet.research_drone_upgrade) multiplier = 5;
                this.displayDrone = Math.min(100 * multiplier, this.displayDrone + ((1 / 3000) * multiplier));
            }

            this.updateVehicleTimers();
            this.updateUI();
        }, 100);
    },

    updateVehicleTimers() {
        this.updateVehicleTimer({
            statusKey: 'vehicle_status',
            startKey: 'vehicle_start_time',
            recallKey: 'vehicle_recall_time',
            levelKey: 'vehicle_level',
            sensorKey: 'vehicle_sensor_lvl',
            hpProp: 'vehicleHP',
            crystalsProp: 'vehicleCrystals',
            timerId: 'vehicle-timer',
            hpBarId: 'vehicle-hp-bar',
            crystalsRate: 0.1,
            sensorFactor: 0.05,
            recallFn: () => this.recallVehicle(),
            destroyFn: () => this.destroyVehicle(),
            pendingFlag: 'recallPending',
        });

        this.updateVehicleTimer({
            statusKey: 'vehicle2_status',
            startKey: 'vehicle2_start_time',
            recallKey: 'vehicle2_recall_time',
            levelKey: 'vehicle2_level',
            sensorKey: 'vehicle2_sensor_lvl',
            hpProp: 'vehicle2HP',
            crystalsProp: 'vehicle2Crystals',
            timerId: 'vehicle2-timer',
            hpBarId: 'vehicle2-hp-bar',
            crystalsRate: 0.2,
            sensorFactor: 0.10,
            levelMultiplier: 2,
            recallFn: () => this.recallVehicle2(),
            destroyFn: () => this.destroyVehicle2(),
            pendingFlag: 'recallVehicle2Pending',
        });
    },

    updateVehicleTimer(options) {
        if (this.planet[options.statusKey] !== 'exploring' && this.planet[options.statusKey] !== 'returning') return;

        const now = new Date();
        const startTime = this.planet[options.startKey] ? new Date(`${this.planet[options.startKey]} UTC`) : null;
        if (!startTime || Number.isNaN(startTime.getTime())) return;

        const secondsOut = (now - startTime) / 1000;
        let damageSeconds = secondsOut;
        const baseDamageRate = 0.1;
        const acceleration = 0.003;
        const rawLevel = this.planet[options.levelKey] || 1;
        const effectiveLevel = options.levelMultiplier ? 1 + ((rawLevel || 1) - 1) * options.levelMultiplier : rawLevel;
        const armorFactor = Math.pow(effectiveLevel, 1.2);
        let displaySeconds = 0;

        if (this.planet[options.statusKey] === 'exploring') {
            const sensorLvl = this.planet[options.sensorKey] || 1;
            const timeBonus = 1 + (secondsOut * 0.0005);
            this[options.crystalsProp] = secondsOut * (options.crystalsRate * (1 + ((sensorLvl - 1) * options.sensorFactor)) * timeBonus);
            displaySeconds = secondsOut;
            if (this.planet.research_auto_recall && this[options.hpProp] <= 90 && !this[options.pendingFlag] && !this.refreshPromise) {
                options.recallFn();
            }
        } else {
            const recallTime = this.planet[options.recallKey] ? new Date(`${this.planet[options.recallKey]} UTC`) : null;
            if (recallTime && !Number.isNaN(recallTime.getTime())) {
                const secondsReturning = (now - recallTime) / 1000;
                const secondsToReturn = (recallTime - startTime) / 1000;
                damageSeconds = Math.min(secondsOut, secondsToReturn * 2);
                displaySeconds = Math.max(0, secondsToReturn - secondsReturning);
                if (secondsReturning >= secondsToReturn) {
                    this.refreshDashboard();
                    return;
                }
            }
        }

        const totalDamage = (damageSeconds * (baseDamageRate + (damageSeconds * acceleration))) / armorFactor;
        this[options.hpProp] = Math.max(0, 100 - totalDamage);
        const mins = Math.floor(displaySeconds / 60);
        const secs = Math.floor(displaySeconds % 60);
        const timerEl = document.getElementById(options.timerId);
        if (timerEl) timerEl.innerText = `${Number.isNaN(mins) ? 0 : mins}:${(Number.isNaN(secs) ? 0 : secs).toString().padStart(2, '0')}`;
        if (this[options.hpProp] <= 0) options.destroyFn();
    },
};
