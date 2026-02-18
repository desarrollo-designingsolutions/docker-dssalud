<?php

namespace App\Http\Resources\InvoiceAudit;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class InvoiceAuditPaginateInvoiceAuditAllResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        return [
            'id' => $this->id,
            'third_id' => $this->third_id,
            'invoice_number' => $this->invoice_number,
            'total_value' => formatNumber($this->total_value),
            'origin' => $this->origin,
            'expedition_date' => Carbon::parse($this->expedition_date)->format('Y-m-d'),
            'date_entry' => Carbon::parse($this->date_entry)->format('Y-m-d'),
            'date_departure' => Carbon::parse($this->date_departure)->format('Y-m-d'),
            'modality' => $this->modality,
            'regimen' => $this->regimen,
            'coverage' => $this->coverage,
            'contract_number' => $this->contract_number,
            'status' => Str::ucfirst($this->status)
        ];
    }
}
