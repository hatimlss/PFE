<?php
// app/Http/Controllers/API/ConsultationController.php
namespace App\Http\Controllers\API;
 
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Consultation;
use Illuminate\Http\Request;
 
class ConsultationController extends Controller
{
    /** GET /api/consultations */
    public function index(Request $request)
    {
        $user  = $request->user();
        $query = Consultation::with(['patient', 'doctor.user', 'ordonnances']);
 
        if ($user->isDoctor()) {
            $query->where('doctor_id', $user->doctor->id);
        }
 
        $query
            ->when($request->patient_id, fn($q, $p) => $q->where('patient_id', $p))
            ->when($request->doctor_id,  fn($q, $d) => $q->where('doctor_id', $d))
            ->latest();
 
        return response()->json($query->paginate($request->per_page ?? 15));
    }
 
    /** GET /api/consultations/{id} */
    public function show(Consultation $consultation)
    {
        return response()->json(
            $consultation->load(['patient', 'doctor.user', 'appointment', 'ordonnances.medicaments'])
        );
    }
 
    /**
     * POST /api/consultations
     * Doctor creates a consultation after finishing an appointment
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'appointment_id' => 'required|exists:appointments,id',
            'diagnostic'     => 'required|string',
            'symptomes'      => 'nullable|string',
            'traitement'     => 'nullable|string',
            'tension'        => 'nullable|string|max:20',
            'poids'          => 'nullable|numeric|min:0|max:500',
            'taille'         => 'nullable|numeric|min:0|max:300',
            'temperature'    => 'nullable|numeric|min:30|max:45',
            'observations'   => 'nullable|string',
            'prochain_rdv'   => 'nullable|date|after:today',
        ]);
 
        $appointment = Appointment::findOrFail($data['appointment_id']);
 
        // Verify the doctor owns this appointment
        $user = $request->user();
        if ($user->isDoctor() && $appointment->doctor_id !== $user->doctor->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }
 
        if ($appointment->consultation) {
            return response()->json(['message' => 'Une consultation existe déjà pour ce rendez-vous.'], 422);
        }
 
        $data['patient_id'] = $appointment->patient_id;
        $data['doctor_id']  = $appointment->doctor_id;
 
        $consultation = Consultation::create($data);
 
        // Auto-mark appointment as terminated
        $appointment->update(['status' => 'termine']);
 
        return response()->json($consultation->load(['patient', 'doctor.user', 'ordonnances']), 201);
    }
 
    /** PUT /api/consultations/{id} */
    public function update(Request $request, Consultation $consultation)
    {
        $data = $request->validate([
            'diagnostic'   => 'sometimes|string',
            'symptomes'    => 'nullable|string',
            'traitement'   => 'nullable|string',
            'tension'      => 'nullable|string|max:20',
            'poids'        => 'nullable|numeric',
            'taille'       => 'nullable|numeric',
            'temperature'  => 'nullable|numeric',
            'observations' => 'nullable|string',
            'prochain_rdv' => 'nullable|date',
        ]);
 
        $user = $request->user();
        if ($user->isDoctor() && $consultation->doctor_id !== $user->doctor->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }
 
        $consultation->update($data);
 
        return response()->json($consultation->fresh()->load(['patient', 'doctor.user', 'ordonnances.medicaments']));
    }
}
 