@extends('layouts.app')

@php
    $activeSection = $section ?? 'dashboard';
    $titles = [
        'dashboard' => 'Dashboard',
        'vaults' => 'Vaults',
        'items' => 'Items',
        'files' => 'Files',
        'settings' => 'Settings',
    ];
@endphp

@section('title', ($titles[$activeSection] ?? 'Dashboard').' | Vault System')
@section('pageTitle', $titles[$activeSection] ?? 'Dashboard')
@section('breadcrumb', 'Vault System / '.($titles[$activeSection] ?? 'Dashboard'))

@section('content')
<div class="d-flex flex-column gap-4" data-dashboard-app>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <p class="text-muted mb-1">Secure workspace</p>
            <h2 class="h4 mb-0" id="sectionHeading">{{ $titles[$activeSection] ?? 'Dashboard' }}</h2>
        </div>
        <div class="d-flex gap-2">
            <x-button variant="outline-secondary" icon="lock" id="lockVaultBtn">Lock</x-button>
            <x-button variant="outline-primary" icon="safe" data-bs-toggle="modal" data-bs-target="#vaultModal">Add Vault</x-button>
            <x-button variant="primary" icon="plus-lg" class="requires-unlocked" data-bs-toggle="modal" data-bs-target="#itemModal">Add Item</x-button>
        </div>
    </div>

    <section id="dashboardSection" class="app-section">
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <x-card title="Total Vaults" icon="safe">
                    <div class="display-6 fw-bold" id="totalVaults">0</div>
                    <p class="text-muted mb-0">Encrypted containers</p>
                </x-card>
            </div>
            <div class="col-md-4">
                <x-card title="Total Items" icon="key">
                    <div class="display-6 fw-bold" id="totalItems">0</div>
                    <p class="text-muted mb-0">Decrypted locally</p>
                </x-card>
            </div>
            <div class="col-md-4">
                <x-card title="Total Files" icon="folder2-open">
                    <div class="display-6 fw-bold" id="totalFiles">0</div>
                    <p class="text-muted mb-0">Browser encrypted</p>
                </x-card>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <x-card title="Recent Items" subtitle="Latest decrypted vault entries" icon="clock-history">
                    <div id="recentItemsList" class="table-responsive"></div>
                </x-card>
            </div>
            <div class="col-lg-4">
                <x-card title="Quick Actions" subtitle="Common vault workflows" icon="lightning">
                    <div class="d-grid gap-2">
                        <x-button variant="primary" icon="safe" data-bs-toggle="modal" data-bs-target="#vaultModal">Create Vault</x-button>
                        <x-button variant="outline-primary" icon="plus-lg" class="requires-unlocked" data-bs-toggle="modal" data-bs-target="#itemModal">Add Item</x-button>
                        <x-button variant="outline-secondary" icon="upload" class="requires-unlocked" data-bs-toggle="modal" data-bs-target="#fileModal">Upload File</x-button>
                    </div>
                </x-card>
            </div>
        </div>
    </section>

    <section id="vaultsSection" class="app-section d-none">
        <x-card title="Vaults" subtitle="Create and manage encrypted vaults" icon="safe">
            <div id="vaultGrid" class="row g-3"></div>
        </x-card>
    </section>

    <section id="itemsSection" class="app-section d-none">
        <x-card title="Items" subtitle="Secrets are decrypted in the browser and masked by default" icon="key">
            <div class="row g-3 align-items-end mb-3">
                <div class="col-md-5">
                    <label class="form-label" for="itemVaultFilter">Vault</label>
                    <select class="form-select" id="itemVaultFilter"></select>
                </div>
                <div class="col-md-7 text-md-end">
                    <x-button variant="primary" icon="plus-lg" class="requires-unlocked" data-bs-toggle="modal" data-bs-target="#itemModal">Add Item</x-button>
                </div>
            </div>
            <div id="itemsTable" class="table-responsive"></div>
        </x-card>
    </section>

    <section id="filesSection" class="app-section d-none">
        <x-card title="Files" subtitle="Upload encrypted files and decrypt downloads locally" icon="folder2-open">
            <div class="row g-3 align-items-end mb-3">
                <div class="col-md-5">
                    <label class="form-label" for="fileVaultFilter">Vault</label>
                    <select class="form-select" id="fileVaultFilter"></select>
                </div>
                <div class="col-md-7 text-md-end">
                    <x-button variant="primary" icon="upload" class="requires-unlocked" data-bs-toggle="modal" data-bs-target="#fileModal">Upload File</x-button>
                </div>
            </div>
            <div id="filesTable" class="table-responsive"></div>
        </x-card>
    </section>

    <section id="settingsSection" class="app-section d-none">
        <div class="row g-4">
            <div class="col-lg-7">
                <x-card title="Security" subtitle="Frontend controls for the active vault session" icon="shield-check">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold">Auto-lock</div>
                                <div class="text-muted small">Vault locks after 30 minutes of inactivity.</div>
                            </div>
                            <span class="badge text-bg-success">Enabled</span>
                        </div>
                        <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold">Masked secrets</div>
                                <div class="text-muted small">Passwords and notes stay hidden until revealed.</div>
                            </div>
                            <span class="badge text-bg-success">Enabled</span>
                        </div>
                        <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold">Client-side encryption</div>
                                <div class="text-muted small">Items and files are encrypted before leaving the browser.</div>
                            </div>
                            <span class="badge text-bg-success">Enabled</span>
                        </div>
                    </div>
                </x-card>
            </div>
            <div class="col-lg-5">
                <x-card title="Session" subtitle="Protect this device when stepping away" icon="person-lock">
                    <div class="d-grid gap-2">
                        <x-button variant="outline-secondary" icon="lock" id="settingsLockBtn">Lock Vault</x-button>
                        <x-button variant="outline-danger" icon="box-arrow-right" id="settingsLogoutBtn">Logout</x-button>
                    </div>
                </x-card>
            </div>
        </div>
    </section>
</div>

<x-modal id="vaultModal" title="Vault">
    <div class="modal-body">
        <form id="vaultForm">
            <input type="hidden" id="vaultId" name="id">
            <x-input label="Vault name" name="name" id="vaultName" placeholder="Personal" required />
            <div class="mb-0">
                <label class="form-label" for="vaultDescription">Description</label>
                <textarea class="form-control" id="vaultDescription" name="description" rows="3" placeholder="Optional notes"></textarea>
            </div>
        </form>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <x-button variant="primary" loading id="saveVaultBtn">Save Vault</x-button>
    </div>
</x-modal>

<x-modal id="itemModal" title="Add Item" size="lg">
    <div class="modal-body">
        <form id="itemForm">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="itemVaultId">Vault</label>
                    <select class="form-select" id="itemVaultId" name="vault_id" required></select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="itemType">Type</label>
                    <select class="form-select" id="itemType" name="type" required>
                        <option value="login">Login</option>
                        <option value="note">Secure Note</option>
                        <option value="credit_card">Credit Card</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <x-input label="Title" name="title" id="itemTitle" placeholder="GitHub" required />
                </div>
                <div class="col-md-6">
                    <x-input label="Username" name="username" id="itemUsername" placeholder="octocat" />
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="itemPassword">Secret</label>
                    <div class="input-group input-group-lg">
                        <input type="password" class="form-control sensitive-input" id="itemPassword" name="password" autocomplete="new-password">
                        <button class="btn btn-outline-secondary" type="button" id="toggleItemPassword">Show</button>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label" for="itemNotes">Notes</label>
                    <textarea class="form-control" id="itemNotes" name="notes" rows="3"></textarea>
                </div>
            </div>
        </form>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <x-button variant="primary" loading id="saveItemBtn">Encrypt & Save</x-button>
    </div>
</x-modal>

<x-modal id="fileModal" title="Upload File">
    <div class="modal-body">
        <form id="fileForm">
            <label class="form-label" for="fileVaultId">Vault</label>
            <select class="form-select mb-3" id="fileVaultId" name="vault_id" required></select>
            <div class="drop-zone" id="dropZone">
                <i class="bi bi-cloud-arrow-up fs-1 text-primary"></i>
                <div class="fw-semibold mt-2">Drop file here or click to browse</div>
                <div class="text-muted small" id="selectedFileText">No file selected</div>
                <input class="d-none" type="file" id="vaultFile" name="file">
            </div>
            <div class="progress mt-3 d-none" id="uploadProgress">
                <div class="progress-bar" style="width: 0%"></div>
            </div>
        </form>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <x-button variant="primary" loading id="uploadFileBtn">Encrypt & Upload</x-button>
    </div>
</x-modal>

<x-modal id="unlockVaultModal" title="Vault Locked" static>
    <div class="modal-body">
        <div class="state-box mb-3">
            <i class="bi bi-lock fs-2 text-primary"></i>
            <div class="fw-semibold mt-2">Your vault is locked</div>
            <div class="text-muted small">Enter your master password to decrypt items again.</div>
        </div>
        <form id="unlockVaultForm">
            <x-input label="Master password" name="unlockMasterPassword" id="unlockMasterPassword" type="password" required autocomplete="current-password" />
            <div id="unlockError" class="alert alert-danger d-none mb-0"></div>
        </form>
    </div>
    <div class="modal-footer">
        <x-button variant="outline-danger" id="unlockLogoutBtn">Logout</x-button>
        <x-button variant="primary" loading id="unlockVaultBtn">Unlock</x-button>
    </div>
</x-modal>

@push('scripts')
<script>
const initialSection = @json($activeSection);
const state = {
    section: initialSection,
    vaults: [],
    itemsByVault: new Map(),
    filesByVault: new Map(),
    decryptedItems: new Map(),
    selectedVaultId: null,
    selectedFile: null,
    locked: false,
};

let unlockModal = null;

document.addEventListener('DOMContentLoaded', async function() {
    if (!localStorage.getItem('api_token')) {
        window.location.href = '{{ route("login") }}';
        return;
    }

    const session = await window.vaultCrypto.loadSessionFromStorage();
    if (session) {
        window.vaultCryptoSession.encryptionKey = session.key;
        window.vaultCryptoSession.email = session.email;
        window.vaultCryptoSession.salt = session.salt;
        window.vaultCryptoSession.iterations = session.iterations;
    }

    unlockModal = new bootstrap.Modal(document.getElementById('unlockVaultModal'));
    state.locked = !window.vaultCryptoSession.encryptionKey;

    wireEvents();
    showSection(initialSection);
    await loadAllData();

    if (state.locked) {
        unlockModal.show();
    }
});

function wireEvents() {
    document.getElementById('saveVaultBtn').addEventListener('click', saveVault);
    document.getElementById('saveItemBtn').addEventListener('click', saveItem);
    document.getElementById('uploadFileBtn').addEventListener('click', uploadEncryptedFile);
    document.getElementById('lockVaultBtn').addEventListener('click', () => lockVaultNow());
    document.getElementById('settingsLockBtn')?.addEventListener('click', () => lockVaultNow());
    document.getElementById('settingsLogoutBtn')?.addEventListener('click', logoutUser);
    document.getElementById('unlockLogoutBtn').addEventListener('click', logoutUser);
    document.getElementById('unlockVaultBtn').addEventListener('click', unlockVault);
    document.getElementById('unlockVaultForm').addEventListener('submit', event => {
        event.preventDefault();
        unlockVault();
    });
    document.getElementById('itemVaultFilter').addEventListener('change', event => {
        state.selectedVaultId = event.target.value;
        renderItems();
    });
    document.getElementById('fileVaultFilter').addEventListener('change', event => {
        state.selectedVaultId = event.target.value;
        renderFiles();
    });
    document.getElementById('toggleItemPassword').addEventListener('click', function() {
        const input = document.getElementById('itemPassword');
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        this.textContent = show ? 'Hide' : 'Show';
    });
    document.querySelectorAll('.sensitive-input').forEach(input => {
        input.addEventListener('copy', event => event.preventDefault());
        input.addEventListener('cut', event => event.preventDefault());
        input.addEventListener('paste', event => event.preventDefault());
    });

    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('vaultFile');
    dropZone.addEventListener('click', () => fileInput.click());
    dropZone.addEventListener('dragover', event => {
        event.preventDefault();
        dropZone.classList.add('dragover');
    });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
    dropZone.addEventListener('drop', event => {
        event.preventDefault();
        dropZone.classList.remove('dragover');
        setSelectedFile(event.dataTransfer.files[0]);
    });
    fileInput.addEventListener('change', event => setSelectedFile(event.target.files[0]));
}

async function loadAllData() {
    setLoading();
    try {
        const vaultResponse = await axios.get('/api/vaults');
        state.vaults = vaultResponse.data.data || [];
        state.selectedVaultId = state.selectedVaultId || state.vaults[0]?.id || null;
        hydrateVaultSelects();

        await Promise.all(state.vaults.map(async vault => {
            const [itemsResponse, filesResponse] = await Promise.all([
                axios.get(`/api/vaults/${vault.id}/items`),
                axios.get(`/api/vaults/${vault.id}/files`),
            ]);
            state.itemsByVault.set(String(vault.id), itemsResponse.data.data || []);
            state.filesByVault.set(String(vault.id), filesResponse.data.data || []);
        }));

        if (!state.locked) {
            await decryptVisibleItems();
        }

        renderAll();
    } catch (error) {
        showAlert(getApiErrorMessage(error, 'Failed to load dashboard data.'), 'danger');
        renderEmptyAll();
    }
}

function setLoading() {
    document.getElementById('vaultGrid').innerHTML = renderLoadingState('Loading vaults...');
    document.getElementById('itemsTable').innerHTML = renderLoadingState('Loading items...');
    document.getElementById('filesTable').innerHTML = renderLoadingState('Loading files...');
    document.getElementById('recentItemsList').innerHTML = renderLoadingState('Loading recent items...');
}

function renderAll() {
    renderSummary();
    renderVaults();
    renderItems();
    renderFiles();
    renderRecentItems();
    setLockedUi();
}

function renderEmptyAll() {
    document.getElementById('vaultGrid').innerHTML = renderEmptyState('Unable to load vaults.');
    document.getElementById('itemsTable').innerHTML = renderEmptyState('Unable to load items.');
    document.getElementById('filesTable').innerHTML = renderEmptyState('Unable to load files.');
    document.getElementById('recentItemsList').innerHTML = renderEmptyState('Unable to load recent items.');
}

function renderSummary() {
    const allItems = getAllItems();
    const allFiles = getAllFiles();
    document.getElementById('totalVaults').textContent = state.vaults.length;
    document.getElementById('totalItems').textContent = allItems.length;
    document.getElementById('totalFiles').textContent = allFiles.length;
}

function renderVaults() {
    const grid = document.getElementById('vaultGrid');
    if (!state.vaults.length) {
        grid.innerHTML = `<div class="col-12">${renderEmptyState('No vaults yet. Create one to get started.')}</div>`;
        return;
    }

    grid.innerHTML = state.vaults.map(vault => {
        const itemCount = (state.itemsByVault.get(String(vault.id)) || []).length;
        return `
            <div class="col-md-6 col-xl-4">
                <div class="card app-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between gap-2">
                            <div>
                                <div class="app-icon mb-3"><i class="bi bi-safe"></i></div>
                                <h5>${escapeHtml(vault.name)}</h5>
                                <p class="text-muted mb-0">${itemCount} item${itemCount === 1 ? '' : 's'}</p>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-light btn-sm" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><button class="dropdown-item" onclick="openVault(${vault.id})">View</button></li>
                                    <li><button class="dropdown-item" onclick="editVault(${vault.id})">Edit</button></li>
                                    <li><button class="dropdown-item text-danger" onclick="deleteVault(${vault.id})">Delete</button></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function renderItems() {
    const container = document.getElementById('itemsTable');
    if (state.locked) {
        container.innerHTML = renderEmptyState('Vault is locked. Unlock to decrypt and view items.');
        return;
    }

    const items = getSelectedItems();
    if (!items.length) {
        container.innerHTML = renderEmptyState('No items in this vault yet.');
        return;
    }

    container.innerHTML = `
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Username</th>
                    <th>Secret</th>
                    <th>Created</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>${items.map(renderItemRow).join('')}</tbody>
        </table>
    `;
}

function renderRecentItems() {
    const container = document.getElementById('recentItemsList');
    if (state.locked) {
        container.innerHTML = renderEmptyState('Unlock the vault to decrypt recent items.');
        return;
    }

    const items = getAllItems()
        .slice()
        .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
        .slice(0, 6);

    if (!items.length) {
        container.innerHTML = renderEmptyState('No recent items yet.');
        return;
    }

    container.innerHTML = `
        <table class="table table-hover align-middle">
            <tbody>${items.map(item => {
                const data = state.decryptedItems.get(String(item.id));
                return `
                    <tr>
                        <td><div class="fw-semibold">${escapeHtml(data?.title || 'Untitled')}</div><div class="text-muted small">${escapeHtml(item.type)}</div></td>
                        <td class="text-end text-muted">${formatDate(item.created_at)}</td>
                    </tr>
                `;
            }).join('')}</tbody>
        </table>
    `;
}

function renderItemRow(item) {
    const data = state.decryptedItems.get(String(item.id));
    const title = data?.title || 'Unable to decrypt';
    const username = data?.username || '';
    const password = data?.secret || '';

    return `
        <tr>
            <td><div class="fw-semibold">${escapeHtml(title)}</div><div class="text-muted small">${escapeHtml(data?.notes || '')}</div></td>
            <td><span class="badge text-bg-light">${escapeHtml(item.type)}</span></td>
            <td>${escapeHtml(username)}</td>
            <td>
                <code class="masked-secret" data-sensitive-item-id="${item.id}" data-sensitive-field="secret">${escapeHtml(maskValue(password))}</code>
                <button class="btn btn-sm btn-outline-secondary ms-2" data-secret-toggle data-revealed="false" onclick="toggleSensitiveField(${item.id}, 'secret', this)">Show</button>
                <button class="btn btn-sm btn-outline-secondary ms-1" onclick="copySensitiveField(${item.id}, 'secret')"><i class="bi bi-clipboard"></i></button>
            </td>
            <td>${formatDate(item.created_at)}</td>
            <td class="text-end">
                <button class="btn btn-sm btn-outline-danger" onclick="deleteItem(${item.id})"><i class="bi bi-trash"></i></button>
            </td>
        </tr>
    `;
}

function renderFiles() {
    const container = document.getElementById('filesTable');
    const files = getSelectedFiles();
    if (!files.length) {
        container.innerHTML = renderEmptyState('No files in this vault yet.');
        return;
    }

    container.innerHTML = `
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>File name</th>
                    <th>Status</th>
                    <th>Upload date</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>${files.map(file => `
                <tr>
                    <td><i class="bi bi-file-earmark-lock me-2 text-primary"></i>${escapeHtml(file.file_name)}</td>
                    <td><span class="badge text-bg-success">Encrypted</span></td>
                    <td>${formatDate(file.created_at)}</td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-primary requires-unlocked" onclick="downloadEncryptedFile(${file.id}, this)">Download</button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteFile(${file.id})">Delete</button>
                    </td>
                </tr>
            `).join('')}</tbody>
        </table>
    `;
    setLockedUi();
}

function hydrateVaultSelects() {
    const options = state.vaults.map(vault => `<option value="${vault.id}">${escapeHtml(vault.name)}</option>`).join('');
    ['itemVaultFilter', 'fileVaultFilter', 'itemVaultId', 'fileVaultId'].forEach(id => {
        const select = document.getElementById(id);
        select.innerHTML = options || '<option value="">No vaults available</option>';
        if (state.selectedVaultId) select.value = state.selectedVaultId;
    });
}

async function decryptVisibleItems() {
    state.decryptedItems.clear();
    const items = getAllItems();
    await Promise.all(items.map(async item => {
        try {
            state.decryptedItems.set(String(item.id), await window.vaultCrypto.decryptVaultItem(item));
        } catch (error) {
            state.decryptedItems.set(String(item.id), { title: 'Decryption failed', username: '', password: '', notes: error.message });
        }
    }));
}

function openVault(vaultId) {
    state.selectedVaultId = String(vaultId);
    hydrateVaultSelects();
    showSection('items');
    renderItems();
}

function editVault(vaultId) {
    const vault = state.vaults.find(item => String(item.id) === String(vaultId));
    if (!vault) return;
    document.getElementById('vaultId').value = vault.id;
    document.getElementById('vaultName').value = vault.name;
    document.getElementById('vaultDescription').value = vault.description || '';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('vaultModal')).show();
}

async function saveVault() {
    const button = document.getElementById('saveVaultBtn');
    setButtonLoading(button, true, 'Saving...');
    const form = document.getElementById('vaultForm');
    const data = Object.fromEntries(new FormData(form));

    try {
        const response = data.id
            ? await axios.put(`/api/vaults/${data.id}`, data)
            : await axios.post('/api/vaults', data);
        if (response.data.success) {
            showAlert(data.id ? 'Vault updated.' : 'Vault created.', 'success');
            bootstrap.Modal.getInstance(document.getElementById('vaultModal')).hide();
            form.reset();
            await loadAllData();
        }
    } catch (error) {
        showAlert(getApiErrorMessage(error, 'Failed to save vault.'), 'danger');
    } finally {
        setButtonLoading(button, false);
    }
}

async function deleteVault(vaultId) {
    if (!confirm('Delete this vault and its contents?')) return;
    try {
        await axios.delete(`/api/vaults/${vaultId}`);
        showAlert('Vault deleted.', 'success');
        state.selectedVaultId = null;
        await loadAllData();
    } catch (error) {
        showAlert(getApiErrorMessage(error, 'Failed to delete vault.'), 'danger');
    }
}

async function saveItem() {
    if (!requireUnlocked()) return;
    const button = document.getElementById('saveItemBtn');
    setButtonLoading(button, true, 'Encrypting...');
    const form = document.getElementById('itemForm');
    const data = Object.fromEntries(new FormData(form));

    try {
        const encrypted = await window.vaultCrypto.encryptVaultItem({
            title: data.title,
            username: data.username || '',
            secret: data.password || '',
            notes: data.notes || '',
        });
        const response = await axios.post(`/api/vaults/${data.vault_id}/items`, {
            type: data.type,
            encrypted_data: encrypted.encrypted_data,
            iv: encrypted.iv,
            tag: encrypted.tag,
        });
        if (response.data.success) {
            showAlert('Item encrypted and saved.', 'success');
            bootstrap.Modal.getInstance(document.getElementById('itemModal')).hide();
            form.reset();
            await loadAllData();
        }
    } catch (error) {
        showAlert(getApiErrorMessage(error, 'Failed to save item.'), 'danger');
    } finally {
        setButtonLoading(button, false);
    }
}

async function deleteItem(itemId) {
    if (!confirm('Delete this item?')) return;
    const vaultId = findVaultForItem(itemId);
    try {
        await axios.delete(`/api/vaults/${vaultId}/items/${itemId}`);
        showAlert('Item deleted.', 'success');
        await loadAllData();
    } catch (error) {
        showAlert(getApiErrorMessage(error, 'Failed to delete item.'), 'danger');
    }
}

function setSelectedFile(file) {
    state.selectedFile = file || null;
    document.getElementById('selectedFileText').textContent = file ? `${file.name} (${formatBytes(file.size)})` : 'No file selected';
}

async function uploadEncryptedFile() {
    if (!requireUnlocked()) return;
    if (!state.selectedFile) {
        showAlert('Choose a file first.', 'warning');
        return;
    }

    const button = document.getElementById('uploadFileBtn');
    const vaultId = document.getElementById('fileVaultId').value;
    const progress = document.getElementById('uploadProgress');
    const progressBar = progress.querySelector('.progress-bar');
    setButtonLoading(button, true, 'Encrypting...');
    progress.classList.remove('d-none');
    progressBar.style.width = '35%';

    try {
        const encrypted = await window.vaultCrypto.encryptFile(state.selectedFile);
        progressBar.style.width = '70%';
        const formData = new FormData();
        formData.append('file', encrypted.encryptedBlob, `${state.selectedFile.name}.enc`);
        formData.append('file_name', state.selectedFile.name);
        formData.append('iv', encrypted.iv);
        formData.append('tag', encrypted.tag);

        const response = await axios.post(`/api/vaults/${vaultId}/files`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        progressBar.style.width = '100%';

        if (response.data.success) {
            showAlert('File encrypted and uploaded.', 'success');
            bootstrap.Modal.getInstance(document.getElementById('fileModal')).hide();
            document.getElementById('fileForm').reset();
            setSelectedFile(null);
            await loadAllData();
        }
    } catch (error) {
        showAlert(getApiErrorMessage(error, 'Failed to upload file.'), 'danger');
    } finally {
        setButtonLoading(button, false);
        setTimeout(() => progress.classList.add('d-none'), 500);
    }
}

async function downloadEncryptedFile(fileId, button = null) {
    if (!requireUnlocked()) return;
    const vaultId = findVaultForFile(fileId);
    setPlainButtonLoading(button, true, 'Downloading...');

    try {
        const urlResponse = await axios.get(`/api/vaults/${vaultId}/files/${fileId}/download-url`);
        const encryptedResponse = await axios.get(urlResponse.data.data.download_url, { responseType: 'arraybuffer' });
        const fileName = encryptedResponse.headers['x-file-name']
            ? decodeURIComponent(encryptedResponse.headers['x-file-name'])
            : `vault-file-${fileId}`;
        const blob = await window.vaultCrypto.decryptFile(
            encryptedResponse.data,
            encryptedResponse.headers['x-file-iv'],
            encryptedResponse.headers['x-file-tag']
        );
        const objectUrl = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = objectUrl;
        link.download = fileName;
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(objectUrl);
    } catch (error) {
        showAlert(getApiErrorMessage(error, 'Failed to download file.'), 'danger');
    } finally {
        setPlainButtonLoading(button, false);
    }
}

async function deleteFile(fileId) {
    if (!confirm('Delete this file?')) return;
    const vaultId = findVaultForFile(fileId);
    try {
        await axios.delete(`/api/vaults/${vaultId}/files/${fileId}`);
        showAlert('File deleted.', 'success');
        await loadAllData();
    } catch (error) {
        showAlert(getApiErrorMessage(error, 'Failed to delete file.'), 'danger');
    }
}

function showSection(section) {
    state.section = section;
    document.querySelectorAll('.app-section').forEach(element => element.classList.add('d-none'));
    document.getElementById(`${section}Section`)?.classList.remove('d-none');
    const labels = { dashboard: 'Dashboard', vaults: 'Vaults', items: 'Items', files: 'Files', settings: 'Settings' };
    document.getElementById('sectionHeading').textContent = labels[section] || 'Dashboard';
}

function setLockedUi() {
    document.querySelectorAll('.requires-unlocked').forEach(element => {
        element.disabled = state.locked;
    });
    document.querySelectorAll('[data-secret-toggle]').forEach(element => {
        element.disabled = state.locked;
    });
}

function lockVaultNow(isAutomatic = false) {
    if (state.locked) return;
    window.vaultCrypto.lockVault();
    state.locked = true;
    state.decryptedItems.clear();
    renderAll();
    unlockModal.show();
    showAlert(isAutomatic ? 'Vault locked after inactivity.' : 'Vault locked.', 'warning');
}

window.lockVaultNow = lockVaultNow;
window.handleVaultAutoLock = () => lockVaultNow(true);

async function unlockVault() {
    const button = document.getElementById('unlockVaultBtn');
    const error = document.getElementById('unlockError');
    error.classList.add('d-none');
    setButtonLoading(button, true, 'Unlocking...');

    try {
        const session = await window.vaultCrypto.loadSessionFromStorage();
        if (!session?.email || !session?.salt) throw new Error('Unlock metadata missing.');
        await window.vaultCrypto.deriveAndStoreKey(
            document.getElementById('unlockMasterPassword').value,
            session.email,
            session.salt,
            session.iterations || window.vaultCrypto.iterations
        );
        document.getElementById('unlockMasterPassword').value = '';
        state.locked = false;
        unlockModal.hide();
        await decryptVisibleItems();
        renderAll();
        showAlert('Vault unlocked.', 'success');
    } catch (exception) {
        error.textContent = 'Unable to unlock vault. Check your master password.';
        error.classList.remove('d-none');
    } finally {
        setButtonLoading(button, false);
    }
}

function requireUnlocked() {
    if (!state.locked && window.vaultCryptoSession.encryptionKey) return true;
    unlockModal.show();
    showAlert('Unlock the vault first.', 'warning');
    return false;
}

function logoutUser() {
    localStorage.removeItem('api_token');
    window.vaultCrypto.clearMemoryKey();
    window.location.href = '{{ route("login") }}';
}
window.logoutUser = logoutUser;

function toggleSensitiveField(itemId, field, button) {
    if (!requireUnlocked()) return;
    const item = state.decryptedItems.get(String(itemId));
    const element = document.querySelector(`[data-sensitive-item-id="${itemId}"][data-sensitive-field="${field}"]`);
    if (!item || !element) return;
    const reveal = button.dataset.revealed !== 'true';
    element.textContent = reveal ? (item[field] || '') : maskValue(item[field]);
    element.classList.toggle('masked-secret', !reveal);
    button.textContent = reveal ? 'Hide' : 'Show';
    button.dataset.revealed = reveal ? 'true' : 'false';
}

async function copySensitiveField(itemId, field) {
    if (!requireUnlocked()) return;
    const item = state.decryptedItems.get(String(itemId));
    if (!item?.[field]) return;
    await navigator.clipboard.writeText(item[field]);
    showAlert('Copied to clipboard.', 'success');
}

function getSelectedItems() {
    return state.itemsByVault.get(String(state.selectedVaultId)) || [];
}

function getSelectedFiles() {
    return state.filesByVault.get(String(state.selectedVaultId)) || [];
}

function getAllItems() {
    return Array.from(state.itemsByVault.values()).flat();
}

function getAllFiles() {
    return Array.from(state.filesByVault.values()).flat();
}

function findVaultForItem(itemId) {
    for (const [vaultId, items] of state.itemsByVault.entries()) {
        if (items.some(item => String(item.id) === String(itemId))) return vaultId;
    }
    return state.selectedVaultId;
}

function findVaultForFile(fileId) {
    for (const [vaultId, files] of state.filesByVault.entries()) {
        if (files.some(file => String(file.id) === String(fileId))) return vaultId;
    }
    return state.selectedVaultId;
}

function renderLoadingState(message) {
    return `<div class="state-box text-muted"><div class="spinner-border spinner-border-sm me-2"></div>${escapeHtml(message)}</div>`;
}

function renderEmptyState(message) {
    return `<div class="state-box text-muted">${escapeHtml(message)}</div>`;
}

function maskValue(value) {
    const normalized = String(value || '');
    return normalized ? '•'.repeat(Math.min(Math.max(normalized.length, 8), 16)) : '';
}

function setButtonLoading(button, loading, label = 'Working...') {
    if (!button) return;
    const spinner = button.querySelector('.spinner-border');
    const text = button.querySelector('.btn-label');
    if (text && !button.dataset.originalLabel) button.dataset.originalLabel = text.textContent;
    button.disabled = loading;
    if (spinner) spinner.classList.toggle('d-none', !loading);
    if (text) text.textContent = loading ? label : button.dataset.originalLabel;
}

function setPlainButtonLoading(button, loading, label = 'Working...') {
    if (!button) return;
    if (!button.dataset.originalLabel) button.dataset.originalLabel = button.textContent;
    button.disabled = loading;
    button.textContent = loading ? label : button.dataset.originalLabel;
}

function getApiErrorMessage(error, fallback) {
    const response = error.response?.data;
    if (response?.message) return response.message;
    if (response?.errors) return Object.values(response.errors).flat().join(' ');
    return error.message || fallback;
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function formatDate(value) {
    return value ? new Date(value).toLocaleDateString() : 'Unknown';
}

function formatBytes(bytes) {
    if (!bytes) return '0 B';
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const index = Math.floor(Math.log(bytes) / Math.log(1024));
    return `${(bytes / Math.pow(1024, index)).toFixed(index ? 1 : 0)} ${sizes[index]}`;
}
</script>
@endpush
@endsection
