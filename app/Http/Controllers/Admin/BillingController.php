<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Billing\BillingService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BillingController extends Controller
{
    public function index(Request $request, BillingService $billing)
    {
        $school = $request->user()->school;

        return Inertia::render('Admin/Billing/Index', ['settings' => $billing->settings($school), 'summary' => $billing->summary($school)]);
    }
}
