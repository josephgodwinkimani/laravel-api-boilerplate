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

use App\Models\User;
use Illuminate\Support\Carbon;
use League\Fractal;

class UserTransformer extends Fractal\TransformerAbstract
{
    /**
     * Summary of transform
     *
     * @param \App\Models\User|array|null $user
     * @return array
     */
    public function transform(User|array|null $user)
    {
        if (is_null($user)) {
            return [];
        }

        if (is_array($user)) {
            $user = json_decode(json_encode($user));
        }

        return [
            'id' => (int) $user->id,
            'token' => $user->token,
            'name' => $user->name,
            'roles' => $user->roles,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
            'remember_token' => $user->remember_token,
            'created_at' => Carbon::parse($user->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => Carbon::parse($user->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}
