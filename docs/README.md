# Vault System API Documentation

This document explains the secure vault file storage API and includes example `curl` requests.

## Overview

The API supports:
- user registration and login via Laravel Sanctum
- vault creation and management
- encrypted item storage and sync
- secure encrypted file upload, update, listing, deletion, and temporary download URLs

Files are encrypted using AES-256-GCM before storage and decrypted only at download time.

## Authentication

Authenticate using the user token returned by login or registration.

### Login

```bash
curl -X POST http://localhost/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"secret123"}'
```

Response includes `token`.

### Auth header

Use:

```bash
-H "Authorization: Bearer <token>"
```

## Vault file endpoints

### Upload a file

```bash
curl -X POST http://localhost/api/vaults/1/files \
  -H "Authorization: Bearer <token>" \
  -F "file=@/path/to/document.pdf"
```

Successful response returns the encrypted file metadata.

### List files in a vault

```bash
curl -X GET http://localhost/api/vaults/1/files \
  -H "Authorization: Bearer <token>"
```

### Get file metadata

```bash
curl -X GET http://localhost/api/vaults/1/files/5 \
  -H "Authorization: Bearer <token>"
```

### Update a file

```bash
curl -X POST http://localhost/api/vaults/1/files/5 \
  -H "Authorization: Bearer <token>" \
  -F "file=@/path/to/new-document.pdf" \
  -X PUT
```

### Delete a file

```bash
curl -X DELETE http://localhost/api/vaults/1/files/5 \
  -H "Authorization: Bearer <token>"
```

## Temporary download URL

### Request a download URL

```bash
curl -X GET http://localhost/api/vaults/1/files/5/download-url \
  -H "Authorization: Bearer <token>"
```

Response contains a temporary URL and expiration time.

### Download the decrypted file

```bash
curl -X GET "http://localhost/api/files/download/<token>" --output downloaded-file.pdf
```

The temporary token is valid only for a short time and is single-use.

## Validation rules

Uploaded files must be one of the allowed types and under 10 MB:
- pdf
- jpg, jpeg, png, gif, svg
- doc, docx
- txt

## Notes

- Files are stored encrypted in `storage/app/private/vaults/{vault_id}`.
- Only the authenticated vault owner may manage and download files.
- The download endpoint decrypts the file using the stored encryption key and returns the original file content.
