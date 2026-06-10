<?php
// app/Http/Controllers/API/AppointmentController.php
namespace App\Http\Controllers\API;
 
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Reception;
use Illuminate\Http\Request;
 
class AppointmentController extends Controller
{
    /** GET /api/appointments */
    public function index(Request $request)
    {
        $user  = $request->user();
        $query = Appointment::with(['patient', 'doctor.user', 'reception.user']);
 
        // Doctors only see their own appointments
        if ($user->isDoctor()) {
            $query->where('doctor_id', $user->doctor->id);
        }
 
        $query
            ->when($request->doctor_id,  fn($q, $d) => $q->where('doctor_id', $d))
            ->when($request->patient_id, fn($q, $p) => $q->where('patient_id', $p))
            ->when($request->status,     fn($q, $s) => $q->where('status', $s))
            ->when($request->type,       fn($q, $t) => $q->where('type', $t))
            ->when($request->date,       fn($q, $d) => $q->whereDate('date_rdv', $d))
            ->when($request->date_from,  fn($q, $d) => $q->whereDate('date_rdv', '>=', $d))
            ->when($request->date_to,    fn($q, $d) => $q->whereDate('date_rdv', '<=', $d))
            ->orderBy('date_rdv')
            ->orderBy('heure_debut');
 
        return response()->json($query->paginate($request->per_page ?? 20));
    }
 
    /** GET /api/appointments/{id} */
    public function show(Appointment $appointment)
    {
        return response()->json(
            $appointment->load(['patient', 'doctor.user', 'reception.user', 'consultation', 'paiement'])
        );
    }
 
    /** POST /api/appointments */
    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id'      => 'required|exists:patients,id',
            'doctor_id'       => 'required|exists:doctors,id',
            'date_rdv'        => 'required|date|after_or_equal:today',
            'heure_debut'     => 'required|date_format:H:i',
            'heure_fin'       => 'required|date_format:H:i|after:heure_debut',
            'motif'           => 'required|string|max:255',
            'type'            => 'required|in:consultation,suivi,urgence,bilan',
            'notes_reception' => 'nullable|string',
        ]);
 
        // Check for scheduling conflict
        $conflict = Appointment::where('doctor_id', $data['doctor_id'])
            ->where('date_rdv', $data['date_rdv'])
            ->whereNotIn('status', ['annule'])
            ->where(fn($q) =>
                $q->whereBetween('heure_debut', [$data['heure_debut'], $data['heure_fin']])
                  ->orWhereBetween('heure_fin',  [$data['heure_debut'], $data['heure_fin']])
                  ->orWhere(fn($q) =>
                      $q->where('heure_debut', '<=', $data['heure_debut'])
                        ->where('heure_fin',   '>=', $data['heure_fin'])
                  )
            )->exists();
 
        if ($conflict) {
            return response()->json([
                'message' => 'Ce créneau est déjà réservé pour ce médecin.',
            ], 422);
        }
 
        // Attach reception if the authenticated user is from reception
        $user = $request->user();
        if ($user->isReception()) {
            $data['reception_id'] = $user->reception->id;
        }
 
        $appointment = Appointment::create($data);
 
        return response()->json($appointment->load(['patient', 'doctor.user']), 201);
    }
 
    /** PUT /api/appointments/{id} */
    public function update(Request $request, Appointment $appointment)
    {
        $data = $request->validate([
            'date_rdv'        => 'sometimes|date',
            'heure_debut'     => 'sometimes|date_format:H:i',
            'heure_fin'       => 'sometimes|date_format:H:i',
            'motif'           => 'sometimes|string|max:255',
            'type'            => 'sometimes|in:consultation,suivi,urgence,bilan',
            'status'          => 'sometimes|in:en_attente,confirme,annule,termine,absent',
            'notes_reception' => 'nullable|string',
            'notes_doctor'    => 'nullable|string',
        ]);
 
        $appointment->update($data);
 
        return response()->json($appointment->fresh()->load(['patient', 'doctor.user', 'reception.user']));
    }
 
    /** PATCH /api/appointments/{id}/status */
    public function updateStatus(Request $request, Appointment $appointment)
    {
        $request->validate([
            'status' => 'required|in:en_attente,confirme,annule,termine,absent',
        ]);
 
        $appointment->update(['status' => $request->status]);
 
        return response()->json(['message' => 'Statut mis à jour.', 'appointment' => $appointment]);
    }
 
    /** DELETE /api/appointments/{id} */
    public function destroy(Appointment $appointment)
    {
        if ($appointment->status === 'termine') {
            return response()->json(['message' => 'Impossible de supprimer un rendez-vous terminé.'], 422);
        }
        $appointment->update(['status' => 'annule']);
        return response()->json(['message' => 'Rendez-vous annulé.']);
    }
 
    /**
     * GET /api/appointments/calendar?doctor_id=1&month=2024-05
     * Returns appointments grouped by date for calendar view
     */
    public function calendar(Request $request)
    {
        $request->validate([
            'doctor_id' => 'nullable|exists:doctors,id',
            'month'     => 'required|date_format:Y-m',
        ]);
 
        [$year, $month] = explode('-', $request->month);
 
        $query = Appointment::with(['patient', 'doctor.user'])
            ->whereYear('date_rdv', $year)
            ->whereMonth('date_rdv', $month)
            ->whereNotIn('status', ['annule']);
 
        if ($request->doctor_id) {
            $query->where('doctor_id', $request->doctor_id);
        }
 
        $grouped = $query->get()->groupBy(fn($a) => $a->date_rdv->toDateString());
 
        return response()->json($grouped);
    }
 
    /**
     * GET /api/appointments/today
     * All appointments for today (reception dashboard)
     */
    public function today(Request $request)
    {
        $user  = $request->user();
        $query = Appointment::with(['patient', 'doctor.user'])
            ->whereDate('date_rdv', today())
            ->orderBy('heure_debut');
 
        if ($user->isDoctor()) {
            $query->where('doctor_id', $user->doctor->id);
        }
 
        return response()->json($query->get());
    }
}