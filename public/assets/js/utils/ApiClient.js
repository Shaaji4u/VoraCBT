export class ApiClient {
    constructor(baseUrl = '/api') {
        this.baseUrl = baseUrl;
    }

    async request(endpoint, method = 'GET', body = null, customHeaders = {}) {
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            ...customHeaders
        };

        // Attempt to get token from localStorage or cookie if implemented
        const token = localStorage.getItem('auth_token');
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }

        const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
        if (csrfTokenMeta && csrfTokenMeta.content) {
            headers['X-CSRF-Token'] = csrfTokenMeta.content;
        }

        const config = {
            method,
            headers,
        };

        if (body) {
            config.body = JSON.stringify(body);
        }

        try {
            const response = await fetch(`${this.baseUrl}${endpoint}`, config);

            if (response.status === 401) {
                // Unauthorized, redirect to login
                // Check if we are already on login page to avoid loop
                if (!window.location.pathname.includes('/login')) {
                    window.location.href = '/login?redirect=' + encodeURIComponent(window.location.pathname);
                }
                return null;
            }

            // Handle 204 No Content
            if (response.status === 204) {
                return null;
            }

            const data = await response.json();

            if (!response.ok) {
                // Structure: { status: 'error', message: '...', errors: [] }
                throw new Error(data.message || `Request failed with status ${response.status}`);
            }

            return data;
        } catch (error) {
            console.error('API Request Error:', error);
            throw error;
        }
    }

    get(endpoint, headers = {}) {
        return this.request(endpoint, 'GET', null, headers);
    }

    post(endpoint, body, headers = {}) {
        return this.request(endpoint, 'POST', body, headers);
    }

    put(endpoint, body, headers = {}) {
        return this.request(endpoint, 'PUT', body, headers);
    }

    delete(endpoint, headers = {}) {
        return this.request(endpoint, 'DELETE', null, headers);
    }
}
