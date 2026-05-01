export const researchViewMethods = {
    updateAdvancedResearchUI(researched) {
        const labResContainer = document.getElementById('lab-research-container');
        
        const setVal = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.innerText = val;
        };

        if (this.planet && !this.planet.research_advanced_lab) {
            if (labResContainer) {
                let totalColored = 0;
                for (const color in this.displayAlien) totalColored += this.displayAlien[color];
                const researchedCount = (this.planet.researched_colors || []).length;
                if (researchedCount >= 2) {
                    labResContainer.classList.remove('hidden');
                    const btn = document.getElementById('research-lab-btn');
                    if (btn) btn.disabled = this.displayCopper < 5000 || totalColored < 10000;
                    const labDesc = document.getElementById('lab-research-desc');
                    if (labDesc) {
                        if (totalColored < 10000) {
                            labDesc.innerText = `Vyžaduje 10 000 barevného materiálu (máš ${Math.floor(totalColored)}).`;
                            labDesc.style.color = '#ff4a4a';
                        } else {
                            labDesc.innerText = 'Odemkne výrobu zkumavek pro pokročilý výzkum. Vyžaduje 2 barvy a dosažení součtu všech barevných materiálů v hodnotě 10 000.';
                            labDesc.style.color = '#888';
                        }
                    }
                } else {
                    labResContainer.classList.add('hidden');
                }
            }
        } else if (labResContainer) {
            labResContainer.classList.add('hidden');
        }

        const labBuildings = document.getElementById('lab-buildings');
        if (this.planet && this.planet.research_advanced_lab) {
            if (labBuildings) labBuildings.classList.remove('hidden');
            const labLvl = this.planet.lab_level;
            const labStorageLvl = this.planet.lab_storage_level;
            setVal('lab-lvl', labLvl);
            setVal('lab-storage-lvl', labStorageLvl);
            const labIronCost = (labLvl + 1) * 5000;
            const labCrystalCost = (labLvl + 1) * 100;
            const storageIronCost = (labStorageLvl + 1) * 8000;
            const storageCrystalCost = (labStorageLvl + 1) * 150;
            setVal('lab-upgrade-cost', `(${labIronCost} Fe, ${labCrystalCost} Kryst.)`);
            setVal('lab-storage-upgrade-cost', `(${storageIronCost} Fe, ${storageCrystalCost} Kryst.)`);
            const upgLabBtn = document.getElementById('upgrade-lab-btn');
            if (upgLabBtn) upgLabBtn.disabled = this.displayIron < labIronCost || this.displayCrystal < labCrystalCost;
            const upgLabStoreBtn = document.getElementById('upgrade-lab-storage-btn');
            if (upgLabStoreBtn) upgLabStoreBtn.disabled = this.displayIron < storageIronCost || this.displayCrystal < storageCrystalCost;
        } else if (labBuildings) {
            labBuildings.classList.add('hidden');
        }

        if (this.planet && this.planet.research_advanced_lab) {
            const resWhCopper = document.getElementById('research-wh-copper-container');
            if (resWhCopper) {
                if (!this.planet.research_warehouse_copper && this.planet.warehouse_level >= 200) {
                    resWhCopper.classList.remove('hidden');
                    const btn = document.getElementById('research-wh-copper-btn');
                    if (btn) btn.disabled = this.displayTubes < 2500;
                } else {
                    resWhCopper.classList.add('hidden');
                }
            }

            const resDrone3 = document.getElementById('research-drone-3-container');
            if (resDrone3) {
                if (!this.planet.research_drone_upgrade_3 && this.planet.research_drone_upgrade_2) {
                    resDrone3.classList.remove('hidden');
                    const btn = document.getElementById('research-drone-3-btn');
                    if (btn) btn.disabled = this.displayTubes < 5000;
                } else {
                    resDrone3.classList.add('hidden');
                }
            }

            const resAutoRecall = document.getElementById('research-auto-recall-container');
            if (resAutoRecall) {
                if (!this.planet.research_auto_recall) {
                    resAutoRecall.classList.remove('hidden');
                    const btn = document.getElementById('research-auto-recall-btn');
                    if (btn) btn.disabled = this.displayTubes < 7500;
                } else {
                    resAutoRecall.classList.add('hidden');
                }
            }

            const resRocketWorkshop = document.getElementById('research-rocket-workshop-container');
            if (resRocketWorkshop) {
                if (!this.planet.research_rocket_workshop) {
                    resRocketWorkshop.classList.remove('hidden');
                    const btn = document.getElementById('research-rocket-workshop-btn');
                    if (btn) btn.disabled = this.displayTubes < 15000;
                } else {
                    resRocketWorkshop.classList.add('hidden');
                }
            }

            const resAlienSlot3 = document.getElementById('research-alien-slot-3-container');
            if (resAlienSlot3) {
                if (this.planet.research_rocket_workshop && !this.planet.research_alien_slot_3) {
                    resAlienSlot3.classList.remove('hidden');
                    let minesAt50 = 0;
                    researched.forEach((color) => {
                        if ((this.planet.alien_resources[color]?.lvl || 0) >= 50) minesAt50++;
                    });
                    const canAfford = this.displayTubes >= 25000 && this.displayIron >= 2000000 && this.displayCopper >= 25000;
                    const btn = document.getElementById('research-alien-slot-3-btn');
                    if (btn) {
                        btn.disabled = !canAfford || minesAt50 < 2;
                        btn.innerText = minesAt50 < 2 ? 'Vyzkoumat (Vyžaduje 2 doly Lvl 50)' : 'Vyzkoumat (25k zkum., 2M Fe, 25k Cu)';
                    }
                } else {
                    resAlienSlot3.classList.add('hidden');
                }
            }

            const resSecretMine = document.getElementById('research-secret-mine-container');
            if (resSecretMine) {
                if (this.planet.research_alien_slot_3 && !this.planet.research_secret_crystal_mine) {
                    resSecretMine.classList.remove('hidden');
                    const btn = document.getElementById('research-secret-mine-btn');
                    if (btn) btn.disabled = this.displayTubes < 30000;
                    setVal('secret-mine-note-text', `Již objeveno ${this.planet.secret_mine_discovered_count} veliteli.`);
                } else {
                    resSecretMine.classList.add('hidden');
                }
            }
        } else {
            const ids = [
                'research-wh-copper-container',
                'research-drone-3-container',
                'research-auto-recall-container',
                'research-rocket-workshop-container',
                'research-alien-slot-3-container',
                'research-secret-mine-container'
            ];
            ids.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.classList.add('hidden');
            });
        }

        const secretMineBld = document.getElementById('secret-mine-building');
        if (this.planet && this.planet.research_secret_crystal_mine) {
            if (secretMineBld) secretMineBld.classList.remove('hidden');
            const lvl = this.planet.secret_crystal_mine_level || 0;
            const cost = 1000000 + (lvl * 50000);
            setVal('secret-mine-lvl', lvl);
            setVal('secret-mine-upgrade-cost', `${(cost / 1000000).toFixed(2)}M Fe`);
            const upgBtn = document.getElementById('upgrade-secret-mine-btn');
            if (upgBtn) upgBtn.disabled = this.displayIron < cost;
            setVal('secret-mine-discovered-count-label', `Objeveno: ${this.planet.secret_mine_discovered_count} veliteli. Bonus: 2^(${this.planet.secret_mine_discovered_count}+1)`);
        } else if (secretMineBld) {
            secretMineBld.classList.add('hidden');
        }

        this.updateRocketWorkshopUI();

        const droneResContainer = document.getElementById('drone-research-container');
        const droneRes2Container = document.getElementById('drone-research-2-container');
        if (this.planet && this.planet.research_copper) {
            if (droneResContainer) {
                if (!this.planet.research_drone_upgrade) {
                    droneResContainer.classList.remove('hidden');
                    const btn = document.getElementById('research-drone-btn');
                    if (btn) btn.disabled = this.displayCopper < 100;
                } else {
                    droneResContainer.classList.add('hidden');
                }
            }
            if (droneRes2Container) {
                const researchedCount = (this.planet.researched_colors || []).length;
                if (this.planet.research_drone_upgrade && !this.planet.research_drone_upgrade_2 && researchedCount >= 2) {
                    droneRes2Container.classList.remove('hidden');
                    const btn2 = document.getElementById('research-drone-2-btn');
                    if (btn2) btn2.disabled = this.displayCopper < 500;
                } else {
                    droneRes2Container.classList.add('hidden');
                }
            }
        } else {
            if (droneResContainer) droneResContainer.classList.add('hidden');
            if (droneRes2Container) droneRes2Container.classList.add('hidden');
        }

        for (const color in this.displayAlien) {
            const el = document.getElementById(`display-res-${color}`);
            if (el) el.innerText = Math.floor(this.displayAlien[color]);
            const btn = document.getElementById(`upgrade-alien-mine-${color}`);
            if (btn) {
                const lvl = this.planet.alien_resources[color].lvl;
                btn.disabled = this.displayIron < (lvl + 1) * 500 || this.displayCrystal < (lvl + 1) * 50;
            }
        }
    },

    updateResearchUI() {
        const researched = this.planet.researched_colors || [];
        const info = document.getElementById('research-info');
        const options = document.getElementById('color-options');
        const count = researched.length;
        const maxSlots = this.planet.research_alien_slot_3 ? 3 : 2;

        if (count >= maxSlots) {
            info.innerHTML = `<p style="color: #28a745;"><strong>Všechny výzkumné sloty (${count}/${maxSlots}) jsou obsazeny.</strong></p>`;
        } else {
            const cost = count === 0 ? 100 : (count === 1 ? 2000 : 10000);
            info.innerHTML = `<p>K dispozici máš slot č. <strong>${count + 1}</strong> za <strong>${cost} krystalů</strong>.</p>`;
        }

        options.innerHTML = '';
        for (const color in this.colorNames) {
            const isResearched = researched.includes(color);
            const btn = document.createElement('button');
            btn.className = `research-btn color-${color}`;
            btn.innerText = this.colorNames[color];
            if (isResearched) {
                btn.disabled = true;
                btn.innerText += ' (Vyzkoumáno)';
            } else if (count >= maxSlots) {
                btn.disabled = true;
            } else {
                const cost = count === 0 ? 100 : (count === 1 ? 2000 : 10000);
                btn.onclick = () => this.researchColor(color);
                if (this.displayCrystal < cost) btn.style.opacity = '0.5';
            }
            options.appendChild(btn);
        }
    },
};
