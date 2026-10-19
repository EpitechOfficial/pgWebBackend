<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LegacyRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CmsUpdateController extends Controller
{
    public function __construct(private readonly LegacyRepository $legacy)
    {
    }

    private function fields(Request $request, array $names, string $message): array|JsonResponse
    {
        $values = [];
        foreach ($names as $name) {
            $values[$name] = trim((string) $request->input($name, ''));
        }

        if (in_array('', $values, true)) {
            return response()->json(['status' => 'error', 'message' => $message], 422);
        }

        return $values;
    }

    private function done(string $message = 'Upload done successfully.', array $data = []): JsonResponse
    {
        $payload = ['status' => 'ok', 'message' => $message];
        if ($data !== []) $payload['data'] = $data;
        return response()->json($payload);
    }

    public function academics(Request $request): JsonResponse
    {
        $values = $this->fields($request, ['introHead', 'headCon', 'finderCon'], 'Intro head, intro content, and finder content are required.');
        if ($values instanceof JsonResponse) return $values;
        $this->legacy->instance()->updateAcademicContent($values['introHead'], $values['headCon'], $values['finderCon']);
        return $this->done();
    }

    public function createProgramme(Request $request): JsonResponse
    {
        return $this->saveProgramme($request, false);
    }

    public function updateProgramme(Request $request): JsonResponse
    {
        return $this->saveProgramme($request, true);
    }

    private function saveProgramme(Request $request, bool $update): JsonResponse
    {
        $values = $this->fields($request, ['department', 'requirement', 'link', 'overview', 'contentOverview', 'ngRequirement', 'foreignRequirement', 'career'], 'All programme fields are required.');
        if ($values instanceof JsonResponse) return $values;
        $arguments = [$values['department'], $values['overview'], $values['requirement'], $values['contentOverview'], $values['link'], $values['ngRequirement'], $values['foreignRequirement'], $values['career']];
        $update ? $this->legacy->instance()->updateProgramme(...$arguments) : $this->legacy->instance()->createProgramme(...$arguments);
        return $this->done();
    }

    public function toggleProgramme(Request $request): JsonResponse
    {
        $values = $this->fields($request, ['department'], 'Programme department is required.');
        if ($values instanceof JsonResponse) return $values;
        $status = $this->legacy->instance()->toggleProgrammeStatus($values['department']);
        return $this->done($status === 1 ? 'Programme enabled successfully.' : 'Programme disabled successfully.', ['status' => $status]);
    }

    public function why(Request $request): JsonResponse
    {
        $values = $this->fields($request, ['intro', 'award', 'speechintro'], 'Why intro, awards, and student speech intro are required.');
        if ($values instanceof JsonResponse) return $values;
        $this->legacy->instance()->updateWhyContent($values['intro'], $values['award'], $values['speechintro']);
        return $this->done();
    }

    public function fees(Request $request): JsonResponse
    {
        $values = $this->fields($request, ['intro', 'fees', 'fundings'], 'Intro, fees, and fundings are required.');
        if ($values instanceof JsonResponse) return $values;
        $this->legacy->instance()->updateFeesContent($values['intro'], $values['fees'], $values['fundings']);
        return $this->done();
    }

    public function admissions(Request $request): JsonResponse
    {
        $values = $this->fields($request, ['heading', 'intro', 'guide', 'after', 'fee', 'link'], 'Heading, intro, guide intro, after intro, acceptance intro, and acceptance link are required.');
        if ($values instanceof JsonResponse) return $values;
        $this->legacy->instance()->updateAdmissionsContent($values['heading'], $values['intro'], $values['guide'], $values['after'], $values['fee'], $values['link']);
        return $this->done();
    }

    public function admissionsGuide(Request $request): JsonResponse
    {
        $values = $this->fields($request, ['intro', 'rules', 'type', 'form', 'guidelines', 'criteria', 'requirement', 'link'], 'All application guide fields are required.');
        if ($values instanceof JsonResponse) return $values;
        $this->legacy->instance()->updateAdmissionsGuideContent($values['intro'], $values['rules'], $values['type'], $values['form'], $values['guidelines'], $values['criteria'], $values['requirement'], $values['link']);
        return $this->done();
    }

    public function admissionsAfter(Request $request): JsonResponse
    {
        $values = $this->fields($request, ['intro', 'track', 'tpe', 'fee', 'clearance'], 'All after application fields are required.');
        if ($values instanceof JsonResponse) return $values;
        $this->legacy->instance()->updateAdmissionsAfterContent($values['intro'], $values['track'], $values['tpe'], $values['fee'], $values['clearance']);
        return $this->done();
    }

    public function international(Request $request): JsonResponse
    {
        $values = $this->fields($request, ['intro', 'gen_req', 'lang_req', 'visa_guide'], 'Introduction, general requirement, language requirement, and visa guidance are required.');
        if ($values instanceof JsonResponse) return $values;
        $this->legacy->instance()->updateInternationalContent($values['intro'], $values['gen_req'], $values['lang_req'], $values['visa_guide']);
        return $this->done();
    }

    public function contact(Request $request): JsonResponse
    {
        $values = $this->fields($request, ['phone', 'email', 'email2', 'facebook', 'twitter', 'instagram'], 'Phone, emails, and all social links are required.');
        if ($values instanceof JsonResponse) return $values;
        $this->legacy->instance()->updateContactContent($values['phone'], $values['email'], $values['email2'], $values['facebook'], $values['twitter'], $values['instagram']);
        return $this->done();
    }
}
