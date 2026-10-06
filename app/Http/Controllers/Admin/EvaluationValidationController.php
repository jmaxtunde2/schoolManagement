<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Evaluations\ValidateEvaluation;
use App\Http\Controllers\Controller;
use App\Models\Evaluation;
use App\Services\TwoFactor\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

class EvaluationValidationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Validate evaluation
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request,
        Evaluation $evaluation,
        ValidateEvaluation $action,
        TwoFactorService $twoFactor
    ): RedirectResponse {
        /*
         * Authorization
         */
        Gate::authorize(
            'validateEvaluation',
            $evaluation
        );

        /*
         * Fresh Authenticator verification
         *
         * Required for the sensitive validation action.
         */
        if (
            ! $twoFactor->sensitiveVerified(
                $request,
                'validate_evaluation'
            )
        ) {
            return redirect()
                ->route(
                    'two-factor.challenge',
                    [
                        'purpose' => 'validate_evaluation',
                        'return' => url()->previous(),
                    ]
                )
                ->with(
                    'error',
                    'Un code Authenticator est requis pour valider cette évaluation.'
                );
        }

        /*
         * Validate evaluation
         */
        try {
            $action->handle(
                $evaluation,
                $request->user()->id
            );
        } catch (RuntimeException $e) {
            return back()->with(
                'error',
                $e->getMessage()
            );
        }

        /*
         * Success
         */
        return back()->with(
            'success',
            'Évaluation validée. Les notifications vont être traitées.'
        );
    }
}
