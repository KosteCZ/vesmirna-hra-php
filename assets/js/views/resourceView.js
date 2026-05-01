export const resourceViewMethods = {
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
        setIcon('icon-secret-mine-container', 'icon-secret-mine', 'mines-crystal.png', 40);
        setIcon('icon-hangar-container', 'icon-vehicle', 'hangar.png', 28);
        setIcon('icon-rocket-workshop-container', 'icon-lab', 'space-workshop.png', 28);
    },

    renderIcon(symbolId, imagePath = null, size = 24) {
        if (this.useImages && imagePath) {
            return `<img src="resources/${imagePath}" width="${size}" height="${size}" alt="icon">`;
        }
        return `<svg width="${size}" height="${size}"><use href="#${symbolId}"/></svg>`;
    },

    updateUI() {
        if (!this.planet) return;
        const researched = this.planet.researched_colors || [];
        const ironLimit = this.planet.iron_storage_limit;

        document.getElementById('display-iron').innerText = Math.floor(this.displayIron);
        document.getElementById('display-limit').innerText = ironLimit;
        document.getElementById('display-energy').innerText = Math.floor(this.displayEnergy);
        document.getElementById('display-crystal').innerText = Math.floor(this.displayCrystal);

        const crystalProdContainer = document.getElementById('crystal-prod-container');
        if (this.planet.research_secret_crystal_mine && this.planet.secret_crystal_mine_level > 0) {
            crystalProdContainer.classList.remove('hidden');
            document.getElementById('crystal-prod').innerText = (this.planet.secret_mine_production * 60).toFixed(1);
        } else {
            crystalProdContainer.classList.add('hidden');
        }

        document.getElementById('iron-prod').innerText = this.planet.iron_production.toFixed(2);
        
        const energyProd = this.planet.energy_production || 0;
        const energyCons = this.planet.energy_consumption || 0;
        const energyNet = energyProd - energyCons;
        const netEl = document.getElementById('energy-net');
        const consEl = document.getElementById('energy-cons');
        if (netEl) {
            netEl.innerText = (energyNet >= 0 ? '+' : '') + energyNet.toFixed(2);
            netEl.style.color = energyNet >= 0 ? '#28a745' : '#ff4a4a';
        }
        if (consEl) {
            consEl.innerText = energyCons.toFixed(2);
        }

        document.getElementById('iron-progress').style.width = `${Math.min(100, (this.displayIron / ironLimit) * 100)}%`;

        const tubesCard = document.getElementById('res-tubes-card');
        if (this.planet.research_advanced_lab) {
            tubesCard.classList.remove('hidden');
            const tubeLimit = this.planet.tube_storage_limit;
            document.getElementById('display-tubes').innerText = Math.floor(this.displayTubes);
            document.getElementById('display-tubes-limit').innerText = tubeLimit;
            document.getElementById('tube-prod-val').innerText = (this.planet.tube_production || 0).toFixed(2);
            document.getElementById('tubes-progress').style.width = `${Math.min(100, (this.displayTubes / tubeLimit) * 100)}%`;
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

        const warehouseCopperBtn = document.getElementById('upgrade-warehouse-copper-eff');
        if (this.planet.research_warehouse_copper) {
            warehouseCopperBtn.classList.remove('hidden');
            const cost = (this.planet.warehouse_level + 1) * 10;
            document.getElementById('warehouse-copper-cost-eff').innerText = `${cost} Cu`;
            warehouseCopperBtn.disabled = this.displayCopper < cost;
        } else {
            warehouseCopperBtn.classList.add('hidden');
        }

        if (this.planet.research_copper) {
            document.getElementById('res-copper-card').classList.remove('hidden');
            document.getElementById('copper-buildings').classList.remove('hidden');
            document.getElementById('copper-research-container').classList.add('hidden');
            const copperLimit = this.planet.copper_storage_limit;
            document.getElementById('display-copper').innerText = Math.floor(this.displayCopper);
            document.getElementById('display-copper-limit').innerText = copperLimit;
            document.getElementById('copper-prod').innerText = this.planet.copper_production.toFixed(2);
            document.getElementById('copper-progress').style.width = `${Math.min(100, (this.displayCopper / copperLimit) * 100)}%`;

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
            let hasEnoughMaterial = false;
            for (const color in this.displayAlien) {
                if (this.displayAlien[color] >= 2000) hasEnoughMaterial = true;
            }
            const btn = document.getElementById('research-copper-btn');
            if (btn) btn.disabled = this.displayIron < 50000 || this.displayCrystal < 50 || !hasEnoughMaterial;
            const desc = document.getElementById('copper-research-desc');
            if (desc) desc.style.color = hasEnoughMaterial ? '#888' : '#ff4a4a';
        }

        this.updateAdvancedResearchUI(researched);
        this.updateVehicleUI();
        this.updateDroneUI();
    },
};
