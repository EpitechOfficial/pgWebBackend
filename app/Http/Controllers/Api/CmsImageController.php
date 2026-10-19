<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CmsUploadService;
use App\Services\LegacyRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

final class CmsImageController extends Controller
{
    public function __construct(private readonly LegacyRepository $legacy, private readonly CmsUploadService $uploads)
    {
    }

    private function image(Request $request, string $key, bool $required, bool $carousel = false): UploadedFile|JsonResponse|null
    {
        if (!$request->hasFile($key)) {
            return $required ? response()->json(['status' => 'error', 'message' => 'Choose image file to upload.'], 422) : null;
        }
        $file = $request->file($key);
        if (!$file->isValid()) return response()->json(['status' => 'error', 'message' => 'There was an error uploading the image.'], 422);
        if (!in_array(strtolower($file->getClientOriginalExtension()), ['png', 'jpg', 'jpeg'], true)) {
            return response()->json(['status' => 'error', 'message' => 'Upload valid images. Only PNG and JPEG are allowed.'], 422);
        }
        if ($file->getSize() > 2000000) return response()->json(['status' => 'error', 'message' => 'Image size exceeds 2MB.'], 422);
        if ($carousel) {
            $size = @getimagesize($file->getRealPath());
            if ((int) ($size[0] ?? 0) > 940 || (int) ($size[1] ?? 0) > 340) {
                return response()->json(['status' => 'error', 'message' => 'Image dimension should be within 940 by 340.'], 422);
            }
        }
        return $file;
    }

    private function store(UploadedFile $file, string $fallback, bool $rejectDuplicate): string|JsonResponse
    {
        $name = $this->uploads->safeName($file, $fallback);
        try { $this->uploads->store($file, $this->uploads->imageDirectory(), $name, $rejectDuplicate); }
        catch (\RuntimeException $exception) {
            if ($exception->getMessage() === 'DUPLICATE_UPLOAD') return response()->json(['status' => 'error', 'message' => 'Sorry, a file with the same name already exists.'], 422);
            throw $exception;
        }
        return $name;
    }

    private function missing(string $message): JsonResponse { return response()->json(['status' => 'error', 'message' => $message], 422); }
    private function notFound(string $message): JsonResponse { return response()->json(['status' => 'error', 'message' => $message], 404); }
    private function done(string $message, array $data = []): JsonResponse { return response()->json(['status' => 'ok', 'message' => $message, ...($data === [] ? [] : ['data' => $data])]); }
    private function deleteReplacedImage(array $existing, string $newName): void
    {
        $oldName = trim((string) ($existing['image'] ?? ''));
        if ($oldName !== '' && $oldName !== $newName) $this->uploads->delete($this->uploads->imageDirectory(), $oldName);
    }

    public function createCarousel(Request $request): JsonResponse
    {
        $link = trim((string) $request->input('link', ''));
        if ($link === '') return $this->missing('Input image url is required.');
        $file = $this->image($request, 'uploadfile', true, true); if ($file instanceof JsonResponse) return $file;
        $name = $this->store($file, 'carousel', true); if ($name instanceof JsonResponse) return $name;
        $id = $this->legacy->instance()->createCarousel($name, $link);
        return $this->done('Image upload done successfully.', ['id' => $id]);
    }

    public function updateCarousel(Request $request): JsonResponse
    {
        $id = $request->integer('id'); $link = trim((string) $request->input('link', ''));
        if ($id <= 0 || $link === '') return $this->missing('Carousel id and image url are required.');
        $existing = $this->legacy->instance()->getCarouselById($id); if ($existing === []) return $this->notFound('Carousel record not found.');
        $file = $this->image($request, 'uploadfile', false, true); if ($file instanceof JsonResponse) return $file;
        $name = null;
        if ($file) { $name = $this->store($file, 'carousel', false); if ($name instanceof JsonResponse) return $name; }
        $this->legacy->instance()->updateCarousel($id, $link, $name);
        if ($name) $this->deleteReplacedImage($existing, $name);
        return $this->done('Carousel updated successfully.');
    }

    public function deleteCarousel(Request $request): JsonResponse
    {
        $id = $request->integer('id'); if ($id <= 0) return $this->missing('Carousel id is required.');
        $existing = $this->legacy->instance()->getCarouselById($id); if ($existing === []) return $this->notFound('Carousel record not found.');
        $this->legacy->instance()->deleteCarousel($id); $this->uploads->delete($this->uploads->imageDirectory(), (string) ($existing['image'] ?? ''));
        return $this->done('Delete done successfully.');
    }

    public function createFlagship(Request $request): JsonResponse
    {
        $dept = trim((string) $request->input('dept', '')); $title = trim((string) $request->input('title', '')); $text = trim((string) $request->input('text', ''));
        if ($dept === '' || $title === '' || $text === '') return $this->missing('Programme, title, and introduction are required.');
        $file = $this->image($request, 'uploadefile', true); if ($file instanceof JsonResponse) return $file;
        $name = $this->store($file, 'cms', true); if ($name instanceof JsonResponse) return $name;
        return $this->done('Flagship programme uploaded successfully.', ['id' => $this->legacy->instance()->createFlagshipProgram($name, $dept, $title, $text)]);
    }

    public function updateFlagship(Request $request): JsonResponse
    {
        $id = $request->integer('id'); $dept = trim((string) $request->input('dept', '')); $title = trim((string) $request->input('title', '')); $text = trim((string) $request->input('text', ''));
        if ($id <= 0 || $dept === '' || $title === '' || $text === '') return $this->missing('Programme id, programme, title, and introduction are required.');
        $existing = $this->legacy->instance()->getFlagshipProgramById($id); if ($existing === []) return $this->notFound('Flagship programme record not found.');
        $file = $this->image($request, 'uploadefile', false); if ($file instanceof JsonResponse) return $file;
        $name = null; if ($file) { $name = $this->store($file, 'cms', false); if ($name instanceof JsonResponse) return $name; }
        $this->legacy->instance()->updateFlagshipProgram($id, $dept, $title, $text, $name);
        if ($name) $this->deleteReplacedImage($existing, $name);
        return $this->done('Flagship programme updated successfully.');
    }

    public function deleteFlagship(Request $request): JsonResponse
    {
        $id = $request->integer('id'); if ($id <= 0) return $this->missing('Flagship programme id is required.');
        $existing = $this->legacy->instance()->getFlagshipProgramById($id); if ($existing === []) return $this->notFound('Flagship programme record not found.');
        $this->legacy->instance()->deleteFlagshipProgram($id); $this->uploads->delete($this->uploads->imageDirectory(), (string) ($existing['image'] ?? ''));
        return $this->done('Delete done successfully.');
    }

    public function provost(Request $request): JsonResponse
    {
        $speech = trim((string) $request->input('speech', '')); if ($speech === '') return $this->missing("Provost's speech is required.");
        $existing = $this->legacy->instance()->getCmsProvostSpeechPayload()['provost'] ?? [];
        $file = $this->image($request, 'uploadpfile', false); if ($file instanceof JsonResponse) return $file;
        $name = null; if ($file) { $name = $this->store($file, 'cms', false); if ($name instanceof JsonResponse) return $name; }
        $this->legacy->instance()->updateProvostSpeech($speech, $name);
        if ($name) $this->deleteReplacedImage($existing, $name);
        return $this->done('Update done successfully.');
    }

    public function academicsImage(Request $request): JsonResponse
    {
        $existing = $this->legacy->instance()->getCmsAcademicsPayload()['academic'] ?? [];
        $file = $this->image($request, 'uploadfile', true); if ($file instanceof JsonResponse) return $file;
        $name = $this->store($file, 'cms', true); if ($name instanceof JsonResponse) return $name;
        $this->legacy->instance()->updateAcademicImage($name);
        $this->deleteReplacedImage($existing, $name);
        return $this->done('Image upload done successfully.');
    }

    public function createFacility(Request $request): JsonResponse
    {
        $course = trim((string) $request->input('department', '')); $header = trim((string) $request->input('header', '')); $content = trim((string) $request->input('content', ''));
        if ($course === '' || $header === '' || $content === '') return $this->missing('Department, header, and content are required.');
        $file = $this->image($request, 'uploadfile', true); if ($file instanceof JsonResponse) return $file;
        $name = $this->store($file, 'cms', true); if ($name instanceof JsonResponse) return $name;
        return $this->done('Facility uploaded successfully.', ['id' => $this->legacy->instance()->createFacility($name, $course, $header, $content)]);
    }

    public function updateFacility(Request $request): JsonResponse
    {
        $id = $request->integer('id'); $course = trim((string) $request->input('department', '')); $header = trim((string) $request->input('header', '')); $content = trim((string) $request->input('content', ''));
        if ($id <= 0 || $course === '' || $header === '' || $content === '') return $this->missing('Facility id, department, header, and content are required.');
        $existing = $this->legacy->instance()->getFacilityById($id); if ($existing === []) return $this->notFound('Facility record not found.');
        $file = $this->image($request, 'uploadfile', false); if ($file instanceof JsonResponse) return $file;
        $name = null; if ($file) { $name = $this->store($file, 'cms', false); if ($name instanceof JsonResponse) return $name; }
        $this->legacy->instance()->updateFacility($id, $course, $header, $content, $name);
        if ($name) $this->deleteReplacedImage($existing, $name);
        return $this->done('Facility updated successfully.');
    }

    public function deleteFacility(Request $request): JsonResponse
    {
        $id = $request->integer('id'); if ($id <= 0) return $this->missing('Facility id is required.');
        $existing = $this->legacy->instance()->getFacilityById($id); if ($existing === []) return $this->notFound('Facility record not found.');
        $this->legacy->instance()->deleteFacility($id); $this->uploads->delete($this->uploads->imageDirectory(), (string) ($existing['image'] ?? ''));
        return $this->done('Delete done successfully.');
    }

    public function createSupervisor(Request $request): JsonResponse
    {
        $course = trim((string) $request->input('department', '')); $nameValue = trim((string) $request->input('name', '')); $title = trim((string) $request->input('title', '')); $link = trim((string) $request->input('link', '')); $about = trim((string) $request->input('about', ''));
        if ($course === '' || $nameValue === '' || $title === '' || $link === '' || $about === '') return $this->missing('Department, name, title, link, and about are required.');
        $file = $this->image($request, 'uploadfile', true); if ($file instanceof JsonResponse) return $file;
        $image = $this->store($file, 'cms', true); if ($image instanceof JsonResponse) return $image;
        return $this->done('Supervisor uploaded successfully.', ['id' => $this->legacy->instance()->createSupervisor($image, $course, $nameValue, $title, $link, $about)]);
    }

    public function updateSupervisor(Request $request): JsonResponse
    {
        $id = $request->integer('id'); $course = trim((string) $request->input('department', '')); $nameValue = trim((string) $request->input('name', '')); $title = trim((string) $request->input('title', '')); $link = trim((string) $request->input('link', '')); $about = trim((string) $request->input('about', ''));
        if ($id <= 0 || $course === '' || $nameValue === '' || $title === '' || $link === '' || $about === '') return $this->missing('Supervisor id, department, name, title, link, and about are required.');
        $existing = $this->legacy->instance()->getSupervisorById($id); if ($existing === []) return $this->notFound('Supervisor record not found.');
        $file = $this->image($request, 'uploadfile', false); if ($file instanceof JsonResponse) return $file;
        $image = null; if ($file) { $image = $this->store($file, 'cms', false); if ($image instanceof JsonResponse) return $image; }
        $this->legacy->instance()->updateSupervisor($id, $course, $nameValue, $title, $link, $about, $image);
        if ($image) $this->deleteReplacedImage($existing, $image);
        return $this->done('Supervisor updated successfully.');
    }

    public function deleteSupervisor(Request $request): JsonResponse
    {
        $id = $request->integer('id'); if ($id <= 0) return $this->missing('Supervisor id is required.');
        $existing = $this->legacy->instance()->getSupervisorById($id); if ($existing === []) return $this->notFound('Supervisor record not found.');
        $this->legacy->instance()->deleteSupervisor($id); $this->uploads->delete($this->uploads->imageDirectory(), (string) ($existing['image'] ?? ''));
        return $this->done('Delete done successfully.');
    }

    public function createStudent(Request $request): JsonResponse
    {
        $quote = trim((string) $request->input('quote', '')); $content = trim((string) $request->input('content', ''));
        if ($quote === '' || $content === '') return $this->missing('Quote and content are required.');
        $file = $this->image($request, 'uploadfile', true); if ($file instanceof JsonResponse) return $file;
        $image = $this->store($file, 'cms', true); if ($image instanceof JsonResponse) return $image;
        return $this->done('Student speech uploaded successfully.', ['id' => $this->legacy->instance()->createStudentSpeech($image, $quote, $content)]);
    }

    public function updateStudent(Request $request): JsonResponse
    {
        $id = $request->integer('id'); $quote = trim((string) $request->input('quote', '')); $content = trim((string) $request->input('content', ''));
        if ($id <= 0 || $quote === '' || $content === '') return $this->missing('Student speech id, quote, and content are required.');
        $existing = $this->legacy->instance()->getStudentSpeechById($id); if ($existing === []) return $this->notFound('Student speech record not found.');
        $file = $this->image($request, 'uploadfile', false); if ($file instanceof JsonResponse) return $file;
        $image = null; if ($file) { $image = $this->store($file, 'cms', false); if ($image instanceof JsonResponse) return $image; }
        $this->legacy->instance()->updateStudentSpeech($id, $quote, $content, $image);
        if ($image) $this->deleteReplacedImage($existing, $image);
        return $this->done('Student speech updated successfully.');
    }

    public function deleteStudent(Request $request): JsonResponse
    {
        $id = $request->integer('id'); if ($id <= 0) return $this->missing('Student speech id is required.');
        $existing = $this->legacy->instance()->getStudentSpeechById($id); if ($existing === []) return $this->notFound('Student speech record not found.');
        $this->legacy->instance()->deleteStudentSpeech($id); $this->uploads->delete($this->uploads->imageDirectory(), (string) ($existing['image'] ?? ''));
        return $this->done('Delete done successfully.');
    }
}
