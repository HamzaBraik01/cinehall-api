<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User; // Utiliser le modèle directement ici ou un UserRepository
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\UserResource; // Pour formater la réponse 'me'

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if (! $token = auth('api')->attempt($validator->validated())) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->createNewToken($token);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|confirmed|min:6',
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 400);
        }

        // Création de l'utilisateur (pourrait être déplacé dans UserRepository)
        $user = User::create(array_merge(
                    $validator->validated(),
                    [
                        'password' => Hash::make($request->password),
                        'is_admin' => false // Par défaut non admin
                    ]
                ));

        // On log l'utilisateur directement après l'inscription et on retourne un token
        $token = auth('api')->login($user);

        // Retourner la réponse avec le token
        return response()->json([
             'message' => 'User successfully registered',
             // 'user' => new UserResource($user) // Ou inclure dans createNewToken
        ], 201)->withCookie(cookie('token', $token, config('jwt.ttl'), "/", null, false, true)); // Exemple stockage cookie HttpOnly

       // return $this->createNewToken($token); // Alternative: retourner le token direct
    }


    public function logout()
    {
        auth('api')->logout();
        return response()->json(['message' => 'Successfully logged out'])
                ->withCookie(cookie()->forget('token')); // Supprimer le cookie
    }

    public function refresh()
    {
        try {
             $newToken = auth('api')->refresh();
             return $this->createNewToken($newToken);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
             return response()->json(['error' => 'Token is invalid'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
             return response()->json(['error' => 'Could not refresh token'], 500);
        }
    }

    public function me()
    {
        // L'utilisateur est déjà récupéré via le middleware 'auth:api'
        return response()->json(new UserResource(auth('api')->user()));
    }

    // Méthode pour la mise à jour du profil (peut être dans un UserController séparé)
    public function updateProfile(Request $request)
    {
        $user = auth('api')->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|between:2,100',
            'email' => 'sometimes|required|string|email|max:100|unique:users,email,' . $user->id,
            'password' => 'sometimes|confirmed|min:6', // Pour changer le mot de passe
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 400);
        }

        $dataToUpdate = $validator->safe()->except('password', 'password_confirmation'); // Exclure mdp pour le moment

        if ($request->filled('password')) {
            $dataToUpdate['password'] = Hash::make($request->password);
        }

        // Utiliser le UserRepository serait mieux ici
        $user->update($dataToUpdate);

        return response()->json(new UserResource($user));
    }

    // Méthode pour la suppression du compte (peut être dans un UserController séparé)
     public function deleteAccount(Request $request)
     {
         $user = auth('api')->user();

         // Ajouter une validation de mot de passe si nécessaire avant suppression
         // if (!Hash::check($request->input('current_password'), $user->password)) {
         //     return response()->json(['error' => 'Incorrect password'], 401);
         // }

         // Utiliser le UserRepository serait mieux ici
         $user->delete();
         auth('api')->logout(); // Invalider le token actuel

         return response()->json(['message' => 'Account successfully deleted'], 200)
                 ->withCookie(cookie()->forget('token'));
     }


    protected function createNewToken($token){
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60, // en secondes
            'user' => new UserResource(auth('api')->user())
        ]);
         //->withCookie(cookie('token', $token, config('jwt.ttl'), "/", null, false, true)); // Option cookie HttpOnly
    }
}