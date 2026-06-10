<?php
// app/Http/Controllers/API/DoctorController.php
namespace App\Http\Controllers\API;
 
use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
 
class DoctorController extends Controller
{
    /** GET /api/doctors */
    public function index(Request $request)
    {
        $query = Doctor::with('user')
            ->when($request->search, fn($q, $s) =>
                $q->whereHas('user', fn($u) => $u->where('name', 'like', "%$s%"))
                  ->orWhere('specialite', 'like', "%$s%")
            )
            ->when($request->specialite, fn($q, $s) =>
                $q->where('specialite', 'like', "%$s%")
            );
 
        return response()->json($query->paginate($request->per_page ?? 15));
    }
 
    /** GET /api/doctors/{id} */
    public function show(Doctor $doctor)
    {
        return response()->json($doctor->load('user'));
    }
 
    /** POST /api/doctors  (Admin only) */
    public function store(Request $request)
    {
        $request->validate([
            'name'             => 'required|string|max:100',
            'email'            => 'required|email|unique:users,email',
            'password'         => 'required|min:8',
            'phone'            => 'nullable|string|max:20',
            'specialite'       => 'required|string|max:100',
            'numero_ordre'     => 'nullable|string|max:50',
            'diplome'          => 'nullable|string|max:150',
            'experience_ans'   => 'nullable|integer|min:0',
            'consultation_fee' => 'nullable|numeric|min:0',
            'bio'              => 'nullable|string',
            'horaires'         => 'nullable|array',
        ]);
 
        return DB::transaction(function () use ($request) {
            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => bcrypt($request->password),
                'role'     => 'doctor',
                'phone'    => $request->phone,
            ]);
 
            $doctor = Doctor::create([
                'user_id'          => $user->id,
                'specialite'       => $request->specialite,
                'numero_ordre'     => $request->numero_ordre,
                'diplome'          => $request->diplome,
                'experience_ans'   => $request->experience_ans ?? 0,
                'consultation_fee' => $request->consultation_fee ?? 0,
                'bio'              => $request->bio,
                'horaires'         => $request->horaires,
            ]);
 
            return response()->json($doctor->load('user'), 201);
        });
    }
 
    /** PUT /api/doctors/{id}  (Admin only) */
    public function update(Request $request, Doctor $doctor)
    {
        $request->validate([
            'name'             => 'sometimes|string|max:100',
            'email'            => ['sometimes','email', Rule::unique('users')->ignore($doctor->user_id)],
            'phone'            => 'nullable|string|max:20',
            'specialite'       => 'sometimes|string|max:100',
            'numero_ordre'     => 'nullable|string|max:50',
            'diplome'          => 'nullable|string|max:150',
            'experience_ans'   => 'nullable|integer|min:0',
            'consultation_fee' => 'nullable|numeric|min:0',
            'bio'              => 'nullable|string',
            'horaires'         => 'nullable|array',
            'is_active'        => 'nullable|boolean',
        ]);
 
        DB::transaction(function () use ($request, $doctor) {
            $doctor->user->update($request->only('name', 'email', 'phone', 'is_active'));
            $doctor->update($request->only(
                'specialite','numero_ordre','diplome','experience_ans','consultation_fee','bio','horaires'
            ));
        });
 
        return response()->json($doctor->fresh()->load('user'));
    }
 
    /** DELETE /api/doctors/{id}  (Admin only) */
    public function destroy(Doctor $doctor)
    {
        $doctor->user->delete(); // cascades to doctor profile
        return response()->json(['message' => 'Médecin supprimé.']);
    }
 
    /**
     * GET /api/doctors/{id}/available-slots?date=2024-05-20
     * Returns available time slots for booking
     */
    public function availableSlots(Request $request, Doctor $doctor)
    {
        $request->validate(['date' => 'required|date|after_or_equal:today']);
 
        $date    = $request->date;
        $dayMap  = ['Sunday'=>'dimanche','Monday'=>'lundi','Tuesday'=>'mardi',
                    'Wednesday'=>'mercredi','Thursday'=>'jeudi','Friday'=>'vendredi','Saturday'=>'samedi'];
        $dayName = $dayMap[date('l', strtotime($date))] ?? null;
 
        $horaires = $doctor->horaires[$dayName] ?? null;
        if (!$horaires) {
            return response()->json(['slots' => [], 'message' => 'Pas de consultation ce jour.']);
        }
 
        [$start, $end] = $horaires;
 
        // Existing appointments that day
        $booked = $doctor->appointments()
            ->where('date_rdv', $date)
            ->whereNotIn('status', ['annule'])
            ->get(['heure_debut', 'heure_fin']);
 
        // Generate 30-min slots
        $slots = [];
        $current = strtotime($start);
        $endTime = strtotime($end);
 
        while ($current + 1800 <= $endTime) {
            $slotStart = date('H:i', $current);
            $slotEnd   = date('H:i', $current + 1800);
 
            $isFree = $booked->every(fn($b) =>
                $slotEnd <= $b->heure_debut || $slotStart >= $b->heure_fin
            );
 
            $slots[] = ['start' => $slotStart, 'end' => $slotEnd, 'available' => $isFree];
            $current += 1800;
        }
 
        return response()->json(['date' => $date, 'slots' => $slots]);
    }
 
    /** GET /api/doctors/{id}/stats  (Admin) */
    public function stats(Doctor $doctor)
    {
        return response()->json([
            'total_appointments'  => $doctor->appointments()->count(),
            'appointments_today'  => $doctor->appointments()->whereDate('date_rdv', today())->count(),
            'total_patients'      => $doctor->appointments()->distinct('patient_id')->count('patient_id'),
            'total_consultations' => $doctor->consultations()->count(),
            'revenue_month'       => $doctor->appointments()
                ->whereMonth('date_rdv', now()->month)
                ->whereHas('paiement', fn($q) => $q->where('status', 'paye'))
                ->with('paiement')
                ->get()
                ->sum(fn($a) => $a->paiement?->montant ?? 0),
        ]);
    }
}