<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Diagnosis;
use App\Models\Doctor;
use App\Models\Exercise;
use App\Models\HepAssignment;
use App\Models\HepExercise;
use App\Models\HepProgram;
use App\Models\HepProgress;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class HepDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Idempotency: skip if demo data already exists
        $existingDemoCount = HepProgram::where('title', 'like', 'Demo HEP%')->count();
        if ($existingDemoCount > 0) {
            Log::info("HepDemoSeeder: Demo HEP data already exists ({$existingDemoCount} programs). Skipping seeding.");
            $this->command?->info("HepDemoSeeder: Demo data already exists ({$existingDemoCount} programs) - skipping.");
            return;
        }

        Log::info('HepDemoSeeder: Starting HEP demo data seeding.');
        $this->command?->info('HepDemoSeeder: Starting...');

        // Ensure specialties exist for doctor creation
        $this->ensureSpecialties();

        // Ensure exercises exist
        $exerciseIds = $this->ensureExercises();

        // Ensure 3 demo doctors
        $doctors = $this->ensureDemoDoctors();
        Log::info('HepDemoSeeder: Doctors ready: ' . $doctors->pluck('id')->implode(', '));
        $this->command?->info('  Doctors: ' . $doctors->count());

        // Ensure patients (at least 10)
        $patients = $this->ensurePatients();
        Log::info('HepDemoSeeder: Patients ready: ' . $patients->count());
        $this->command?->info('  Patients: ' . $patients->count());

        // Realistic program templates pool (15 templates)
        $templates = $this->programTemplates();

        // Track overall stats
        $totalPrograms = 0;
        $totalExercises = 0;
        $totalAssignments = 0;
        $totalProgress = 0;

        foreach ($doctors as $doctorIndex => $doctor) {
            $doctorUser = $doctor->user;
            if (! $doctorUser) {
                Log::warning("HepDemoSeeder: Doctor id {$doctor->id} has no user, skipping.");
                continue;
            }

            // Determine program count and status distribution per doctor
            // Requirement: first doctor total_programs ~10, active ~4, assigned ~6, completed ~2
            if ($doctorIndex === 0) {
                $programCount = 10;
                $statuses = ['active','active','active','active','completed','completed','draft','draft','paused','paused'];
                $assignmentCount = 6; // to achieve assigned ~6 stat for first doctor
            } elseif ($doctorIndex === 1) {
                $programCount = 8;
                $statuses = ['active','active','active','completed','completed','draft','draft','paused'];
                $assignmentCount = 3;
            } else {
                $programCount = 9;
                $statuses = ['active','active','active','completed','completed','draft','draft','paused','paused'];
                $assignmentCount = 3;
            }

            Log::info("HepDemoSeeder: Seeding {$programCount} programs for Doctor {$doctor->id} ({$doctorUser->name})");
            $this->command?->info("  Doctor {$doctor->id} ({$doctorUser->name}): {$programCount} programs");

            $createdPrograms = collect();

            for ($i = 0; $i < $programCount; $i++) {
                $template = $templates[($doctorIndex * 10 + $i) % count($templates)];
                // Make title unique per doctor to avoid collisions but keep prefix
                $title = $template['title'];
                // Ensure title uniqueness across doctors by appending if needed but keep prefix check simple
                // We use updateOrCreate on title+doctor_id, so same title across doctors is isolated
                // For same doctor, titles are distinct because templates are cycled without immediate repeat (10 < 15)
                $status = $statuses[$i] ?? $statuses[array_rand($statuses)];

                // Pick a patient for the program's patient_id (HEP programs require patient_id)
                /** @var User $programPatient */
                $programPatient = $patients->random();

                // Ensure diagnosis exists
                $diagnosis = $this->ensureDiagnosis($programPatient, $doctorUser);

                // Optionally create/linking appointment (50% chance) - nullable
                $appointmentId = null;
                if (rand(0, 1) === 1) {
                    try {
                        $appointment = Appointment::factory()->create([
                            'doctor_id' => $doctor->id,
                            'patient_id' => $programPatient->id,
                            'status' => 'completed',
                        ]);
                        $appointmentId = $appointment->id;
                    } catch (\Throwable $e) {
                        Log::warning('HepDemoSeeder: Failed to create appointment: ' . $e->getMessage());
                        $appointmentId = null;
                    }
                }

                $program = HepProgram::updateOrCreate(
                    [
                        'title' => $title,
                        'doctor_id' => $doctor->id,
                    ],
                    [
                        'description' => $template['description'],
                        'patient_id' => $programPatient->id,
                        'diagnosis_id' => $diagnosis->id,
                        'appointment_id' => $appointmentId,
                        'duration_weeks' => $template['duration_weeks'],
                        'frequency_per_week' => $template['frequency_per_week'],
                        'goals' => $template['goals'],
                        'precautions' => $template['precautions'],
                        'status' => $status,
                    ]
                );

                $createdPrograms->push($program);
                $totalPrograms++;

                // Create 3-5 HepExercises for this program
                $exerciseCount = rand(3, 5);
                $selectedExerciseIds = collect($exerciseIds)->random($exerciseCount);

                foreach ($selectedExerciseIds as $order => $exId) {
                    $exerciseModel = Exercise::find($exId);
                    $isDurationBased = $exerciseModel && in_array($exerciseModel->category, ['cardiovascular', 'balance']) && rand(0, 1) === 1;

                    HepExercise::updateOrCreate(
                        [
                            'hep_program_id' => $program->id,
                            'exercise_id' => $exId,
                            'order' => $order,
                        ],
                        [
                            'sets' => $isDurationBased ? rand(2, 4) : rand(2, 4),
                            'reps' => $isDurationBased ? null : rand(8, 15),
                            'duration_seconds' => $isDurationBased ? rand(30, 120) : (rand(0, 1) ? rand(30, 90) : null),
                            'rest_seconds' => rand(30, 90),
                            'frequency' => collect(['Daily', '3x/week', '5x/week', '2x/day'])->random(),
                            'progression_notes' => collect([
                                'Progress to resistance band next week if pain <3/10',
                                'Increase reps by 2 when RPE <5',
                                'Add 5 sec hold per week as tolerated',
                                'Advance to single-leg variation when balance >30s',
                                null,
                            ])->random(),
                            'week_number' => rand(1, max(1, $program->duration_weeks)),
                        ]
                    );
                    $totalExercises++;
                }

                Log::info("HepDemoSeeder: Created program '{$program->title}' [{$status}] for doctor {$doctor->id} with {$exerciseCount} exercises.");
            }

            // Create assignments (2-3 per doctor, but 6 for first doctor to satisfy stats)
            // Link programs to patients with progress entries
            $programsToAssign = $createdPrograms->filter(fn ($p) => in_array($p->status, ['active', 'completed']))
                ->take($assignmentCount);

            // If not enough active/completed, fill from remaining
            if ($programsToAssign->count() < $assignmentCount) {
                $remaining = $createdPrograms->whereNotIn('id', $programsToAssign->pluck('id'))->take($assignmentCount - $programsToAssign->count());
                $programsToAssign = $programsToAssign->merge($remaining);
            }

            // Ensure we use distinct patients per assignment where possible
            $availablePatients = $patients->shuffle();

            foreach ($programsToAssign->values() as $idx => $program) {
                /** @var User $patient */
                $patient = $availablePatients[$idx % $availablePatients->count()];

                // Ensure patient has a diagnosis for FK consistency (already handled) and avoid duplicate assignment
                $existingAssignment = HepAssignment::where('hep_program_id', $program->id)
                    ->where('patient_id', $patient->id)
                    ->first();

                if ($existingAssignment) {
                    $assignment = $existingAssignment;
                } else {
                    $completionStatus = $program->status === 'completed'
                        ? 'completed'
                        : collect(['pending', 'in_progress', 'completed'])->random();

                    // Due date based on program duration
                    $assignedAt = now()->subDays(rand(1, 21));
                    $dueDate = (clone $assignedAt)->addWeeks($program->duration_weeks);

                    $assignment = HepAssignment::create([
                        'hep_program_id' => $program->id,
                        'patient_id' => $patient->id,
                        'assigned_by' => $doctorUser->id,
                        'assigned_at' => $assignedAt,
                        'due_date' => $dueDate->toDateString(),
                        'completion_status' => $completionStatus,
                        'patient_notes' => collect([
                            'Feeling stronger each session, pain decreasing.',
                            'Some soreness after exercises but manageable.',
                            'Completed at home without issues.',
                            null,
                        ])->random(),
                        'clinician_feedback' => $completionStatus === 'completed'
                            ? 'Excellent adherence. Progressing to phase 2.'
                            : collect(['Keep up the good work!', 'Focus on form over speed.', null])->random(),
                    ]);
                    $totalAssignments++;
                }

                // Create progress entries (2-4 per assignment, linked to exercises of the program)
                $programExercises = HepExercise::where('hep_program_id', $program->id)->inRandomOrder()->take(rand(2, 4))->get();
                foreach ($programExercises as $hepExercise) {
                    // Create 1-2 progress rows per exercise to simulate multiple days
                    $entriesPerExercise = rand(1, 2);
                    for ($p = 0; $p < $entriesPerExercise; $p++) {
                        $sets = $hepExercise->sets ?? rand(2, 4);
                        $reps = $hepExercise->reps ?? rand(10, 15);
                        $duration = $hepExercise->duration_seconds ?? rand(30, 90);

                        // Idempotency for progress: check by assignment+exercise+date
                        $date = now()->subDays(rand(0, 14))->toDateString();

                        $exists = HepProgress::where('hep_assignment_id', $assignment->id)
                            ->where('hep_exercise_id', $hepExercise->id)
                            ->where('date', $date)
                            ->exists();
                        if ($exists) {
                            continue;
                        }

                        HepProgress::create([
                            'hep_assignment_id' => $assignment->id,
                            'hep_exercise_id' => $hepExercise->id,
                            'date' => $date,
                            'completed_sets' => max(1, $sets - rand(0, 1)),
                            'completed_reps' => $reps,
                            'duration_completed' => $duration - rand(0, 10),
                            'pain_level' => rand(0, 4),
                            'difficulty_rating' => rand(3, 7),
                            'notes' => collect([
                                'Completed with good form, no increase in pain.',
                                'Mild discomfort at end range, rested 60s extra.',
                                null,
                                'Patient reported improved confidence.',
                            ])->random(),
                        ]);
                        $totalProgress++;
                    }
                }

                Log::info("HepDemoSeeder: Created assignment program {$program->id} -> patient {$patient->id} [{$assignment->completion_status}] with progress.");
            }
        }

        // Final stats for first doctor (should satisfy requirement)
        $firstDoctor = $doctors->first();
        if ($firstDoctor) {
            $statsTotal = HepProgram::where('doctor_id', $firstDoctor->id)->count();
            $statsActive = HepProgram::where('doctor_id', $firstDoctor->id)->where('status', 'active')->count();
            $statsCompleted = HepProgram::where('doctor_id', $firstDoctor->id)->where('status', 'completed')->count();
            $statsAssigned = HepAssignment::whereHas('hepProgram', fn ($q) => $q->where('doctor_id', $firstDoctor->id))->count();

            Log::info("HepDemoSeeder: First doctor (ID {$firstDoctor->id}) stats -> total: {$statsTotal}, active: {$statsActive}, assigned: {$statsAssigned}, completed: {$statsCompleted}");
            $this->command?->info("  First doctor stats: total={$statsTotal}, active={$statsActive}, assigned={$statsAssigned}, completed={$statsCompleted}");
        }

        Log::info("HepDemoSeeder: Completed. Programs: {$totalPrograms}, Exercises: {$totalExercises}, Assignments: {$totalAssignments}, Progress: {$totalProgress}");
        $this->command?->info("HepDemoSeeder: Done. Programs: {$totalPrograms}, Exercises: {$totalExercises}, Assignments: {$totalAssignments}, Progress: {$totalProgress}");
    }

    private function ensureSpecialties(): void
    {
        $needed = ['Orthopedics', 'Physical Therapy', 'Sports Medicine'];
        foreach ($needed as $name) {
            Specialty::firstOrCreate(['name' => $name], ['description' => $name . ' specialty']);
        }
    }

    private function ensureExercises(): \Illuminate\Support\Collection
    {
        $count = Exercise::count();
        if ($count < 10) {
            Log::info("HepDemoSeeder: Only {$count} exercises found, creating 15 demo exercises.");
            Exercise::factory()->count(15)->create();
        }

        // Ensure at least some realistic exercise names exist for linking
        $exerciseIds = Exercise::pluck('id');
        if ($exerciseIds->isEmpty()) {
            // Fallback: create one
            $ex = Exercise::factory()->create();
            $exerciseIds = collect([$ex->id]);
        }

        return $exerciseIds;
    }

    private function ensureDemoDoctors(): \Illuminate\Support\Collection
    {
        $demoData = [
            [
                'name' => 'Dr. Alex Morgan',
                'email' => 'demo.hep.doctor1@medcura.com',
                'specialty' => 'Orthopedics',
                'bio' => 'Sports medicine and orthopedic rehabilitation specialist with 12 years of experience in HEP design for post-operative ACL and joint recovery.',
                'city' => 'Denver',
                'state' => 'CO',
            ],
            [
                'name' => 'Dr. Priya Sharma',
                'email' => 'demo.hep.doctor2@medcura.com',
                'specialty' => 'Physical Therapy',
                'bio' => 'Doctor of Physical Therapy specializing in shoulder, spine, and neuromuscular rehabilitation. Passionate about evidence-based home programs.',
                'city' => 'Austin',
                'state' => 'TX',
            ],
            [
                'name' => 'Dr. Jordan Lee',
                'email' => 'demo.hep.doctor3@medcura.com',
                'specialty' => 'Sports Medicine',
                'bio' => 'Sports medicine physician focusing on lower extremity, gait training, and chronic pain management via personalized HEPs.',
                'city' => 'Seattle',
                'state' => 'WA',
            ],
        ];

        $doctors = collect();
        foreach ($demoData as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password123'),
                    'role' => 'doctor',
                    'email_verified_at' => now(),
                    'phone' => '+1' . rand(2000000000, 9999999999),
                ]
            );

            $specialty = Specialty::where('name', $data['specialty'])->first()
                ?? Specialty::first();

            $doctor = Doctor::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'specialty_id' => $specialty?->id,
                    'license_number' => 'DEMO' . strtoupper(substr(md5($data['email']), 0, 6)),
                    'bio' => $data['bio'],
                    'consultation_fee' => rand(15000, 30000),
                    'appointment_duration' => 30,
                    'address' => rand(100, 9999) . ' Rehab Way',
                    'city' => $data['city'],
                    'state' => $data['state'],
                    'zip_code' => str_pad((string) rand(10000, 99999), 5, '0', STR_PAD_LEFT),
                    'country' => 'United States',
                    'phone' => $user->phone ?? '+1' . rand(2000000000, 9999999999),
                    'languages' => ['English'],
                    'is_verified' => true,
                    'is_active' => true,
                    'auto_approve_appointments' => true,
                    'average_rating' => rand(42, 50) / 10,
                    'total_reviews' => rand(20, 80),
                ]
            );

            // Ensure is_active true even if existed
            if (! $doctor->is_active) {
                $doctor->update(['is_active' => true]);
            }

            $doctors->push($doctor);
        }

        return $doctors;
    }

    private function ensurePatients(): \Illuminate\Support\Collection
    {
        $patients = User::where('role', 'patient')->take(20)->get();
        if ($patients->count() < 10) {
            $needed = 10 - $patients->count();
            Log::info("HepDemoSeeder: Only {$patients->count()} patients found, creating {$needed} demo patients.");
            $newPatients = collect();
            for ($i = 1; $i <= $needed; $i++) {
                $newPatients->push(User::factory()->create([
                    'role' => 'patient',
                    'email' => 'demo.hep.patient' . uniqid() . '@medcura.com',
                ]));
            }
            // Also create via demo pattern for idempotency
            $patients = User::where('role', 'patient')->take(20)->get();
        }

        // If still < 10 due to factory email randomization, create more with deterministic demo emails
        if ($patients->count() < 10) {
            for ($i = $patients->count() + 1; $i <= 10; $i++) {
                $user = User::firstOrCreate(
                    ['email' => "demo.hep.patient{$i}@medcura.com"],
                    [
                        'name' => fake()->name(),
                        'password' => Hash::make('password123'),
                        'role' => 'patient',
                        'email_verified_at' => now(),
                    ]
                );
                $patients->push($user);
            }
        }

        return $patients->take(15);
    }

    private function ensureDiagnosis(User $patient, User $doctorUser): Diagnosis
    {
        // Reuse recent diagnosis if exists for this patient+doctor, otherwise create
        $existing = Diagnosis::where('patient_id', $patient->id)
            ->where('doctor_id', $doctorUser->id)
            ->latest()
            ->first();

        if ($existing) {
            return $existing;
        }

        return Diagnosis::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctorUser->id,
            'diagnosis_text' => collect([
                'ACL tear rehabilitation post-op',
                'Rotator cuff tendinopathy',
                'Chronic low back pain with lumbar stabilization deficit',
                'Patellofemoral pain syndrome',
                'Ankle lateral ligament sprain grade II',
            ])->random(),
        ]);
    }

    private function programTemplates(): array
    {
        return [
            [
                'title' => 'Demo HEP - Knee Rehabilitation - ACL Recovery',
                'description' => 'Comprehensive ACL post-operative rehabilitation (Weeks 4-12). Focus on restoring full extension, progressive quadriceps activation, closed-chain strengthening, and proprioceptive training. Incorporates cryotherapy guidance and effusion monitoring. Transition from NWB to FWB with brace wean protocol.',
                'goals' => ['Restore knee flexion to 130° and full extension', 'Reduce pain to ≤2/10 during activity', 'Increase quadriceps strength to 4+/5 MMT', 'Improve single-leg balance to 30s eyes open'],
                'precautions' => ['Avoid open-chain extension 0-45° early post-op', 'No pivoting/cutting until cleared by surgeon', 'Monitor for effusion; reduce load if swelling >1+'],
                'duration_weeks' => 8,
                'frequency_per_week' => 5,
            ],
            [
                'title' => 'Demo HEP - Shoulder Mobility - Rotator Cuff Repair',
                'description' => 'Phase II rotator cuff repair protocol emphasizing protected ROM, scapular stabilization, and gradual cuff activation. Pendulums to active-assisted pulleys, isometrics, and periscapular strengthening. Pain-guided progression with sling wean timeline.',
                'goals' => ['Achieve 160° forward flexion', 'Reduce night pain to 1/10', 'Increase external rotation strength to 4/5', 'Restore overhead reaching for ADLs'],
                'precautions' => ['No active abduction >90° for 6 weeks', 'Avoid lifting >5 lbs with operative arm', 'Keep sling on except for exercises'],
                'duration_weeks' => 10,
                'frequency_per_week' => 5,
            ],
            [
                'title' => 'Demo HEP - Lower Back Strengthening - Lumbar Stabilization',
                'description' => 'Evidence-based lumbar stabilization for chronic LBP with motor control deficit. McGill Big 3, dead bug progressions, bird dog, and hip hinge retraining. Core endurance and neutral spine education, with gradual return to functional lifting mechanics.',
                'goals' => ['Increase prone plank to 60 seconds', 'Reduce Oswestry disability score by 30%', 'Improve hip hinge pattern without lumbar flexion', 'Return to 30-min walk without pain flare'],
                'precautions' => ['Avoid end-range flexion under load', 'No high-velocity rotation', 'Stop if radiating pain below knee'],
                'duration_weeks' => 6,
                'frequency_per_week' => 4,
            ],
            [
                'title' => 'Demo HEP - Hip Replacement Recovery - Post-Op Mobility',
                'description' => 'Anterior THA post-op gait and ADL training. Hip precautions education, abductor activation, heel slides, quadriceps sets, and progressive weight-bearing. Emphasis on safe transfers, sock aid use, and dislocation risk mitigation through Weeks 0-8.',
                'goals' => ['Ambulate 500m without assistive device', 'Achieve 90° hip flexion without compensation', 'Reduce pain to 2/10 at rest', 'Independent bed mobility and transfers'],
                'precautions' => ['No hip flexion >90° for 6 weeks', 'No adduction across midline', 'No internal rotation of operated leg'],
                'duration_weeks' => 8,
                'frequency_per_week' => 5,
            ],
            [
                'title' => 'Demo HEP - Ankle Sprain Rehabilitation - Lateral Ligament',
                'description' => 'Grade II lateral ankle sprain functional rehab. Acute RICE to progressive balance, peroneal strengthening with bands, calf raises, and agility ladder. Return-to-run criteria with star excursion balance progression and tape vs brace decision.',
                'goals' => ['Reduce ankle swelling to trace', 'Achieve single-leg balance eyes closed 20s', 'Pain-free jog 10 minutes', 'Star excursion anterior reach >90% limb length'],
                'precautions' => ['Avoid inversion stress for 3 weeks', 'No running until pain-free hop', 'Progress lace-up brace to no brace'],
                'duration_weeks' => 4,
                'frequency_per_week' => 6,
            ],
            [
                'title' => 'Demo HEP - Cervical Spine Mobility - Neck Pain Relief',
                'description' => 'Cervical mechanical neck pain with deep neck flexor training. Chin tucks, scapular retraction, thoracic extension over foam roller, and postural correction for desk workers. Ergonomic assessment and micro-break scheduling integrated.',
                'goals' => ['Increase cervical retraction ROM by 25%', 'Reduce Neck Disability Index by 20%', 'Deep neck flexor endurance to 30s', 'Decrease headache frequency to <1/week'],
                'precautions' => ['Avoid end-range extension with dizziness', 'No heavy overhead lifting', 'Monitor for radicular symptoms'],
                'duration_weeks' => 6,
                'frequency_per_week' => 4,
            ],
            [
                'title' => 'Demo HEP - Post-Stroke Gait Training - Hemiparesis Recovery',
                'description' => 'Task-specific gait training for post-stroke hemiparesis (Brunnstrom IV). Weight shifting, step symmetry via treadmill with partial support, AFO gait, and dual-task balance. Caregiver training for safe assist and home carryover.',
                'goals' => ['Increase gait speed to 0.8 m/s', 'Improve Berg Balance to 45/56', 'Single support time symmetry within 15%', '10m walk test improvement 20%'],
                'precautions' => ['Supervision for all ambulation', 'Monitor HR/BP, stop if SBP >180', 'AFO on during weight-bearing'],
                'duration_weeks' => 12,
                'frequency_per_week' => 5,
            ],
            [
                'title' => 'Demo HEP - Tennis Elbow Recovery - Lateral Epicondylitis',
                'description' => 'Lateral epicondylitis eccentric wrist extensor protocol with Tyler twist, isometrics, and progressive grip loading. Activity modification for keyboard/mouse, counterforce brace education, and gradual return to racquet sport.',
                'goals' => ['Reduce VAS pain with gripping to 2/10', 'Increase grip strength to 90% contralateral', 'Pain-free 20 wrist extensions with 1kg', 'Return to typing 4h without pain'],
                'precautions' => ['Avoid aggravating wrist extension with load', 'No corticosteroid within progression window', 'Modify tool grip diameter'],
                'duration_weeks' => 6,
                'frequency_per_week' => 5,
            ],
            [
                'title' => 'Demo HEP - Plantar Fasciitis Relief - Foot Strengthening',
                'description' => 'Plantar fascia offload and intrinsic foot strengthening: calf gastro-soleus stretch, plantar fascia rolling, short foot doming, and towel scrunches. Footwear and orthotic counseling, graded return to running with load management.',
                'goals' => ['First-step pain ≤2/10', 'Increase single-leg calf raise to 15 reps', 'Ankle dorsiflexion to 10° knee extended', 'Walk 30 min barefoot on grass pain-free'],
                'precautions' => ['Avoid barefoot on hard surfaces early', 'No running until first-step pain <3/10', 'Progress orthotic wear time'],
                'duration_weeks' => 6,
                'frequency_per_week' => 6,
            ],
            [
                'title' => 'Demo HEP - Core Stability - Diastasis Recti Recovery',
                'description' => 'Postpartum diastasis recti and pelvic core retraining. Transverse abdominis activation with diaphragmatic breathing, heel slides, dead bug, and glute bridge. Cough/sneeze splinting and progressive return to plank/push-up.',
                'goals' => ['Reduce IRD width to <2 fingerbreadths', 'Hold drawing-in 10s x10 with breathing', 'Pelvic floor endurance improvement', 'Return to carrying infant without doming'],
                'precautions' => ['Avoid full sit-ups/crunches early', 'No heavy lifting >10 lbs first 4 weeks', 'Monitor for coning/doming'],
                'duration_weeks' => 8,
                'frequency_per_week' => 4,
            ],
            [
                'title' => 'Demo HEP - Balance Training - Vestibular Hypofunction',
                'description' => 'Vestibular hypofunction habituation and gaze stabilization: Brandt-Daroff, gaze fixation VOR x1/x2, tandem balance, and sensory organization progressions. Fall risk education and home safety checklist.',
                'goals' => ['Reduce Dizziness Handicap Inventory by 18 points', 'Tandem stance eyes closed 15s', 'VOR cancellation symptom-free 60s', 'No falls in 4 weeks'],
                'precautions' => ['Sit after Brandt-Daroff until dizziness settles', 'Supervision for eyes-closed balance', 'Avoid driving if acute vertigo'],
                'duration_weeks' => 6,
                'frequency_per_week' => 5,
            ],
            [
                'title' => 'Demo HEP - Hip Abductor Strengthening - IT Band Syndrome',
                'description' => 'Iliotibial band syndrome with hip abductor and gluteal medius focused program: clamshells, side steps with band, single-leg deadlift, and ITB foam rolling. Run-walk progression and cadence retraining.',
                'goals' => ['Increase hip abductor MMT to 5/5', 'Pain-free run 20 min', 'Single-leg squat alignment without valgus', 'Reduce Ober test tightness'],
                'precautions' => ['Avoid downhill running early', 'No deep squat with knee valgus', 'Progress band resistance weekly'],
                'duration_weeks' => 6,
                'frequency_per_week' => 4,
            ],
            [
                'title' => 'Demo HEP - Post-Fracture Wrist Mobility - Distal Radius',
                'description' => 'Distal radius fracture post-immobilization (Week 6-12). Edema control, wrist ROM pulleys, pronation/supination with dowel, and putty grip progressions. Scar mobilization and desensitization included.',
                'goals' => ['Wrist extension to 60°', 'Grip strength 70% contralateral', 'Pronation/supination to 80° each', 'Return to light ADLs independently'],
                'precautions' => ['No weight-bearing through wrist 8 weeks', 'Avoid forceful end-range if pin site pain', 'Monitor for CRPS signs'],
                'duration_weeks' => 6,
                'frequency_per_week' => 5,
            ],
            [
                'title' => 'Demo HEP - Hamstring Strain Recovery - Sprint Return',
                'description' => 'Grade I-II hamstring strain eccentric and lumbopelvic program: Nordic hamstring prep, single-leg bridge, slider curls, and progressive sprint drills. High-speed running criteria and H:Q ratio retest.',
                'goals' => ['Pain-free maximal sprint', 'Single-leg bridge 30s', 'Nordic hold 20s', 'H:Q ratio >0.6 at 60°/s'],
                'precautions' => ['No sprinting until pain-free jog', 'Avoid aggressive static stretch week 1', 'Progress speed 10% per session'],
                'duration_weeks' => 6,
                'frequency_per_week' => 4,
            ],
            [
                'title' => 'Demo HEP - Pediatric Flat Foot Correction - Arch Development',
                'description' => 'Pediatric flexible flatfoot intrinsic and tibialis posterior strengthening: heel raises, towel scrunches, barefoot balance games, and supportive footwear guidance. Parental home-play integration.',
                'goals' => ['Improve single-leg heel raise to 10 reps', 'Visible medial arch in single-leg stance', 'Pain-free 20-min play', 'Heel valgus <5°'],
                'precautions' => ['Make exercises play-based, avoid pain', 'No rigid orthotic if asymptomatic', 'Encourage barefoot on varied textures'],
                'duration_weeks' => 12,
                'frequency_per_week' => 3,
            ],
        ];
    }
}
