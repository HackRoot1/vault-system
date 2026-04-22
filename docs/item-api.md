# Vault Items API Documentation

This document provides examples of how to use the Vault Items APIs with encryption support. All item endpoints require authentication via Bearer token.

## Base URL
Assume the base URL is `http://localhost:8000/api` (adjust according to your setup).

## Authentication
All item endpoints require the `Authorization: Bearer {token}` header. Obtain the token from the login API.

## Encryption Overview

Items are stored with end-to-end encryption using AES-256-GCM. The data is never stored in plain text.

### Data Structure Before Encryption

**Login Item Example:**
```json
{
    "username": "myusername",
    "password": "mypassword123",
    "url": "https://example.com",
    "notes": "Optional notes"
}
```

**Note Item Example:**
```json
{
    "title": "My Secret Note",
    "content": "This is a secure note content."
}
```

### Encrypted Storage Format

The JSON data is encrypted and stored as:
- `encrypted_data`: Base64-encoded encrypted payload
- `iv`: Base64-encoded initialization vector (12 bytes)
- `tag`: Base64-encoded authentication tag (16 bytes)

**Database Example:**
```sql
INSERT INTO items (vault_id, type, encrypted_data, iv, tag, created_at, updated_at) VALUES (
    1,
    'login',
    'U2FsdGVkX1+...',  -- Encrypted data
    'MTIzNDU2Nzg5MDEy',  -- IV
    'YWJjZGVmZ2hpams=',  -- Tag
    NOW(),
    NOW()
);
```

## Endpoints

### 1. List Items in Vault
**Endpoint:** `GET /vaults/{vault_id}/items`

**Description:** Get all items in a specific vault (must belong to authenticated user).

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

### 2. Create Item
**Endpoint:** `POST /vaults/{vault_id}/items`

**Description:** Create a new item in a specific vault (must belong to authenticated user).

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

### 3. Get Item
**Endpoint:** `GET /vaults/{vault_id}/items/{item_id}`

**Description:** Get a specific item from a vault (must belong to authenticated user).

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

### 4. Update Item
**Endpoint:** `PUT /vaults/{vault_id}/items/{item_id}` or `PATCH /vaults/{vault_id}/items/{item_id}`

**Description:** Update a specific item in a vault (must belong to authenticated user).

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

### 5. Delete Item
**Endpoint:** `DELETE /vaults/{vault_id}/items/{item_id}`

**Description:** Delete a specific item from a vault (must belong to authenticated user).

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

## Security Notes

- Data is encrypted at rest using AES-256-GCM with unique IV and authentication tag per item
- Plain text data is never stored in the database
- Encryption/decryption happens only in memory during API requests
- Each item is encrypted independently for better security
- The encryption key is derived from the application key (should be per-user in production)

## Notes
- All endpoints require authentication and proper vault/item ownership
- Item types are restricted to 'login' and 'note'
- The 'data' field must be a valid JSON object
- API responses use a consistent format with `success`, `message`, and `data` fields
- Use the token obtained from login in the Authorization header for all requests