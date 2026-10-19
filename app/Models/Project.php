<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'template_id',
        'template_version_id',
        'title',
        'slug',
        'status',
        'recipient_name',
        'age',
        'birthday_date',
        'sender_name',
        'short_message',
        'letter_content',
        'closing_message',
        'custom_settings',
        'published_at',
        'views_count',
    ];

    protected function casts(): array
    {
        return [
            'age' => 'integer',
            'birthday_date' => 'date:Y-m-d',
            'custom_settings' => 'array',
            'published_at' => 'datetime',
            'views_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function templateVersion(): BelongsTo
    {
        return $this->belongsTo(TemplateVersion::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(ProjectMedia::class)->orderBy('sort_order', 'asc');
    }

    public function sharedLink(): HasOne
    {
        return $this->hasOne(SharedLink::class);
    }
}
