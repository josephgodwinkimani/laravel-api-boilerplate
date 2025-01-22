<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */

namespace App\Models{
    /**
     *
     *
     * @property int $id
     * @property string $name
     * @property \Illuminate\Support\Carbon|null $created_at
     * @property \Illuminate\Support\Carbon|null $updated_at
     * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Role> $roles
     * @property-read int|null $roles_count
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission newModelQuery()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission newQuery()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission query()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereCreatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereId($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereName($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereUpdatedAt($value)
     */
    class Permission extends \Eloquent
    {
    }
}

namespace App\Models{
    /**
     *
     *
     * @property int $id
     * @property string $name
     * @property \Illuminate\Support\Carbon|null $created_at
     * @property \Illuminate\Support\Carbon|null $updated_at
     * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Permission> $permissions
     * @property-read int|null $permissions_count
     * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
     * @property-read int|null $users_count
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newModelQuery()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newQuery()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Role query()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereCreatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereId($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereName($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereUpdatedAt($value)
     */
    class Role extends \Eloquent
    {
    }
}

namespace App\Models{
    /**
     *
     *
     * @property int $id
     * @property string $name
     * @property string $subdomain
     * @property string $connection
     * @property \Illuminate\Support\Carbon|null $created_at
     * @property \Illuminate\Support\Carbon|null $updated_at
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant newModelQuery()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant newQuery()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant query()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant whereConnection($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant whereCreatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant whereId($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant whereName($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant whereSubdomain($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant whereUpdatedAt($value)
     */
    class Tenant extends \Eloquent
    {
    }
}

namespace App\Models{
    /**
     *
     *
     * @property int $id
     * @property string $name
     * @property string $phone
     * @property string $email
     * @property \Illuminate\Support\Carbon|null $email_verified_at
     * @property string $password
     * @property string|null $remember_token
     * @property \Illuminate\Support\Carbon|null $created_at
     * @property \Illuminate\Support\Carbon|null $updated_at
     * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
     * @property-read int|null $notifications_count
     * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Role> $roles
     * @property-read int|null $roles_count
     * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
     * @property-read int|null $tokens_count
     * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePhone($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
     * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
     */
    class User extends \Eloquent
    {
    }
}
