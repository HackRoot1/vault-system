# Key Derivation System Documentation

This document explains the key derivation system, the difference between hashing and encryption, and how the system handles password-based encryption.

## Table of Contents
1. [Hashing vs Encryption](#hashing-vs-encryption)
2. [Key Derivation with PBKDF2](#key-derivation-with-pbkdf2)
3. [System Architecture](#system-architecture)
4. [Security Flow](#security-flow)
5. [API Examples](#api-examples)

## Hashing vs Encryption

### Hashing
- **One-way function**: Cannot be reversed
- **Deterministic**: Same input always produces same output
- **Use case**: Authentication (password verification)
- **Example**: Password storage for login verification

**Process:**
```
Password + Random Salt → Hash Function → Hashed Password (stored in DB)
"mypassword123" + "abc123..." → BCrypt/SHA256 → "$2y$10$abcd..."
```

### Encryption
- **Two-way function**: Can be reversed with the correct key
- **Requires key**: Data encrypted with key A can only be decrypted with key A
- **Use case**: Data protection (securing sensitive information)
- **Example**: Encrypting vault items with user's password derivative

**Process:**
```
Plain Data + Encryption Key → Encryption Algorithm → Encrypted Data
"username: john" + [32-byte key] → AES-256-GCM → "U2FsdGVkX1..."
```

### Quick Comparison

| Aspect | Hashing | Encryption |
|--------|---------|-----------|
| Reversible | No | Yes |
| Purpose | Authentication | Data Protection |
| Key Required | No | Yes |
| Database Risk | Low (hash only) | Medium (with key) |
| Example Use | Login passwords | Vault items |

## Key Derivation with PBKDF2

### What is PBKDF2?
PBKDF2 (Password-Based Key Derivation Function 2) is a recommended key derivation function that:
- Converts a weak password into a strong cryptographic key
- Uses computational difficulty (iterations) to slow down attacks
- Requires a random salt to prevent rainbow tables

### Why PBKDF2?
- **Slow by design**: Makes brute force attacks impractical
- **Battle-tested**: NIST and IETF recommended
- **Simple**: Easy to understand and implement
- **Reproducible**: Same password + salt always produces same key

### Parameters

| Parameter | Value | Purpose |
|-----------|-------|---------|
| Algorithm | SHA-256 | Hashing algorithm |
| Iterations | 100,000 | Computational cost (adjustable) |
| Salt | 16 bytes (32 hex chars) | Random value per user |
| Output | 32 bytes | AES-256 key size |

### Key Generation Flow

```
User Password: "MySecure@Pass123"
            ↓
Salt (stored in DB): "3f7a8b2c9d1e4f5a6c8b3d9e2f1a5c7b"
            ↓
Iterations (stored in DB): 100,000
            ↓
PBKDF2(SHA-256, password, salt, iterations) → 32-byte binary key
            ↓
Encryption Key: [used for AES-256-GCM]
```

### Important Notes

- **Salt is NOT secret**: It's stored alongside iterations. Security comes from the computational cost and randomness.
- **Password-derived**: The key is regenerated from the password, making it impossible for the server to derive the key without the password.
- **Per-user**: Each user has their own salt, so even identical passwords produce different keys.
- **Verifiable**: If a user provides the correct password, the derived key will match.

## System Architecture

### User Registration

```
1. User provides: name, email, password
   ↓
2. Generate random salt
3. Store password hash (for authentication)
4. Store salt and iterations (for key derivation)
   ↓
Database:
  - name: "John Doe"
  - email: "john@example.com"
  - password: "$2y$10$..." (BCrypt hash)
  - encryption_salt: "3f7a8b2c9d1e4f5a6c8b3d9e2f1a5c7b"
  - key_iterations: 100000
   ↓
5. Derive encryption key from password
6. Return API token
```

### User Login

```
1. User provides: email, password
   ↓
2. Authenticate password using BCrypt hash
   If fails → Error
   ↓
3. Retrieve: encryption_salt, key_iterations
   ↓
4. Derive key: PBKDF2(password, salt, iterations)
   ↓
5. Store key in session/memory for this login
   ↓
6. Return API token
```

### Encrypting Vault Items

```
1. User sends: vault_id, item_type, item_data (JSON)
   ↓
2. Retrieve encryption key from current session
   ↓
3. Convert item_data to JSON string
   ↓
4. Generate random IV (12 bytes)
   ↓
5. Encrypt: AES-256-GCM(key, JSON, IV) → Ciphertext + Tag
   ↓
6. Store in database:
   - vault_id: 1
   - type: "login"
   - encrypted_data: "U2FsdGVkX1..." (base64)
   - iv: "dGVzdGl2..." (base64)
   - tag: "YXV0aHRhZ..." (base64)
```

### Retrieving Vault Items

```
1. User requests: /api/vaults/{vault_id}/items
   ↓
2. Authenticate user and verify vault ownership
   ↓
3. Retrieve encryption key from current session
   ↓
4. For each item in database:
   - Decrypt: AES-256-GCM(key, ciphertext, IV, tag) → JSON
   - Parse JSON
   - Return as decrypted object
   ↓
5. Return: [{ id, type, data, ... }, ...]
```

## Security Flow

### Complete User Lifecycle

```
┌─────────────────────────────────────────────────────────┐
│ 1. REGISTRATION                                         │
├─────────────────────────────────────────────────────────┤
│ Input: name, email, password                            │
│ Generate: salt (random 16 bytes)                        │
│ Store: password_hash, salt, iterations                  │
│ Derive: encryption_key = PBKDF2(password, salt, iter)   │
│ Output: token                                           │
└─────────────────────────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────┐
│ 2. LOGIN                                                │
├─────────────────────────────────────────────────────────┤
│ Input: email, password                                  │
│ Verify: password against stored hash                    │
│ Retrieve: salt, iterations (from DB)                    │
│ Derive: encryption_key = PBKDF2(password, salt, iter)   │
│ Store: key in session memory (NOT in database)          │
│ Output: token                                           │
└─────────────────────────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────┐
│ 3. ENCRYPT VAULT ITEM                                   │
├─────────────────────────────────────────────────────────┤
│ Input: item_type, item_data                             │
│ Retrieve: encryption_key (from session)                 │
│ Process: JSON → Encrypt(key, json, iv) → Ciphertext     │
│ Store: encrypted_data, iv, tag (in database)            │
│ Note: NO plain data stored anywhere                     │
└─────────────────────────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────┐
│ 4. DECRYPT VAULT ITEM                                   │
├─────────────────────────────────────────────────────────┤
│ Input: item_id                                          │
│ Retrieve: encryption_key (from session)                 │
│ Process: Decrypt(key, ciphertext, iv, tag) → JSON       │
│ Output: decrypted item_data                             │
└─────────────────────────────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────┐
│ 5. LOGOUT                                               │
├─────────────────────────────────────────────────────────┤
│ Action: Clear encryption_key from session memory        │
│ Action: Revoke API token                                │
│ Result: User cannot access encrypted data               │
└─────────────────────────────────────────────────────────┘
```

### Key Storage Security

| Location | What's Stored | Sensitive? | Risk |
|----------|--------------|-----------|------|
| Database | password_hash | No | Low - one-way hash |
| Database | encryption_salt | No | Low - needs password |
| Database | key_iterations | No | Low - public constant |
| Database | encrypted_data | No | Low - needs key |
| Session Memory | encryption_key | Yes | Medium - in RAM only |
| Never Stored | user's password | N/A | Good - not saved |

**Important**: The encryption key is stored in PHP session memory (RAM) only during the user's login session. It's never stored in the database or transmitted after initial derivation.

## API Examples

### 1. Register with Key Derivation

**Request:**
```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "MySecure@Pass123",
    "password_confirmation": "MySecure@Pass123"
  }'
```

**What happens:**
1. Random salt is generated: `3f7a8b2c9d1e4f5a6c8b3d9e2f1a5c7b`
2. Password is hashed with BCrypt
3. Salt and iterations stored in database
4. Key derived: `PBKDF2(SHA-256, "MySecure@Pass123", salt, 100000)`
5. Token returned for API use

**Response:**
```json
{
    "success": true,
    "message": "User registered successfully",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "encryption_salt": "3f7a8b2c9d1e4f5a6c8b3d9e2f1a5c7b",
            "key_iterations": 100000
        },
        "token": "1|abc123def456..."
    }
}
```

### 2. Login with Key Re-derivation

**Request:**
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "MySecure@Pass123"
  }'
```

**What happens:**
1. Authenticate with BCrypt: `verify("MySecure@Pass123", stored_hash)`
2. Retrieve user's salt: `3f7a8b2c9d1e4f5a6c8b3d9e2f1a5c7b`
3. Retrieve iterations: `100000`
4. Re-derive key: `PBKDF2(SHA-256, "MySecure@Pass123", salt, 100000)`
5. Store key in session memory
6. Return token

**Response:**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com"
        },
        "token": "2|xyz789uvw456..."
    }
}
```

### 3. Add Encrypted Item

**Request:**
```bash
curl -X POST http://localhost:8000/api/vaults/1/items \
  -H "Authorization: Bearer 2|xyz789uvw456..." \
  -H "Content-Type: application/json" \
  -d '{
    "type": "login",
    "data": {
        "username": "john_smith",
        "password": "SecretPass@123",
        "url": "https://example.com"
    }
  }'
```

**Plain data being encrypted:**
```json
{
    "username": "john_smith",
    "password": "SecretPass@123",
    "url": "https://example.com"
}
```

**Encryption process:**
```
JSON String: {"username":"john_smith","password":"SecretPass@123","url":"https://example.com"}
             ↓
Encryption Key (from session): [32-byte binary derived from password]
             ↓
IV (random): dGVzdGl2ZWN0b3I= (base64)
             ↓
AES-256-GCM: U2FsdGVkX19mVNfxXKV8... (base64 ciphertext)
             ↓
Auth Tag: YXV0aHRhZ2Zvcmdj (base64)
```

**What's stored in database:**
```json
{
    "id": 1,
    "vault_id": 1,
    "type": "login",
    "encrypted_data": "U2FsdGVkX19mVNfxXKV8...",
    "iv": "dGVzdGl2ZWN0b3I=",
    "tag": "YXV0aHRhZ2Zvcmdj",
    "created_at": "2026-04-22T18:30:00Z"
}
```

**API Response:**
```json
{
    "success": true,
    "message": "Item created successfully",
    "data": {
        "id": 1,
        "vault_id": 1,
        "type": "login",
        "data": {
            "username": "john_smith",
            "password": "SecretPass@123",
            "url": "https://example.com"
        },
        "created_at": "2026-04-22T18:30:00Z",
        "updated_at": "2026-04-22T18:30:00Z"
    }
}
```

**Note:** API returns decrypted data, but database only stores encrypted data!

### 4. Retrieve Encrypted Items

**Request:**
```bash
curl -X GET http://localhost:8000/api/vaults/1/items \
  -H "Authorization: Bearer 2|xyz789uvw456..."
```

**Decryption process:**
```
From Database:
  - encrypted_data: "U2FsdGVkX19mVNfxXKV8..."
  - iv: "dGVzdGl2ZWN0b3I="
  - tag: "YXV0aHRhZ2Zvcmdj"
             ↓
Encryption Key (from session): [32-byte binary derived from password]
             ↓
AES-256-GCM Decrypt: {"username":"john_smith",...}
             ↓
JSON Parse: { username: "john_smith", ... }
```

**API Response (decrypted):**
```json
{
    "success": true,
    "message": "Success",
    "data": [
        {
            "id": 1,
            "vault_id": 1,
            "type": "login",
            "data": {
                "username": "john_smith",
                "password": "SecretPass@123",
                "url": "https://example.com"
            },
            "created_at": "2026-04-22T18:30:00Z",
            "updated_at": "2026-04-22T18:30:00Z"
        }
    ]
}
```

### 5. Logout

**Request:**
```bash
curl -X POST http://localhost:8000/api/logout \
  -H "Authorization: Bearer 2|xyz789uvw456..."
```

**What happens:**
1. Encryption key cleared from session memory
2. API token revoked
3. User cannot decrypt items anymore

**Response:**
```json
{
    "success": true,
    "message": "Logged out successfully",
    "data": null
}
```

## Key Points Summary

### Security Guarantees
- ✅ Passwords never stored as plaintext (use BCrypt)
- ✅ Encryption keys never stored in database (only in session RAM)
- ✅ Each user has unique salt (rainbow table resistant)
- ✅ 100,000 iterations (brute force resistant)
- ✅ AES-256-GCM provides authenticated encryption
- ✅ Unique IV and Auth Tag per encrypted item

### Production Considerations
- Increase iterations if CPU allows (test on target hardware)
- Use HTTPS/TLS for all API communication
- Implement session timeout for encryption keys
- Consider per-vault encryption keys for additional security
- Use secure password reset mechanism (new salt/key)
- Log encryption key derivation attempts
- Monitor for brute force attempts on login

### Performance Notes
- PBKDF2 with 100,000 iterations takes ~100-200ms per login (intentional - security feature)
- Encryption/decryption of items is very fast (<1ms per item)
- Consider caching derived key in Redis for distributed systems (with appropriate expiration)