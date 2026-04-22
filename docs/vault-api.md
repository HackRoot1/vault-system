# Vault API Documentation

This document provides examples of how to use the Vault APIs. All vault endpoints require authentication via Bearer token.

## Base URL
Assume the base URL is `http://localhost:8000/api` (adjust according to your setup).

## Authentication
All vault endpoints require the `Authorization: Bearer {token}` header. Obtain the token from the login API.

## Endpoints

### 1. List Vaults
**Endpoint:** `GET /vaults`

**Description:** Get all vaults belonging to the authenticated user.

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

### 2. Create Vault
**Endpoint:** `POST /vaults`

**Description:** Create a new vault for the authenticated user.

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

### 3. Get Vault
**Endpoint:** `GET /vaults/{id}`

**Description:** Get a specific vault by ID (must belong to authenticated user).

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

### 4. Update Vault
**Endpoint:** `PUT /vaults/{id}` or `PATCH /vaults/{id}`

**Description:** Update a specific vault (must belong to authenticated user).

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

### 5. Delete Vault
**Endpoint:** `DELETE /vaults/{id}`

**Description:** Delete a specific vault (must belong to authenticated user).

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

## Notes
- All endpoints require authentication.
- Users can only access/modify their own vaults.
- Vault names are required and must be strings up to 255 characters.
- API responses use a consistent format with `success`, `message`, and `data` fields.
- Use the token obtained from login in the Authorization header for all requests.