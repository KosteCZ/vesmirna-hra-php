export function createAuth(game) {
    return {
        isSignUp: false,

        toggleMode() {
            this.isSignUp = !this.isSignUp;
            document.getElementById('auth-title').innerText = this.isSignUp ? 'Registrace do Kolonie' : 'PĹ™ihlĂˇĹˇenĂ­ na Palubu';
            document.getElementById('auth-submit').innerText = this.isSignUp ? 'ZaloĹľit kolonii' : 'Vstoupit do hry';
            document.getElementById('auth-switch-text').innerText = this.isSignUp ? 'UĹľ mĂˇĹˇ ĂşÄŤet?' : 'JeĹˇtÄ› nemĂˇĹˇ kolonii?';
            document.getElementById('auth-switch-btn').innerText = this.isSignUp ? 'PĹ™ihlĂˇsit se' : 'Zaregistrovat se';
            document.getElementById('playerName-group').classList.toggle('hidden', !this.isSignUp);
        },

        async init() {
            const res = await fetch('auth.php?action=status');
            const data = await res.json();
            document.getElementById('loading-section').classList.add('hidden');

            const stored = localStorage.getItem('game_use_images');
            const useImages = stored === null ? true : stored === 'true';
            document.getElementById('graphics-toggle').checked = useImages;
            game.useImages = useImages;

            if (data.authenticated) {
                game.showDashboard(data.user);
            } else {
                document.getElementById('auth-section').classList.remove('hidden');
            }
        },

        async logout() {
            await fetch('auth.php?action=logout');
            location.reload();
        },
    };
}
