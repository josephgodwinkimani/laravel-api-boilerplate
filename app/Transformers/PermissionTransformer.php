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

use App\Models\Permission;
use Illuminate\Support\Carbon;
use League\Fractal;

class PermissionTransformer extends Fractal\TransformerAbstract
{
    /**
     * Summary of transform
     *
     * @param \App\Models\Permission|array|null $user
     * @return array
     */
    public function transform(Permission|array|null $permission)
    {
        if (is_null($permission)) {
            return [];
        }

        if (is_array($permission)) {
            $permission = json_decode(json_encode($permission));
        }

        return [
            'id' => (int) $permission->id,
            'name' => $permission->name,
            'roles' => $permission->roles,
            'created_at' => Carbon::parse($permission->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => Carbon::parse($permission->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}
