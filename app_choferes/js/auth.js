document.addEventListener('DOMContentLoaded', () => {
    // 1. Verificar si ya existe una sesión activa
    checkSession();

    // 2. Asociar el evento submit al formulario
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }
});

function checkSession() {
    const isLoggedIn = localStorage.getItem('isLoggedIn');
    // Si ya está logueado y estamos en el login, redirigir al main.html
    if (isLoggedIn === 'true' && window.location.pathname.endsWith('index.html')) {
        window.location.href = 'main.html';
    }
}

async function handleLogin(e) {
    e.preventDefault(); // Evitar que el form recargue la página
    
    // Obtener elementos del DOM
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const submitBtn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    const btnLoading = document.getElementById('btnLoading');
    const errorMessage = document.getElementById('errorMessage');
    const errorText = document.getElementById('errorText');

    const username = usernameInput.value.trim();
    const password = passwordInput.value;

    // Cambiar estado visual a "Cargando"
    btnText.textContent = 'Verificando...';
    btnLoading.classList.remove('hidden');
    submitBtn.disabled = true;
    submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
    errorMessage.classList.add('hidden');

    try {
        // El login.php original espera recibir los datos por POST (FormData), no en JSON.
        const formData = new FormData();
        formData.append('username', username);
        formData.append('password', password);

        const response = await fetch(`${CONFIG.API_URL}login.php`, {
            method: 'POST',
            body: formData
        });

        // La respuesta del PHP original es "OK" o "ERROR", no es un JSON.
        const responseText = await response.text();
        const data = responseText.trim();

        if (data === "OK") {
            // Emular el comportamiento de SharedPreferences de Android
            localStorage.setItem('isLoggedIn', 'true');
            localStorage.setItem('username', username);
            // Redirigir a la pantalla principal
            window.location.href = 'main.html';
        } else {
            // Mostrar error si la respuesta es "ERROR" u otra cosa
            showError('Usuario o contraseña incorrectos.');
        }

    } catch (error) {
        console.error('Error durante el login:', error);
        showError('No se pudo conectar con el servidor. Verifica tu conexión.');
    } finally {
        // Restaurar botón a su estado original
        btnText.textContent = 'Entrar';
        btnLoading.classList.add('hidden');
        submitBtn.disabled = false;
        submitBtn.classList.remove('opacity-75', 'cursor-not-allowed');
    }

    function showError(msg) {
        errorText.textContent = msg;
        errorMessage.classList.remove('hidden');
    }
}
