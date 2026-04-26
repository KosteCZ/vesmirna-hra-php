import { resourceViewMethods } from './views/resourceView.js';
import { researchViewMethods } from './views/researchView.js';
import { vehicleViewMethods } from './views/vehicleView.js';
import { workshopViewMethods } from './views/workshopView.js';
import { alienViewMethods } from './views/alienView.js';

export const gameUIMethods = {
    ...resourceViewMethods,
    ...researchViewMethods,
    ...vehicleViewMethods,
    ...workshopViewMethods,
    ...alienViewMethods,
};
