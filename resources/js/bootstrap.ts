import axios from 'axios';

// Configure axios for Laravel Sanctum SPA authentication
// Following Laravel Sanctum documentation recommendations

// Set base URL for your Laravel application
axios.defaults.baseURL = 'http://localhost:8000';

// Enable credentials for session-based authentication
axios.defaults.withCredentials = true;

// Enable XSRF token handling (for newer versions of Axios)
if (typeof (axios.defaults as any).withXSRFToken !== 'undefined') {
    (axios.defaults as any).withXSRFToken = true;
}

// Set default headers for Laravel
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['Accept'] = 'application/json';

// Make axios available globally if needed
(window as any).axios = axios;
