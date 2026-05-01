export const vehicleViewMethods = {
    updateVehicleUI() {
        if (!this.planet) return;

        const setVal = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.innerText = val;
        };

        const noVehView = document.getElementById('no-vehicle-view');
        const vehView = document.getElementById('vehicle-view');

        if (this.planet.vehicle_level === 0 && this.planet.vehicle_status !== 'destroyed') {
            if (noVehView) noVehView.classList.remove('hidden');
            if (vehView) vehView.classList.add('hidden');
            const iconCont = document.getElementById('no-vehicle-icon-container');
            if (iconCont) iconCont.innerHTML = this.renderIcon('icon-vehicle', 'vehicle-1.png', 30);
        } else {
            if (noVehView) noVehView.classList.add('hidden');
            if (vehView) vehView.classList.remove('hidden');
            const iconCont = document.getElementById('vehicle-icon-container');
            if (iconCont) iconCont.innerHTML = this.renderIcon('icon-vehicle', 'vehicle-1.png', 50);
            
            setVal('vehicle-lvl', this.planet.vehicle_level);
            const sensorLvl = this.planet.vehicle_sensor_lvl || 1;
            setVal('vehicle-sensor-lvl', sensorLvl);
            const upgradeCost = (this.planet.vehicle_level + 1) * 500;
            const sensorCost = sensorLvl * 1000;
            setVal('vehicle-upgrade-cost', upgradeCost);
            setVal('vehicle-sensor-cost', sensorCost);
            
            const upgVehBtn = document.getElementById('upgrade-vehicle-btn');
            if (upgVehBtn) upgVehBtn.disabled = this.displayIron < upgradeCost;
            const upgSensBtn = document.getElementById('upgrade-sensors-btn');
            if (upgSensBtn) upgSensBtn.disabled = this.displayIron < sensorCost;

            const vehIdle = document.getElementById('vehicle-idle');
            if (vehIdle) vehIdle.classList.toggle('hidden', this.planet.vehicle_status !== 'idle');
            const vehActive = document.getElementById('vehicle-active');
            if (vehActive) vehActive.classList.toggle('hidden', this.planet.vehicle_status !== 'exploring' && this.planet.vehicle_status !== 'returning');
            const vehDestroyed = document.getElementById('vehicle-destroyed');
            if (vehDestroyed) vehDestroyed.classList.toggle('hidden', this.planet.vehicle_status !== 'destroyed');

            if (this.planet.vehicle_status === 'exploring' || this.planet.vehicle_status === 'returning') {
                setVal('vehicle-hp-val', Math.floor(this.vehicleHP));
                const hpBar = document.getElementById('vehicle-hp-bar');
                if (hpBar) {
                    hpBar.style.width = `${this.vehicleHP}%`;
                    hpBar.style.background = this.vehicleHP < 30 ? '#ff4a4a' : '#28a745';
                }
                setVal('vehicle-crystals', Math.floor(this.vehicleCrystals));
                setVal('vehicle-status-text', this.planet.vehicle_status === 'exploring' ? '🛰️ Probíhá průzkum...' : '🚀 Vozidlo se vrací...');
                const recallBtn = document.getElementById('recall-btn');
                if (recallBtn) recallBtn.classList.toggle('hidden', this.planet.vehicle_status === 'returning');
            }
        }

        const h2Section = document.getElementById('hangar2-section');
        if (this.planet.research_copper) {
            if (h2Section) h2Section.classList.remove('hidden');
            const noVeh2View = document.getElementById('no-vehicle2-view');
            const veh2View = document.getElementById('vehicle2-view');
            if (this.planet.vehicle2_level === 0 && this.planet.vehicle2_status !== 'destroyed') {
                if (noVeh2View) noVeh2View.classList.remove('hidden');
                if (veh2View) veh2View.classList.add('hidden');
                const iconCont2 = document.getElementById('no-vehicle2-icon-container');
                if (iconCont2) iconCont2.innerHTML = this.renderIcon('icon-vehicle', 'vehicle-2.png', 30);
            } else {
                if (noVeh2View) noVeh2View.classList.add('hidden');
                if (veh2View) veh2View.classList.remove('hidden');
                const iconCont2 = document.getElementById('vehicle2-icon-container');
                if (iconCont2) iconCont2.innerHTML = this.renderIcon('icon-vehicle', 'vehicle-2.png', 50);
                
                setVal('vehicle2-lvl', this.planet.vehicle2_level);
                const sensor2Lvl = this.planet.vehicle2_sensor_lvl || 1;
                setVal('vehicle2-sensor-lvl', sensor2Lvl);
                const upgrade2Cost = (this.planet.vehicle2_level + 1) * 100;
                const sensor2Cost = sensor2Lvl * 150;
                setVal('vehicle2-upgrade-cost', upgrade2Cost);
                setVal('vehicle2-sensor-cost', sensor2Cost);

                const upgVeh2Btn = document.getElementById('upgrade-vehicle2-btn');
                if (upgVeh2Btn) upgVeh2Btn.disabled = this.displayCopper < upgrade2Cost;
                const upgSens2Btn = document.getElementById('upgrade-sensors2-btn');
                if (upgSens2Btn) upgSens2Btn.disabled = this.displayCopper < sensor2Cost;

                const veh2Idle = document.getElementById('vehicle2-idle');
                if (veh2Idle) veh2Idle.classList.toggle('hidden', this.planet.vehicle2_status !== 'idle');
                const veh2Active = document.getElementById('vehicle2-active');
                if (veh2Active) veh2Active.classList.toggle('hidden', this.planet.vehicle2_status !== 'exploring' && this.planet.vehicle2_status !== 'returning');
                const veh2Destroyed = document.getElementById('vehicle2-destroyed');
                if (veh2Destroyed) veh2Destroyed.classList.toggle('hidden', this.planet.vehicle2_status !== 'destroyed');

                if (this.planet.vehicle2_status === 'exploring' || this.planet.vehicle2_status === 'returning') {
                    setVal('vehicle2-hp-val', Math.floor(this.vehicle2HP));
                    const hpBar2 = document.getElementById('vehicle2-hp-bar');
                    if (hpBar2) {
                        hpBar2.style.width = `${this.vehicle2HP}%`;
                        hpBar2.style.background = this.vehicle2HP < 30 ? '#ff4a4a' : '#28a745';
                    }
                    setVal('vehicle2-crystals', Math.floor(this.vehicle2Crystals));
                    setVal('vehicle2-status-text', this.planet.vehicle2_status === 'exploring' ? '🛰️ Probíhá průzkum...' : '🚀 Vozidlo se vrací...');
                    const recallBtn2 = document.getElementById('recall2-btn');
                    if (recallBtn2) recallBtn2.classList.toggle('hidden', this.planet.vehicle2_status === 'returning');
                }
            }
        } else if (h2Section) {
            h2Section.classList.add('hidden');
        }
    },

    updateDroneUI() {
        if (!this.planet) return;

        const setVal = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.innerText = val;
        };

        const noDroneView = document.getElementById('no-drone-view');
        const droneView = document.getElementById('drone-view');

        if (this.planet.has_drone) {
            if (noDroneView) noDroneView.classList.add('hidden');
            if (droneView) droneView.classList.remove('hidden');
            const iconCont = document.getElementById('drone-icon-container');
            if (iconCont) iconCont.innerHTML = this.renderIcon('icon-drone', 'dron-crystals.png', 24);
            
            const limit = this.planet.drone_storage_limit || 100;
            setVal('drone-storage-val', Math.floor(this.displayDrone));
            setVal('drone-storage-limit', limit);
            
            const droneProgress = document.getElementById('drone-progress-bar');
            if (droneProgress) droneProgress.style.width = `${Math.min(100, (this.displayDrone / limit) * 100)}%`;
            const collectBtn = document.getElementById('collect-drone-btn');
            if (collectBtn) collectBtn.disabled = this.displayDrone < 1;
        } else {
            if (noDroneView) noDroneView.classList.remove('hidden');
            if (droneView) droneView.classList.add('hidden');
            const noDroneIconCont = document.getElementById('no-drone-icon-container');
            if (noDroneIconCont) noDroneIconCont.innerHTML = this.renderIcon('icon-drone', 'dron-crystals.png', 24);
        }
    },
};
