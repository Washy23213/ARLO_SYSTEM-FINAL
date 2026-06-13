<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlumniImportBatch extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'source_file_name',
        'imported_by_guard',
        'imported_by_id',
        'imported_at',
        'rows_count',
        'imported_count',
        'updated_count',
        'skipped_count',
    ];

    protected function casts(): array
    {
        return [
            'imported_at' => 'datetime',
            'rows_count' => 'integer',
            'imported_count' => 'integer',
            'updated_count' => 'integer',
            'skipped_count' => 'integer',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'alumni_import_batch_id');
    }
}
