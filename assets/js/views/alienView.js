export const alienViewMethods = {
    updateAlienUI() {
        const researched = this.planet.researched_colors || [];
        const resContainer = document.getElementById('alien-resources');
        const bldContainer = document.getElementById('alien-buildings');
        if (researched.length === 0) {
            resContainer.classList.add('hidden');
            bldContainer.classList.add('hidden');
            return;
        }

        resContainer.classList.remove('hidden');
        bldContainer.classList.remove('hidden');
        resContainer.innerHTML = '';
        bldContainer.innerHTML = '';

        researched.forEach((color) => {
            const data = this.planet.alien_resources[color];
            const colorCode = this.getColorCode(color);

            const resCard = document.createElement('div');
            resCard.className = `res-card ${color}`;
            const resIcon = this.renderIcon('icon-alien-res', `am-${color}.png`, 24);
            resCard.innerHTML = `
                <div class="res-icon" style="color: ${colorCode}">${resIcon}</div>
                <div class="res-data">
                    <span class="label">${this.colorNames[color]} materiĂˇl</span>
                    <span class="value" id="display-res-${color}">${Math.floor(this.displayAlien[color])}</span>
                    <span class="prod">+${data.prod.toFixed(2)}/s</span>
                </div>
            `;
            resContainer.appendChild(resCard);

            const ironCost = (data.lvl + 1) * 500;
            const crystalCost = (data.lvl + 1) * 50;
            const bldCard = document.createElement('div');
            bldCard.className = 'building-card';
            bldCard.innerHTML = `
                <div style="color: ${colorCode}">${this.renderIcon('icon-alien-res', `am-${color}.png`, 40)}</div>
                <h3>${this.colorNames[color]} dĹŻl</h3>
                <p class="lvl">ĂšroveĹ ${data.lvl}</p>
                <p class="desc">Produkuje vzĂˇcnĂ˝ ${this.colorNames[color].toLowerCase()} materiĂˇl.</p>
                <button onclick="game.upgradeAlienMine('${color}')" id="upgrade-alien-mine-${color}">
                    VylepĹˇit <span>(${ironCost} Fe, ${crystalCost} Kryst.)</span>
                </button>
            `;
            bldContainer.appendChild(bldCard);
        });
    },

    getColorCode(color) {
        return this.colorCodes[color] || '#fff';
    },
};
