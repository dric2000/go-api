<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\RegisterResource;

class UserController extends Controller
{
    // Mise à jour du profil (nom, email, téléphone) de l'utilisateur connecté.
    public function update(UpdateUserRequest $request)
    {
        $user = $request->user();
        $user->update($request->only(['name', 'email', 'phone']));

        return response()->json([
            'message' => 'Profil mis à jour.',
            'user' => new RegisterResource($user),
        ]);
    }
}
