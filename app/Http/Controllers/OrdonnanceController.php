<?php
// app/Http/Controllers/API/OrdonnanceController.php
namespace App\Http\Controllers\API;
 
use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\Ordonnance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
 
class OrdonnanceController extends Controller
{
    /** GET /api/ordonnances */
    public function index(Request $request)
    {
        $user  = $request->user();
        $query = Ordonnance::with(['patient', 'doctor.user', 'medicaments', 'consultation']);
 
        if ($user->isDoctor()) {
            $query->where('doctor_id', $user->doctor->id);
        }
 
        $query
            ->when($request->patient_id, fn($q, $p) => $q->where('patient_id', $p))
            ->when($request->doctor_id,  fn($q, $d) => $q->where('doctor_id', $d))
            ->latest();
 
        return response()->json($query->paginate($request->per_page ?? 15));
    }
 
    /** GET /api/ordonnances/{id} */
    public function show(Ordonnance $ordonnance)
    {
        return response()->json(
            $ordonnance->load(['patient', 'doctor.user', 'medicaments', 'consultation'])
        );
    }
 
    /**
     * POST /api/ordonnances
     * Creates an ordonnance with its list of medicaments
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'consultation_id'        => 'required|exists:consultations,id',
            'date_emission'          => 'required|date',
            'validite_jours'         => 'nullable|integer|min:1|max:365',
            'instructions_generales' => 'nullable|string',
            'medicaments'            => 'required|array|min:1',
            'medicaments.*.nom'          => 'required|string|max:150',
            'medicaments.*.dosage'       => 'required|string|max:80',
            'medicaments.*.frequence'    => 'required|string|max:100',
            'medicaments.*.duree'        => 'required|string|max:80',
            'medicaments.*.instructions' => 'nullable|string',
        ]);
 
        $consultation = Consultation::findOrFail($data['consultation_id']);
 
        // Authorization
        $user = $request->user();
        if ($user->isDoctor() && $consultation->doctor_id !== $user->doctor->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }
 
        return DB::transaction(function () use ($data, $consultation) {
            $ordonnance = Ordonnance::create([
                'consultation_id'        => $data['consultation_id'],
                'patient_id'             => $consultation->patient_id,
                'doctor_id'              => $consultation->doctor_id,
                'date_emission'          => $data['date_emission'],
                'validite_jours'         => $data['validite_jours'] ?? 30,
                'instructions_generales' => $data['instructions_generales'] ?? null,
            ]);
 
            foreach ($data['medicaments'] as $med) {
                $ordonnance->medicaments()->create($med);
            }
 
            return response()->json(
                $ordonnance->load(['patient', 'doctor.user', 'medicaments', 'consultation']),
                201
            );
        });
    }
 
    /** PUT /api/ordonnances/{id} */
    public function update(Request $request, Ordonnance $ordonnance)
    {
        $data = $request->validate([
            'validite_jours'         => 'nullable|integer|min:1|max:365',
            'instructions_generales' => 'nullable|string',
            'medicaments'            => 'nullable|array',
            'medicaments.*.id'           => 'nullable|exists:medicaments,id',
            'medicaments.*.nom'          => 'required_with:medicaments|string|max:150',
            'medicaments.*.dosage'       => 'required_with:medicaments|string|max:80',
            'medicaments.*.frequence'    => 'required_with:medicaments|string|max:100',
            'medicaments.*.duree'        => 'required_with:medicaments|string|max:80',
            'medicaments.*.instructions' => 'nullable|string',
        ]);
 
        $user = $request->user();
        if ($user->isDoctor() && $ordonnance->doctor_id !== $user->doctor->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }
 
        DB::transaction(function () use ($data, $ordonnance) {
            $ordonnance->update([
                'validite_jours'         => $data['validite_jours'] ?? $ordonnance->validite_jours,
                'instructions_generales' => $data['instructions_generales'] ?? $ordonnance->instructions_generales,
            ]);
 
            if (!empty($data['medicaments'])) {
                // Replace all medicaments
                $ordonnance->medicaments()->delete();
                foreach ($data['medicaments'] as $med) {
                    $ordonnance->medicaments()->create($med);
                }
            }
        });
 
        return response()->json($ordonnance->fresh()->load(['patient', 'doctor.user', 'medicaments']));
    }
 
    /** DELETE /api/ordonnances/{id} */
    public function destroy(Ordonnance $ordonnance)
    {
        $user = request()->user();
        if ($user->isDoctor() && $ordonnance->doctor_id !== $user->doctor->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }
        $ordonnance->delete();
        return response()->json(['message' => 'Ordonnance supprimée.']);
    }
 
    /**
     * GET /api/ordonnances/{id}/print
     * Returns formatted data for PDF generation on the frontend
     */
    public function printData(Ordonnance $ordonnance)
    {
        $ordonnance->load(['patient', 'doctor.user', 'medicaments', 'consultation']);
 
        return response()->json([
            'clinic' => [
                'nom'      => config('app.clinic_name', 'Clinique Médicale'),
                'adresse'  => config('app.clinic_address', ''),
                'tel'      => config('app.clinic_phone', ''),
                'email'    => config('app.clinic_email', ''),
            ],
            'doctor' => [
                'nom'         => $ordonnance->doctor->user->name,
                'specialite'  => $ordonnance->doctor->specialite,
                'numero_ordre'=> $ordonnance->doctor->numero_ordre,
            ],
            'patient' => [
                'nom_complet'    => $ordonnance->patient->full_name,
                'cin'            => $ordonnance->patient->cin,
                'date_naissance' => $ordonnance->patient->date_naissance?->format('d/m/Y'),
                'age'            => $ordonnance->patient->age,
                'sexe'           => $ordonnance->patient->sexe,
            ],
            'ordonnance' => [
                'numero'                 => str_pad($ordonnance->id, 6, '0', STR_PAD_LEFT),
                'date_emission'          => $ordonnance->date_emission->format('d/m/Y'),
                'validite_jours'         => $ordonnance->validite_jours,
                'instructions_generales' => $ordonnance->instructions_generales,
                'medicaments'            => $ordonnance->medicaments,
                'is_expired'             => $ordonnance->isExpired(),
            ],
            'consultation' => [
                'diagnostic' => $ordonnance->consultation?->diagnostic,
            ],
        ]);
    }
}