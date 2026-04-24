@extends('layouts.app')

@section('content')
<div class="container auth-container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Login to Vault System</h4>
                </div>
                <div class="card-body">
                    <form id="loginForm">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3" id="twoFactorSection" style="display: none;">
                            <label for="two_factor_code" class="form-label">2FA Code</label>
                            <input type="text" class="form-control" id="two_factor_code" name="two_factor_code" placeholder="Enter 2FA code">
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary" id="loginBtn">
                                <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                                <span id="loginBtnText">Login</span>
                            </button>
                        </div>
                    </form>
                    <div class="text-center mt-3">
                        <p>Don't have an account? <a href="{{ route('register') }}">Register here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Master Password Modal -->
<div class="modal fade" id="masterPasswordModal" tabindex="-1" aria-labelledby="masterPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="masterPasswordModalLabel">Enter Master Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Please enter your master password to unlock your vault.</p>
                <form id="masterPasswordForm">
                    <div class="mb-3">
                        <label for="masterPassword" class="form-label">Master Password</label>
                        <input type="password" class="form-control" id="masterPassword" name="masterPassword" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary" id="unlockBtn">
                            <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                            Unlock Vault
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

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

        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(loginForm);
            const data = Object.fromEntries(formData);

            // Show loading
            loginBtn.disabled = true;
            spinner.classList.remove('d-none');
            const loginBtnText = document.getElementById('loginBtnText');
            loginBtnText.textContent = twoFactorSection.style.display === 'block' ? 'Verify 2FA' : 'Login';

            try {
                const response = await axios.post('/api/login', data);
                const payload = response.data.data;

                if (response.data.success) {
                    if (payload?.requires_2fa) {
                        // Show 2FA input
                        twoFactorSection.style.display = 'block';
                        loginBtnText.textContent = 'Verify 2FA';
                        showAlert('Please enter your 2FA code', 'info');
                    } else {
                        // Store login data and show master password modal
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
                    twoFactorSection.style.display = 'block';
                    loginBtnText.textContent = 'Verify 2FA';
                }
            } finally {
                loginBtn.disabled = false;
                spinner.classList.add('d-none');
            }
        });

        // Handle master password form
        masterPasswordForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const masterPassword = document.getElementById('masterPassword').value;

            unlockBtn.disabled = true;
            unlockSpinner.classList.remove('d-none');

            try {
                const salt = loginPayload.crypto?.salt || window.vaultCrypto.getStoredSalt(loginData.email);
                const iterations = loginPayload.crypto?.iterations || 100000;

                if (!salt) {
                    throw new Error('Missing encryption salt for key derivation.');
                }

                await window.vaultCrypto.deriveAndStoreKey(
                    masterPassword,
                    loginData.email,
                    salt,
                    iterations
                );

                masterPasswordModal.hide();
                showAlert('Login successful!', 'success');
                setTimeout(() => {
                    window.location.href = '{{ route("dashboard") }}';
                }, 1000);
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