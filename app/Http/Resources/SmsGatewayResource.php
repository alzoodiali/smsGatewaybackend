<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SmsGatewayResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'device_id' => $this->device_id,
            'name' => $this->name,
            'phone_number' => $this->phone_number,
            'sim_slot' => $this->sim_slot,
            'status' => $this->status,
            'battery_level' => $this->battery_level,
            'signal_strength' => $this->signal_strength,
            'last_ping' => $this->last_ping?->toIso8601String(),
        ];
    }
}
