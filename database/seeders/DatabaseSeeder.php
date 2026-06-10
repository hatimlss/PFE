<?php
// database/seeders/DatabaseSeeder.php
namespace Database\Seeders;
 
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Doctor;
use App\Models\Medicament;
use App\Models\Ordonnance;
use App\Models\Paiement;
use App\Models\Patient;
use App\Models\Reception;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
 
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── ADMIN ─────────────────────────────────────────────────────────────
        User::create([
            'name'      => 'Administrateur',
            'email'     => 'admin@clinic.ma',
            'password'  => Hash::make('password'),
            'role'      => 'admin',
            'phone'     => '0600000001',
            'is_active' => true,
        ]);
 
        // ── DOCTORS ───────────────────────────────────────────────────────────
        $doctorUsers = [
            ['name' => 'Dr. Ahmed Benali',    'email' => 'ahmed.benali@clinic.ma',    'specialite' => 'Médecine Générale',  'fee' => 150, 'exp' => 10],
            ['name' => 'Dr. Fatima Ouali',    'email' => 'fatima.ouali@clinic.ma',    'specialite' => 'Cardiologie',        'fee' => 300, 'exp' => 15],
            ['name' => 'Dr. Youssef Radi',    'email' => 'youssef.radi@clinic.ma',    'specialite' => 'Pédiatrie',          'fee' => 200, 'exp' => 8],
        ];
 
        $doctors = [];
        foreach ($doctorUsers as $d) {
            $user = User::create([
                'name'      => $d['name'],
                'email'     => $d['email'],
                'password'  => Hash::make('password'),
                'role'      => 'doctor',
                'phone'     => '06' . rand(10000000, 99999999),
                'is_active' => true,
            ]);
 
            $doctors[] = Doctor::create([
                'user_id'          => $user->id,
                'specialite'       => $d['specialite'],
                'numero_ordre'     => 'OM-' . rand(10000, 99999),
                'experience_ans'   => $d['exp'],
                'consultation_fee' => $d['fee'],
                'bio'              => "Spécialiste en {$d['specialite']} avec {$d['exp']} ans d'expérience.",
                'horaires'         => [
                    'lundi'    => ['08:00', '17:00'],
                    'mardi'    => ['08:00', '17:00'],
                    'mercredi' => ['08:00', '13:00'],
                    'jeudi'    => ['08:00', '17:00'],
                    'vendredi' => ['08:00', '16:00'],
                ],
            ]);
        }
 
        // ── RECEPTION ─────────────────────────────────────────────────────────
        $receptionUser = User::create([
            'name'      => 'Samira Lahlou',
            'email'     => 'reception@clinic.ma',
            'password'  => Hash::make('password'),
            'role'      => 'reception',
            'phone'     => '0600000010',
            'is_active' => true,
        ]);
 
        $reception = Reception::create([
            'user_id' => $receptionUser->id,
            'poste'   => "Agent d'accueil principal",
        ]);
 
        // ── PATIENTS ──────────────────────────────────────────────────────────
        $patientsData = [
            ['nom' => 'El Amrani',  'prenom' => 'Khalid',  'cin' => 'AB123456', 'sexe' => 'M', 'dob' => '1985-03-15', 'tel' => '0612345678', 'gs' => 'A+',  'maladie' => 'Hypertension artérielle'],
            ['nom' => 'Bensaid',    'prenom' => 'Nadia',   'cin' => 'CD234567', 'sexe' => 'F', 'dob' => '1992-07-22', 'tel' => '0623456789', 'gs' => 'O+',  'maladie' => 'Diabète de type 2'],
            ['nom' => 'Tahiri',     'prenom' => 'Omar',    'cin' => 'EF345678', 'sexe' => 'M', 'dob' => '1978-11-30', 'tel' => '0634567890', 'gs' => 'B+',  'maladie' => 'Douleurs lombaires chroniques'],
            ['nom' => 'Cherkaoui',  'prenom' => 'Zineb',   'cin' => 'GH456789', 'sexe' => 'F', 'dob' => '2000-01-05', 'tel' => '0645678901', 'gs' => 'AB+', 'maladie' => 'Asthme'],
            ['nom' => 'Idrissi',    'prenom' => 'Hassan',  'cin' => 'IJ567890', 'sexe' => 'M', 'dob' => '1960-09-18', 'tel' => '0656789012', 'gs' => 'O-',  'maladie' => 'Insuffisance cardiaque légère'],
        ];
 
        $patients = [];
        foreach ($patientsData as $i => $pd) {
            $patients[] = Patient::create([
                'nom'                 => $pd['nom'],
                'prenom'              => $pd['prenom'],
                'cin'                 => $pd['cin'],
                'date_naissance'      => $pd['dob'],
                'sexe'                => $pd['sexe'],
                'telephone'           => $pd['tel'],
                'ville'               => 'Casablanca',
                'groupe_sanguin'      => $pd['gs'],
                'description_maladie' => $pd['maladie'],
                'medecin_traitant_id' => $doctors[$i % count($doctors)]->id,
            ]);
        }
 
        // ── APPOINTMENTS ──────────────────────────────────────────────────────
        $statuses   = ['confirme', 'confirme', 'confirme', 'en_attente', 'termine'];
        $types      = ['consultation', 'suivi', 'urgence', 'bilan'];
 
        $appointments = [];
        for ($i = 0; $i < 10; $i++) {
            $appointments[] = Appointment::create([
                'patient_id'   => $patients[$i % count($patients)]->id,
                'doctor_id'    => $doctors[$i % count($doctors)]->id,
                'reception_id' => $reception->id,
                'date_rdv'     => now()->addDays(rand(-5, 10))->toDateString(),
                'heure_debut'  => sprintf('%02d:00', rand(8, 15)),
                'heure_fin'    => sprintf('%02d:30', rand(9, 16)),
                'motif'        => 'Consultation de routine - ' . $patientsData[$i % count($patientsData)]['maladie'],
                'status'       => $statuses[$i % count($statuses)],
                'type'         => $types[$i % count($types)],
            ]);
        }
 
        // ── CONSULTATION + ORDONNANCE for a terminated appointment ────────────
        $terminatedAppt = collect($appointments)->firstWhere('status', 'termine');
        if ($terminatedAppt) {
            $consult = Consultation::create([
                'appointment_id' => $terminatedAppt->id,
                'patient_id'     => $terminatedAppt->patient_id,
                'doctor_id'      => $terminatedAppt->doctor_id,
                'diagnostic'     => 'Hypertension artérielle bien contrôlée sous traitement.',
                'symptomes'      => 'Légères céphalées matinales.',
                'tension'        => '13/8',
                'poids'          => 78.5,
                'taille'         => 175.0,
                'temperature'    => 37.1,
                'observations'   => 'Continuer le traitement actuel. Revoir dans 3 mois.',
                'prochain_rdv'   => now()->addMonths(3)->toDateString(),
            ]);
 
            $ordonnance = Ordonnance::create([
                'consultation_id'        => $consult->id,
                'patient_id'             => $consult->patient_id,
                'doctor_id'              => $consult->doctor_id,
                'date_emission'          => now()->toDateString(),
                'validite_jours'         => 30,
                'instructions_generales' => 'Prendre les médicaments régulièrement. Éviter le sel.',
            ]);
 
            Medicament::create([
                'ordonnance_id' => $ordonnance->id,
                'nom'           => 'Amlodipine',
                'dosage'        => '5mg',
                'frequence'     => '1 fois par jour',
                'duree'         => '3 mois',
                'instructions'  => 'Le matin avec un verre d\'eau.',
            ]);
 
            Medicament::create([
                'ordonnance_id' => $ordonnance->id,
                'nom'           => 'Ramipril',
                'dosage'        => '10mg',
                'frequence'     => '1 fois par jour',
                'duree'         => '3 mois',
                'instructions'  => 'Le soir avant le repas.',
            ]);
 
            // Paiement for this appointment
            Paiement::create([
                'patient_id'     => $terminatedAppt->patient_id,
                'appointment_id' => $terminatedAppt->id,
                'montant'        => $doctors[$terminatedAppt->doctor_id - 2]->consultation_fee ?? 150,
                'mode_paiement'  => 'especes',
                'status'         => 'paye',
                'date_paiement'  => now()->toDateString(),
                'created_by'     => $receptionUser->id,
            ]);
        }
 
        $this->command->info('✅ Base de données initialisée avec succès!');
        $this->command->table(
            ['Rôle', 'Email', 'Mot de passe'],
            [
                ['Admin',     'admin@clinic.ma',           'password'],
                ['Médecin 1', 'ahmed.benali@clinic.ma',    'password'],
                ['Médecin 2', 'fatima.ouali@clinic.ma',    'password'],
                ['Médecin 3', 'youssef.radi@clinic.ma',    'password'],
                ['Réception', 'reception@clinic.ma',       'password'],
            ]
        );
    }
}
