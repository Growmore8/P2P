<?php

namespace App\Models\Concerns;

/**
 * For models without updated_at: fill created_at from the app clock (app timezone),
 * instead of the database default (which is UTC and would show the wrong time).
 */
trait SetsCreatedAt
{
    protected static function bootSetsCreatedAt(): void
    {
        static::creating(function ($model) {
            if (empty($model->created_at)) {
                $model->created_at = now();
            }
        });
    }
}
