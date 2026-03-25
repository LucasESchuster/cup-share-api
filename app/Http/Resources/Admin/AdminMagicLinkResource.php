<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminMagicLinkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'user'       => [
                'id'    => $this->user->id,
                'name'  => $this->user->name,
                'email' => $this->user->email,
            ],
            'created_at' => $this->created_at,
            'expires_at' => $this->expires_at,
            'used_at'    => $this->used_at,
            'status'     => $this->resolveStatus(),
        ];
    }

    private function resolveStatus(): string
    {
        if ($this->used_at !== null) {
            return 'used';
        }

        if ($this->expires_at->isPast()) {
            return 'expired';
        }

        return 'pending';
    }
}
