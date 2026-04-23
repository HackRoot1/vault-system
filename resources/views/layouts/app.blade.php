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
        window.vaultCryptoSession = {
            encryptionKey: null,
            salt: null,
            iterations: 100000,
            email: null,
        };

        window.vaultCrypto = {
            iterations: 100000,

            passwordToUint8Array(password) {
                return new TextEncoder().encode(password);
            },

            hexToUint8Array(hex) {
                if (!hex || hex.length % 2 !== 0) {
                    throw new Error('Salt must be a valid hex string.');
                }

                const bytes = new Uint8Array(hex.length / 2);

                for (let i = 0; i < hex.length; i += 2) {
                    bytes[i / 2] = parseInt(hex.slice(i, i + 2), 16);
                }

                return bytes;
            },

            getSaltStorageKey(email) {
                return `vault_crypto_salt:${String(email).trim().toLowerCase()}`;
            },

            storeSalt(email, salt) {
                localStorage.setItem(this.getSaltStorageKey(email), salt);
            },

            getStoredSalt(email) {
                return localStorage.getItem(this.getSaltStorageKey(email));
            },

            arrayBufferToBase64(buffer) {
                const bytes = buffer instanceof Uint8Array ? buffer : new Uint8Array(buffer);
                let binary = '';

                bytes.forEach(byte => {
                    binary += String.fromCharCode(byte);
                });

                return btoa(binary);
            },

            base64ToUint8Array(base64) {
                const binary = atob(base64);
                const bytes = new Uint8Array(binary.length);

                for (let i = 0; i < binary.length; i++) {
                    bytes[i] = binary.charCodeAt(i);
                }

                return bytes;
            },

            async deriveAesKey(password, saltHex, iterations = this.iterations) {
                const passwordBytes = this.passwordToUint8Array(password);
                const saltBytes = this.hexToUint8Array(saltHex);

                const baseKey = await crypto.subtle.importKey(
                    'raw',
                    passwordBytes,
                    'PBKDF2',
                    false,
                    ['deriveKey']
                );

                return crypto.subtle.deriveKey(
                    {
                        name: 'PBKDF2',
                        salt: saltBytes,
                        iterations,
                        hash: 'SHA-256',
                    },
                    baseKey,
                    {
                        name: 'AES-GCM',
                        length: 256,
                    },
                    false,
                    ['encrypt', 'decrypt']
                );
            },

            async deriveAndStoreKey(password, email, saltHex, iterations = this.iterations) {
                const key = await this.deriveAesKey(password, saltHex, iterations);

                this.storeSalt(email, saltHex);
                window.vaultCryptoSession.encryptionKey = key;
                window.vaultCryptoSession.salt = saltHex;
                window.vaultCryptoSession.iterations = iterations;
                window.vaultCryptoSession.email = String(email).trim().toLowerCase();

                return key;
            },

            async encryptVaultItem(item) {
                const key = window.vaultCryptoSession.encryptionKey;

                if (!key) {
                    throw new Error('Encryption key is not available in memory. Please log in again.');
                }

                const iv = crypto.getRandomValues(new Uint8Array(12));
                const plaintext = new TextEncoder().encode(JSON.stringify(item));
                const encryptedBuffer = await crypto.subtle.encrypt(
                    {
                        name: 'AES-GCM',
                        iv,
                        tagLength: 128,
                    },
                    key,
                    plaintext
                );

                const encryptedBytes = new Uint8Array(encryptedBuffer);
                const tagLength = 16;
                const ciphertext = encryptedBytes.slice(0, encryptedBytes.length - tagLength);
                const tag = encryptedBytes.slice(encryptedBytes.length - tagLength);

                return {
                    encrypted_data: this.arrayBufferToBase64(ciphertext),
                    iv: this.arrayBufferToBase64(iv),
                    tag: this.arrayBufferToBase64(tag),
                };
            },

            async decryptVaultItem({ encrypted_data, iv, tag }) {
                const key = window.vaultCryptoSession.encryptionKey;

                if (!key) {
                    throw new Error('Encryption key is not available in memory. Please log in again.');
                }

                const ciphertext = this.base64ToUint8Array(encrypted_data);
                const ivBytes = this.base64ToUint8Array(iv);
                const tagBytes = this.base64ToUint8Array(tag);
                const encryptedBytes = new Uint8Array(ciphertext.length + tagBytes.length);

                encryptedBytes.set(ciphertext, 0);
                encryptedBytes.set(tagBytes, ciphertext.length);

                try {
                    const decryptedBuffer = await crypto.subtle.decrypt(
                        {
                            name: 'AES-GCM',
                            iv: ivBytes,
                            tagLength: 128,
                        },
                        key,
                        encryptedBytes
                    );

                    const plaintext = new TextDecoder().decode(decryptedBuffer);

                    return JSON.parse(plaintext);
                } catch (error) {
                    throw new Error('Unable to decrypt item. The in-memory key may be missing or incorrect.');
                }
            },

            clearMemoryKey() {
                window.vaultCryptoSession.encryptionKey = null;
                window.vaultCryptoSession.salt = null;
                window.vaultCryptoSession.iterations = this.iterations;
                window.vaultCryptoSession.email = null;
            },
        };

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
                    window.vaultCrypto.clearMemoryKey();
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
