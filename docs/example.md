# Frontend Encryption and Decryption Example

This example documents how the vault frontend performs client-side AES-256-GCM encryption and decryption, and how encrypted data is passed to the API.

## Key Derivation and Session Key Storage

The frontend derives an AES key from the user's master password using PBKDF2 with SHA-256.

Key helper functions:

```js
async deriveAesKey(password, saltHex, iterations = 100000) {
    const baseKey = await crypto.subtle.importKey(
        'raw',
        new TextEncoder().encode(password),
        'PBKDF2',
        false,
        ['deriveKey']
    );

    return crypto.subtle.deriveKey(
        {
            name: 'PBKDF2',
            salt: this.hexToUint8Array(saltHex),
            iterations,
            hash: 'SHA-256',
        },
        baseKey,
        { name: 'AES-GCM', length: 256 },
        true,
        ['encrypt', 'decrypt']
    );
}
```

The derived key is stored in `window.vaultCryptoSession.encryptionKey` and optionally serialized into session storage as a base64 raw key.

## Encrypting a Vault Item

The frontend encrypts item data before sending it to the API.

Example item data:

```js
const item = {
    title: data.title,
    username: data.username || '',
    password: data.password || '',
    notes: data.notes || '',
};
```

Encryption function:

```js
async encryptVaultItem(item) {
    const key = window.vaultCryptoSession.encryptionKey;
    const iv = crypto.getRandomValues(new Uint8Array(12));
    const encryptedBuffer = await crypto.subtle.encrypt(
        { name: 'AES-GCM', iv, tagLength: 128 },
        key,
        new TextEncoder().encode(JSON.stringify(item))
    );

    const encryptedBytes = new Uint8Array(encryptedBuffer);
    const ciphertext = encryptedBytes.slice(0, encryptedBytes.length - 16);
    const tag = encryptedBytes.slice(encryptedBytes.length - 16);

    return {
        encrypted_data: this.arrayBufferToBase64(ciphertext),
        iv: this.arrayBufferToBase64(iv),
        tag: this.arrayBufferToBase64(tag),
    };
}
```

## API Request Payload for Item Create

The encrypted item is sent to the backend as JSON payload:

```json
{
  "type": "login",
  "encrypted_data": "BASE64_ENCRYPTED_PAYLOAD",
  "iv": "BASE64_IV",
  "tag": "BASE64_TAG"
}
```

This is created in frontend code before calling the API:

```js
const encrypted = await window.vaultCrypto.encryptVaultItem(item);
await axios.post(`/api/vaults/${data.vault_id}/items`, {
    type: data.type,
    encrypted_data: encrypted.encrypted_data,
    iv: encrypted.iv,
    tag: encrypted.tag,
});
```

## Decrypting a Vault Item

When item data is loaded, the frontend uses the same key and AES-GCM parameters to decrypt it.

Decryption function:

```js
async decryptVaultItem({ encrypted_data, iv, tag }) {
    const key = window.vaultCryptoSession.encryptionKey;
    const ciphertext = this.base64ToUint8Array(encrypted_data);
    const tagBytes = this.base64ToUint8Array(tag);
    const encryptedBytes = new Uint8Array(ciphertext.length + tagBytes.length);
    encryptedBytes.set(ciphertext, 0);
    encryptedBytes.set(tagBytes, ciphertext.length);

    const decryptedBuffer = await crypto.subtle.decrypt(
        { name: 'AES-GCM', iv: this.base64ToUint8Array(iv), tagLength: 128 },
        key,
        encryptedBytes
    );
    return JSON.parse(new TextDecoder().decode(decryptedBuffer));
}
```

## Encrypting a File Before Upload

Files are encrypted in a similar way, but the encrypted ciphertext is sent as a binary blob.

```js
async encryptFile(file) {
    const key = window.vaultCryptoSession.encryptionKey;
    const iv = crypto.getRandomValues(new Uint8Array(12));
    const encryptedBuffer = await crypto.subtle.encrypt(
        { name: 'AES-GCM', iv, tagLength: 128 },
        key,
        await this.readFileAsArrayBuffer(file)
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
}
```

## File Upload Request Example

The frontend uploads the encrypted file using `FormData`:

```js
const encrypted = await window.vaultCrypto.encryptFile(state.selectedFile);
const formData = new FormData();
formData.append('file', encrypted.encryptedBlob, `${state.selectedFile.name}.enc`);
formData.append('file_name', state.selectedFile.name);
formData.append('iv', encrypted.iv);
formData.append('tag', encrypted.tag);

await axios.post(`/api/vaults/${vaultId}/files`, formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
});
```

## Decrypting a Downloaded File

When a file is downloaded, the API returns the encrypted bytes and headers containing `iv` and `tag`.

```js
const encryptedResponse = await axios.get(downloadUrl, { responseType: 'arraybuffer' });
const blob = await window.vaultCrypto.decryptFile(
    encryptedResponse.data,
    encryptedResponse.headers['x-file-iv'],
    encryptedResponse.headers['x-file-tag']
);
```

The file is then saved using a browser object URL.

## Summary

- The frontend derives the AES key from the master password.
- Items are encrypted to base64 `encrypted_data`, `iv`, and `tag` before API submission.
- Files are encrypted and uploaded as a binary blob with `iv` and `tag` metadata.
- Decryption happens in the browser using the same derived encryption key.
