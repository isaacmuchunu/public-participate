<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Constituency;
use App\Models\County;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GeoDivisionController extends Controller
{
    public function counties(Request $request): JsonResponse
    {
        $counties = County::query()
            ->when($request->string('search')->isNotEmpty(), function ($query) use ($request) {
                $query->where('name', 'like', '%'.$request->string('search')->toString().'%');
            })
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return response()->json(['data' => $counties]);
    }

    public function constituencies(County $county): JsonResponse
    {
        $constituencies = $county
            ->constituencies()
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return response()->json(['data' => $constituencies]);
    }

    public function wards(Constituency $constituency): JsonResponse
    {
        $wards = $constituency
            ->wards()
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return response()->json(['data' => $wards]);
    }
}
