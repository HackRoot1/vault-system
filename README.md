# Vault System API Documentation

A secure, encrypted vault system built with Laravel 12, featuring end-to-end encryption, multi-device synchronization, and secure file storage.

## Overview

The Vault System API provides a secure platform for storing sensitive information with the following features:

- **User Authentication**: Laravel Sanctum-based authentication with API tokens
- **Vault Management**: Create and manage multiple secure vaults per user
- **Encrypted Items**: Store login credentials, notes, and other sensitive data with AES-256-GCM encryption
- **Multi-device Sync**: Synchronize vault items across devices with efficient delta updates
- **Secure File Storage**: Upload, store, and download encrypted files with temporary URLs
- **Key Derivation**: PBKDF2-based key derivation from user passwords for enhanced security

## Security Features

- **End-to-End Encryption**: All sensitive data is encrypted using AES-256-GCM
- **Password-Based Key Derivation**: PBKDF2 with 100,000 iterations for strong key generation
- **Per-User Encryption**: Each user has unique encryption keys derived from their password
- **Secure File Storage**: Files are encrypted before storage and decrypted only at download
- **API Token Authentication**: Bearer token authentication via Laravel Sanctum
- **Input Validation**: Comprehensive validation on all API endpoints
- **Rate Limiting**: Configurable rate limits on all API endpoints (10/min for login, 5/min for register, 100/min for others)
- **Two-Factor Authentication**: Optional Google Authenticator 2FA support
- **Device Tracking**: Track login devices with IP and user agent information
- **Soft Deletes**: Secure deletion with synchronization support
- **CORS Configuration**: Proper CORS setup for API security

## Base URL

All API endpoints assume the base URL: `http://localhost:8000/api`

Adjust according to your setup.

## Authentication

All endpoints except registration and login require authentication via Bearer token.

### Register User

**Endpoint:** `POST /register`

**Request Body:**
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "MySecure@Pass123",
    "password_confirmation": "MySecure@Pass123"
}
```

**cURL Example:**
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

**Success Response (201):**
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

### Login User

**Endpoint:** `POST /login`

**Request Body:**
```json
{
    "email": "john@example.com",
    "password": "MySecure@Pass123",
    "two_factor_code": "123456" // Optional, required if 2FA is enabled
}
```

**cURL Example (without 2FA):**
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "MySecure@Pass123"
  }'
```

**cURL Example (with 2FA):**
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "MySecure@Pass123",
    "two_factor_code": "123456"
  }'
```

**Success Response (200):**
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

**2FA Required Response (200):**
```json
{
    "success": true,
    "message": "Two-factor authentication required",
    "data": {
        "requires_2fa": true,
        "user_id": 1
    }
}
```

### Get User Info

**Endpoint:** `GET /user`

**Headers:**
- `Authorization: Bearer {token}`

**cURL Example:**
```bash
curl -X GET http://localhost:8000/api/user \
  -H "Authorization: Bearer 2|xyz789uvw456..."
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Success",
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com"
    }
}
```

### Logout User

**Endpoint:** `POST /logout`

**Headers:**
- `Authorization: Bearer {token}`

**cURL Example:**
```bash
curl -X POST http://localhost:8000/api/logout \
  -H "Authorization: Bearer 2|xyz789uvw456..."
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Logged out successfully",
    "data": null
}
```

### Setup Two-Factor Authentication

**Endpoint:** `POST /2fa/setup`

**Headers:**
- `Authorization: Bearer {token}`

**cURL Example:**
```bash
curl -X POST http://localhost:8000/api/2fa/setup \
  -H "Authorization: Bearer 2|xyz789uvw456..."
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Scan the QR code with Google Authenticator and use the generated code to verify",
    "data": {
        "secret": "JBSWY3DPEHPK3PXP",
        "qr_code_url": "otpauth://totp/Vault%20System:user@example.com?secret=JBSWY3DPEHPK3PXP&issuer=Vault%20System"
    }
}
```

### Verify Two-Factor Authentication

**Endpoint:** `POST /2fa/verify`

**Headers:**
- `Authorization: Bearer {token}`
- `Content-Type: application/json`

**Request Body:**
```json
{
    "code": "123456"
}
```

**cURL Example:**
```bash
curl -X POST http://localhost:8000/api/2fa/verify \
  -H "Authorization: Bearer 2|xyz789uvw456..." \
  -H "Content-Type: application/json" \
  -d '{"code": "123456"}'
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Two-factor authentication enabled successfully",
    "data": null
}
```

### Disable Two-Factor Authentication

**Endpoint:** `POST /2fa/disable`

**Headers:**
- `Authorization: Bearer {token}`
- `Content-Type: application/json`

**Request Body:**
```json
{
    "code": "123456"
}
```

**cURL Example:**
```bash
curl -X POST http://localhost:8000/api/2fa/disable \
  -H "Authorization: Bearer 2|xyz789uvw456..." \
  -H "Content-Type: application/json" \
  -d '{"code": "123456"}'
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Two-factor authentication disabled successfully",
    "data": null
}
```

### Get Device History

**Endpoint:** `GET /devices`

**Headers:**
- `Authorization: Bearer {token}`

**cURL Example:**
```bash
curl -X GET http://localhost:8000/api/devices \
  -H "Authorization: Bearer 2|xyz789uvw456..."
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Device history retrieved successfully",
    "data": {
        "device_history": [
            {
                "ip": "192.168.1.100",
                "user_agent": "Mozilla/5.0...",
                "timestamp": "2026-04-23T12:00:00.000000Z"
            }
        ],
        "last_login": {
            "timestamp": "2026-04-23T12:00:00.000000Z",
            "ip": "192.168.1.100",
            "user_agent": "Mozilla/5.0..."
        }
    }
}
```

## Vault API

Vaults are containers for encrypted items and files. All vault endpoints require authentication.

### List Vaults

**Endpoint:** `GET /vaults`

**Headers:**
- `Authorization: Bearer {token}`

**cURL Example:**
```bash
curl -X GET http://localhost:8000/api/vaults \
  -H "Authorization: Bearer 1|abc123def456..."
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Success",
    "data": [
        {
            "id": 1,
            "name": "My Personal Vault",
            "created_at": "2026-04-22T18:00:00.000000Z",
            "updated_at": "2026-04-22T18:00:00.000000Z"
        },
        {
            "id": 2,
            "name": "Work Vault",
            "created_at": "2026-04-22T18:05:00.000000Z",
            "updated_at": "2026-04-22T18:05:00.000000Z"
        }
    ]
}
```

### Create Vault

**Endpoint:** `POST /vaults`

**Headers:**
- `Authorization: Bearer {token}`
- `Content-Type: application/json`

**Request Body:**
```json
{
    "name": "New Vault Name"
}
```

**cURL Example:**
```bash
curl -X POST http://localhost:8000/api/vaults \
  -H "Authorization: Bearer 1|abc123def456..." \
  -H "Content-Type: application/json" \
  -d '{
    "name": "New Vault Name"
  }'
```

**Success Response (201):**
```json
{
    "success": true,
    "message": "Vault created successfully",
    "data": {
        "id": 3,
        "name": "New Vault Name",
        "created_at": "2026-04-22T18:10:00.000000Z",
        "updated_at": "2026-04-22T18:10:00.000000Z"
    }
}
```

**Error Response (422 for validation errors):**
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "name": ["The name field is required."]
    }
}
```

### Get Vault

**Endpoint:** `GET /vaults/{id}`

**Headers:**
- `Authorization: Bearer {token}`

**cURL Example:**
```bash
curl -X GET http://localhost:8000/api/vaults/1 \
  -H "Authorization: Bearer 1|abc123def456..."
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Success",
    "data": {
        "id": 1,
        "name": "My Personal Vault",
        "created_at": "2026-04-22T18:00:00.000000Z",
        "updated_at": "2026-04-22T18:00:00.000000Z"
    }
}
```

**Error Response (403 if not owned):**
```json
{
    "success": false,
    "message": "Unauthorized"
}
```

**Error Response (404 if not found):**
```json
{
    "success": false,
    "message": "Not Found"
}
```

### Update Vault

**Endpoint:** `PUT /vaults/{id}` or `PATCH /vaults/{id}`

**Headers:**
- `Authorization: Bearer {token}`
- `Content-Type: application/json`

**Request Body:**
```json
{
    "name": "Updated Vault Name"
}
```

**cURL Example:**
```bash
curl -X PUT http://localhost:8000/api/vaults/1 \
  -H "Authorization: Bearer 1|abc123def456..." \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Updated Vault Name"
  }'
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Vault updated successfully",
    "data": {
        "id": 1,
        "name": "Updated Vault Name",
        "created_at": "2026-04-22T18:00:00.000000Z",
        "updated_at": "2026-04-22T18:15:00.000000Z"
    }
}
```

### Delete Vault

**Endpoint:** `DELETE /vaults/{id}`

**Headers:**
- `Authorization: Bearer {token}`

**cURL Example:**
```bash
curl -X DELETE http://localhost:8000/api/vaults/1 \
  -H "Authorization: Bearer 1|abc123def456..."
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Vault deleted successfully",
    "data": null
}
```

## Vault Items API

Items are encrypted data stored within vaults. Supported types: `login`, `note`.

### List Items in Vault

**Endpoint:** `GET /vaults/{vault_id}/items`

**Headers:**
- `Authorization: Bearer {token}`

**cURL Example:**
```bash
curl -X GET http://localhost:8000/api/vaults/1/items \
  -H "Authorization: Bearer 1|abc123def456..."
```

**Success Response (200):**
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
                "username": "myusername",
                "password": "mypassword123",
                "url": "https://example.com",
                "notes": "Optional notes"
            },
            "created_at": "2026-04-22T18:15:00.000000Z",
            "updated_at": "2026-04-22T18:15:00.000000Z"
        },
        {
            "id": 2,
            "vault_id": 1,
            "type": "note",
            "data": {
                "title": "My Secret Note",
                "content": "This is a secure note content."
            },
            "created_at": "2026-04-22T18:20:00.000000Z",
            "updated_at": "2026-04-22T18:20:00.000000Z"
        }
    ]
}
```

### Create Item

**Endpoint:** `POST /vaults/{vault_id}/items`

**Headers:**
- `Authorization: Bearer {token}`
- `Content-Type: application/json`

**Request Body for Login Item:**
```json
{
    "type": "login",
    "data": {
        "username": "myusername",
        "password": "mypassword123",
        "url": "https://example.com",
        "notes": "Optional notes"
    }
}
```

**Request Body for Note Item:**
```json
{
    "type": "note",
    "data": {
        "title": "My Secret Note",
        "content": "This is a secure note content."
    }
}
```

**cURL Example for Login Item:**
```bash
curl -X POST http://localhost:8000/api/vaults/1/items \
  -H "Authorization: Bearer 1|abc123def456..." \
  -H "Content-Type: application/json" \
  -d '{
    "type": "login",
    "data": {
        "username": "myusername",
        "password": "mypassword123",
        "url": "https://example.com",
        "notes": "Optional notes"
    }
  }'
```

**Success Response (201):**
```json
{
    "success": true,
    "message": "Item created successfully",
    "data": {
        "id": 3,
        "vault_id": 1,
        "type": "login",
        "data": {
            "username": "myusername",
            "password": "mypassword123",
            "url": "https://example.com",
            "notes": "Optional notes"
        },
        "created_at": "2026-04-22T18:25:00.000000Z",
        "updated_at": "2026-04-22T18:25:00.000000Z"
    }
}
```

**Error Response (422 for validation errors):**
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "type": ["The type field is required."],
        "data": ["The data field is required."]
    }
}
```

### Get Item

**Endpoint:** `GET /vaults/{vault_id}/items/{item_id}`

**Headers:**
- `Authorization: Bearer {token}`

**cURL Example:**
```bash
curl -X GET http://localhost:8000/api/vaults/1/items/3 \
  -H "Authorization: Bearer 1|abc123def456..."
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Success",
    "data": {
        "id": 3,
        "vault_id": 1,
        "type": "login",
        "data": {
            "username": "myusername",
            "password": "mypassword123",
            "url": "https://example.com",
            "notes": "Optional notes"
        },
        "created_at": "2026-04-22T18:25:00.000000Z",
        "updated_at": "2026-04-22T18:25:00.000000Z"
    }
}
```

**Error Response (403 if not owned):**
```json
{
    "success": false,
    "message": "Unauthorized"
}
```

### Update Item

**Endpoint:** `PUT /vaults/{vault_id}/items/{item_id}` or `PATCH /vaults/{vault_id}/items/{item_id}`

**Headers:**
- `Authorization: Bearer {token}`
- `Content-Type: application/json`

**Request Body:**
```json
{
    "type": "login",
    "data": {
        "username": "updatedusername",
        "password": "newpassword123",
        "url": "https://updated-example.com",
        "notes": "Updated notes"
    }
}
```

**cURL Example:**
```bash
curl -X PUT http://localhost:8000/api/vaults/1/items/3 \
  -H "Authorization: Bearer 1|abc123def456..." \
  -H "Content-Type: application/json" \
  -d '{
    "type": "login",
    "data": {
        "username": "updatedusername",
        "password": "newpassword123",
        "url": "https://updated-example.com",
        "notes": "Updated notes"
    }
  }'
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Item updated successfully",
    "data": {
        "id": 3,
        "vault_id": 1,
        "type": "login",
        "data": {
            "username": "updatedusername",
            "password": "newpassword123",
            "url": "https://updated-example.com",
            "notes": "Updated notes"
        },
        "created_at": "2026-04-22T18:25:00.000000Z",
        "updated_at": "2026-04-22T18:30:00.000000Z"
    }
}
```

### Delete Item

**Endpoint:** `DELETE /vaults/{vault_id}/items/{item_id}`

**Headers:**
- `Authorization: Bearer {token}`

**cURL Example:**
```bash
curl -X DELETE http://localhost:8000/api/vaults/1/items/3 \
  -H "Authorization: Bearer 1|abc123def456..."
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Item deleted successfully",
    "data": null
}
```

## Item Sync API

Synchronize vault items across multiple devices efficiently.

### Sync Items

**Endpoint:** `GET /items/sync?last_sync={timestamp}`

**Headers:**
- `Authorization: Bearer {token}`

**Query Parameters:**
- `last_sync` - ISO 8601 timestamp of the last sync point, e.g. `2026-04-23T12:00:00Z`

**cURL Example:**
```bash
curl -X GET "http://localhost:8000/api/items/sync?last_sync=2026-04-23T12:00:00Z" \
  -H "Authorization: Bearer 1|abc123def456..."
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Success",
  "data": [
    {
      "id": 5,
      "vault_id": 4,
      "type": "login",
      "data": {
        "username": "alice",
        "password": "secret"
      },
      "created_at": "2026-04-22T18:00:00.000000Z",
      "updated_at": "2026-04-23T12:15:00.000000Z",
      "deleted_at": null
    },
    {
      "id": 8,
      "vault_id": 4,
      "type": "note",
      "data": {
        "title": "Old note",
        "content": "This note was deleted"
      },
      "created_at": "2026-04-22T19:00:00.000000Z",
      "updated_at": "2026-04-23T12:30:00.000000Z",
      "deleted_at": "2026-04-23T12:45:00.000000Z"
    }
  ]
}
```

## File API

Upload, manage, and download encrypted files within vaults.

### Upload File

**Endpoint:** `POST /vaults/{vault_id}/files`

**Headers:**
- `Authorization: Bearer {token}`

**Form Data:**
- `file` - The file to upload

**cURL Example:**
```bash
curl -X POST http://localhost:8000/api/vaults/1/files \
  -H "Authorization: Bearer 1|abc123def456..." \
  -F "file=@/path/to/document.pdf"
```

**Success Response (201):**
```json
{
    "success": true,
    "message": "File uploaded successfully",
    "data": {
        "id": 1,
        "vault_id": 1,
        "filename": "document.pdf",
        "original_filename": "document.pdf",
        "mime_type": "application/pdf",
        "size": 12345,
        "created_at": "2026-04-22T18:30:00.000000Z",
        "updated_at": "2026-04-22T18:30:00.000000Z"
    }
}
```

### List Files in Vault

**Endpoint:** `GET /vaults/{vault_id}/files`

**Headers:**
- `Authorization: Bearer {token}`

**cURL Example:**
```bash
curl -X GET http://localhost:8000/api/vaults/1/files \
  -H "Authorization: Bearer 1|abc123def456..."
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Success",
    "data": [
        {
            "id": 1,
            "vault_id": 1,
            "filename": "document.pdf",
            "original_filename": "document.pdf",
            "mime_type": "application/pdf",
            "size": 12345,
            "created_at": "2026-04-22T18:30:00.000000Z",
            "updated_at": "2026-04-22T18:30:00.000000Z"
        }
    ]
}
```

### Get File Metadata

**Endpoint:** `GET /vaults/{vault_id}/files/{file_id}`

**Headers:**
- `Authorization: Bearer {token}`

**cURL Example:**
```bash
curl -X GET http://localhost:8000/api/vaults/1/files/5 \
  -H "Authorization: Bearer 1|abc123def456..."
```

### Update File

**Endpoint:** `POST /vaults/{vault_id}/files/{file_id}`

**Headers:**
- `Authorization: Bearer {token}`

**Form Data:**
- `file` - The new file to upload

**cURL Example:**
```bash
curl -X POST http://localhost:8000/api/vaults/1/files/5 \
  -H "Authorization: Bearer 1|abc123def456..." \
  -F "file=@/path/to/new-document.pdf"
```

### Delete File

**Endpoint:** `DELETE /vaults/{vault_id}/files/{file_id}`

**Headers:**
- `Authorization: Bearer {token}`

**cURL Example:**
```bash
curl -X DELETE http://localhost:8000/api/vaults/1/files/5 \
  -H "Authorization: Bearer 1|abc123def456..."
```

### Get Download URL

**Endpoint:** `GET /vaults/{vault_id}/files/{file_id}/download-url`

**Headers:**
- `Authorization: Bearer {token}`

**cURL Example:**
```bash
curl -X GET http://localhost:8000/api/vaults/1/files/5/download-url \
  -H "Authorization: Bearer 1|abc123def456..."
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Download URL generated",
    "data": {
        "url": "http://localhost:8000/api/files/download/abc123def456...",
        "expires_at": "2026-04-22T19:00:00.000000Z"
    }
}
```

### Download File

**Endpoint:** `GET /files/download/{token}`

**cURL Example:**
```bash
curl -X GET "http://localhost:8000/api/files/download/abc123def456..." \
  --output downloaded-file.pdf
```

## Key Derivation System

### Overview

The system uses PBKDF2 (Password-Based Key Derivation Function 2) to derive strong encryption keys from user passwords.

**Key Parameters:**
- Algorithm: SHA-256
- Iterations: 100,000
- Salt: 16 bytes (random per user)
- Output: 32 bytes (AES-256 key)

### Hashing vs Encryption

| Aspect | Hashing | Encryption |
|--------|---------|-----------|
| Reversible | No | Yes |
| Purpose | Authentication | Data Protection |
| Key Required | No | Yes |
| Example Use | Login passwords | Vault items |

### Security Flow

1. **Registration**: Generate salt, store BCrypt hash, derive encryption key
2. **Login**: Verify password, retrieve salt, re-derive encryption key
3. **Encryption**: Use derived key with AES-256-GCM for data protection
4. **Decryption**: Use derived key to decrypt data on retrieval
5. **Logout**: Clear encryption key from session memory

## File Validation Rules

- **Allowed Types**: pdf, jpg, jpeg, png, gif, svg, doc, docx, txt
- **Maximum Size**: 10 MB
- **Storage**: Files are encrypted and stored in `storage/app/private/vaults/{vault_id}`

## CSRF Protection

Since this is an API-only application using Laravel Sanctum for token-based authentication, CSRF protection is not required for API endpoints. Sanctum automatically handles CSRF protection for SPA authentication but does not require CSRF tokens for API requests authenticated with Bearer tokens.

However, if you plan to add web routes or SPA authentication in the future, consider implementing CSRF protection:

1. **For Web Routes**: Laravel automatically includes CSRF protection
2. **For SPA Authentication**: Sanctum provides CSRF cookies for SPA requests
3. **For API-Only**: Bearer token authentication is sufficient

## Rate Limiting

The API implements comprehensive rate limiting to prevent abuse:

- **Login Endpoint**: 10 requests per minute per IP
- **Registration Endpoint**: 5 requests per minute per IP
- **Other API Endpoints**: 100 requests per minute per IP
- **File Downloads**: Limited by temporary URLs (single-use, time-limited)

Rate limit violations return HTTP 429 with retry information.

## Common Security Mistakes to Avoid

1. **Storing Plain Text Passwords**: Always use BCrypt for password hashing
2. **Reusing Encryption Keys**: Each user should have unique keys derived from their password
3. **Storing Keys in Database**: Encryption keys should only exist in session memory
4. **Weak Key Derivation**: Use sufficient iterations (100,000+) for PBKDF2
5. **No Input Validation**: Validate all user inputs to prevent injection attacks
6. **Missing Authentication**: Require authentication on all sensitive endpoints
7. **Insecure File Storage**: Always encrypt files before storage
8. **Permanent Tokens**: Use temporary tokens for file downloads
9. **No Rate Limiting**: Implement rate limiting to prevent brute force attacks
10. **Missing CORS Configuration**: Configure CORS properly for API security

## API Response Format

All API responses follow a consistent format:

```json
{
    "success": true|false,
    "message": "Description of the result",
    "data": { ... } | [ ... ] | null,
    "errors": { ... } // Only present on validation errors
}
```

## Error Codes

- **200**: Success
- **201**: Created
- **400**: Bad Request
- **401**: Unauthorized
- **403**: Forbidden
- **404**: Not Found
- **422**: Validation Failed
- **500**: Internal Server Error

## Installation & Setup

1. Clone the repository
2. Run `composer install`
3. Run `npm install && npm run build`
4. Copy `.env.example` to `.env` and configure database
5. Run `php artisan migrate`
6. Run `php artisan serve`

## License

This project is open-sourced software licensed under the MIT license.
