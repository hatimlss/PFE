<?php
// app/Http/Controllers/API/ReceptionController.php
namespace App\Http\Controllers\API;
 
use App\Http\Controllers\Controller;
use App\Models\Reception;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
 
class ReceptionController extends Controller
{
    /** GET /api/receptions */
    public function index(Request $request)
    {
        $query = Reception::with('user')
            ->when($request->search, fn($q, $s) =>
                $q->whereHas('user', fn($u) => $u->where('name', 'like', "%$s%"))
            );
 
        return response()->json($query->paginate($request->per_page ?? 15));
    }
 
    /** GET /api/receptions/{id} */
    public function show(Reception $reception)
    {
        return response()->json($reception->load('user'));
    }
 
    /** POST /api/receptions (Admin only) */
    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'phone'    => 'nullable|string|max:20',
            'poste'    => 'nullable|string|max:100',
        ]);
 
        return DB::transaction(function () use ($request) {
            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => bcrypt($request->password),
                'role'     => 'reception',
                'phone'    => $request->phone,
            ]);
 
            $reception = Reception::create([
                'user_id' => $user->id,
                'poste'   => $request->poste ?? "Agent d'accueil",
            ]);
 
            return response()->json($reception->load('user'), 201);
        });
    }
 
    /** PUT /api/receptions/{id} (Admin only) */
    public function update(Request $request, Reception $reception)
    {
        $request->validate([
            'name'      => 'sometimes|string|max:100',
            'email'     => ['sometimes','email', Rule::unique('users')->ignore($reception->user_id)],
            'phone'     => 'nullable|string|max:20',
            'poste'     => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
        ]);
 
        DB::transaction(function () use ($request, $reception) {
            $reception->user->update($request->only('name', 'email', 'phone', 'is_active'));
            $reception->update($request->only('poste'));
        });
 
        return response()->json($reception->fresh()->load('user'));
    }
 
    /** DELETE /api/receptions/{id} (Admin only) */
    public function destroy(Reception $reception)
    {
        $reception->user->delete();
        return response()->json(['message' => 'Agent de réception supprimé.']);
    }
}