<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Bulletin scolaire</title>
    <style>
        @page { margin: 24px 28px 28px; }
        * { box-sizing: border-box; }
        body { color: #172033; font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        .school-head { border-bottom: 2px solid #1e40af; padding-bottom: 12px; width: 100%; }
        .school-head td { vertical-align: middle; }
        .logo { max-height: 64px; max-width: 90px; }
        .school-name { color: #102a54; font-size: 17px; font-weight: bold; }
        .muted { color: #596579; font-size: 9px; line-height: 1.5; }
        .title { margin: 15px 0 4px; text-align: center; color: #102a54; font-size: 18px; font-weight: bold; }
        .period { margin: 0 0 14px; text-align: center; color: #53627a; font-size: 11px; text-transform: uppercase; }
        .identity { width: 100%; margin: 12px 0; border-collapse: collapse; }
        .identity td { border: 1px solid #cbd3df; padding: 7px 8px; width: 50%; }
        .label { color: #64748b; font-size: 8px; text-transform: uppercase; }
        .value { margin-top: 3px; font-size: 10px; font-weight: bold; }
        table.grades { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .grades th { background: #eaf0f8; color: #1e3558; font-size: 8px; text-align: left; }
        .grades th, .grades td { border: 1px solid #cbd3df; padding: 7px 6px; }
        .grades td.number, .grades th.number { text-align: center; white-space: nowrap; }
        .summary { width: 100%; margin-top: 12px; border-collapse: collapse; }
        .summary td { width: 50%; border: 1px solid #cbd3df; padding: 10px; }
        .summary .main-value { margin-top: 3px; color: #123b73; font-size: 16px; font-weight: bold; }
        .section-title { margin: 15px 0 6px; padding-bottom: 4px; border-bottom: 1px solid #cbd3df; color: #1e3558; font-size: 10px; font-weight: bold; text-transform: uppercase; }
        .attendance { width: 100%; border-collapse: collapse; }
        .attendance td { border-bottom: 1px solid #e1e6ed; padding: 6px; }
        .notes { min-height: 42px; padding: 8px; border: 1px solid #cbd3df; line-height: 1.5; }
        .signatures { width: 100%; margin-top: 30px; border-collapse: collapse; }
        .signatures td { width: 33.33%; height: 58px; vertical-align: top; text-align: center; }
        .sign-line { margin: 38px 12px 0; border-top: 1px solid #7b8799; padding-top: 5px; color: #596579; font-size: 8px; }
        .footer { margin-top: 12px; border-top: 1px solid #d5dce5; padding-top: 6px; color: #7b8799; font-size: 7px; text-align: right; }
    </style>
</head>
<body>
    <table class="school-head">
        <tr>
            <td style="width: 100px;">
                <?php if($logoAbsolutePath): ?>
                    <img class="logo" src="<?php echo e($logoAbsolutePath); ?>" alt="Logo de l'école">
                <?php endif; ?>
            </td>
            <td>
                <div class="school-name"><?php echo e(data_get($snapshot, 'school.name', config('app.name'))); ?></div>
                <?php if(data_get($snapshot, 'school.slogan')): ?><div class="muted"><?php echo e(data_get($snapshot, 'school.slogan')); ?></div><?php endif; ?>
                <div class="muted">
                    <?php echo e(collect([data_get($snapshot, 'school.address'), data_get($snapshot, 'school.city'), data_get($snapshot, 'school.country')])->filter()->implode(', ')); ?>

                    <?php if(data_get($snapshot, 'school.phone')): ?> · <?php echo e(data_get($snapshot, 'school.phone')); ?><?php endif; ?>
                    <?php if(data_get($snapshot, 'school.email')): ?> · <?php echo e(data_get($snapshot, 'school.email')); ?><?php endif; ?>
                </div>
            </td>
        </tr>
    </table>

    <h1 class="title">Bulletin scolaire</h1>
    <p class="period"><?php echo e(data_get($snapshot, 'academic_year.name')); ?> · <?php echo e(data_get($snapshot, 'period.name')); ?></p>

    <table class="identity">
        <tr>
            <td><div class="label">Élève</div><div class="value"><?php echo e(data_get($snapshot, 'student.name')); ?></div></td>
            <td><div class="label">Classe</div><div class="value"><?php echo e(data_get($snapshot, 'class.name')); ?></div></td>
        </tr>
        <tr>
            <td><div class="label">Matricule</div><div class="value"><?php echo e(data_get($snapshot, 'student.matricule') ?: '—'); ?></div></td>
            <td><div class="label">Version du bulletin</div><div class="value"><?php echo e($reportCard->version); ?> · <?php echo e(optional($reportCard->generated_at)->format('d/m/Y')); ?></div></td>
        </tr>
    </table>

    <table class="grades">
        <thead><tr><th>Matière</th><th class="number">Moyenne / 20</th><th class="number">Coef.</th><th>Appréciation</th><th>Commentaire</th></tr></thead>
        <tbody>
        <?php $__currentLoopData = $reportCard->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td><?php echo e($item->subject_name ?: $item->subject?->name); ?></td>
                <td class="number"><?php echo e($item->average !== null ? number_format((float) $item->average, 2, ',', ' ') : '—'); ?></td>
                <td class="number"><?php echo e(number_format((float) $item->coefficient, 1, ',', ' ')); ?></td>
                <td><?php echo e($item->appreciation ?: '—'); ?></td>
                <td><?php echo e($item->teacher_comment ?: '—'); ?></td>
            </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>

    <table class="summary">
        <tr>
            <td><div class="label">Moyenne générale</div><div class="main-value"><?php echo e(number_format((float) $reportCard->general_average, 2, ',', ' ')); ?> / 20</div></td>
            <td><div class="label">Classement</div><div class="main-value"><?php echo e($reportCard->rank ? $reportCard->rank.'e / '.$reportCard->total_students : 'Non classé'); ?></div></td>
        </tr>
    </table>

    <div class="section-title">Assiduité</div>
    <table class="attendance">
        <tr><td>Absences</td><td><?php echo e(data_get($reportCard->attendance_summary, 'absences', 0)); ?></td><td>Justifiées</td><td><?php echo e(data_get($reportCard->attendance_summary, 'justified_absences', 0)); ?></td></tr>
        <tr><td>Non justifiées</td><td><?php echo e(data_get($reportCard->attendance_summary, 'unjustified_absences', 0)); ?></td><td>Retards</td><td><?php echo e(data_get($reportCard->attendance_summary, 'late_count', 0)); ?> · <?php echo e(data_get($reportCard->attendance_summary, 'delay_minutes', 0)); ?> min</td></tr>
    </table>

    <div class="section-title">Appréciation générale</div>
    <div class="notes"><?php echo e($reportCard->appreciation ?: ' '); ?></div>

    <table class="signatures">
        <tr>
            <td><div class="sign-line">Professeur principal</div></td>
            <td><div class="sign-line">Censeur / Responsable pédagogique</div></td>
            <td><div class="sign-line">Direction · Cachet de l’établissement</div></td>
        </tr>
    </table>
    <div class="footer">Document émis le <?php echo e(optional($reportCard->published_at ?? $reportCard->generated_at)->format('d/m/Y à H:i')); ?></div>
</body>
</html><?php /**PATH /home/jmaxtunde/Documents/schoolManagement/resources/views/pdf/report-card.blade.php ENDPATH**/ ?>