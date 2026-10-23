<?php

namespace App\Http\Controllers\Censeur;

use App\Actions\Evaluations\ValidateEvaluation;
use App\Enums\EvaluationStatus;
use App\Http\Controllers\Controller;
use App\Models\Evaluation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class EvaluationValidationController extends Controller
{
    public function store(Request $request, Evaluation $evaluation, ValidateEvaluation $action): RedirectResponse
    {
        $evaluation->loadMissing('enteredBy');
        $evaluation->loadMissing('submitter');

        abort_unless(
            $request->user()->isCenseur()
                && (int) $evaluation->school_id === (int) $request->user()->school_id
                && (($evaluation->status === EvaluationStatus::Draft
                        && (int) $evaluation->entered_by === (int) $request->user()->id)
                    || ($evaluation->status === EvaluationStatus::InProgress
                        && ($evaluation->enteredBy?->isTeacher()
                            || $evaluation->submitter?->isTeacher()))),
            403
        );

        try {
            $action->handle($evaluation, $request->user()->id);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Évaluation validée. Les notifications seront traitées par la commande prévue.');
    }
}
