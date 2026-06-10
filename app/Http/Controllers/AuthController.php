<?php
// app/Http/Controllers/API/AuthController.php
namespace App\Http\Controllers\API;
 
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
 
class AuthController extends Controller
{
    /**
     * POST /api/login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);
 
        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['Les identifiants sont incorrects.'],
            ]);
        }
 
        /** @var User $user */
        $user = Auth::user();
 
        if (!$user->is_active) {
            Auth::logout();
            return response()->json(['message' => 'Compte désactivé. Contactez l\'administrateur.'], 403);
        }
 
        $token = $user->createToken('auth_token')->plainTextToken;
 
        // Load role-specific profile
        $profile = match ($user->role) {
            'doctor'    => $user->load('doctor'),
            'reception' => $user->load('reception'),
            default     => $user,
        };
 
        return response()->json([
            'token'   => $token,
            'user'    => $profile,
            'role'    => $user->role,
            'message' => 'Connexion réussie.',
        ]);
    }
 
    /**
     * POST /api/logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Déconnexion réussie.']);
    }
 
    /**
     * GET /api/me
     */
    public function me(Request $request)
    {
        $user = $request->user();
        return match ($user->role) {
            'doctor'    => response()->json($user->load('doctor')),
            'reception' => response()->json($user->load('reception')),
            default     => response()->json($user),
        };
    }
 
    /**
     * PUT /api/me/password
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password'         => 'required|min:8|confirmed',
        ]);
 
        $request->user()->update(['password' => bcrypt($request->password)]);
        return response()->json(['message' => 'Mot de passe modifié avec succès.']);
    }
}