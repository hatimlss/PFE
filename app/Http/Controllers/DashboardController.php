<?php
// app/Http/Controllers/API/DashboardController.php
namespace App\Http\Controllers\API;
 
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Paiement;
use App\Models\Consultation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
 
class DashboardController extends Controller
{
    /**
     * GET /api/dashboard
     * Returns stats relevant to the authenticated user's role
     */
    public function index(Request $request)
    {
        $user = $request->user();
 
        return match ($user->role) {
            'admin'     => $this->adminStats(),
            'doctor'    => $this->doctorStats($user->doctor->id),
            'reception' => $this->receptionStats(),
            default     => response()->json(['message' => 'Rôle non reconnu.'], 403),
        };
    }
 
    // ── ADMIN ─────────────────────────────────────────────────────────────────
    private function adminStats()
    {
        $today = today();
        $month = now()->month;
        $year  = now()->year;
 
        return response()->json([
            // Totals
            'total_patients'      => Patient::where('is_active', true)->count(),
            'total_doctors'       => Doctor::count(),
            'total_appointments'  => Appointment::count(),
            'total_consultations' => Consultation::count(),
 
            // Today
            'rdv_aujourd_hui'     => Appointment::whereDate('date_rdv', $today)->count(),
            'rdv_confirmes'       => Appointment::whereDate('date_rdv', $today)->where('status', 'confirme')->count(),
            'rdv_en_attente'      => Appointment::whereDate('date_rdv', $today)->where('status', 'en_attente')->count(),
            'rdv_annules_auj'     => Appointment::whereDate('date_rdv', $today)->where('status', 'annule')->count(),
 
            // Revenue
            'revenus_mois'        => Paiement::whereYear('date_paiement', $year)
                                        ->whereMonth('date_paiement', $month)
                                        ->where('status', 'paye')
                                        ->sum('montant'),
            'revenus_semaine'     => Paiement::whereBetween('date_paiement', [now()->startOfWeek(), now()->endOfWeek()])
                                        ->where('status', 'paye')
                                        ->sum('montant'),
 
            // Appointments by status (donut chart)
            'rdv_par_status'      => Appointment::select('status', DB::raw('count(*) as total'))
                                        ->groupBy('status')->get(),
 
            // Appointments last 7 days (line chart)
            'rdv_7_jours'         => Appointment::select(
                                            DB::raw('DATE(date_rdv) as date'),
                                            DB::raw('count(*) as total')
                                        )
                                        ->whereBetween('date_rdv', [now()->subDays(6), now()])
                                        ->groupBy('date')
                                        ->orderBy('date')
                                        ->get(),
 
            // Top doctors by appointments
            'top_medecins'        => Doctor::withCount('appointments')
                                        ->orderByDesc('appointments_count')
                                        ->with('user')
                                        ->limit(5)
                                        ->get(),
 
            // New patients this month
            'nouveaux_patients'   => Patient::whereYear('created_at', $year)
                                        ->whereMonth('created_at', $month)
                                        ->count(),
        ]);
    }
 
    // ── DOCTOR ────────────────────────────────────────────────────────────────
    private function doctorStats(int $doctorId)
    {
        $today = today();
 
        return response()->json([
            'rdv_aujourd_hui'     => Appointment::where('doctor_id', $doctorId)
                                        ->whereDate('date_rdv', $today)->count(),
            'prochain_rdv'        => Appointment::where('doctor_id', $doctorId)
                                        ->upcoming()->first()?->load('patient'),
            'total_patients'      => Appointment::where('doctor_id', $doctorId)
                                        ->distinct('patient_id')->count('patient_id'),
            'total_consultations' => Consultation::where('doctor_id', $doctorId)->count(),
            'total_ordonnances'   => \App\Models\Ordonnance::where('doctor_id', $doctorId)->count(),
 
            // Today's schedule
            'agenda_aujourd_hui'  => Appointment::where('doctor_id', $doctorId)
                                        ->whereDate('date_rdv', $today)
                                        ->whereNotIn('status', ['annule'])
                                        ->orderBy('heure_debut')
                                        ->with('patient')
                                        ->get(),
 
            // This week
            'rdv_semaine'         => Appointment::where('doctor_id', $doctorId)
                                        ->whereBetween('date_rdv', [now()->startOfWeek(), now()->endOfWeek()])
                                        ->whereNotIn('status', ['annule'])
                                        ->orderBy('date_rdv')->orderBy('heure_debut')
                                        ->with('patient')
                                        ->get(),
        ]);
    }
 
    // ── RECEPTION ─────────────────────────────────────────────────────────────
    private function receptionStats()
    {
        $today = today();
 
        return response()->json([
            'rdv_aujourd_hui'    => Appointment::whereDate('date_rdv', $today)->count(),
            'rdv_en_attente'     => Appointment::where('status', 'en_attente')->count(),
            'rdv_confirmes'      => Appointment::whereDate('date_rdv', $today)->where('status', 'confirme')->count(),
            'paiements_attente'  => Paiement::where('status', 'en_attente')->count(),
            'nouveaux_patients'  => Patient::whereDate('created_at', $today)->count(),
 
            // Today's full schedule (all doctors)
            'agenda_aujourd_hui' => Appointment::whereDate('date_rdv', $today)
                                        ->whereNotIn('status', ['annule'])
                                        ->orderBy('heure_debut')
                                        ->with(['patient', 'doctor.user'])
                                        ->get(),
 
            // Upcoming 3 days
            'rdv_a_venir'        => Appointment::whereBetween('date_rdv', [$today, $today->copy()->addDays(3)])
                                        ->whereIn('status', ['en_attente', 'confirme'])
                                        ->orderBy('date_rdv')->orderBy('heure_debut')
                                        ->with(['patient', 'doctor.user'])
                                        ->get(),
        ]);
    }
}