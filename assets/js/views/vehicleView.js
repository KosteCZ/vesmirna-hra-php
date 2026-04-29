export const vehicleViewMethods = {
    updateVehicleUI() {
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
    },

    updateDroneUI() {
        if (this.planet && this.planet.has_drone) {
            document.getElementById('no-drone-view').classList.add('hidden');
            document.getElementById('drone-view').classList.remove('hidden');
            document.getElementById('drone-icon-container').innerHTML = this.renderIcon('icon-drone', 'dron-crystals.png', 24);
            const limit = this.planet.drone_storage_limit || 100;
            document.getElementById('drone-storage-val').innerText = Math.floor(this.displayDrone);
            const limitEl = document.getElementById('drone-storage-limit');
            if (limitEl) limitEl.innerText = limit;
            document.getElementById('drone-progress-bar').style.width = `${Math.min(100, (this.displayDrone / limit) * 100)}%`;
            document.getElementById('collect-drone-btn').disabled = this.displayDrone < 1;
        } else if (this.planet) {
            document.getElementById('no-drone-view').classList.remove('hidden');
            document.getElementById('no-drone-icon-container').innerHTML = this.renderIcon('icon-drone', 'dron-crystals.png', 24);
            document.getElementById('drone-view').classList.add('hidden');
        }
    },
};
