<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\LoginResource;
use App\Http\Resources\RegisterResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterRequest $request) {

        $user = DB::transaction(function() use($request) {
            $user = User::create([
                'name' => $request->name, 
                'email' => $request->email, 
                'phone' => $request->phone, 
                'password' => Hash::make($request->password),
            ]); 

            $user->travelerProfile()->create(); 

            return $user; 
        });
    
    

        $token = $user->createToken('mobile-app')->plainTextToken; 

        return response()->json([
            'user' => new RegisterResource($user), 
            'token' =>$token, 
            // un seul $ : $$user est le "variable variable" de PHP, pas une faute de frappe anodine -
            // ca castait $user en string (JSON via __toString d'Eloquent) et cherchait une variable portant ce nom
            'Votre profil voyageur' => $user->travelerProfile,
        ]); 
    }

    public function login(LoginRequest $request) {
        $user = User::where('email', $request->email)->first();
        
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['messae' => 'Identifiants incorrects'], 401); 
        }

        $token = $user->createToken('mobile-app')->plainTextToken; 

        return response()->json([
            'user' => new LoginResource($user), 
            'token' => $token
        ]); 
    }
}
