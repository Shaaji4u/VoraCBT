import { ApiClient } from '../utils/ApiClient.js';

document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('loginForm');
    if (!loginForm) return;

    const loginBtn = document.getElementById('loginBtn');
    const btnText = document.getElementById('btnText');
    const btnIcon = document.getElementById('btnIcon');
    const btnSpinner = document.getElementById('btnSpinner');
    const errorAlert = document.getElementById('loginError');

    const api = new ApiClient('/auth'); // Using /auth as base URL for login

    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        // Clear previous errors
        errorAlert.classList.add('d-none');
        errorAlert.textContent = '';

        // Show loading state
        loginBtn.setAttribute('disabled', 'disabled');
        btnText.textContent = 'Signing in...';
        btnIcon.classList.add('d-none');
        btnSpinner.classList.remove('d-none');

        const formData = new FormData(loginForm);
        const data = Object.fromEntries(formData.entries());

        try {
            // Send application/json payload as implemented in AuthController
            const response = await api.post('/login', data);

            btnText.textContent = 'Success!';
            btnSpinner.classList.add('d-none');
            btnIcon.textContent = 'check_circle';
            btnIcon.classList.remove('d-none');

            // Redirect on success
            setTimeout(() => {
                window.location.href = response.data?.redirect || '/';
            }, 500);

        } catch (error) {
            // Restore button state
            loginBtn.removeAttribute('disabled');
            btnText.textContent = 'Sign In';
            btnSpinner.classList.add('d-none');
            btnIcon.textContent = 'arrow_forward';
            btnIcon.classList.remove('d-none');

            // Show error
            errorAlert.textContent = error.message;
            errorAlert.classList.remove('d-none');
        }
    });
});
