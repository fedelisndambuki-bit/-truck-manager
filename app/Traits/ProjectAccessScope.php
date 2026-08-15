<?php

namespace App\Traits;

use App\Models\Project;
use App\Models\ProjectUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * A second, narrower global scope layered on top of MultiTenantScope's
 * company-level scope (both apply with AND semantics — confirmed safe,
 * Laravel supports any number of named global scopes per model). Internal
 * staff are completely unaffected: the scope is a no-op unless the acting
 * user is project-restricted (User::isProjectRestricted()), which only
 * external users and explicitly-flagged internal users trigger.
 *
 * Deliberately NOT applied to BaseModel globally — only opted into the
 * confirmed set of models that carry a `project_id` column (or, for
 * `Project` itself, `id`). Applying it universally would crash any query
 * against an unrelated model with no such column.
 */
trait ProjectAccessScope
{
    protected static function bootProjectAccessScope(): void
    {
        static::addGlobalScope('project_access', function (Builder $builder) {
            $user = Auth::user();

            if (! $user || ! $user->isProjectRestricted()) {
                return;
            }

            // Query ProjectUser directly rather than via User::assignedProjects()
            // (a Project-joining BelongsToMany) — joining triggers Project's
            // own global scopes (both 'project_access', which would recurse
            // forever, and 'company', whose unqualified `company_id` column
            // becomes ambiguous once project_users is joined in).
            $projectIds = ProjectUser::query()
                ->where('user_id', $user->id)
                ->where('status', 'Active')
                ->pluck('project_id');
            $table = $builder->getModel()->getTable();
            $column = static::class === Project::class ? 'id' : 'project_id';

            $builder->whereIn("{$table}.{$column}", $projectIds);
        });
    }

    public function scopeWithoutProjectAccessScope(Builder $builder): Builder
    {
        return $builder->withoutGlobalScope('project_access');
    }
}
