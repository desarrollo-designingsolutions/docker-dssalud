<?php

namespace App\Models;

use App\Enums\Assignment\StatusAssignmentEnum;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use HasFactory, HasUuids, Searchable, SoftDeletes;

    protected $fillable = [
        'id',
        'company_id',
        'type_identification',
        'identification_number',
        'first_name',
        'second_name',
        'first_surname',
        'second_surname',
        'gender',
    ];

    public function getFullNameAttribute()
    {
        return $this->first_name.' '.$this->second_name.' '.$this->first_surname.' '.$this->second_surname;
    }

    public function invoice_audit(): BelongsTo
    {
        return $this->belongsTo(InvoiceAudit::class);
    }

    public function glosas()
    {
        return $this->hasManyThrough(
            Glosa::class,
            Service::class
        );
    }

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function sumGlosasTotalValue()
    {
        return $this->glosas()->sum('glosa_value');
    }

    public function assignmentStatusFor($request): string
    {
        $hasPending = $this->invoicePatients()
            ->whereHas('assignment', function ($query) use ($request) {
                $query->whereNot(
                    'status',
                    StatusAssignmentEnum::ASSIGNMENT_EST_003->value
                );

                if (! empty($request['user_id'])) {
                    $query->where('user_id', $request['user_id']);
                }
            })
            ->exists();

        return $hasPending ? 'pending' : 'finished';
    }

    public function invoicePatients()
    {
        return $this->belongsToMany(
            InvoiceAudit::class,
            'invoice_audit_patients',
            'patient_id',
            'invoice_audit_id'
        );
    }
}
