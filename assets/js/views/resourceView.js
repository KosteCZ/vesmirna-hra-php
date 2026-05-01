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

        const setVal = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.innerText = val;
        };

        setVal('display-iron', Math.floor(this.displayIron));
        setVal('display-limit', ironLimit);
        setVal('display-energy', Math.floor(this.displayEnergy));
        setVal('display-crystal', Math.floor(this.displayCrystal));

        const crystalProdContainer = document.getElementById('crystal-prod-container');
        if (crystalProdContainer) {
            if (this.planet.research_secret_crystal_mine && this.planet.secret_crystal_mine_level > 0) {
                crystalProdContainer.classList.remove('hidden');
                setVal('crystal-prod', (this.planet.secret_mine_production * 60).toFixed(1));
            } else {
                crystalProdContainer.classList.add('hidden');
            }
        }

        setVal('iron-prod', this.planet.iron_production.toFixed(2));
        
        const energyProd = this.planet.energy_production || 0;
        const energyCons = this.planet.energy_consumption || 0;
        const energyNet = energyProd - energyCons;
        
        setVal('energy-prod', energyProd.toFixed(2));
        setVal('energy-cons', energyCons.toFixed(2));
        
        const netEl = document.getElementById('energy-net');
        if (netEl) {
            netEl.innerText = (energyNet >= 0 ? '+' : '') + energyNet.toFixed(2);
            netEl.style.color = energyNet >= 0 ? '#28a745' : '#ff4a4a';
        }

        const ironProgress = document.getElementById('iron-progress');
        if (ironProgress) {
            ironProgress.style.width = `${Math.min(100, (this.displayIron / ironLimit) * 100)}%`;
        }

        const tubesCard = document.getElementById('res-tubes-card');
        if (tubesCard) {
            if (this.planet.research_advanced_lab) {
                tubesCard.classList.remove('hidden');
                const tubeLimit = this.planet.tube_storage_limit;
                setVal('display-tubes', Math.floor(this.displayTubes));
                setVal('display-tubes-limit', tubeLimit);
                setVal('tube-prod-val', (this.planet.tube_production || 0).toFixed(2));
                const tubesProgress = document.getElementById('tubes-progress');
                if (tubesProgress) tubesProgress.style.width = `${Math.min(100, (this.displayTubes / tubeLimit) * 100)}%`;
            } else {
                tubesCard.classList.add('hidden');
            }
        }

        const mineCost = 100 * this.planet.mine_level;
        const solarCost = 100 * this.planet.solar_plant_level;
        const warehouseCost = 100 * this.planet.warehouse_level;
        setVal('mine-lvl', this.planet.mine_level);
        setVal('solar-lvl', this.planet.solar_plant_level);
        setVal('warehouse-lvl', this.planet.warehouse_level);
        setVal('mine-cost', `(${mineCost} Fe)`);
        setVal('solar-cost', `(${solarCost} Fe)`);

        const upgradeWarehouseBtn = document.getElementById('upgrade-warehouse');
        if (upgradeWarehouseBtn) {
            if (this.planet.warehouse_level >= 200) {
                upgradeWarehouseBtn.classList.add('hidden');
            } else {
                upgradeWarehouseBtn.classList.remove('hidden');
                setVal('warehouse-cost', `(${warehouseCost} Fe)`);
                upgradeWarehouseBtn.disabled = this.displayIron < warehouseCost;
            }
        }

        const upgradeMineBtn = document.getElementById('upgrade-mine');
        if (upgradeMineBtn) upgradeMineBtn.disabled = this.displayIron < mineCost;
        const upgradeSolarBtn = document.getElementById('upgrade-solar');
        if (upgradeSolarBtn) upgradeSolarBtn.disabled = this.displayIron < solarCost;

        const warehouseCopperBtn = document.getElementById('upgrade-warehouse-copper-eff');
        if (warehouseCopperBtn) {
            if (this.planet.research_warehouse_copper) {
                warehouseCopperBtn.classList.remove('hidden');
                const cost = (this.planet.warehouse_level + 1) * 10;
                setVal('warehouse-copper-cost-eff', `${cost} Cu`);
                warehouseCopperBtn.disabled = this.displayCopper < cost;
            } else {
                warehouseCopperBtn.classList.add('hidden');
            }
        }

        if (this.planet.research_copper) {
            const copperCard = document.getElementById('res-copper-card');
            const copperBld = document.getElementById('copper-buildings');
            const copperRes = document.getElementById('copper-research-container');
            if (copperCard) copperCard.classList.remove('hidden');
            if (copperBld) copperBld.classList.remove('hidden');
            if (copperRes) copperRes.classList.add('hidden');

            const copperLimit = this.planet.copper_storage_limit;
            setVal('display-copper', Math.floor(this.displayCopper));
            setVal('display-copper-limit', copperLimit);
            setVal('copper-prod', this.planet.copper_production.toFixed(2));
            const copperProgress = document.getElementById('copper-progress');
            if (copperProgress) copperProgress.style.width = `${Math.min(100, (this.displayCopper / copperLimit) * 100)}%`;

            const copperMineLvl = this.planet.mine_copper_lvl;
            const copperWarehouseLvl = this.planet.warehouse_copper_lvl;
            setVal('mine-copper-lvl', copperMineLvl);
            setVal('warehouse-copper-lvl', copperWarehouseLvl);

            const copperMineIronCost = (copperMineLvl + 1) * 1000;
            const copperMineCrystalCost = (copperMineLvl + 1) * 10;
            const copperWarehouseIronCost = (copperWarehouseLvl + 1) * 2000;
            const copperWarehouseCrystalCost = (copperWarehouseLvl + 1) * 20;
            setVal('mine-copper-cost', `(${copperMineIronCost} Fe, ${copperMineCrystalCost} Kryst.)`);
            setVal('warehouse-copper-cost', `(${copperWarehouseIronCost} Fe, ${copperWarehouseCrystalCost} Kryst.)`);
            
            const upgMineCu = document.getElementById('upgrade-mine-copper');
            if (upgMineCu) upgMineCu.disabled = this.displayIron < copperMineIronCost || this.displayCrystal < copperMineCrystalCost;
            const upgWhCu = document.getElementById('upgrade-warehouse-copper');
            if (upgWhCu) upgWhCu.disabled = this.displayIron < copperWarehouseIronCost || this.displayCrystal < copperWarehouseCrystalCost;
        } else {
            const copperCard = document.getElementById('res-copper-card');
            const copperBld = document.getElementById('copper-buildings');
            const copperRes = document.getElementById('copper-research-container');
            if (copperCard) copperCard.classList.add('hidden');
            if (copperBld) copperBld.classList.add('hidden');
            if (copperRes) copperRes.classList.remove('hidden');

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
