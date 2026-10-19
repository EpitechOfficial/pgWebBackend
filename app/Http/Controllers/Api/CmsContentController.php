<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LegacyRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CmsContentController extends Controller
{
    public function __construct(private readonly LegacyRepository $legacy)
    {
    }

    private function ok(array $data): JsonResponse
    {
        return response()->json(['status' => 'ok', 'data' => $data]);
    }

    public function home(): JsonResponse { return $this->ok($this->legacy->instance()->getCmsHomePayload()); }
    public function flagship(): JsonResponse { return $this->ok($this->legacy->instance()->getCmsFlagshipProgrammesPayload()); }
    public function speech(): JsonResponse { return $this->ok($this->legacy->instance()->getCmsProvostSpeechPayload()); }
    public function academics(): JsonResponse { return $this->ok($this->legacy->instance()->getCmsAcademicsPayload()); }
    public function programmes(): JsonResponse { return $this->ok($this->legacy->instance()->getCmsProgrammesPayload()); }
    public function facilities(): JsonResponse { return $this->ok($this->legacy->instance()->getCmsFacilitiesPayload()); }
    public function supervisors(): JsonResponse { return $this->ok($this->legacy->instance()->getCmsSupervisorsPayload()); }
    public function why(): JsonResponse { return $this->ok($this->legacy->instance()->getCmsWhyPayload()); }
    public function students(): JsonResponse { return $this->ok($this->legacy->instance()->getCmsStudentSpeechesPayload()); }
    public function fees(): JsonResponse { return $this->ok($this->legacy->instance()->getCmsFeesPayload()); }
    public function admissions(): JsonResponse { return $this->ok($this->legacy->instance()->getCmsAdmissionsPayload()); }
    public function admissionsGuide(): JsonResponse { return $this->ok($this->legacy->instance()->getCmsAdmissionsGuidePayload()); }
    public function admissionsAfter(): JsonResponse { return $this->ok($this->legacy->instance()->getCmsAdmissionsAfterPayload()); }
    public function international(): JsonResponse { return $this->ok($this->legacy->instance()->getCmsInternationalPayload()); }
    public function contact(): JsonResponse { return $this->ok($this->legacy->instance()->getCmsContactPayload()); }

    public function programmeDetail(Request $request): JsonResponse
    {
        $values = $request->validate(['department' => ['required', 'string']]);
        $item = $this->legacy->instance()->getCmsProgrammeDetailPayload(trim($values['department']));

        if ($item === []) {
            return response()->json(['status' => 'error', 'message' => 'Programme record not found.'], 404);
        }

        return $this->ok(['item' => $item]);
    }
}
