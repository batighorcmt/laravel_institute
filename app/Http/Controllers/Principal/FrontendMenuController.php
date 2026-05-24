<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Principal\UpdateFrontendMenuRequest;
use App\Models\School;
use App\Models\SchoolFrontendSetting;
use App\Services\FrontendMenuService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class FrontendMenuController extends Controller
{
    public function __construct(
        protected FrontendMenuService $menuService
    ) {}

    public function index(School $school): View
    {
        return view('principal.frontend.menu', compact('school'));
    }

    public function getData(School $school): JsonResponse
    {
        $settings = SchoolFrontendSetting::firstOrCreate(['school_id' => $school->id]);

        return response()->json([
            'frontend_menus' => $this->menuService->resolveStored($settings),
            'pages' => $this->menuService->pagesForSchool($school->id),
            'sections' => $this->menuService->homepageSections(),
        ]);
    }

    public function updateData(UpdateFrontendMenuRequest $request, School $school): JsonResponse
    {
        $settings = SchoolFrontendSetting::firstOrCreate(['school_id' => $school->id]);
        $settings->update([
            'frontend_menus' => [
                'menus' => $request->input('menus', []),
                'locations' => $request->input('locations', []),
            ],
        ]);

        return response()->json([
            'message' => 'মেনু সফলভাবে সংরক্ষণ হয়েছে।',
            'frontend_menus' => $this->menuService->resolveStored($settings->fresh()),
        ]);
    }
}
