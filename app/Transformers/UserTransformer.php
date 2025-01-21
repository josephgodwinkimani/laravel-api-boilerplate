<?php

namespace App\Transformers;

use App\Models\User;
use Illuminate\Support\Carbon;
use League\Fractal;

class UserTransformer extends Fractal\TransformerAbstract
{
    /**
     * Summary of transform
     *
     * @param \App\Models\User|null $user
     * @return array
     */
    public function transform(User|null $user)
    {
        if (is_null($user)) {
            return [];
        }

        return [
            'id' => (int) $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
            'remember_token' => $user->remember_token,
            'created_at' => Carbon::parse($user->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => Carbon::parse($user->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}
