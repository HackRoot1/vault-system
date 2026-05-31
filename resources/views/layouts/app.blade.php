<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Vault System'))</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/axios@1.6.0/dist/axios.min.js"></script>

    <style>
        :root {
            --app-bg: #f6f7fb;
            --app-sidebar: #111827;
            --app-sidebar-muted: #9ca3af;
            --app-border: #e5e7eb;
            --app-text: #111827;
        }

        body {
            background: var(--app-bg);
            color: var(--app-text);
        }

        .auth-shell {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 2rem 1rem;
        }

        .auth-card {
            max-width: 460px;
            width: 100%;
        }

        .app-shell {
            min-height: 100vh;
            display: flex;
        }

        .app-sidebar {
            width: 268px;
            background: var(--app-sidebar);
            color: #fff;
            position: fixed;
            inset: 0 auto 0 0;
            z-index: 1040;
            transform: translateX(0);
            transition: transform .2s ease;
        }

        .app-sidebar .brand {
            height: 72px;
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: 0 1.25rem;
            border-bottom: 1px solid rgba(255,255,255,.08);
            font-weight: 700;
        }

        .brand-mark {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            background: #2563eb;
        }

        .app-nav {
            padding: 1rem;
        }

        .app-nav a {
            color: var(--app-sidebar-muted);
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .75rem .85rem;
            border-radius: .75rem;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: .25rem;
        }

        .app-nav a:hover,
        .app-nav a.active {
            background: rgba(255,255,255,.1);
            color: #fff;
        }

        .app-main {
            flex: 1;
            margin-left: 268px;
            min-width: 0;
        }

        .app-topbar {
            height: 72px;
            background: rgba(255,255,255,.92);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--app-border);
            position: sticky;
            top: 0;
            z-index: 1020;
        }

        .app-content {
            padding: 1.5rem;
        }

        .app-card {
            border: 1px solid var(--app-border);
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(15, 23, 42, .04);
        }

        .app-icon {
            width: 42px;
            height: 42px;
            border-radius: .9rem;
            display: inline-grid;
            place-items: center;
            background: #eff6ff;
            color: #2563eb;
            flex: 0 0 auto;
        }

        .state-box {
            border: 1px dashed var(--app-border);
            border-radius: .9rem;
            padding: 2rem;
            text-align: center;
            background: #fff;
        }

        .masked-secret {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            letter-spacing: .08em;
        }

        .drop-zone {
            border: 1px dashed #9ca3af;
            border-radius: 1rem;
            padding: 2rem;
            text-align: center;
            background: #f9fafb;
            cursor: pointer;
        }

        .drop-zone.dragover {
            border-color: #2563eb;
            background: #eff6ff;
        }

        .toast-container {
            z-index: 1080;
        }

        @media (max-width: 991.98px) {
            .app-sidebar {
                transform: translateX(-100%);
            }

            .app-sidebar.show {
                transform: translateX(0);
            }

            .app-main {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    @hasSection('authLayout')
        <main class="auth-shell">
            @yield('content')
        </main>
    @else
        <div class="app-shell">
            <aside class="app-sidebar" id="appSidebar">
                <div class="brand">
                    <div class="brand-mark"><i class="bi bi-shield-lock"></i></div>
                    <span>Vault System</span>
                </div>
                <nav class="app-nav">
                    <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="bi bi-grid-1x2"></i> Dashboard
                    </a>
                    <a href="{{ route('vaults') }}" class="{{ request()->routeIs('vaults') ? 'active' : '' }}">
                        <i class="bi bi-safe"></i> Vaults
                    </a>
                    <a href="{{ route('items') }}" class="{{ request()->routeIs('items') ? 'active' : '' }}">
                        <i class="bi bi-key"></i> Items
                    </a>
                    <a href="{{ route('files') }}" class="{{ request()->routeIs('files') ? 'active' : '' }}">
                        <i class="bi bi-folder2-open"></i> Files
                    </a>
                    <a href="{{ route('settings') }}" class="{{ request()->routeIs('settings') ? 'active' : '' }}">
                        <i class="bi bi-gear"></i> Settings
                    </a>
                </nav>
            </aside>

            <div class="app-main">
                <header class="app-topbar">
                    <div class="h-100 px-3 px-lg-4 d-flex align-items-center justify-content-between gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <button class="btn btn-light d-lg-none" type="button" id="sidebarToggle">
                                <i class="bi bi-list"></i>
                            </button>
                            <div>
                                <div class="text-muted small">@yield('breadcrumb', 'Vault')</div>
                                <h1 class="h4 mb-0">@yield('pageTitle', 'Dashboard')</h1>
                            </div>
                        </div>

                        <div class="d-none d-md-flex flex-grow-1 justify-content-center">
                            <div class="input-group" style="max-width: 420px;">
                                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                                <input class="form-control" id="globalSearch" type="search" placeholder="Search vault data">
                            </div>
                        </div>

                        <div class="dropdown">
                            <button class="btn btn-white border dropdown-toggle" data-bs-toggle="dropdown" type="button">
                                <i class="bi bi-person-circle me-1"></i>
                                Account
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><button class="dropdown-item" type="button" id="topbarLockBtn"><i class="bi bi-lock me-2"></i>Lock vault</button></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><button class="dropdown-item text-danger" type="button" id="topbarLogoutBtn"><i class="bi bi-box-arrow-right me-2"></i>Logout</button></li>
                            </ul>
                        </div>
                    </div>
                </header>

                <main class="app-content">
                    @yield('content')
                </main>
            </div>
        </div>
    @endif

    <div class="toast-container position-fixed top-0 end-0 p-3" id="toastContainer"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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

    // ── Base64 Helpers ──────────────────────────────────────────────────
    //
    // Flutter uses dart:convert base64.encode which produces:
    //   - Standard base64 alphabet: A-Z a-z 0-9 + /
    //   - WITH padding (=) always
    //
    // These helpers must match that exactly.

    arrayBufferToBase64(buffer) {
        const bytes = new Uint8Array(buffer);
        let binary = '';
        for (let i = 0; i < bytes.length; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return btoa(binary);
    },

    base64ToUint8Array(base64Str) {
        let s = base64Str
            .replace(/-/g, '+')
            .replace(/_/g, '/');

        const pad = s.length % 4;
        if (pad === 2) s += '==';
        else if (pad === 3) s += '=';

        const binary = atob(s);
        const bytes = new Uint8Array(binary.length);
        for (let i = 0; i < binary.length; i++) {
            bytes[i] = binary.charCodeAt(i);
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

    async importRawAesKey(base64Key) {
        return crypto.subtle.importKey(
            'raw',
            this.base64ToUint8Array(base64Key),
            { name: 'AES-GCM', length: 256 },
            true,
            ['encrypt', 'decrypt']
        );
    },

    async storeKeyInSession(masterPassword, email, salt, iterations) {
        if (!window.vaultCryptoSession.encryptionKey) {
            throw new Error('No key is available to store for this session.');
        }

        const rawKey = await crypto.subtle.exportKey('raw', window.vaultCryptoSession.encryptionKey);
        sessionStorage.setItem('vault_session', JSON.stringify({
            email,
            salt,
            iterations,
            rawKey: this.arrayBufferToBase64(rawKey),
        }));
    },

    async loadSessionFromStorage() {
        const dataStr = sessionStorage.getItem('vault_session');
        if (!dataStr) return null;

        const data = JSON.parse(dataStr);
        if (!data.rawKey) {
            return {
                key: null,
                email: data.email,
                salt: data.salt,
                iterations: data.iterations,
            };
        }

        return {
            key: await this.importRawAesKey(data.rawKey),
            email: data.email,
            salt: data.salt,
            iterations: data.iterations,
        };
    },

    async deriveAndStoreKey(password, email, saltHex, iterations = this.iterations) {
        const key = await this.deriveKey(password, saltHex, iterations);
        this.storeSalt(email, saltHex);
        window.vaultCryptoSession.encryptionKey = key;
        window.vaultCryptoSession.salt = saltHex;
        window.vaultCryptoSession.iterations = iterations;
        window.vaultCryptoSession.email = String(email).trim().toLowerCase();
        await this.storeKeyInSession(password, window.vaultCryptoSession.email, saltHex, iterations);
        return key;
    },

    clearMemoryKey() {
        window.vaultCryptoSession.encryptionKey = null;
        window.vaultCryptoSession.salt = null;
        window.vaultCryptoSession.iterations = this.iterations;
        window.vaultCryptoSession.email = null;
        sessionStorage.removeItem('vault_session');
    },

    lockVault() {
        const sessionData = {
            email: window.vaultCryptoSession.email,
            salt: window.vaultCryptoSession.salt,
            iterations: window.vaultCryptoSession.iterations || this.iterations,
        };
        window.vaultCryptoSession.encryptionKey = null;
        if (sessionData.email && sessionData.salt) {
            sessionStorage.setItem('vault_session', JSON.stringify(sessionData));
        } else {
            sessionStorage.removeItem('vault_session');
        }
    },

    hexToUint8Array(hex) {
        const clean = hex.replace(/\s/g, '').toLowerCase();
        const bytes = new Uint8Array(clean.length / 2);
        for (let i = 0; i < bytes.length; i++) {
            bytes[i] = parseInt(clean.substring(i * 2, i * 2 + 2), 16);
        }
        return bytes;
    },

    // ── Key Derivation ──────────────────────────────────────────────────
    //
    // Must match Flutter exactly:
    //   Pbkdf2(macAlgorithm: Hmac.sha256(), iterations: N, bits: 256)
    //
    async deriveKey(masterPassword, saltHex, iterations) {
        const passwordBytes = new TextEncoder().encode(masterPassword);
        const salt = this.hexToUint8Array(saltHex);

        const baseKey = await crypto.subtle.importKey(
            'raw',
            passwordBytes,
            { name: 'PBKDF2' },
            false,
            ['deriveKey']
        );

        const aesKey = await crypto.subtle.deriveKey(
            {
                name: 'PBKDF2',
                salt: salt,
                iterations: iterations,
                hash: 'SHA-256',
            },
            baseKey,
            { name: 'AES-GCM', length: 256 },
            true,
            ['encrypt', 'decrypt']
        );

        return aesKey;
    },

    // ── Session Storage ─────────────────────────────────────────────────
    async initSession(masterPassword, saltHex, iterations) {
        const key = await this.deriveKey(masterPassword, saltHex, iterations);
        window.vaultCryptoSession = {
            encryptionKey: key,
            saltHex: saltHex,
            iterations: iterations,
        };
        return key;
    },

    // ── Encryption ──────────────────────────────────────────────────────
    async encryptVaultItem(plainObject) {
        const key = window.vaultCryptoSession.encryptionKey;
        const iv = crypto.getRandomValues(new Uint8Array(12));
        const plainText = JSON.stringify(plainObject);
        const plainBytes = new TextEncoder().encode(plainText);

        const encryptedBuffer = await crypto.subtle.encrypt(
            { name: 'AES-GCM', iv: iv, tagLength: 128 },
            key,
            plainBytes
        );

        const encryptedBytes = new Uint8Array(encryptedBuffer);
        const ciphertext = encryptedBytes.slice(0, encryptedBytes.length - 16);
        const tag = encryptedBytes.slice(encryptedBytes.length - 16);

        return {
            encrypted_data: this.arrayBufferToBase64(ciphertext),
            iv: this.arrayBufferToBase64(iv),
            tag: this.arrayBufferToBase64(tag),
        };
    },

    // ── Decryption ──────────────────────────────────────────────────────
    async decryptVaultItem({ encrypted_data, iv, tag }) {
        const key = window.vaultCryptoSession.encryptionKey;

        const ciphertext = this.base64ToUint8Array(encrypted_data);
        const ivBytes = this.base64ToUint8Array(iv);
        const tagBytes = this.base64ToUint8Array(tag);

        if (ivBytes.length !== 12) {
            throw new Error(`Invalid IV length: ${ivBytes.length} bytes (expected 12)`);
        }
        if (tagBytes.length !== 16) {
            throw new Error(`Invalid tag length: ${tagBytes.length} bytes (expected 16)`);
        }

        const combined = new Uint8Array(ciphertext.length + tagBytes.length);
        combined.set(ciphertext, 0);
        combined.set(tagBytes, ciphertext.length);

        const decryptedBuffer = await crypto.subtle.decrypt(
            { name: 'AES-GCM', iv: ivBytes, tagLength: 128 },
            key,
            combined
        );

        const plainText = new TextDecoder().decode(decryptedBuffer);
        return JSON.parse(plainText);
    },

    // ── File Encryption ─────────────────────────────────────────────────
    async encryptFile(file) {
        const key = window.vaultCryptoSession.encryptionKey;
        const iv = crypto.getRandomValues(new Uint8Array(12));

        const fileBuffer = await this.readFileAsArrayBuffer(file);
        const encryptedBuffer = await crypto.subtle.encrypt(
            { name: 'AES-GCM', iv, tagLength: 128 },
            key,
            fileBuffer
        );

        const encryptedBytes = new Uint8Array(encryptedBuffer);
        const ciphertext = encryptedBytes.slice(0, encryptedBytes.length - 16);
        const tag = encryptedBytes.slice(encryptedBytes.length - 16);

        return {
            encryptedBlob: new Blob([ciphertext], { type: 'application/octet-stream' }),
            fileName: file.name,
            mimeType: file.type || 'application/octet-stream',
            iv: this.arrayBufferToBase64(iv),
            tag: this.arrayBufferToBase64(tag),
        };
    },

    async decryptFile(encryptedArrayBuffer, ivBase64, tagBase64) {
        const key = window.vaultCryptoSession.encryptionKey;
        const iv = this.base64ToUint8Array(ivBase64);
        const tagBytes = this.base64ToUint8Array(tagBase64);

        const ciphertext = new Uint8Array(encryptedArrayBuffer);
        const combined = new Uint8Array(ciphertext.length + tagBytes.length);
        combined.set(ciphertext, 0);
        combined.set(tagBytes, ciphertext.length);

        const decryptedBuffer = await crypto.subtle.decrypt(
            { name: 'AES-GCM', iv, tagLength: 128 },
            key,
            combined
        );

        return new Blob([decryptedBuffer]);
    },

    readFileAsArrayBuffer(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => resolve(reader.result);
            reader.onerror = () => reject(reader.error);
            reader.readAsArrayBuffer(file);
        });
    },

    // ── Debug Helper ────────────────────────────────────────────────────
    async debugExportKey() {
        const key = window.vaultCryptoSession.encryptionKey;
        const raw = await crypto.subtle.exportKey('raw', key);
        const hex = [...new Uint8Array(raw)]
            .map(b => b.toString(16).padStart(2, '0'))
            .join('');
        console.log('[VaultCrypto] derived key hex:', hex);
        console.log('[VaultCrypto] key length bytes:', new Uint8Array(raw).length);
        return hex;
    },

};

        (async () => {
            if (typeof window.vaultCrypto.loadSessionFromStorage === 'function') {
                const session = await window.vaultCrypto.loadSessionFromStorage();
                if (session) {
                    window.vaultCryptoSession.encryptionKey = session.key;
                    window.vaultCryptoSession.email = session.email;
                    window.vaultCryptoSession.salt = session.salt;
                    window.vaultCryptoSession.iterations = session.iterations;
                }
            }
        })();

        const token = localStorage.getItem('api_token');
        if (token) {
            window.axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
        }

        window.axios.interceptors.response.use(
            response => response,
            error => {
                if (error.response?.status === 401) {
                    localStorage.removeItem('api_token');
                    window.vaultCrypto.clearMemoryKey();
                    if (window.location.pathname !== '/login') {
                        window.location.href = '/login';
                    }
                }
                return Promise.reject(error);
            }
        );

        window.showAlert = function(message, type = 'info') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            const palette = type === 'danger' ? 'text-bg-danger' : type === 'success' ? 'text-bg-success' : type === 'warning' ? 'text-bg-warning' : 'text-bg-primary';
            toast.className = `toast align-items-center border-0 ${palette}`;
            toast.role = 'alert';
            toast.ariaLive = 'assertive';
            toast.ariaAtomic = 'true';

            const wrapper = document.createElement('div');
            wrapper.className = 'd-flex';
            const body = document.createElement('div');
            body.className = 'toast-body';
            body.appendChild(document.createTextNode(String(message)));
            const close = document.createElement('button');
            close.type = 'button';
            close.className = 'btn-close btn-close-white me-2 m-auto';
            close.setAttribute('data-bs-dismiss', 'toast');
            close.ariaLabel = 'Close';
            wrapper.appendChild(body);
            wrapper.appendChild(close);
            toast.appendChild(wrapper);
            container.appendChild(toast);

            const toastInstance = new bootstrap.Toast(toast, { delay: 4500 });
            toast.addEventListener('hidden.bs.toast', () => toast.remove());
            toastInstance.show();
        };

        let sessionTimeout = 30 * 60 * 1000;
        let timeoutId;

        function resetSessionTimeout() {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(checkSessionTimeout, sessionTimeout);
        }

        function checkSessionTimeout() {
            if (typeof window.handleVaultAutoLock === 'function') {
                window.handleVaultAutoLock();
                return;
            }

            localStorage.removeItem('api_token');
            window.vaultCrypto.clearMemoryKey();
            if (!document.body.classList.contains('auth-page')) {
                showAlert('Your session has expired due to inactivity. Please log in again.', 'warning');
                setTimeout(() => window.location.href = '/login', 2000);
            }
        }

        ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'].forEach(event => {
            document.addEventListener(event, resetSessionTimeout, false);
        });
        resetSessionTimeout();

        document.getElementById('sidebarToggle')?.addEventListener('click', () => {
            document.getElementById('appSidebar')?.classList.toggle('show');
        });

        document.getElementById('topbarLogoutBtn')?.addEventListener('click', () => {
            if (typeof window.logoutUser === 'function') {
                window.logoutUser();
                return;
            }
            localStorage.removeItem('api_token');
            window.vaultCrypto.clearMemoryKey();
            window.location.href = '/login';
        });

        document.getElementById('topbarLockBtn')?.addEventListener('click', () => {
            if (typeof window.lockVaultNow === 'function') {
                window.lockVaultNow();
            }
        });
    </script>

    @stack('scripts')
</body>
</html>
