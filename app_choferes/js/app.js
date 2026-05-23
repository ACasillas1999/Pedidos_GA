// Configuración global de la aplicación
const CONFIG = {
    // La API (el backend actual en PHP) se encuentra en un nivel superior en la carpeta 'app'
    API_URL: '../app/'
};

// Registro del Service Worker para funcionalidades PWA (offline, instalación)
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('./service-worker.js')
            .then(registration => {
                console.log('ServiceWorker registrado con éxito en el scope:', registration.scope);
            })
            .catch(error => {
                console.log('Falló el registro del ServiceWorker:', error);
            });
    });
}

// Lógica para el botón manual de instalación PWA
let deferredPrompt;
window.addEventListener('beforeinstallprompt', (e) => {
    // Prevenir que Chrome muestre el prompt automáticamente si preferimos el botón
    e.preventDefault();
    deferredPrompt = e;
    
    // Mostrar el botón de instalación en index.html o main.html si existe
    const installContainer = document.getElementById('installContainer');
    if (installContainer) {
        installContainer.classList.remove('hidden');
    }
});

document.addEventListener('DOMContentLoaded', () => {
    const installBtn = document.getElementById('installBtn');
    if (installBtn) {
        installBtn.addEventListener('click', async () => {
            if (deferredPrompt) {
                // Mostrar el prompt nativo
                deferredPrompt.prompt();
                // Esperar a que el usuario responda
                const { outcome } = await deferredPrompt.userChoice;
                if (outcome === 'accepted') {
                    console.log('El usuario aceptó instalar la app');
                } else {
                    console.log('El usuario rechazó instalar la app');
                }
                deferredPrompt = null;
                // Ocultar el botón después de responder
                const installContainer = document.getElementById('installContainer');
                if (installContainer) {
                    installContainer.classList.add('hidden');
                }
            }
        });
    }
});
