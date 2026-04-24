@extends('layouts.app')

@section('title', 'Register | Vault System')
@section('authLayout', 'true')

@section('content')
<div class="auth-card">
    <x-card title="Create your vault" subtitle="Start with a login password and a separate master password." icon="person-plus">
        <form id="registerForm" novalidate>
            <x-input label="Full name" name="name" id="name" placeholder="Alex Morgan" required />
            <x-input label="Email address" name="email" id="email" type="email" placeholder="you@example.com" required />
            <x-input label="Password" name="password" id="password" type="password" required minlength="8" />
            <x-input label="Confirm password" name="password_confirmation" id="password_confirmation" type="password" required />
            <x-input label="Master password" name="master_password" id="master_password" type="password" required minlength="8" help="Used only for encrypting vault data. Keep it separate from your login password." />
            <div class="progress mb-3" style="height: 8px;">
                <div class="progress-bar" id="passwordStrength" style="width: 0%"></div>
            </div>
            <x-input label="Confirm master password" name="master_password_confirmation" id="master_password_confirmation" type="password" required />

            <div class="d-grid">
                <x-button type="submit" variant="primary" loading id="registerBtn">Create Account</x-button>
            </div>
        </form>

        <div class="text-center mt-4">
            <span class="text-muted">Already have an account?</span>
            <a href="{{ route('login') }}" class="text-decoration-none">Login</a>
        </div>
    </x-card>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const registerForm = document.getElementById('registerForm');
    const registerBtn = document.getElementById('registerBtn');
    const spinner = registerBtn.querySelector('.spinner-border');
    const strength = document.getElementById('passwordStrength');

    document.getElementById('master_password').addEventListener('input', function() {
        const value = this.value;
        let score = 0;
        if (value.length >= 8) score += 25;
        if (/[A-Z]/.test(value)) score += 25;
        if (/[0-9]/.test(value)) score += 25;
        if (/[^A-Za-z0-9]/.test(value)) score += 25;
        strength.style.width = `${score}%`;
        strength.className = `progress-bar ${score < 50 ? 'bg-danger' : score < 75 ? 'bg-warning' : 'bg-success'}`;
    });

    registerForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(registerForm));

        if (data.password !== data.password_confirmation) {
            showAlert('Passwords do not match.', 'danger');
            return;
        }
        if (data.master_password !== data.master_password_confirmation) {
            showAlert('Master passwords do not match.', 'danger');
            return;
        }

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
                showAlert('Registration successful.', 'success');
                setTimeout(() => window.location.href = '{{ route("dashboard") }}', 700);
            }
        } catch (error) {
            showAlert(error.response?.data?.message || 'Registration failed.', 'danger');
        } finally {
            registerBtn.disabled = false;
            spinner.classList.add('d-none');
        }
    });
});
</script>
@endpush
@endsection
