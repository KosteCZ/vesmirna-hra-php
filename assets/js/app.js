import { createGame } from './gameState.js';
import { createAuth } from './auth.js';

const game = createGame();
const auth = createAuth(game);

window.game = game;
window.auth = auth;

document.getElementById('auth-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const playerName = document.getElementById('playerName').value;

    const formData = new FormData();
    formData.append('email', email);
    formData.append('password', password);
    if (auth.isSignUp) formData.append('playerName', playerName);

    const action = auth.isSignUp ? 'register' : 'login';
    const res = await fetch(`auth.php?action=${action}`, {
        method: 'POST',
        body: formData,
    });
    const data = await res.json();
    if (data.success) {
        auth.init();
    } else {
        alert(data.error);
    }
});

auth.init();
setInterval(() => {
    game.fetchLeaderboard();
    game.fetchGlobalStats();
}, 5000);
