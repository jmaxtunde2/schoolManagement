<?php

namespace App\Services\Tenancy;

use App\Models\School;
use App\Models\SchoolDomain;
use Illuminate\Http\Request;

class TenantResolver
{
	public function resolve(Request $request): ?School
	{
		$host = strtolower($request->getHost());

		if ($host && ($domain = SchoolDomain::query()->where('domain', $host)->where('verified', true)->first())) {
			return $domain->school;
		}

		if ($user = auth()->user()) {
			return $user->school;
		}

		return null;
	}

	public function resolveBySlug(string $slug): ?School
	{
		return School::query()->where('slug', $slug)->first();
	}
}
