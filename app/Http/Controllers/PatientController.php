<?php
// app/Http/Controllers/API/PatientController.php
namespace App\Http\Controllers\API;
 
use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
 
class PatientController extends Controller
{
    /** GET /api/patients */
    public function index(Request $request)
    {
        $query = Patient::with('medecinTraitant.user')
            ->when($request->search, fn($q, $s) =>
                $q->where('nom', 'like', "%$s%")
                  ->orWhere('prenom', 'like', "%$s%")
                  ->orWhere('cin', 'like', "%$s%")
                  ->orWhere('telephone', 'like', "%$s%")
            )
            ->when($request->doctor_id, fn($q, $d) =>
                $q->where('medecin_traitant_id', $d)
            )
            ->when(isset($request->is_active), fn($q) =>
                $q->where('is_active', $request->boolean('is_active'))
            )
            ->latest();
 
        return response()->json($query->paginate($request->per_page ?? 15));
    }
 
    /** GET /api/patients/{id} */
    public function show(Patient $patient)
    {
        return response()->json(
            $patient->load([
                'medecinTraitant.user',
                'appointments.doctor.user',
                'consultations.ordonnances.medicaments',
                'paiements',
            ])
        );
    }
 
    /** POST /api/patients */
    public function store(Request $request)
    {
        $data = $request->validate([
            'nom'                  => 'required|string|max:80',
            'prenom'               => 'required|string|max:80',
            'cin'                  => 'required|string|max:20|unique:patients,cin',
            'date_naissance'       => 'nullable|date|before:today',
            'sexe'                 => 'required|in:M,F,autre',
            'telephone'            => 'nullable|string|max:20',
            'email'                => 'nullable|email',
            'adresse'              => 'nullable|string',
            'ville'                => 'nullable|string|max:80',
            'groupe_sanguin'       => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'description_maladie'  => 'nullable|string',
            'antecedents'          => 'nullable|string',
            'allergies'            => 'nullable|string',
            'assurance_nom'        => 'nullable|string|max:100',
            'assurance_numero'     => 'nullable|string|max:100',
            'medecin_traitant_id'  => 'nullable|exists:doctors,id',
        ]);
 
        $patient = Patient::create($data);
 
        return response()->json($patient->load('medecinTraitant.user'), 201);
    }
 
    /** PUT /api/patients/{id} */
    public function update(Request $request, Patient $patient)
    {
        $data = $request->validate([
            'nom'                  => 'sometimes|string|max:80',
            'prenom'               => 'sometimes|string|max:80',
            'cin'                  => ['sometimes','string','max:20', Rule::unique('patients')->ignore($patient->id)],
            'date_naissance'       => 'nullable|date|before:today',
            'sexe'                 => 'sometimes|in:M,F,autre',
            'telephone'            => 'nullable|string|max:20',
            'email'                => 'nullable|email',
            'adresse'              => 'nullable|string',
            'ville'                => 'nullable|string|max:80',
            'groupe_sanguin'       => 'nullable|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'description_maladie'  => 'nullable|string',
            'antecedents'          => 'nullable|string',
            'allergies'            => 'nullable|string',
            'assurance_nom'        => 'nullable|string|max:100',
            'assurance_numero'     => 'nullable|string|max:100',
            'medecin_traitant_id'  => 'nullable|exists:doctors,id',
            'is_active'            => 'nullable|boolean',
        ]);
 
        $patient->update($data);
 
        return response()->json($patient->fresh()->load('medecinTraitant.user'));
    }
 
    /** DELETE /api/patients/{id} (soft-deactivate) */
    public function destroy(Patient $patient)
    {
        $patient->update(['is_active' => false]);
        return response()->json(['message' => 'Patient désactivé.']);
    }
 
    /** GET /api/patients/{id}/history — full medical history */
    public function history(Patient $patient)
    {
        return response()->json([
            'patient'       => $patient->load('medecinTraitant.user'),
            'appointments'  => $patient->appointments()
                                ->with('doctor.user', 'reception.user')
                                ->orderByDesc('date_rdv')->get(),
            'consultations' => $patient->consultations()
                                ->with('doctor.user', 'ordonnances.medicaments')
                                ->orderByDesc('created_at')->get(),
            'paiements'     => $patient->paiements()
                                ->with('appointment')
                                ->orderByDesc('created_at')->get(),
        ]);
    }
}