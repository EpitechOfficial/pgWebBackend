<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LegacyRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SiteController extends Controller
{
    public function __construct(private readonly LegacyRepository $legacy)
    {
    }

    private function ok(array $data, array $extra = []): JsonResponse
    {
        return response()->json(['status' => 'ok', ...$extra, 'data' => $data]);
    }

    public function home(): JsonResponse { return $this->ok($this->legacy->instance()->getHomePayload()); }
    public function contact(): JsonResponse { return $this->ok($this->legacy->instance()->getContactPayload()); }
    public function academics(): JsonResponse { return $this->ok($this->legacy->instance()->getAcademicsPayload()); }
    public function admissions(): JsonResponse { return $this->ok($this->legacy->instance()->getAdmissionsPayload()); }
    public function why(): JsonResponse { return $this->ok($this->legacy->instance()->getWhyPayload()); }
    public function researchAreas(): JsonResponse { return $this->ok($this->legacy->instance()->getResearchAreasPayload()); }
    public function fees(): JsonResponse { return $this->ok($this->legacy->instance()->getFeesPayload()); }
    public function internationalStudents(): JsonResponse { return $this->ok($this->legacy->instance()->getInternationalStudentsPayload()); }
    public function setupFee(): JsonResponse { return $this->ok($this->legacy->instance()->getSetupFeePayload()); }
    public function internationalFee(): JsonResponse { return $this->ok($this->legacy->instance()->getInternationalSetupFeePayload()); }

    public function researchAreaOptions(Request $request): JsonResponse
    {
        $validated = $request->validate(['facultyId' => ['required', 'integer', 'min:1']]);
        $id = (int) $validated['facultyId'];
        return $this->ok($this->legacy->instance()->getResearchAreaOptions($id), ['facultyId' => $id]);
    }

    public function researchAreaResults(Request $request): JsonResponse
    {
        $values = $request->validate([
            'facultyId' => ['required', 'integer', 'min:1'],
            'departmentId' => ['required', 'integer', 'min:1'],
            'degreeId' => ['required', 'integer', 'min:1'],
        ]);
        return $this->ok($this->legacy->instance()->getResearchAreaResults((int) $values['facultyId'], (int) $values['departmentId'], (int) $values['degreeId']));
    }

    public function programmeDetail(Request $request): JsonResponse
    {
        $values = $request->validate(['department' => ['required', 'string']]);
        return $this->ok($this->legacy->instance()->getProgrammeDetailPayload(trim($values['department'])));
    }

    public function programmes(Request $request): JsonResponse
    {
        $page = max(1, $request->integer('page', 1));
        return $this->ok($this->legacy->instance()->getProgrammesPayload($page, trim((string) $request->query('search', ''))));
    }

    public function programmeOverview(Request $request): JsonResponse
    {
        $values = $request->validate(['department' => ['required', 'string']]);
        return $this->ok($this->legacy->instance()->getProgramOverviewPayload(trim($values['department'])));
    }

    public function setupFeeOptions(Request $request): JsonResponse
    {
        $values = $request->validate(['facultyId' => ['required', 'integer', 'min:1']]);
        $id = (int) $values['facultyId'];
        return $this->ok($this->legacy->instance()->getSetupFeeOptions($id), ['facultyId' => $id]);
    }

    public function setupFeeDegrees(Request $request): JsonResponse
    {
        $values = $request->validate(['departmentId' => ['required', 'integer', 'min:1']]);
        $id = (int) $values['departmentId'];
        return $this->ok(['degrees' => $this->legacy->instance()->getSetupFeeDegrees($id)], ['departmentId' => $id]);
    }

    public function setupFeeCalculation(Request $request): JsonResponse
    {
        $values = $request->validate([
            'facultyId' => ['required', 'integer', 'min:1'], 'departmentId' => ['required', 'integer', 'min:1'],
            'degreeId' => ['required', 'integer', 'min:1'], 'mode' => ['required', 'string'],
            'type' => ['required', 'string'], 'paymentType' => ['required', 'string'], 'installmentType' => ['nullable', 'string'],
        ]);
        return $this->ok($this->legacy->instance()->getSetupFeeCalculation(
            (int) $values['facultyId'], (int) $values['departmentId'], (int) $values['degreeId'],
            $values['mode'], $values['type'], $values['paymentType'], $values['installmentType'] ?? ''
        ));
    }

    public function internationalFeeCalculation(Request $request): JsonResponse
    {
        $values = $request->validate([
            'facultyId' => ['required', 'integer', 'min:1'], 'departmentId' => ['required', 'integer', 'min:1'],
            'degreeId' => ['required', 'integer', 'min:1'], 'type' => ['required', 'string'],
        ]);
        return $this->ok($this->legacy->instance()->getInternationalSetupFeeCalculation(
            (int) $values['facultyId'], (int) $values['departmentId'], (int) $values['degreeId'], $values['type']
        ));
    }

    public function statistics(Request $request): JsonResponse
    {
        $values = $request->validate(['session' => ['required', 'string']]);
        return $this->ok($this->legacy->instance()->getStatistics($values['session']), ['session' => $values['session']]);
    }
}
