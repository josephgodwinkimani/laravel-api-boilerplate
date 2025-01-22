<?php

declare(strict_types=1);

/*
 * This file is part of the laravel-api-boilerplate project.
 *
 * (c) Joseph Godwin Kimani <josephgodwinkimani@gmx.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Transformers;

use App\Models\Role;
use Illuminate\Support\Carbon;
use League\Fractal;

class RoleTransformer extends Fractal\TransformerAbstract
{
    /**
     * Summary of transform
     *
     * @param \App\Models\Role|array|null $user
     * @return array
     */
    public function transform(Role|array|null $role)
    {
        if (is_null($role)) {
            return [];
        }

        if (is_array($role)) {
            $role = json_decode(json_encode($role));
        }

        return [
            'id' => (int) $role->id,
            'name' => $role->name,
            'permissions' => $role->permissions,
            'created_at' => Carbon::parse($role->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => Carbon::parse($role->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}
