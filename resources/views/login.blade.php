@extends('layouts.app')

@section('title', 'Login | Vault System')
@section('authLayout', 'true')

@section('content')
<div class="auth-card">
    <x-card title="Welcome back" subtitle="Sign in to unlock your encrypted workspace." icon="shield-lock">
        <form id="loginForm" novalidate>
            <x-input label="Email address" name="email" id="email" type="email" placeholder="you@example.com" required />

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="input-group input-group-lg">
                    <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password">
                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">Show</button>
                </div>
                <div class="invalid-feedback d-block" data-field-error="password"></div>
            </div>

            <div class="mb-3 d-none" id="twoFactorSection">
                <label for="two_factor_code" class="form-label">2FA code</label>
                <input type="text" class="form-control form-control-lg" id="two_factor_code" name="two_factor_code" placeholder="123456">
            </div>

            <div class="d-flex align-items-center justify-content-between mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="rememberMe" name="remember">
                    <label class="form-check-label" for="rememberMe">Remember me</label>
                </div>
                <a href="{{ route('register') }}" class="text-decoration-none">Create account</a>
            </div>

            <div class="d-grid">
                <x-button type="submit" variant="primary" loading id="loginBtn">
                    <span id="loginBtnText">Login</span>
                </x-button>
            </div>
        </form>
    </x-card>
</div>

<x-modal id="masterPasswordModal" title="Unlock Vault" static>
    <div class="modal-body">
        <p class="text-muted">Enter your master password to derive your browser encryption key.</p>
        <form id="masterPasswordForm">
            <x-input label="Master password" name="masterPassword" id="masterPassword" type="password" required autocomplete="current-password" />
        </form>
    </div>
    <div class="modal-footer">
        <x-button type="submit" variant="primary" loading id="unlockBtn" form="masterPasswordForm">Unlock Vault</x-button>
    </div>
</x-modal>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const loginBtn = document.getElementById('loginBtn');
    const spinner = loginBtn.querySelector('.spinner-border');
    const twoFactorSection = document.getElementById('twoFactorSection');
    const masterPasswordModal = new bootstrap.Modal(document.getElementById('masterPasswordModal'));
    const masterPasswordForm = document.getElementById('masterPasswordForm');
    const unlockBtn = document.getElementById('unlockBtn');
    const unlockSpinner = unlockBtn.querySelector('.spinner-border');

    let loginData = null;
    let loginPayload = null;

    document.getElementById('togglePassword').addEventListener('click', function() {
        const input = document.getElementById('password');
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        this.textContent = show ? 'Hide' : 'Show';
    });

    loginForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(loginForm));
        loginBtn.disabled = true;
        spinner.classList.remove('d-none');
        document.getElementById('loginBtnText').textContent = twoFactorSection.classList.contains('d-none') ? 'Logging in...' : 'Verifying...';

        try {
            const response = await axios.post('/api/login', data);
            const payload = response.data.data;

            if (response.data.success) {
                if (payload?.requires_2fa) {
                    twoFactorSection.classList.remove('d-none');
                    document.getElementById('loginBtnText').textContent = 'Verify 2FA';
                    showAlert('Please enter your 2FA code.', 'info');
                } else {
                    loginData = data;
                    loginPayload = payload;
                    localStorage.setItem('api_token', payload.token);
                    window.axios.defaults.headers.common['Authorization'] = `Bearer ${payload.token}`;
                    masterPasswordModal.show();
                }
            }
        } catch (error) {
            const message = error.response?.data?.message || 'Login failed';
            showAlert(message, 'danger');
            if (error.response?.data?.requires_2fa) {
                twoFactorSection.classList.remove('d-none');
                document.getElementById('loginBtnText').textContent = 'Verify 2FA';
            }
        } finally {
            loginBtn.disabled = false;
            spinner.classList.add('d-none');
            if (twoFactorSection.classList.contains('d-none')) {
                document.getElementById('loginBtnText').textContent = 'Login';
            }
        }
    });

    masterPasswordForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        unlockBtn.disabled = true;
        unlockSpinner.classList.remove('d-none');

        try {
            const salt = loginPayload.crypto?.salt || window.vaultCrypto.getStoredSalt(loginData.email);
            const iterations = loginPayload.crypto?.iterations || 100000;
            if (!salt) throw new Error('Missing encryption salt for key derivation.');

            await window.vaultCrypto.deriveAndStoreKey(
                document.getElementById('masterPassword').value,
                loginData.email,
                salt,
                iterations
            );

            masterPasswordModal.hide();
            showAlert('Login successful.', 'success');
            setTimeout(() => window.location.href = '{{ route("dashboard") }}', 600);
        } catch (error) {
            showAlert('Failed to unlock vault: ' + error.message, 'danger');
        } finally {
            unlockBtn.disabled = false;
            unlockSpinner.classList.add('d-none');
        }
    });
});
</script>
@endpush
@endsection
