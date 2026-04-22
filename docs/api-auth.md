# API Authentication Documentation

This document provides examples of how to use the authentication APIs built with Laravel Sanctum.

## Base URL
Assume the base URL is `http://localhost:8000/api` (adjust according to your setup).

## Endpoints

### 1. User Registration
**Endpoint:** `POST /register`

**Description:** Register a new user.

**Request Body:**
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

**cURL Example:**
```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
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
            "email_verified_at": null,
            "created_at": "2026-04-22T12:00:00.000000Z",
            "updated_at": "2026-04-22T12:00:00.000000Z"
        },
        "token": "1|abc123def456..."
    }
}
```

**Error Response (422 for validation errors):**
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "email": ["The email has already been taken."]
    }
}
```

### 2. User Login
**Endpoint:** `POST /login`

**Description:** Authenticate a user and get an API token.

**Request Body:**
```json
{
    "email": "john@example.com",
    "password": "password123"
}
```

**cURL Example:**
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "password123"
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
            "email": "john@example.com",
            "email_verified_at": null,
            "created_at": "2026-04-22T12:00:00.000000Z",
            "updated_at": "2026-04-22T12:00:00.000000Z"
        },
        "token": "1|abc123def456..."
    }
}
```

**Error Response (401):**
```json
{
    "success": false,
    "message": "Invalid credentials"
}
```

**Rate Limiting:** This endpoint is rate limited to 10 attempts per minute. If exceeded, you'll get a 429 status code.

### 3. User Logout
**Endpoint:** `POST /logout`

**Description:** Revoke the current API token.

**Headers:**
- `Authorization: Bearer {token}`

**cURL Example:**
```bash
curl -X POST http://localhost:8000/api/logout \
  -H "Authorization: Bearer 1|abc123def456..."
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Logged out successfully",
    "data": null
}
```

**Error Response (401 if not authenticated):**
```json
{
    "success": false,
    "message": "Unauthenticated."
}
```

### 4. Get Authenticated User
**Endpoint:** `GET /user`

**Description:** Get the details of the authenticated user.

**Headers:**
- `Authorization: Bearer {token}`

**cURL Example:**
```bash
curl -X GET http://localhost:8000/api/user \
  -H "Authorization: Bearer 1|abc123def456..."
```

**Success Response (200):**
```json
{
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "email_verified_at": null,
    "created_at": "2026-04-22T12:00:00.000000Z",
    "updated_at": "2026-04-22T12:00:00.000000Z"
}
```

## Notes
- All protected routes require the `Authorization: Bearer {token}` header.
- Tokens are issued on login and registration.
- Passwords are hashed using Laravel's default hashing.
- Rate limiting is applied to the login endpoint to prevent brute force attacks.
- Use the token in the Authorization header for subsequent requests to protected endpoints.