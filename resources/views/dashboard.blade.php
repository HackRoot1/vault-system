@extends('layouts.app')

@section('content')
<style>
    .dashboard-shell {
        max-width: 1200px;
        margin: 2rem auto;
    }

    .state-box {
        border: 1px dashed #dee2e6;
        border-radius: .5rem;
        padding: 2rem;
        text-align: center;
    }

    .vault-btn.active {
        color: #fff;
    }

    .vault-meta {
        font-size: .8rem;
    }
</style>

<div class="container dashboard-shell">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="mb-1">Vault Dashboard</h1>
                    <p class="text-muted mb-0">Encrypted vaults and decrypted items for the current session.</p>
                </div>
                <div>
                    <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#createVaultModal">
                        Create Vault
                    </button>
                    <button class="btn btn-outline-danger" id="logoutBtn">Logout</button>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">My Vaults</h5>
                    <span class="badge text-bg-secondary" id="vaultCount">0</span>
                </div>
                <div class="card-body">
                    <div id="vaultError" class="alert alert-danger d-none" role="alert"></div>
                    <div id="vaultsList">
                        <div class="state-box text-muted">
                            <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                            Loading vaults...
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div id="vaultEmptyState" class="card shadow-sm">
                <div class="card-body">
                    <div class="state-box text-muted">
                        Select a vault to view decrypted items.
                    </div>
                </div>
            </div>

            <div id="vaultContent" class="d-none">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h5 id="vaultTitle" class="mb-0">Vault Items</h5>
                            <small class="text-muted" id="vaultSubtitle">Items are decrypted locally in your browser.</small>
                        </div>
                        <div>
                            <button class="btn btn-sm btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#uploadFileModal">
                                Upload File
                            </button>
                            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#createItemModal">
                                Add Item
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="itemError" class="alert alert-danger d-none" role="alert"></div>
                        <div id="itemsList">
                            <div class="state-box text-muted">Select a vault to view items.</div>
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
                <button type="button" class="btn btn-primary" id="createVaultBtn">
                    <span class="btn-label">Create Vault</span>
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Upload File Modal -->
<div class="modal fade" id="uploadFileModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload Encrypted File</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="uploadFileForm">
                    <div class="mb-3">
                        <label for="vaultFile" class="form-label">File</label>
                        <input type="file" class="form-control" id="vaultFile" name="file" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Final API Payload</label>
                        <pre id="filePayloadPreview" class="bg-light border rounded p-3 small mb-0">Encrypted file metadata will appear here before submit.</pre>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="uploadFileBtn">
                    <span class="btn-label">Encrypt & Upload</span>
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                </button>
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
                    <div class="mb-3">
                        <label for="itemType" class="form-label">Type</label>
                        <select class="form-select" id="itemType" name="type" required>
                            <option value="login">Login</option>
                            <option value="note">Secure Note</option>
                            <option value="credit_card">Credit Card</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="itemTitle" class="form-label">Title</label>
                        <input type="text" class="form-control" id="itemTitle" name="title" placeholder="GitHub" required>
                    </div>
                    <div class="mb-3">
                        <label for="itemUsername" class="form-label">Username</label>
                        <input type="text" class="form-control" id="itemUsername" name="username" placeholder="octocat">
                    </div>
                    <div class="mb-3">
                        <label for="itemPassword" class="form-label">Secret</label>
                        <input type="password" class="form-control" id="itemPassword" name="password" placeholder="Password, code, or card data">
                    </div>
                    <div class="mb-3">
                        <label for="itemNotes" class="form-label">Notes</label>
                        <textarea class="form-control" id="itemNotes" name="notes" rows="3" placeholder="Optional notes"></textarea>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Final API Payload</label>
                        <pre id="itemPayloadPreview" class="bg-light border rounded p-3 small mb-0">Encrypted payload will appear here before submit.</pre>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="createItemBtn">
                    <span class="btn-label">Add Item</span>
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let currentVaultId = null;
let currentVaultName = null;

document.addEventListener('DOMContentLoaded', async function() {
    // Check if user is authenticated
    const token = localStorage.getItem('api_token');
    if (!token) {
        window.location.href = '{{ route("login") }}';
        return;
    }

    // Load session from storage first
    await (async () => {
        const session = await window.vaultCrypto.loadSessionFromStorage();
        if (session) {
            window.vaultCryptoSession.encryptionKey = session.key;
            window.vaultCryptoSession.email = session.email;
            window.vaultCryptoSession.salt = session.salt;
            window.vaultCryptoSession.iterations = session.iterations;
        }
    })();

    // Check if encryption key is available
    if (!window.vaultCryptoSession.encryptionKey) {
        showAlert('Your session has expired. Please log in again.', 'danger');
        setTimeout(() => {
            window.location.href = '{{ route("login") }}';
        }, 2000);
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

    // Upload encrypted file
    document.getElementById('uploadFileBtn').addEventListener('click', uploadEncryptedFile);
});

async function loadVaults() {
    const vaultsList = document.getElementById('vaultsList');
    const vaultError = document.getElementById('vaultError');
    const vaultCount = document.getElementById('vaultCount');

    setInlineError(vaultError, null);
    vaultCount.textContent = '0';
    vaultsList.innerHTML = renderLoadingState('Loading vaults...');

    try {
        const response = await axios.get('/api/vaults');
        const vaults = response.data.data;

        vaultsList.innerHTML = '';
        vaultCount.textContent = vaults.length;

        if (vaults.length === 0) {
            vaultsList.innerHTML = renderEmptyState('No vaults found. Create your first vault.');
            return;
        }

        vaults.forEach(vault => {
            const vaultElement = document.createElement('div');
            vaultElement.className = 'mb-2';
            vaultElement.innerHTML = `
                <button class="btn ${String(vault.id) === String(currentVaultId) ? 'btn-primary active' : 'btn-outline-primary'} w-100 text-start vault-btn" data-vault-id="${vault.id}" data-vault-name="${escapeHtml(vault.name)}">
                    <span class="d-block fw-semibold">${escapeHtml(vault.name)}</span>
                    <span class="vault-meta ${String(vault.id) === String(currentVaultId) ? 'text-white-50' : 'text-muted'}">
                        Created ${formatDate(vault.created_at)}
                    </span>
                </button>
            `;
            vaultsList.appendChild(vaultElement);
        });

        // Add event listeners to vault buttons
        document.querySelectorAll('.vault-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const vaultId = this.dataset.vaultId;
                currentVaultName = this.dataset.vaultName;
                loadVaultItems(vaultId);
            });
        });

    } catch (error) {
        const message = getApiErrorMessage(error, 'Failed to load vaults');
        vaultsList.innerHTML = renderEmptyState('Vaults could not be loaded.');
        setInlineError(vaultError, message);
        showAlert(message, 'danger');
    }
}

async function loadVaultItems(vaultId) {
    currentVaultId = vaultId;
    const vaultContent = document.getElementById('vaultContent');
    const vaultEmptyState = document.getElementById('vaultEmptyState');
    const vaultTitle = document.getElementById('vaultTitle');
    const vaultSubtitle = document.getElementById('vaultSubtitle');
    const itemsList = document.getElementById('itemsList');
    const itemError = document.getElementById('itemError');

    vaultEmptyState.classList.add('d-none');
    vaultContent.classList.remove('d-none');
    setInlineError(itemError, null);
    itemsList.innerHTML = renderLoadingState('Loading and decrypting vault items...');
    setActiveVaultButton(vaultId);

    try {
        const [vaultResponse, itemsResponse, filesResponse] = await Promise.all([
            axios.get(`/api/vaults/${vaultId}`),
            axios.get(`/api/vaults/${vaultId}/items`),
            axios.get(`/api/vaults/${vaultId}/files`),
        ]);
        const vault = vaultResponse.data.data;
        const items = itemsResponse.data.data;
        const files = filesResponse.data.data;

        currentVaultName = vault.name;
        vaultTitle.textContent = vault.name;
        vaultSubtitle.textContent = `${items.length} item${items.length === 1 ? '' : 's'} and ${files.length} file${files.length === 1 ? '' : 's'}`;

        if (!window.vaultCryptoSession.encryptionKey) {
            itemsList.innerHTML = renderEmptyState('Your session has expired. Please log in again.');
            setTimeout(() => {
                window.location.href = '{{ route("login") }}';
            }, 2000);
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
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h6 class="mb-0">Items</h6>
                <span class="badge text-bg-light">${decryptedItems.length}</span>
            </div>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Username</th>
                            <th>Secret</th>
                            <th>Notes</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${decryptedItems.length ? decryptedItems.map(renderItemRow).join('') : '<tr><td colspan="8" class="text-muted">No items in this vault yet.</td></tr>'}
                    </tbody>
                </table>
            </div>
            <div class="d-flex align-items-center justify-content-between mt-4 mb-2">
                <h6 class="mb-0">Files</h6>
                <span class="badge text-bg-light">${files.length}</span>
            </div>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${files.length ? files.map(renderFileRow).join('') : '<tr><td colspan="4" class="text-muted">No files in this vault yet.</td></tr>'}
                    </tbody>
                </table>
            </div>
        `;

    } catch (error) {
        const message = getApiErrorMessage(error, 'Failed to load vault items');
        itemsList.innerHTML = renderEmptyState('Items could not be loaded.');
        setInlineError(itemError, message);
        showAlert(message, 'danger');
    }
}

function setActiveVaultButton(vaultId) {
    document.querySelectorAll('.vault-btn').forEach(button => {
        const isActive = String(button.dataset.vaultId) === String(vaultId);
        const meta = button.querySelector('.vault-meta');

        button.classList.toggle('btn-primary', isActive);
        button.classList.toggle('active', isActive);
        button.classList.toggle('btn-outline-primary', !isActive);

        if (meta) {
            meta.classList.toggle('text-white-50', isActive);
            meta.classList.toggle('text-muted', !isActive);
        }
    });
}

function renderFileRow(file) {
    const createdAt = new Date(file.created_at).toLocaleDateString();

    return `
        <tr>
            <td>${escapeHtml(file.file_name)}</td>
            <td><span class="badge text-bg-success">Encrypted</span></td>
            <td>${createdAt}</td>
            <td class="text-end">
                <button class="btn btn-sm btn-outline-primary me-2" onclick="downloadEncryptedFile(${file.id}, this)">Download</button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteFile(${file.id}, this)">Delete</button>
            </td>
        </tr>
    `;
}

function renderLoadingState(message) {
    return `
        <div class="state-box text-muted">
            <div class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></div>
            ${escapeHtml(message)}
        </div>
    `;
}

function renderEmptyState(message) {
    return `<div class="state-box text-muted">${escapeHtml(message)}</div>`;
}

function setInlineError(element, message) {
    if (!element) {
        return;
    }

    if (!message) {
        element.classList.add('d-none');
        element.textContent = '';
        return;
    }

    element.textContent = message;
    element.classList.remove('d-none');
}

function formatDate(value) {
    if (!value) {
        return 'unknown date';
    }

    return new Date(value).toLocaleDateString();
}

function getApiErrorMessage(error, fallback) {
    const response = error.response?.data;

    if (response?.message) {
        return response.message;
    }

    if (response?.errors) {
        return Object.values(response.errors).flat().join(' ');
    }

    return error.message || fallback;
}

function setButtonLoading(button, isLoading, loadingText = 'Working...') {
    if (!button) {
        return;
    }

    const label = button.querySelector('.btn-label');
    const spinner = button.querySelector('.spinner-border');

    button.disabled = isLoading;

    if (label) {
        if (!button.dataset.originalLabel) {
            button.dataset.originalLabel = label.textContent;
        }

        label.textContent = isLoading ? loadingText : button.dataset.originalLabel;
    }

    if (spinner) {
        spinner.classList.toggle('d-none', !isLoading);
    }
}

function setPlainButtonLoading(button, isLoading, loadingText = 'Working...') {
    if (!button) {
        return;
    }

    if (!button.dataset.originalLabel) {
        button.dataset.originalLabel = button.textContent;
    }

    button.disabled = isLoading;
    button.textContent = isLoading ? loadingText : button.dataset.originalLabel;
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
                <td colspan="5" class="text-danger">${escapeHtml(item.decryptionError)}</td>
                <td><span class="badge text-bg-danger">Decryption failed</span></td>
                <td>${createdAt}</td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteItem(${item.id}, this)">Delete</button>
                </td>
            </tr>
        `;
    }

    return `
        <tr>
            <td>${escapeHtml(item.data?.title)}</td>
            <td>${escapeHtml(item.data?.username)}</td>
            <td><code>${escapeHtml(item.data?.password)}</code></td>
            <td>${escapeHtml(item.data?.notes)}</td>
            <td>${escapeHtml(item.type)}</td>
            <td><span class="badge text-bg-success">Decrypted</span></td>
            <td>${createdAt}</td>
            <td class="text-end">
                <button class="btn btn-sm btn-outline-danger" onclick="deleteItem(${item.id}, this)">Delete</button>
            </td>
        </tr>
    `;
}

async function createVault() {
    const form = document.getElementById('createVaultForm');
    const button = document.getElementById('createVaultBtn');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);

    setButtonLoading(button, true, 'Creating...');

    try {
        const response = await axios.post('/api/vaults', data);

        if (response.data.success) {
            showAlert('Vault created successfully!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('createVaultModal')).hide();
            form.reset();
            loadVaults();
        }
    } catch (error) {
        const message = getApiErrorMessage(error, 'Failed to create vault');
        showAlert(message, 'danger');
    } finally {
        setButtonLoading(button, false);
    }
}

async function createItem() {
    if (!currentVaultId) {
        showAlert('Please select a vault first', 'warning');
        return;
    }

    if (!window.vaultCryptoSession.encryptionKey) {
        showAlert('Your session has expired. Please log in again.', 'danger');
        setTimeout(() => {
            window.location.href = '{{ route("login") }}';
        }, 2000);
        return;
    }

    const form = document.getElementById('createItemForm');
    const button = document.getElementById('createItemBtn');
    const payloadPreview = document.getElementById('itemPayloadPreview');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);

    setButtonLoading(button, true, 'Encrypting...');

    try {
        const vaultItem = {
            title: data.title,
            username: data.username || '',
            password: data.password || '',
            notes: data.notes || '',
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
            payloadPreview.textContent = 'Encrypted payload will appear here before submit.';
            loadVaultItems(currentVaultId);
        }
    } catch (error) {
        const message = getApiErrorMessage(error, 'Failed to create item');
        showAlert(message, 'danger');
    } finally {
        setButtonLoading(button, false);
    }
}

async function uploadEncryptedFile() {
    if (!currentVaultId) {
        showAlert('Please select a vault first', 'warning');
        return;
    }

    if (!window.vaultCryptoSession.encryptionKey) {
        showAlert('Your session has expired. Please log in again.', 'danger');
        setTimeout(() => {
            window.location.href = '{{ route("login") }}';
        }, 2000);
        return;
    }

    const form = document.getElementById('uploadFileForm');
    const button = document.getElementById('uploadFileBtn');
    const fileInput = document.getElementById('vaultFile');
    const payloadPreview = document.getElementById('filePayloadPreview');
    const file = fileInput.files[0];

    if (!file) {
        showAlert('Please choose a file first', 'warning');
        return;
    }

    setButtonLoading(button, true, 'Encrypting...');

    try {
        const encrypted = await window.vaultCrypto.encryptFile(file);
        const formData = new FormData();

        formData.append('file', encrypted.encryptedBlob, `${file.name}.enc`);
        formData.append('file_name', file.name);
        formData.append('iv', encrypted.iv);
        formData.append('tag', encrypted.tag);

        payloadPreview.textContent = JSON.stringify({
            file: `${file.name}.enc`,
            file_name: file.name,
            iv: encrypted.iv,
            tag: encrypted.tag,
            original_size: encrypted.size,
        }, null, 2);

        const response = await axios.post(`/api/vaults/${currentVaultId}/files`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        if (response.data.success) {
            showAlert('File encrypted and uploaded successfully!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('uploadFileModal')).hide();
            form.reset();
            payloadPreview.textContent = 'Encrypted file metadata will appear here before submit.';
            loadVaultItems(currentVaultId);
        }
    } catch (error) {
        const message = getApiErrorMessage(error, 'Failed to upload file');
        showAlert(message, 'danger');
    } finally {
        setButtonLoading(button, false);
    }
}

async function downloadEncryptedFile(fileId, button = null) {
    if (!window.vaultCryptoSession.encryptionKey) {
        showAlert('Your session has expired. Please log in again.', 'danger');
        setTimeout(() => {
            window.location.href = '{{ route("login") }}';
        }, 2000);
        return;
    }

    setPlainButtonLoading(button, true, 'Downloading...');

    try {
        const urlResponse = await axios.get(`/api/vaults/${currentVaultId}/files/${fileId}/download-url`);
        const downloadUrl = urlResponse.data.data.download_url;
        const encryptedResponse = await axios.get(downloadUrl, {
            responseType: 'arraybuffer',
        });

        const iv = encryptedResponse.headers['x-file-iv'];
        const tag = encryptedResponse.headers['x-file-tag'];
        const encodedName = encryptedResponse.headers['x-file-name'];
        const fileName = encodedName ? decodeURIComponent(encodedName) : `vault-file-${fileId}`;
        const blob = await window.vaultCrypto.decryptFile(encryptedResponse.data, iv, tag);
        const objectUrl = URL.createObjectURL(blob);
        const link = document.createElement('a');

        link.href = objectUrl;
        link.download = fileName;
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(objectUrl);
    } catch (error) {
        const message = getApiErrorMessage(error, 'Failed to download file');
        showAlert(message, 'danger');
    } finally {
        setPlainButtonLoading(button, false);
    }
}

async function deleteFile(fileId, button = null) {
    if (!confirm('Are you sure you want to delete this file?')) {
        return;
    }

    setPlainButtonLoading(button, true, 'Deleting...');

    try {
        const response = await axios.delete(`/api/vaults/${currentVaultId}/files/${fileId}`);

        if (response.data.success) {
            showAlert('File deleted successfully!', 'success');
            loadVaultItems(currentVaultId);
        }
    } catch (error) {
        showAlert(getApiErrorMessage(error, 'Failed to delete file'), 'danger');
    } finally {
        setPlainButtonLoading(button, false);
    }
}

async function deleteItem(itemId, button = null) {
    if (!confirm('Are you sure you want to delete this item?')) {
        return;
    }

    setPlainButtonLoading(button, true, 'Deleting...');

    try {
        const response = await axios.delete(`/api/vaults/${currentVaultId}/items/${itemId}`);

        if (response.data.success) {
            showAlert('Item deleted successfully!', 'success');
            loadVaultItems(currentVaultId);
        }
    } catch (error) {
        showAlert(getApiErrorMessage(error, 'Failed to delete item'), 'danger');
    } finally {
        setPlainButtonLoading(button, false);
    }
}

</script>
@endpush
@endsection
