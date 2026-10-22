<?php

namespace Database\Seeders;

use App\Enums\AttendanceJustificationStatus;
use App\Enums\AttendanceStatus;
use App\Enums\AttendanceValidationStatus;
use App\Enums\ExpenseCategory;
use App\Enums\ExpenseStatus;
use App\Enums\NotificationStatus;
use App\Enums\Role;
use App\Enums\SaleCategory;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\AttendanceJustification;
use App\Models\AttendanceRecord;
use App\Models\ClassRoom;
use App\Models\ContributionPayment;
use App\Models\Evaluation;
use App\Models\Expense;
use App\Models\Notification;
use App\Models\ParentGuardian;
use App\Models\ParentMessage;
use App\Models\Room;
use App\Models\SalaryRecord;
use App\Models\Sale;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentContribution;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Données de démonstration de l'école « CSP Bilingue Saint Padre PIO ».
 *
 * Étape 1 — emploi du temps : on planifie les 5 classes du collège
 * (6ème A, 6ème B, 5ème B, 4ème A, 3ème A) du lundi au vendredi, avec
 * 4 créneaux par jour (08:00-10:00, 10:00-12:00, 12:00-14:00, 14:00-16:00).
 * Le tableau de bord « du jour » pourra ainsi afficher des cours en cours
 * d'exécution (statut « current »).
 *
 * L'emploi du temps est généré sans collision : à un jour/créneau donné,
 * chaque classe a une matière, un enseignant et une salle distincts.
 *
 * Étape 2 — finance : dépenses, ventes, bulletins de paie et contributions
 * sur douze mois glissants, avec un bénéfice annuel positif.
 *
 * Étape 3 — assiduité : relevés quotidiens depuis la rentrée
 * (présences, absences, retards, permissions), avec des justifications parentales.
 *
 * Étape 4 — vie scolaire : inscriptions d'élèves, de parents et recrutements
 * de personnel étalés sur douze mois pour alimenter la page « Performance ».
 *
 * Seeder idempotent : peut être rejoué sans créer de doublons.
 */
class SaintPadrePioSeeder extends Seeder
{
    private const SLUG = 'csp-bilingue-saint-padre-pio';

    /** Classes du collège à planifier, dans l'ordre. */
    private const LEVELS = ['6ème', '5ème', '4ème', '3ème'];

    /** Créneaux journaliers : [début, fin]. */
    private const SLOTS = [
        ['08:00', '10:00'],
        ['10:00', '12:00'],
        ['12:00', '14:00'],
        ['14:00', '16:00'],
    ];

    /** Jours couverts : lundi (1) → vendredi (5). */
    private const DAYS = [1, 2, 3, 4, 5];

    /**
     * Enseignants : un titulaire par matière. Les trois premiers comptes
     * existent déjà dans l'école, les autres sont créés pour la démo.
     *
     * @var array<int, array{name: string, email: string, phone: string, subject: string}>
     */
    private const TEACHERS = [
        ['name' => 'Jean DUPONT', 'email' => 'jean.dupont@ecole.test', 'phone' => '+229 01 97 10 00 01', 'subject' => 'Mathématiques'],
        ['name' => 'Marie MARTIN', 'email' => 'marie.martin@ecole.test', 'phone' => '+229 01 97 10 00 02', 'subject' => 'Français'],
        ['name' => 'Karine HUYO', 'email' => 'karineuy@ecole.test', 'phone' => '+229 01 97 10 00 03', 'subject' => 'Anglais'],
        ['name' => 'Paul AGBODJAN', 'email' => 'paul.agbodjan@csp-padre-pio.test', 'phone' => '+229 01 97 10 00 04', 'subject' => 'Physique'],
        ['name' => 'Sophie KPADONOU', 'email' => 'sophie.kpadonou@csp-padre-pio.test', 'phone' => '+229 01 97 10 00 05', 'subject' => 'SVT'],
        ['name' => 'Rachid BIO', 'email' => 'rachid.bio@csp-padre-pio.test', 'phone' => '+229 01 97 10 00 06', 'subject' => 'Histoire-Geographie'],
        ['name' => 'Grace DOSSOU', 'email' => 'grace.dossou@csp-padre-pio.test', 'phone' => '+229 01 97 10 00 07', 'subject' => 'Informatique'],
        ['name' => 'Edmond ZINSOU', 'email' => 'edmond.zinsou@csp-padre-pio.test', 'phone' => '+229 01 97 10 00 08', 'subject' => 'Philisophie'],
    ];

    /** Salaire mensuel de base (FCFA) par rôle, avant variation. */
    private const PAYROLL = [
        'admin' => 90000,
        'censeur' => 70000,
        'teacher' => 50000,
        'secretary' => 45000,
        'accountant' => 60000,
        'surveillant' => 40000,
    ];

    /**
     * Dépenses récurrentes : [catégorie, libellé, montant de base, variation max].
     *
     * @var array<int, array{0: ExpenseCategory, 1: string, 2: int, 3: int}>
     */
    private const EXPENSE_ITEMS = [
        [ExpenseCategory::Fonctionnelle, 'Frais administratifs', 30000, 12000],
        [ExpenseCategory::Fonctionnelle, 'Électricité et eau', 28000, 12000],
        [ExpenseCategory::Entretien, 'Entretien des locaux', 15000, 8000],
        [ExpenseCategory::Fourniture, 'Fournitures de bureau', 9000, 5000],
    ];

    /** Achats ponctuels (un par mois en rotation) : [catégorie, libellé, montant]. */
    private const OCCASIONAL_EXPENSES = [
        [ExpenseCategory::Materiel, 'Mobilier scolaire', 90000],
        [ExpenseCategory::Communication, 'Impression de supports', 40000],
        [ExpenseCategory::Transport, 'Carburant et entretien véhicule', 55000],
        [ExpenseCategory::Materiel, 'Équipement informatique', 120000],
    ];

    /** Articles vendus : [catégorie, libellé, prix unitaire]. */
    private const SALE_ITEMS = [
        [SaleCategory::Uniforme, 'Uniforme complet', 12000],
        [SaleCategory::Manuel, 'Manuel scolaire', 5000],
        [SaleCategory::Fourniture, 'Pack de fournitures', 3500],
        [SaleCategory::Autre, 'Badge et carte d\'élève', 1500],
    ];

    public function run(): void
    {
        $school = School::withoutGlobalScopes()->where('slug', self::SLUG)->first();

        if (! $school) {
            throw new RuntimeException(
                'École introuvable (slug : '.self::SLUG.'). Créez-la avant de lancer ce seeder.'
            );
        }

        $year = AcademicYear::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->where('is_current', true)
            ->latest('id')
            ->first();

        if (! $year) {
            throw new RuntimeException(
                "Aucune année scolaire en cours pour « {$school->name} ». Créez-en une d'abord."
            );
        }

        $classes = $this->collegeClasses($school);
        $subjects = Subject::withoutGlobalScopes()->where('school_id', $school->id)->orderBy('id')->get();
        $rooms = Room::withoutGlobalScopes()->where('school_id', $school->id)->orderBy('id')->get();

        if ($classes->isEmpty() || $subjects->isEmpty() || $rooms->count() < $classes->count()) {
            throw new RuntimeException(
                "Données de base incomplètes pour « {$school->name} » : il faut des classes, des matières et au moins une salle par classe."
            );
        }

        $teacherBySubject = $this->seedTeachers($school, $subjects);

        $this->seedAssignments($classes, $subjects, $teacherBySubject);

        $count = $this->seedTimetable($school, $year, $classes, $subjects, $rooms, $teacherBySubject);

        $finance = $this->seedFinance($school, $year);

        $payments = $this->seedContributionPayments($school, $year);

        $attendance = $this->seedAttendance($school, $year, $classes);

        $enrollment = $this->seedEnrollmentHistory($school);

        $communication = $this->seedCommunication($school);

        $messages = $this->seedParentMessages($school);

        $this->command?->info(sprintf(
            'CSP Bilingue Saint Padre PIO : %d classe(s), %d créneaux planifiés (lundi–vendredi).',
            $classes->count(),
            $count,
        ));

        $this->command?->info(sprintf(
            'Finance : %d dépenses, %d ventes, %d bulletins de paie, %d contributions.',
            $finance['expenses'],
            $finance['sales'],
            $finance['salaries'],
            $finance['contributions'],
        ));

        $this->command?->info(sprintf(
            'Assiduité (%s → aujourd\'hui) : %d relevés — %d absences, %d retards, %d permissions.',
            $this->attendanceHistoryStart($school)->format('d/m/Y'),
            $attendance['records'],
            $attendance['absent'],
            $attendance['late'],
            $attendance['permission'],
        ));

        $this->command?->info(sprintf(
            'Vie scolaire (depuis la rentrée) : %d élèves inscrits, %d parents créés, %d membres du personnel.',
            $enrollment['students'],
            $enrollment['guardians'],
            $enrollment['staff'],
        ));

        $this->command?->info(sprintf(
            'Communication (depuis la rentrée) : %d SMS envoyés, %d échecs.',
            $communication['sent'],
            $communication['failed'],
        ));

        $this->command?->info(sprintf(
            'Scolarité : %d versements enregistrés, %d message(s) parent.',
            $payments,
            $messages,
        ));
    }

    /** @return Collection<int, ClassRoom> */
    private function collegeClasses(School $school)
    {
        $order = array_flip(self::LEVELS);

        return ClassRoom::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->whereIn('level', self::LEVELS)
            ->get()
            ->sortBy([
                fn (ClassRoom $a, ClassRoom $b) => ($order[$a->level] ?? 99) <=> ($order[$b->level] ?? 99),
                fn (ClassRoom $a, ClassRoom $b) => strcmp((string) $a->name, (string) $b->name),
            ])
            ->values();
    }

    /**
     * Crée/réutilise un enseignant par matière et retourne la table
     * [subject_id => Teacher].
     *
     * @param  Collection<int, Subject>  $subjects
     * @return array<int, Teacher>
     */
    private function seedTeachers(School $school, $subjects): array
    {
        $byName = $subjects->keyBy('name');
        $teacherBySubject = [];

        foreach (self::TEACHERS as $data) {
            $subject = $byName->get($data['subject']);

            if (! $subject) {
                continue;
            }

            $user = User::withoutGlobalScopes()->firstOrNew(['email' => $data['email']]);
            $user->forceFill([
                'name' => $data['name'],
                'school_id' => $school->id,
                'role' => Role::Teacher,
                'is_active' => true,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ])->save();

            $teacher = Teacher::withoutGlobalScopes()->updateOrCreate(
                ['user_id' => $user->id],
                ['school_id' => $school->id, 'phone' => $data['phone']],
            );

            $teacherBySubject[$subject->id] = $teacher;
        }

        return $teacherBySubject;
    }

    /**
     * Affecte chaque enseignant à ses classes pour la matière dont il est titulaire.
     *
     * @param  Collection<int, ClassRoom>  $classes
     * @param  Collection<int, Subject>  $subjects
     * @param  array<int, Teacher>  $teacherBySubject
     */
    private function seedAssignments($classes, $subjects, array $teacherBySubject): void
    {
        foreach ($classes as $class) {
            // Toutes les matières figurent au programme de la classe.
            $class->subjects()->syncWithoutDetaching($subjects->pluck('id')->all());

            foreach ($subjects as $subject) {
                $teacher = $teacherBySubject[$subject->id] ?? null;

                if (! $teacher) {
                    continue;
                }

                $teacher->assignments()->firstOrCreate([
                    'class_id' => $class->id,
                    'subject_id' => $subject->id,
                ]);
            }
        }
    }

    /**
     * Génère l'emploi du temps hebdomadaire sans collision.
     *
     * Rotation : matière = sujets[(indexClasse + indexJour + indexCréneau) % n]
     * → sur un même jour/créneau, les 5 classes ont 5 matières (donc 5 enseignants)
     * distinctes. La salle est fixe par classe (salle de classe).
     *
     * @param  Collection<int, ClassRoom>  $classes
     * @param  Collection<int, Subject>  $subjects
     * @param  Collection<int, Room>  $rooms
     * @param  array<int, Teacher>  $teacherBySubject
     */
    private function seedTimetable(School $school, AcademicYear $year, $classes, $subjects, $rooms, array $teacherBySubject): int
    {
        // On repart d'un emploi du temps propre pour les classes du collège.
        Timetable::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->where('academic_year_id', $year->id)
            ->whereIn('class_id', $classes->pluck('id')->all())
            ->delete();

        // Matières disposant d'un enseignant titulaire.
        $subjects = $subjects->filter(fn (Subject $s) => isset($teacherBySubject[$s->id]))->values();
        $subjectCount = max($subjects->count(), 1);

        $classList = $classes->values();
        $roomList = $rooms->values();

        $created = 0;

        foreach (self::DAYS as $day) {
            foreach (self::SLOTS as $slotIndex => [$startsAt, $endsAt]) {
                foreach ($classList as $classIndex => $class) {
                    $subject = $subjects[($classIndex + ($day - 1) + $slotIndex) % $subjectCount];
                    $teacher = $teacherBySubject[$subject->id];
                    $room = $roomList[$classIndex % $roomList->count()];

                    Timetable::withoutGlobalScopes()->create([
                        'school_id' => $school->id,
                        'academic_year_id' => $year->id,
                        'class_id' => $class->id,
                        'subject_id' => $subject->id,
                        'teacher_id' => $teacher->id,
                        'room_id' => $room->id,
                        'day_of_week' => $day,
                        'starts_at' => $startsAt,
                        'ends_at' => $endsAt,
                    ]);

                    $created++;
                }
            }
        }

        return $created;
    }

    /** Compteur de références de dépenses (DEP-0001…). */
    private int $expenseSequence = 0;

    /**
     * Données de démonstration du module Finance sur les douze derniers mois :
     * dépenses (dont la paie), ventes et contributions des élèves. Ces données
     * alimentent les graphiques d'évolution (BuildMoneyEvolution) et les
     * statistiques des écrans Finance.
     *
     * On repart d'un jeu propre pour l'école afin de rester idempotent.
     *
     * @return array{expenses: int, sales: int, salaries: int, contributions: int}
     */
    private function seedFinance(School $school, AcademicYear $year): array
    {
        // Générateur déterministe : le même jeu de données à chaque exécution.
        mt_srand(20261013);
        $this->expenseSequence = 0;

        SalaryRecord::withoutGlobalScopes()->where('school_id', $school->id)->delete();
        Expense::withoutGlobalScopes()->where('school_id', $school->id)->delete();
        Sale::withoutGlobalScopes()->where('school_id', $school->id)->delete();
        StudentContribution::withoutGlobalScopes()->where('school_id', $school->id)->delete();

        $author = User::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->where('role', Role::Admin->value)
            ->orderBy('id')
            ->value('id');

        $staff = User::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->get(['id', 'role'])
            ->filter(fn (User $user) => $user->role->isSchoolStaff())
            ->values();

        // Douze mois glissants, du plus ancien au mois courant.
        $months = collect(range(11, 0))
            ->map(fn (int $back) => Carbon::today()->startOfMonth()->subMonths($back));

        $expenses = 0;
        $salaries = 0;

        foreach ($months as $index => $month) {
            $isCurrent = $index === $months->count() - 1;

            // +1 pour la dépense de paie du mois (les lignes sont comptées à part).
            $expenses += $this->seedMonthlyExpenses($school, $author, $month, $index) + 1;
            $salaries += $this->seedPayroll($school, $author, $staff, $month, $isCurrent);
        }

        $students = Student::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->orderBy('id')
            ->get();

        $sales = $this->seedSales($school, $author, $months, $students);
        $contributions = $this->seedContributions($school, $year, $students);

        return [
            'expenses' => $expenses,
            'sales' => $sales,
            'salaries' => $salaries,
            'contributions' => $contributions,
        ];
    }

    /** Dépenses courantes du mois + un achat ponctuel. */
    private function seedMonthlyExpenses(School $school, ?int $author, Carbon $month, int $index): int
    {
        $day = min(4 + $index, 26);
        $count = 0;

        foreach (self::EXPENSE_ITEMS as [$category, $label, $base, $variation]) {
            $this->createExpense($school, $author, $category, $label, $base + mt_rand(0, $variation), $month, $day);
            $count++;
        }

        [$category, $label, $amount] = self::OCCASIONAL_EXPENSES[$index % count(self::OCCASIONAL_EXPENSES)];
        $amount += mt_rand(0, 40000);

        // Les achats importants restent en attente d'approbation.
        $this->createExpense($school, $author, $category, $label, $amount, $month, $day, $amount > 50000);

        return $count + 1;
    }

    /**
     * Feuille de paie du mois : une dépense de catégorie « salaire » portant
     * le total, et une ligne `salary_records` par membre du personnel.
     */
    private function seedPayroll(School $school, ?int $author, $staff, Carbon $month, bool $isCurrent): int
    {
        $lines = $staff->map(function (User $member) {
            $base = self::PAYROLL[$member->role->value] ?? 60000;

            return ['staff_id' => $member->id, 'amount' => $base + mt_rand(0, 12000)];
        });

        $total = (int) $lines->sum('amount');
        $status = $isCurrent ? ExpenseStatus::Pending : ExpenseStatus::Approved;

        $expense = Expense::withoutGlobalScopes()->create([
            'school_id' => $school->id,
            'category' => ExpenseCategory::Salaire->value,
            'reference' => 'SAL-'.$month->format('Y-m'),
            'label' => 'Salaires de '.$this->frenchMonth($month),
            'amount' => $total,
            'expense_date' => $month->toDateString(),
            'status' => $status->value,
            'created_by' => $author,
        ]);

        if ($status === ExpenseStatus::Approved) {
            $expense->forceFill(['approved_by' => $author, 'approved_at' => $month->copy()->addDays(3)])->save();
        }

        foreach ($lines as $line) {
            SalaryRecord::withoutGlobalScopes()->create([
                'school_id' => $school->id,
                'expense_id' => $expense->id,
                'staff_id' => $line['staff_id'],
                'salary_month' => $month->toDateString(),
                'amount' => $line['amount'],
            ]);
        }

        return $lines->count();
    }

    private function createExpense(School $school, ?int $author, ExpenseCategory $category, string $label, int $amount, Carbon $month, int $day, bool $pending = false): void
    {
        $this->expenseSequence++;
        $date = $month->copy()->day($day);
        $status = $pending ? ExpenseStatus::Pending : ExpenseStatus::Approved;

        $expense = Expense::withoutGlobalScopes()->create([
            'school_id' => $school->id,
            'category' => $category->value,
            'reference' => 'DEP-'.str_pad((string) $this->expenseSequence, 4, '0', STR_PAD_LEFT),
            'label' => $label,
            'amount' => $amount,
            'expense_date' => $date->toDateString(),
            'status' => $status->value,
            'justification' => $pending ? 'Facture à valider par l\'administration.' : null,
            'created_by' => $author,
        ]);

        if ($status === ExpenseStatus::Approved) {
            $expense->forceFill(['approved_by' => $author, 'approved_at' => $date->copy()->addDays(2)])->save();
        }
    }

    /** Ventes de fournitures, manuels et uniformes réparties sur l'année. */
    private function seedSales(School $school, ?int $author, $months, $students): int
    {
        $buyers = $students
            ->map(fn (Student $student) => trim($student->first_name.' '.$student->last_name))
            ->filter()
            ->values()
            ->all();
        $buyers[] = 'Parent d\'élève';
        $buyers[] = 'Client externe';

        $created = 0;

        // Les ventes (uniformes, manuels, fournitures) sont la principale
        // recette de l'établissement : volumes soutenus toute l'année, avec
        // un pic à la rentrée (septembre–octobre).
        foreach ($months as $index => $month) {
            $count = 6 + ($index % 3);
            $backToSchool = $index >= 10 ? 1.6 : 1.0;

            for ($i = 0; $i < $count; $i++) {
                [$category, $item, $unitPrice] = self::SALE_ITEMS[($index + $i) % count(self::SALE_ITEMS)];
                $quantity = (int) round((2 + mt_rand(0, 48)) * $backToSchool);
                $quantity = max(1, $quantity);
                $day = min(3 + (($index * 3 + $i * 5) % 24), 27);

                Sale::withoutGlobalScopes()->create([
                    'school_id' => $school->id,
                    'category' => $category->value,
                    'item' => $item,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => $quantity * $unitPrice,
                    'sold_at' => $month->copy()->day($day)->toDateString(),
                    'buyer' => $buyers[($index + $i) % count($buyers)],
                    'created_by' => $author,
                ]);

                $created++;
            }
        }

        return $created;
    }

    /** Contribution annuelle de chaque élève, avec des statuts variés. */
    private function seedContributions(School $school, AcademicYear $year, $students): int
    {
        $created = 0;
        $due = 250000;

        foreach ($students as $index => $student) {
            // La majorité des familles règlent leur scolarité (recette majeure).
            $paid = match ($index % 5) {
                0, 1, 3 => $due,
                2 => (int) round($due * 0.72),
                default => (int) round($due * 0.48),
            };
            $status = $paid >= $due ? 'paid' : ($paid > 0 ? 'partial' : 'pending');

            $paidAt = $paid > 0
                ? Carbon::today()->startOfMonth()->subMonths($index % 6)->addDays(3 + ($index % 9))
                : null;

            StudentContribution::withoutGlobalScopes()->updateOrCreate(
                [
                    'school_id' => $school->id,
                    'student_id' => $student->id,
                    'academic_year_id' => $year->id,
                ],
                [
                    'parent_id' => $student->guardians()->first()?->id,
                    'amount_due' => $due,
                    'amount_paid' => $paid,
                    'status' => $status,
                    'paid_at' => $paidAt,
                ],
            );

            $created++;
        }

        return $created;
    }

    /** Versements de scolarité : chaque contribution réglée est éclatée en plusieurs échéances. */
    private function seedContributionPayments(School $school, AcademicYear $year): int
    {
        ContributionPayment::withoutGlobalScopes()->where('school_id', $school->id)->delete();

        $contributions = StudentContribution::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->where('amount_paid', '>', 0)
            ->orderBy('id')
            ->get();

        if ($contributions->isEmpty()) {
            return 0;
        }

        $recorder = User::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->whereIn('role', [Role::Secretary->value, Role::Admin->value])
            ->orderByRaw('CASE WHEN role = ? THEN 0 ELSE 1 END', [Role::Secretary->value])
            ->value('id');

        $rentree = $this->attendanceHistoryStart($school);
        $today = Carbon::today();
        $methods = ['cash', 'mobile_money', 'bank_transfer'];
        $created = 0;
        $sequence = 0;

        foreach ($contributions as $index => $contribution) {
            $paid = (int) $contribution->amount_paid;
            $installments = 1 + ($index % 3);
            $base = intdiv($paid, $installments);
            $lastDate = null;

            for ($step = 0; $step < $installments; $step++) {
                $amount = $step === $installments - 1
                    ? $paid - $base * ($installments - 1)
                    : $base;

                if ($amount <= 0) {
                    continue;
                }

                $date = $this->spreadDate(($index * 3 + $step) % 12, 12, $rentree, $today)
                    ->setTime(9 + ($step % 4), 0);
                $sequence++;

                ContributionPayment::withoutGlobalScopes()->create([
                    'school_id' => $school->id,
                    'student_contribution_id' => $contribution->id,
                    'parent_id' => $contribution->parent_id,
                    'amount' => $amount,
                    'method' => $methods[$step % count($methods)],
                    'reference' => sprintf('VIR-%d-%04d', $year->id, $sequence),
                    'receipt_number' => sprintf('REC-%d-%04d', $year->id, $sequence),
                    'paid_at' => $date->toDateString(),
                    'recorded_by' => $recorder,
                ]);

                $lastDate = $date;
                $created++;
            }

            if ($lastDate) {
                $contribution->timestamps = false;
                $contribution->paid_at = $lastDate;
                $contribution->save();
            }
        }

        return $created;
    }

    /** Quelques messages adressés à l'établissement par les parents de la démo. */
    private function seedParentMessages(School $school): int
    {
        ParentMessage::withoutGlobalScopes()->where('school_id', $school->id)->delete();

        $parents = ParentGuardian::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->whereHas('students')
            ->orderBy('id')
            ->take(4)
            ->get();

        if ($parents->isEmpty()) {
            return 0;
        }

        $samples = [
            ['Demande de rendez-vous', 'Bonjour, je souhaiterais rencontrer le professeur principal de mon enfant afin de faire le point sur son trimestre.'],
            ['Frais de scolarité', 'Bonjour, je souhaite obtenir un échéancier pour le solde des frais de scolarité restants.'],
            ['Assiduité', 'Bonjour, mon enfant a été absent la semaine dernière pour raison médicale. Je joins un justificatif.'],
            ['Vie scolaire', "Bonjour, serait-il possible d'avoir des informations sur les activités périscolaires proposées ?"],
        ];

        $rentree = $this->attendanceHistoryStart($school);
        $today = Carbon::today();
        $total = max(1, $parents->count());
        $created = 0;

        foreach ($parents as $index => $parent) {
            $sample = $samples[$index % count($samples)];
            $student = $parent->students()->orderBy('id')->first();
            $date = $this->spreadDate($index, $total, $rentree, $today)->setTime(10 + ($index % 5), 30);
            $isRead = $index % 3 === 0;

            $message = ParentMessage::withoutGlobalScopes()->create([
                'school_id' => $school->id,
                'parent_id' => $parent->id,
                'student_id' => $student?->id,
                'subject' => $samples[$index % count($samples)][0],
                'body' => $samples[$index % count($samples)][1],
                'status' => $isRead ? ParentMessage::STATUS_READ : ParentMessage::STATUS_NEW,
                'read_at' => $isRead ? $date->copy()->addDay() : null,
            ]);

            $message->timestamps = false;
            $message->created_at = $date;
            $message->updated_at = $date;
            $message->save();

            $created++;
        }

        return $created;
    }

    /**
     * Début de l'historique d'assiduité : le premier jour de l'année scolaire
     * (la rentrée), borne du calendrier scolaire de l'école.
     */
    private function attendanceHistoryStart(School $school): Carbon
    {
        $start = AcademicPeriod::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->orderBy('starts_at')
            ->value('starts_at');

        return $start ? Carbon::parse($start)->startOfDay() : Carbon::today()->startOfDay();
    }

    /**
     * Reconstruit l'assiduité des classes du collège depuis la rentrée :
     * une majorité de présents, saupoudrée d'absences, de retards et de permissions.
     * Les absences des élèves rattachés à un parent reçoivent une justification.
     *
     * @param  Collection<int, ClassRoom>  $classes
     * @return array{records: int, absent: int, late: int, permission: int}
     */
    private function seedAttendance(School $school, AcademicYear $year, $classes): array
    {
        $totals = ['records' => 0, 'absent' => 0, 'late' => 0, 'permission' => 0];

        $recorder = User::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->whereIn('role', [Role::Censeur->value, Role::Admin->value])
            ->orderByRaw('CASE WHEN role = ? THEN 0 ELSE 1 END', [Role::Censeur->value])
            ->orderBy('id')
            ->first();

        $students = Student::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->where('is_active', true)
            ->whereIn('class_id', $classes->pluck('id')->all())
            ->with('guardians:id,name')
            ->orderBy('class_id')
            ->orderBy('id')
            ->get();

        if (! $recorder || $students->isEmpty()) {
            return $totals;
        }

        $periods = AcademicPeriod::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->where('academic_year_id', $year->id)
            ->orderBy('position')
            ->get();

        $start = $this->attendanceHistoryStart($school);
        $end = Carbon::today()->startOfDay();

        if ($end->lessThan($start)) {
            return $totals;
        }

        // Idempotence : on repart d'un historique propre, borné à l'année scolaire.
        AttendanceRecord::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->delete();

        $absentReasons = ['Maladie', 'Raison familiale', 'Voyage hors ville', 'Absence non justifiée'];
        $permissionReasons = ['Rendez-vous médical', 'Convocation administrative', 'Décès dans la famille', 'Compétition sportive'];

        $dayIndex = 0;
        for ($date = $start->copy(); $date->lessThanOrEqualTo($end); $date->addDay()) {
            if ($date->isWeekend()) {
                continue;
            }

            $isToday = $date->isSameDay($end);
            $period = $periods->first(
                fn (AcademicPeriod $item) => $item->starts_at->lte($date) && $item->ends_at->gte($date),
            );

            foreach ($students as $studentIndex => $student) {
                $status = $isToday
                    ? $this->todayAttendanceStatus($studentIndex, $dayIndex, $date)
                    : $this->attendanceStatusFor($studentIndex, $dayIndex, $date);

                $at = $date->copy()->setTime(8, 0);
                $isLate = $status === AttendanceStatus::Late;
                $isPermission = $status === AttendanceStatus::Permission;

                $record = AttendanceRecord::withoutGlobalScopes()->firstOrNew([
                    'school_id' => $school->id,
                    'student_id' => $student->id,
                    'attendance_date' => $date->toDateString(),
                ]);

                $record->fill([
                    'school_id' => $school->id,
                    'class_room_id' => $student->class_id,
                    'academic_period_id' => $period?->id,
                    'status' => $status,
                    'delay_minutes' => $isLate ? 5 * (1 + (($studentIndex + $dayIndex) % 6)) : null,
                    'reason' => $this->attendanceReason($status, $studentIndex, $dayIndex, $absentReasons, $permissionReasons),
                    'note' => null,
                    'recorded_by' => $recorder->id,
                    'recorded_at' => $at,
                    'justified_at' => $isPermission ? $at : null,
                    'justified_by' => $isPermission ? $recorder->id : null,
                    'validation_status' => AttendanceValidationStatus::Validated,
                    'validated_at' => $at,
                    'validated_by' => $recorder->id,
                ]);

                $record->save();

                $this->seedJustification($record, $student, $studentIndex, $dayIndex, $recorder);

                $totals['records']++;
                if ($status !== AttendanceStatus::Present) {
                    $totals[$status->value]++;
                }
            }

            $dayIndex++;
        }

        return $totals;
    }

    /**
     * Statut quotidien d'assiduité : majorité de présents (≈ 84 %), le reste
     * réparti entre absences, retards et permissions. La « tension » mensuelle
     * fait varier le taux d'un mois à l'autre pour un graphique vivant.
     */
    private function attendanceStatusFor(int $studentIndex, int $dayIndex, Carbon $date): AttendanceStatus
    {
        $roll = ($studentIndex * 7 + $dayIndex * 3) % 100;
        $absentLimit = 5 + $this->monthlyAbsencePressure($date);

        return match (true) {
            $roll < $absentLimit => AttendanceStatus::Absent,
            $roll < $absentLimit + 7 => AttendanceStatus::Late,
            $roll < $absentLimit + 11 => AttendanceStatus::Permission,
            default => AttendanceStatus::Present,
        };
    }

    /**
     * Pression mensuelle (−2 à +6 points d'absences) dérivée du mois : donne des
     * taux d'assiduité réalistes entre ≈ 78 % et ≈ 86 % selon les périodes.
     */
    private function monthlyAbsencePressure(Carbon $date): int
    {
        return match (($date->year * 12 + $date->month) % 5) {
            0 => -2,
            1 => 0,
            2 => 2,
            3 => 4,
            default => 6,
        };
    }

    /** Aujourd'hui : au moins une permission, un retard et une absence visibles. */
    private function todayAttendanceStatus(int $studentIndex, int $dayIndex, Carbon $date): AttendanceStatus
    {
        return match ($studentIndex) {
            0 => AttendanceStatus::Permission,
            1 => AttendanceStatus::Late,
            2 => AttendanceStatus::Absent,
            default => $this->attendanceStatusFor($studentIndex, $dayIndex, $date),
        };
    }

    /**
     * @param  array<int, string>  $absentReasons
     * @param  array<int, string>  $permissionReasons
     */
    private function attendanceReason(
        AttendanceStatus $status,
        int $studentIndex,
        int $dayIndex,
        array $absentReasons,
        array $permissionReasons,
    ): ?string {
        $key = $studentIndex + $dayIndex;

        return match ($status) {
            AttendanceStatus::Absent => $absentReasons[$key % count($absentReasons)],
            AttendanceStatus::Permission => $permissionReasons[$key % count($permissionReasons)],
            AttendanceStatus::Late => 'Retard de transport',
            default => null,
        };
    }

    /**
     * Ajoute une justification parentale à certaines absences (élèves rattachés à
     * un parent) pour alimenter la file de validation du censeur.
     */
    private function seedJustification(AttendanceRecord $record, Student $student, int $studentIndex, int $dayIndex, User $reviewer): void
    {
        if ($record->status !== AttendanceStatus::Absent || $student->guardians->isEmpty()) {
            return;
        }

        $approved = ($studentIndex + $dayIndex) % 2 === 0;
        $reviewedAt = $record->attendance_date->copy()->setTime(9, 30);

        AttendanceJustification::query()->create([
            'attendance_record_id' => $record->id,
            'parent_id' => $student->guardians->first()->id,
            'reason' => 'Absence de '.$student->first_name.' : '.$record->reason,
            'status' => $approved ? AttendanceJustificationStatus::Approved : AttendanceJustificationStatus::Pending,
            'reviewed_by' => $approved ? $reviewer->id : null,
            'reviewed_at' => $approved ? $reviewedAt : null,
        ]);

        if ($approved) {
            $record->update([
                'justified_at' => $reviewedAt,
                'justified_by' => $reviewer->id,
            ]);
        }
    }

    /**
     * Étale sur douze mois les dates d'inscription des élèves et de recrutement
     * du personnel, et crée un parent pour chaque élève qui n'en a pas encore :
     * alimente les graphiques « Élèves & parents » et « Personnel ».
     *
     * Les dates sont réparties entre la rentrée et aujourd'hui pour rester dans
     * la fenêtre affichée par la page « Performance » (année scolaire en cours).
     *
     * @return array{students: int, guardians: int, staff: int}
     */
    private function seedEnrollmentHistory(School $school): array
    {
        $students = Student::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->orderBy('id')
            ->get();

        if ($students->isEmpty()) {
            return ['students' => 0, 'guardians' => 0, 'staff' => 0];
        }

        $rentree = $this->attendanceHistoryStart($school);
        $today = Carbon::today();
        $total = $students->count();

        $guardians = 0;

        foreach ($students as $index => $student) {
            $date = $this->spreadDate($index, $total, $rentree, $today)->setTime(9, 0);

            $student->timestamps = false;
            $student->created_at = $date;
            $student->updated_at = $date;
            $student->save();

            $existing = $student->guardians()->get();

            if ($existing->isNotEmpty()) {
                foreach ($existing as $guardian) {
                    $guardian->timestamps = false;
                    $guardian->created_at = $date;
                    $guardian->updated_at = $date;
                    $guardian->save();
                }

                continue;
            }

            $guardian = ParentGuardian::withoutGlobalScopes()->updateOrCreate(
                ['school_id' => $school->id, 'phone' => $this->parentPhone($index)],
                ['name' => 'Parent de '.$student->first_name, 'is_active' => true],
            );

            $guardian->timestamps = false;
            $guardian->created_at = $date;
            $guardian->updated_at = $date;
            $guardian->save();

            $guardian->students()->syncWithoutDetaching([$student->id => ['relationship' => 'parent']]);
            $guardians++;
        }

        $staff = User::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->whereIn('role', array_map(fn (Role $role) => $role->value, Role::assignableStaff()))
            ->orderBy('id')
            ->get();

        foreach ($staff as $index => $member) {
            $date = $this->spreadDate($index, $staff->count(), $rentree, $today)->setTime(9, 0);

            $member->timestamps = false;
            $member->created_at = $date;
            $member->updated_at = $date;
            $member->save();
        }

        return [
            'students' => $students->count(),
            'guardians' => $guardians,
            'staff' => $staff->count(),
        ];
    }

    /**
     * Journal de communication (SMS) réparti depuis la rentrée, rattaché à une
     * évaluation réelle et à un parent existant, pour alimenter la carte
     * « Communication » de la page Performance.
     *
     * @return array{sent: int, failed: int}
     */
    private function seedCommunication(School $school): array
    {
        $evaluation = Evaluation::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->orderBy('id')
            ->first();

        $student = Student::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->whereHas('guardians')
            ->orderBy('id')
            ->first();

        $guardian = $student?->guardians()->first();

        if (! $evaluation || ! $student || ! $guardian) {
            return ['sent' => 0, 'failed' => 0];
        }

        $rentree = $this->attendanceHistoryStart($school);
        $today = Carbon::today();

        $sent = 0;
        $failed = 0;

        for ($index = 0; $index < 36; $index++) {
            $date = $this->spreadDate($index, 36, $rentree, $today)->setTime(8 + ($index % 6), 15);
            if ($date->isAfter(Carbon::now())) {
                $date = Carbon::now()->subMinutes(20 * (($index % 5) + 1));
            }

            $isFailed = $index % 11 === 0;
            $status = $isFailed
                ? NotificationStatus::Failed
                : ($index % 3 === 0 ? NotificationStatus::Sent : NotificationStatus::Delivered);

            $notification = Notification::withoutGlobalScopes()->firstOrNew([
                'school_id' => $school->id,
                'idempotency_key' => 'demo-'.$school->id.'-'.$index,
            ]);

            $notification->fill([
                'evaluation_id' => $evaluation->id,
                'student_id' => $student->id,
                'parent_id' => $guardian->id,
                'channel' => 'sms',
                'phone' => $guardian->phone,
                'message' => 'Bulletin de '.$student->first_name.' disponible ('.$this->frenchMonth($date).').',
                'provider' => 'log',
                'status' => $status,
                'attempts' => $isFailed ? 2 : 1,
                'estimated_cost' => 15,
                'cost_currency' => 'XOF',
                'error' => $isFailed ? 'Opérateur injoignable' : null,
                'sent_at' => $isFailed ? null : $date,
                'delivered_at' => $status === NotificationStatus::Delivered ? $date->copy()->addMinutes(2) : null,
            ]);

            $notification->timestamps = false;
            $notification->created_at = $date;
            $notification->updated_at = $date;
            $notification->save();

            $isFailed ? $failed++ : $sent++;
        }

        return ['sent' => $sent, 'failed' => $failed];
    }

    /**
     * Date répartie linéairement entre la rentrée et aujourd'hui, pour rester
     * dans la fenêtre affichée par la page « Performance ».
     */
    private function spreadDate(int $index, int $total, Carbon $start, Carbon $end): Carbon
    {
        $days = max(0, (int) round($start->diffInDays($end)));

        if ($total <= 1 || $days === 0) {
            return $start->copy();
        }

        return $start->copy()->addDays((int) round($index * $days / ($total - 1)));
    }

    /** Numéro de téléphone unique et déterministe pour les parents générés. */
    private function parentPhone(int $index): string
    {
        return sprintf('+229 96 55 %02d %02d', intdiv($index, 100), $index % 100);
    }

    private function frenchMonth(Carbon $date): string
    {
        $months = [
            1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril', 5 => 'mai', 6 => 'juin',
            7 => 'juillet', 8 => 'août', 9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre',
        ];

        return $months[$date->month].' '.$date->format('Y');
    }
}
