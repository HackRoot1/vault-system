@extends('layouts.app')

@section('content')
<div class="container dashboard-container">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Vault Dashboard</h1>
                <div>
                    <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#createVaultModal">
                        Create Vault
                    </button>
                    <button class="btn btn-outline-danger" id="logoutBtn">Logout</button>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>My Vaults</h5>
                </div>
                <div class="card-body">
                    <div id="vaultsList">
                        <div class="text-center text-muted">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div id="vaultContent" class="d-none">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 id="vaultTitle">Vault Items</h5>
                        <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#createItemModal">
                            Add Item
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="itemsList">
                            <p class="text-muted">Select a vault to view items</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Vault Modal -->
<div class="modal fade" id="createVaultModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Vault</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="createVaultForm">
                    <div class="mb-3">
                        <label for="vaultName" class="form-label">Vault Name</label>
                        <input type="text" class="form-control" id="vaultName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="vaultDescription" class="form-label">Description (Optional)</label>
                        <textarea class="form-control" id="vaultDescription" name="description" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="createVaultBtn">Create Vault</button>
            </div>
        </div>
    </div>
</div>

<!-- Create Item Modal -->
<div class="modal fade" id="createItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="createItemForm">
                    <input type="hidden" name="type" value="login">
                    <div class="mb-3">
                        <label for="itemTitle" class="form-label">Title</label>
                        <input type="text" class="form-control" id="itemTitle" name="title" placeholder="GitHub" required>
                    </div>
                    <div class="mb-3">
                        <label for="itemUsername" class="form-label">Username</label>
                        <input type="text" class="form-control" id="itemUsername" name="username" placeholder="octocat" required>
                    </div>
                    <div class="mb-3">
                        <label for="itemPassword" class="form-label">Password</label>
                        <input type="password" class="form-control" id="itemPassword" name="password" placeholder="Enter password" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Final API Payload</label>
                        <pre id="itemPayloadPreview" class="bg-light border rounded p-3 small mb-0">Encrypted payload will appear here before submit.</pre>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="createItemBtn">Add Item</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let currentVaultId = null;

document.addEventListener('DOMContentLoaded', function() {
    // Check if user is authenticated
    const token = localStorage.getItem('api_token');
    if (!token) {
        window.location.href = '{{ route("login") }}';
        return;
    }

    // Set up axios with token
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;

    loadVaults();

    // Logout functionality
    document.getElementById('logoutBtn').addEventListener('click', function() {
        localStorage.removeItem('api_token');
        window.vaultCrypto.clearMemoryKey();
        window.location.href = '{{ route("login") }}';
    });

    // Create vault
    document.getElementById('createVaultBtn').addEventListener('click', createVault);

    // Create item
    document.getElementById('createItemBtn').addEventListener('click', createItem);
});

async function loadVaults() {
    try {
        const response = await axios.get('/api/vaults');
        const vaults = response.data.data;

        const vaultsList = document.getElementById('vaultsList');
        vaultsList.innerHTML = '';

        if (vaults.length === 0) {
            vaultsList.innerHTML = '<p class="text-muted">No vaults found. Create your first vault!</p>';
            return;
        }

        vaults.forEach(vault => {
            const vaultElement = document.createElement('div');
            vaultElement.className = 'mb-2';
            vaultElement.innerHTML = `
                <button class="btn btn-outline-primary w-100 text-start vault-btn" data-vault-id="${vault.id}">
                    <strong>${vault.name}</strong>
                    ${vault.description ? `<br><small class="text-muted">${vault.description}</small>` : ''}
                </button>
            `;
            vaultsList.appendChild(vaultElement);
        });

        // Add event listeners to vault buttons
        document.querySelectorAll('.vault-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const vaultId = this.dataset.vaultId;
                loadVaultItems(vaultId);
            });
        });

    } catch (error) {
        showAlert('Failed to load vaults', 'danger');
    }
}

async function loadVaultItems(vaultId) {
    currentVaultId = vaultId;

    try {
        const response = await axios.get(`/api/vaults/${vaultId}/items`);
        const items = response.data.data;

        const vaultContent = document.getElementById('vaultContent');
        const vaultTitle = document.getElementById('vaultTitle');
        const itemsList = document.getElementById('itemsList');

        vaultContent.classList.remove('d-none');

        // Get vault details
        const vaultResponse = await axios.get(`/api/vaults/${vaultId}`);
        const vault = vaultResponse.data.data;
        vaultTitle.textContent = `${vault.name} - Items`;

        itemsList.innerHTML = '';

        if (items.length === 0) {
            itemsList.innerHTML = '<p class="text-muted">No items in this vault yet.</p>';
            return;
        }

        const decryptedItems = await Promise.all(items.map(async item => {
            try {
                const data = await window.vaultCrypto.decryptVaultItem(item);

                return {
                    ...item,
                    data,
                    decryptionError: null,
                };
            } catch (error) {
                return {
                    ...item,
                    data: null,
                    decryptionError: error.message,
                };
            }
        }));

        itemsList.innerHTML = `
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Username</th>
                            <th>Password</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${decryptedItems.map(renderItemRow).join('')}
                    </tbody>
                </table>
            </div>
        `;

    } catch (error) {
        const message = error.message || 'Failed to load vault items';
        showAlert(message, 'danger');
    }
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function renderItemRow(item) {
    const createdAt = new Date(item.created_at).toLocaleDateString();

    if (item.decryptionError) {
        return `
            <tr>
                <td colspan="4" class="text-danger">${escapeHtml(item.decryptionError)}</td>
                <td><span class="badge text-bg-danger">Decryption failed</span></td>
                <td>${createdAt}</td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteItem(${item.id})">Delete</button>
                </td>
            </tr>
        `;
    }

    return `
        <tr>
            <td>${escapeHtml(item.data?.title)}</td>
            <td>${escapeHtml(item.data?.username)}</td>
            <td><code>${escapeHtml(item.data?.password)}</code></td>
            <td>${escapeHtml(item.type)}</td>
            <td><span class="badge text-bg-success">Decrypted</span></td>
            <td>${createdAt}</td>
            <td class="text-end">
                <button class="btn btn-sm btn-outline-danger" onclick="deleteItem(${item.id})">Delete</button>
            </td>
        </tr>
    `;
}

async function createVault() {
    const form = document.getElementById('createVaultForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);

    try {
        const response = await axios.post('/api/vaults', data);

        if (response.data.success) {
            showAlert('Vault created successfully!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('createVaultModal')).hide();
            form.reset();
            loadVaults();
        }
    } catch (error) {
        const message = error.response?.data?.message || 'Failed to create vault';
        showAlert(message, 'danger');
    }
}

async function createItem() {
    if (!currentVaultId) {
        showAlert('Please select a vault first', 'warning');
        return;
    }

    if (!window.vaultCryptoSession.encryptionKey) {
        showAlert('Your encryption key is not in memory. Please log in again before adding items.', 'danger');
        return;
    }

    const form = document.getElementById('createItemForm');
    const payloadPreview = document.getElementById('itemPayloadPreview');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);

    try {
        const vaultItem = {
            title: data.title,
            username: data.username,
            password: data.password,
        };

        const encryptedPayload = await window.vaultCrypto.encryptVaultItem(vaultItem);
        const apiPayload = {
            type: data.type,
            encrypted_data: encryptedPayload.encrypted_data,
            iv: encryptedPayload.iv,
            tag: encryptedPayload.tag,
        };

        payloadPreview.textContent = JSON.stringify(apiPayload, null, 2);

        const response = await axios.post(`/api/vaults/${currentVaultId}/items`, apiPayload);

        if (response.data.success) {
            showAlert('Item created successfully!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('createItemModal')).hide();
            form.reset();
            payloadPreview.textContent = JSON.stringify(apiPayload, null, 2);
            loadVaultItems(currentVaultId);
        }
    } catch (error) {
        const message = error.response?.data?.message || error.message || 'Failed to create item';
        showAlert(message, 'danger');
    }
}

async function deleteItem(itemId) {
    if (!confirm('Are you sure you want to delete this item?')) {
        return;
    }

    try {
        const response = await axios.delete(`/api/vaults/${currentVaultId}/items/${itemId}`);

        if (response.data.success) {
            showAlert('Item deleted successfully!', 'success');
            loadVaultItems(currentVaultId);
        }
    } catch (error) {
        showAlert('Failed to delete item', 'danger');
    }
}

</script>
@endpush
@endsection
