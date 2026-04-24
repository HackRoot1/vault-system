@extends('layouts.app')

@section('content')
<div class="container auth-container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">Register for Vault System</h4>
                </div>
                <div class="card-body">
                    <form id="registerForm">
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required minlength="8">
                        </div>
                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                        </div>
                        <div class="mb-3">
                            <label for="master_password" class="form-label">Master Password</label>
                            <input type="password" class="form-control" id="master_password" name="master_password" required minlength="8">
                            <div class="form-text">This password will be used to encrypt your vault data. Keep it secure and separate from your login password.</div>
                        </div>
                        <div class="mb-3">
                            <label for="master_password_confirmation" class="form-label">Confirm Master Password</label>
                            <input type="password" class="form-control" id="master_password_confirmation" name="master_password_confirmation" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success" id="registerBtn">
                                <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                                Register
                            </button>
                        </div>
                    </form>
                    <div class="text-center mt-3">
                        <p>Already have an account? <a href="{{ route('login') }}">Login here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const registerForm = document.getElementById('registerForm');
        const registerBtn = document.getElementById('registerBtn');
        const spinner = registerBtn.querySelector('.spinner-border');

        registerForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(registerForm);
            const data = Object.fromEntries(formData);

            // Validate passwords match
            if (data.password !== data.password_confirmation) {
                showAlert('Passwords do not match', 'danger');
                return;
            }

            // Validate master passwords match
            if (data.master_password !== data.master_password_confirmation) {
                showAlert('Master passwords do not match', 'danger');
                return;
            }

            // Show loading
            registerBtn.disabled = true;
            spinner.classList.remove('d-none');

            try {
                const response = await axios.post('/api/register', data);
                const payload = response.data.data;

                if (response.data.success) {
                    localStorage.setItem('api_token', payload.token);
                    window.axios.defaults.headers.common['Authorization'] = `Bearer ${payload.token}`;

                    await window.vaultCrypto.deriveAndStoreKey(
                        data.master_password,
                        data.email,
                        payload.crypto.salt,
                        payload.crypto.iterations || 100000
                    );

                    showAlert('Registration successful!', 'success');
                    setTimeout(() => {
                        window.location.href = '{{ route("dashboard") }}';
                    }, 1000);
                }
            } catch (error) {
                const message = error.response?.data?.message || 'Registration failed';
                showAlert(message, 'danger');
            } finally {
                registerBtn.disabled = false;
                spinner.classList.add('d-none');
            }
        });
    });
</script>
@endpush
@endsection
