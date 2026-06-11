<?php

namespace Sultonisky\Slogy\Traits;

use Sultonisky\Slogy\Models\ActivityLog;
use Carbon\Carbon;

trait HasSlogy {

    protected static function bootHasSlogy(): void {

        if (config('slogy.events.created', true)) {
            static::created(function ($model) {
                static::logActivity($model, 'created');
            });
        }

        if (config('slogy.events.updated', true)) {
            static::updated(function ($model) {
                static::logActivity($model, 'updated');
            });
        }

        if (config('slogy.events.deleted', true)) {
            static::deleted(function ($model) {
                static::logActivity($model, 'deleted');
            });

            if (method_exists(static::class, 'restored')) {
                static::restored(function ($model) {
                    static::logActivity($model, 'restored');
                });
            }

            if (method_exists(static::class, 'forceDeleted')) {
                static::forceDeleted(function ($model) {
                    static::logActivity($model, 'forceDeleted');
                });
            }
        }

    }

    public function activities()
    {
        return $this->morphMany(ActivityLog::class, 'subject', 'model', 'model_id');
    }

    protected static function logActivity($model, string $action): void
    {
        $ignored = array_merge(
            config('slogy.ignored_attributes', []),
            $model->slogyIgnore ?? []
        );

        $oldValues = null;
        $allAttributes = $model->getAttributes();
        
        // If model has slogyLog, only log those attributes
        if (isset($model->slogyLog) && !empty($model->slogyLog)) {
            $newValues = array_intersect_key($allAttributes, array_flip($model->slogyLog));
        } else {
            $newValues = array_diff_key($allAttributes, array_flip($ignored));
        }

        if ($action === 'updated') {
            $changes = $model->getChanges();
            
            if (isset($model->slogyLog) && !empty($model->slogyLog)) {
                $newValues = array_intersect_key($changes, array_flip($model->slogyLog));
            } else {
                $newValues = array_diff_key($changes, array_flip($ignored));
            }
            
            if (empty($newValues)) {
                return;
            }

            $oldValues = array_intersect_key($model->getOriginal(), $newValues);
        }

        if ($action === 'deleted') {
            $newValues = null;
            $original = $model->getOriginal();
            
            if (isset($model->slogyLog) && !empty($model->slogyLog)) {
                $oldValues = array_intersect_key($original, array_flip($model->slogyLog));
            } else {
                $oldValues = array_diff_key($original, array_flip($ignored));
            }
        }

        if ($action === 'restored' || $action === 'forceDeleted') {
            $newValues = null;
            $oldValues = null;
        }

        $subjectLabel = static::getSlogySubjectLabel($model);
        
        $description = method_exists($model, 'getSlogyDescription') 
            ? $model->getSlogyDescription($action) 
            : "Model " . class_basename($model) . ($subjectLabel ? " ({$subjectLabel})" : "") . " has been {$action}";

        $userId = auth()->id() ?? 0;
        
        // Prepare data for hashing
        $logData = [
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'model' => get_class($model),
            'model_id' => $model->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ];

        // Get previous log's hash
        $lastLog = ActivityLog::getLastLog();
        $previousHash = $lastLog ? $lastLog->current_hash : null;
        $isGenesis = !$lastLog;

        // Create log first to get consistent created_at timestamp
        $log = ActivityLog::create([
            'user_id'       => $userId,
            'action'        => $action,
            'description'   => $description,
            'model'         => get_class($model),
            'model_id'      => $model->id,
            'old_values'    => $oldValues,
            'new_values'    => $newValues,
            'previous_hash' => $previousHash,
            'current_hash'  => '', // Temporary empty
            'is_genesis'    => $isGenesis,
        ]);

        // Now calculate hash with the actual created_at from database
        $timestamp = $log->created_at->toDateTimeString();
        $currentHash = ActivityLog::calculateHash($previousHash, $logData, $timestamp);

        // Update the log with correct hash
        $log->current_hash = $currentHash;
        $log->save();
    }

    protected static function getSlogySubjectLabel($model): ?string
    {
        if (isset($model->slogyLabel)) {
            return $model->{$model->slogyLabel};
        }

        foreach (['name', 'title', 'email', 'username'] as $attribute) {
            if (isset($model->{$attribute})) {
                return $model->{$attribute};
            }
        }

        return null;
    }
}