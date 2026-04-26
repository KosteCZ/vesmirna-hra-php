import { rocketPartNames, rocketPartImages, colorNames, colorCodes } from './config.js';
import { gameUIMethods } from './gameUI.js';
import { gameSimulationMethods } from './gameSimulation.js';
import { gameActionMethods } from './gameActions.js';

export function createGame() {
    const game = {
        planet: null,
        displayIron: 0,
        displayEnergy: 0,
        displayCrystal: 0,
        displayCopper: 0,
        displayTubes: 0,
        displayAlien: {},
        displayDrone: 0,
        vehicleHP: 100,
        vehicleCrystals: 0,
        vehicle2HP: 100,
        vehicle2Crystals: 0,
        interval: null,
        refreshPromise: null,
        recallPending: false,
        recallVehicle2Pending: false,
        useImages: true,
        rocketPartNames,
        rocketPartImages,
        colorNames,
        colorCodes,
    };

    Object.assign(game, gameUIMethods, gameSimulationMethods, gameActionMethods);
    return game;
}
