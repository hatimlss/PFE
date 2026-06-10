<?php
// app/Http/Controllers/API/PaiementController.php
namespace App\Http\Controllers\API;
 
use App\Http\Controllers\Controller;
use App\Models\Paiement;
use Illuminate\Http\Request;
 
class PaiementController extends Controller
{
    /** GET /api/paiements */
    public function index(Request $request)
    {
        $query = Paiement::with(['patient', 'appointment.doctor.user', 'createdBy'])
            ->when($request->patient_id,  fn($q, $p) => $q->where('patient_id', $p))
            ->when($request->status,      fn($q, $s) => $q->where('status', $s))
            ->when($request->date_from,   fn($q, $d) => $q->whereDate('date_paiement', '>=', $d))
            ->when($request->date_to,     fn($q, $d) => $q->whereDate('date_paiement', '<=', $d))
            ->latest();
 
        return response()->json($query->paginate($request->per_page ?? 15));
    }
 
    /** GET /api/paiements/{id} */
    public function show(Paiement $paiement)
    {
        return response()->json($paiement->load(['patient', 'appointment', 'createdBy']));
    }
 
    /** POST /api/paiements */
    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id'     => 'required|exists:patients,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'montant'        => 'required|numeric|min:0',
            'mode_paiement'  => 'required|in:especes,carte,virement,assurance,cheque',
            'status'         => 'required|in:en_attente,paye,partiel,rembourse',
            'reference'      => 'nullable|string|max:100',
            'date_paiement'  => 'nullable|date',
            'notes'          => 'nullable|string',
        ]);
 
        $data['created_by'] = $request->user()->id;
 
        $paiement = Paiement::create($data);
 
        return response()->json($paiement->load(['patient', 'appointment']), 201);
    }
 
    /** PUT /api/paiements/{id} */
    public function update(Request $request, Paiement $paiement)
    {
        $data = $request->validate([
            'montant'       => 'sometimes|numeric|min:0',
            'mode_paiement' => 'sometimes|in:especes,carte,virement,assurance,cheque',
            'status'        => 'sometimes|in:en_attente,paye,partiel,rembourse',
            'reference'     => 'nullable|string|max:100',
            'date_paiement' => 'nullable|date',
            'notes'         => 'nullable|string',
        ]);
 
        $paiement->update($data);
 
        return response()->json($paiement->fresh()->load(['patient', 'appointment']));
    }
 
    /** DELETE /api/paiements/{id} */
    public function destroy(Paiement $paiement)
    {
        $paiement->delete();
        return response()->json(['message' => 'Paiement supprimé.']);
    }
 
    /** GET /api/paiements/stats — monthly revenue summary */
    public function stats(Request $request)
    {
        $month = $request->month ?? now()->format('Y-m');
        [$year, $mon] = explode('-', $month);
 
        return response()->json([
            'total_revenus'      => Paiement::whereYear('date_paiement', $year)
                                        ->whereMonth('date_paiement', $mon)
                                        ->where('status', 'paye')
                                        ->sum('montant'),
            'total_en_attente'   => Paiement::where('status', 'en_attente')->sum('montant'),
            'count_transactions' => Paiement::whereYear('date_paiement', $year)
                                        ->whereMonth('date_paiement', $mon)
                                        ->count(),
            'par_mode'           => Paiement::whereYear('date_paiement', $year)
                                        ->whereMonth('date_paiement', $mon)
                                        ->where('status', 'paye')
                                        ->groupBy('mode_paiement')
                                        ->selectRaw('mode_paiement, SUM(montant) as total')
                                        ->get(),
        ]);
    }
}