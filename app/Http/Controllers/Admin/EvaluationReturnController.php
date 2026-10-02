<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Evaluations\ReturnEvaluation;
use App\Http\Controllers\Controller;
use App\Models\Evaluation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

class EvaluationReturnController extends Controller
{
    public function store(
        Request $request,
        Evaluation $evaluation,
        ReturnEvaluation $action
    ): RedirectResponse {
        Gate::authorize('returnEvaluation', $evaluation);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $action->handle(
                $evaluation,
                $request->user()->id,
                $validated['reason']
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with(
            'success',
            'Évaluation retournée à l’enseignant pour correction.'
        );
    }
}