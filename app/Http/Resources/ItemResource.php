<?php

namespace App\Http\Resources;

use App\Helpers\EncryptionHelper;
use App\Services\EncryptionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $key = EncryptionHelper::getUserKey();
        if (!$key) {
            throw new \Exception('Encryption key not available');
        }

        $encryption = new EncryptionService($key);
        $decryptedData = json_decode(
            $encryption->decrypt(
                $this->encrypted_data,
                $this->iv,
                $this->tag
            ),
            true
        );

        return [
            'id' => $this->id,
            'vault_id' => $this->vault_id,
            'type' => $this->type,
            'data' => $decryptedData,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
