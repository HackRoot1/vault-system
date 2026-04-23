# Vault Item Sync Documentation

This document explains how to use the vault item sync endpoint.

## Overview

The sync API returns vault items that were updated after a given timestamp. It supports multi-device sync by allowing clients to request only records changed since their last successful sync.

### Soft deletes
- Deleted items are not removed permanently from the database.
- Deleted items are returned with `deleted_at` set.
- This allows clients to remove deleted items locally and stay in sync.

## Endpoint

**GET** `/api/items/sync?last_sync={timestamp}`

**Headers:**
- `Authorization: Bearer {token}`

### Query parameters
- `last_sync` - ISO 8601 timestamp of the last sync point, e.g. `2026-04-23T12:00:00Z`

## Response

The endpoint returns only items that have changed since `last_sync`.
- Includes items with `updated_at > last_sync`
- Includes soft-deleted items with `deleted_at > last_sync`

Example response:
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

## How multi-device sync works

1. Device A syncs at `2026-04-23T12:00:00Z`.
2. Device B changes an item at `2026-04-23T12:15:00Z`.
3. Device A later calls `/api/items/sync?last_sync=2026-04-23T12:00:00Z`.
4. The server returns only items changed after that timestamp.
5. Device A updates or removes local items with the returned data.

This avoids downloading the full dataset and keeps devices in sync efficiently.

## Handling edge cases

### Deleted items
- Soft deletes preserve deleted item records.
- If an item is deleted after `last_sync`, the record is included with `deleted_at` set.
- The client should remove or mark those items as deleted locally.

### Timestamp drift
- Use UTC timestamps for `last_sync` to avoid timezone mismatches.
- Example format: `2026-04-23T12:00:00Z`

### No changes since last sync
- The API returns an empty `data` array.

### Invalid timestamp
- If `last_sync` is missing or invalid, the endpoint returns a 400 error.

## Example curl request

```bash
curl -X GET "http://localhost:8000/api/items/sync?last_sync=2026-04-23T12:00:00Z" \
  -H "Authorization: Bearer 1|abc123def456..."
```

## Notes

- This endpoint is protected by Sanctum.
- Only items belonging to the authenticated user's vaults are returned.
- The `updated_at` column is used to determine changed items.
- Soft delete support ensures deleted items replicate correctly across devices.
