<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Vault System') }}</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Axios -->
    <script src="https://cdn.jsdelivr.net/npm/axios@1.6.0/dist/axios.min.js"></script>

    <!-- Custom CSS -->
    <style>
        .auth-container {
            max-width: 400px;
            margin: 5rem auto;
        }
        .dashboard-container {
            max-width: 1200px;
            margin: 2rem auto;
        }
        .alert {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
        }
    </style>
</head>
<body>
    <div id="app">
        @yield('content')
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Setup Axios -->
    <script>
        window.axios = axios;
        window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

        // Set up axios interceptor to include Authorization header
        const token = localStorage.getItem('api_token');
        if (token) {
            window.axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
        }

        // Global error handler for axios
        window.axios.interceptors.response.use(
            response => response,
            error => {
                if (error.response?.status === 401) {
                    // Token expired or invalid
                    localStorage.removeItem('api_token');
                    if (window.location.pathname !== '/login') {
                        window.location.href = '/login';
                    }
                }
                return Promise.reject(error);
            }
        );

        // Global alert function
        window.showAlert = function(message, type = 'info') {
            const alertContainer = document.createElement('div');
            alertContainer.className = `alert alert-${type} alert-dismissible fade show`;
            alertContainer.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            document.body.appendChild(alertContainer);

            // Auto remove after 5 seconds
            setTimeout(() => {
                if (alertContainer.parentNode) {
                    alertContainer.remove();
                }
            }, 5000);
        };
    </script>

    @stack('scripts')
</body>
</html>