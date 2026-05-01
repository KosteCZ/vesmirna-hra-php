export function createAuth(game) {
    return {
        isSignUp: false,

        toggleMode() {
            this.isSignUp = !this.isSignUp;
            const title = document.getElementById('auth-title');
            if (title) title.innerText = this.isSignUp ? 'Registrace do Kolonie' : 'Přihlášení na palubu';
            const submit = document.getElementById('auth-submit');
            if (submit) submit.innerText = this.isSignUp ? 'Založit kolonii' : 'Vstoupit do hry';
            const switchText = document.getElementById('auth-switch-text');
            if (switchText) switchText.innerText = this.isSignUp ? 'Už máš účet?' : 'Ještě nemáš kolonii?';
            const switchBtn = document.getElementById('auth-switch-btn');
            if (switchBtn) switchBtn.innerText = this.isSignUp ? 'Přihlásit se' : 'Zaregistrovat se';
            const playerNameGroup = document.getElementById('playerName-group');
            if (playerNameGroup) playerNameGroup.classList.toggle('hidden', !this.isSignUp);
        },

        async init() {
            const res = await fetch('auth.php?action=status');
            const data = await res.json();
            const loader = document.getElementById('loading-section');
            if (loader) loader.classList.add('hidden');

            const stored = localStorage.getItem('game_use_images');
            const useImages = stored === null ? true : stored === 'true';
            const graphicsToggle = document.getElementById('graphics-toggle');
            if (graphicsToggle) graphicsToggle.checked = useImages;
            game.useImages = useImages;

            if (data.authenticated) {
                game.showDashboard(data.user);
            } else {
                const authSect = document.getElementById('auth-section');
                if (authSect) authSect.classList.remove('hidden');
            }
        },

        async logout() {
            await fetch('auth.php?action=logout');
            location.reload();
        },
    };
}
