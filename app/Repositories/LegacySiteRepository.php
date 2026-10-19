<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Database\DatabaseManager;
use PDO;
use PDOException;
use Throwable;

final class LegacySiteRepository
{
    private readonly PDO $pdo;

    public function __construct(DatabaseManager $database)
    {
        $this->pdo = $database->connection()->getPdo();
    }

    public function getHomePayload(): array
    {
        return [
            'contact' => $this->fetchSingleRow('SELECT * FROM contact_site LIMIT 1'),
            'academic' => $this->fetchSingleRow('SELECT * FROM academic_site LIMIT 1'),
            'departments' => $this->fetchColumn(
                "SELECT DISTINCT department FROM courses_site WHERE status = '1' ORDER BY department"
            ),
            'carousel' => $this->fetchAllRows('SELECT * FROM carousel_site ORDER BY id DESC'),
            'provost' => $this->fetchSingleRow('SELECT * FROM provost_site LIMIT 1'),
            'quickLinks' => $this->getQuickLinks(),
            'flagshipPrograms' => $this->fetchAllRows('SELECT * FROM events_site ORDER BY RAND() LIMIT 9')
        ];
    }

    public function getContactPayload(): array
    {
        return [
            'contact' => $this->fetchSingleRow('SELECT * FROM contact_site LIMIT 1')
        ];
    }

    public function authenticateCmsUser(string $username, string $password): array
    {
        $user = $this->fetchPreparedSingleRow(
            'SELECT username, password FROM login_site WHERE username = :username LIMIT 1',
            ['username' => $username]
        );

        if (!$user || empty($user['password']) || !password_verify($password, (string) $user['password'])) {
            throw new PDOException('Invalid login details.', 401);
        }

        return [
            'username' => (string) ($user['username'] ?? '')
        ];
    }

    public function getCmsHomePayload(): array
    {
        return [
            'carousels' => $this->fetchAllRows('SELECT * FROM carousel_site ORDER BY id DESC'),
            'flagshipPrograms' => $this->fetchAllRows('SELECT * FROM events_site ORDER BY id DESC'),
            'provost' => $this->fetchSingleRow('SELECT * FROM provost_site LIMIT 1')
        ];
    }

    public function getCmsFlagshipProgrammesPayload(): array
    {
        return [
            'flagshipPrograms' => $this->fetchAllRows('SELECT * FROM events_site ORDER BY id DESC'),
            'programmeOptions' => $this->fetchAllRows(
                "SELECT DISTINCT dept_new.department
                 FROM fieldofinterest5
                 INNER JOIN dept_new ON dept_new.id = fieldofinterest5.dept
                 WHERE fieldofinterest5.app_status = 0
                 ORDER BY dept_new.department"
            )
        ];
    }

    public function getCmsProvostSpeechPayload(): array
    {
        return [
            'provost' => $this->fetchSingleRow('SELECT * FROM provost_site LIMIT 1')
        ];
    }

    public function getCmsAcademicsPayload(): array
    {
        return [
            'academic' => $this->fetchSingleRow('SELECT * FROM academic_site LIMIT 1')
        ];
    }

    public function getCmsProgrammesPayload(): array
    {
        $rows = $this->fetchAllRows(
            "SELECT
                c.department,
                c.status,
                COALESCE(MAX(fac_new.faculty), '') AS faculty,
                GROUP_CONCAT(DISTINCT degree_new.degree ORDER BY degree_new.degree SEPARATOR '||') AS degree_list
             FROM courses_site c
             LEFT JOIN dept_new ON dept_new.department = c.department
             LEFT JOIN fieldofinterest5 ON fieldofinterest5.dept = dept_new.id
             LEFT JOIN fac_new ON fac_new.id = fieldofinterest5.fac
             LEFT JOIN degree_new ON degree_new.id = fieldofinterest5.degree
             GROUP BY
                c.department,
                c.status
             ORDER BY c.department"
        );

        $items = array_map(static function (array $row): array {
            $degreeList = trim((string) ($row['degree_list'] ?? ''));
            $degrees = $degreeList === ''
                ? []
                : array_values(array_filter(array_map('trim', explode('||', $degreeList))));

            return [
                'department' => (string) ($row['department'] ?? ''),
                'faculty' => (string) ($row['faculty'] ?? ''),
                'degrees' => $degrees,
                'status' => (int) ($row['status'] ?? 0)
            ];
        }, $rows);

        return [
            'items' => $items,
            'programmeOptions' => $this->fetchAllRows(
                "SELECT DISTINCT dept_new.department
                 FROM fieldofinterest5
                 INNER JOIN dept_new ON dept_new.id = fieldofinterest5.dept
                 WHERE fieldofinterest5.app_status = 0
                 ORDER BY dept_new.department"
            )
        ];
    }

    public function getCmsFacilitiesPayload(): array
    {
        return [
            'facilities' => $this->fetchAllRows('SELECT * FROM facilities_site ORDER BY id DESC'),
            'programmeOptions' => $this->fetchAllRows(
                "SELECT DISTINCT dept_new.department
                 FROM fieldofinterest5
                 INNER JOIN dept_new ON dept_new.id = fieldofinterest5.dept
                 WHERE fieldofinterest5.app_status = 0
                 ORDER BY dept_new.department"
            )
        ];
    }

    public function getCmsSupervisorsPayload(): array
    {
        return [
            'supervisors' => $this->fetchAllRows('SELECT * FROM supervisor_site ORDER BY id DESC'),
            'programmeOptions' => $this->fetchAllRows(
                "SELECT DISTINCT dept_new.department
                 FROM fieldofinterest5
                 INNER JOIN dept_new ON dept_new.id = fieldofinterest5.dept
                 WHERE fieldofinterest5.app_status = 0
                 ORDER BY dept_new.department"
            )
        ];
    }

    public function getCmsWhyPayload(): array
    {
        return [
            'why' => $this->fetchSingleRow('SELECT * FROM why_site LIMIT 1')
        ];
    }

    public function getCmsStudentSpeechesPayload(): array
    {
        return [
            'students' => $this->fetchAllRows('SELECT * FROM student_site ORDER BY id DESC')
        ];
    }

    public function getCmsFeesPayload(): array
    {
        return [
            'fees' => $this->fetchSingleRow('SELECT * FROM fees_site LIMIT 1')
        ];
    }

    public function getCmsAdmissionsPayload(): array
    {
        return [
            'admissions' => $this->fetchSingleRow('SELECT * FROM admission_site LIMIT 1')
        ];
    }

    public function getCmsAdmissionsGuidePayload(): array
    {
        return [
            'guide' => $this->fetchSingleRow('SELECT * FROM guide_site LIMIT 1')
        ];
    }

    public function getCmsAdmissionsAfterPayload(): array
    {
        return [
            'after' => $this->fetchSingleRow('SELECT * FROM after_site LIMIT 1')
        ];
    }

    public function getCmsInternationalPayload(): array
    {
        return [
            'international' => $this->fetchSingleRow('SELECT * FROM international_site LIMIT 1')
        ];
    }

    public function getCmsContactPayload(): array
    {
        return [
            'contact' => $this->fetchSingleRow('SELECT * FROM contact_site LIMIT 1')
        ];
    }

    public function getCmsProgrammeDetailPayload(string $department): array
    {
        $department = trim($department);

        if ($department === '') {
            return [];
        }

        $row = $this->fetchPreparedSingleRow(
            "SELECT
                c.department,
                c.overview,
                c.requirement,
                c.contentoverview,
                c.link,
                c.ngrequirement,
                c.foreignrequirement,
                c.career,
                c.status,
                COALESCE(MAX(fac_new.faculty), '') AS faculty,
                GROUP_CONCAT(DISTINCT degree_new.degree ORDER BY degree_new.degree SEPARATOR '||') AS degree_list
             FROM courses_site c
             LEFT JOIN dept_new ON dept_new.department = c.department
             LEFT JOIN fieldofinterest5 ON fieldofinterest5.dept = dept_new.id
             LEFT JOIN fac_new ON fac_new.id = fieldofinterest5.fac
             LEFT JOIN degree_new ON degree_new.id = fieldofinterest5.degree
             WHERE c.department = :department
             GROUP BY
                c.department,
                c.overview,
                c.requirement,
                c.contentoverview,
                c.link,
                c.ngrequirement,
                c.foreignrequirement,
                c.career,
                c.status
             LIMIT 1",
            ['department' => $department]
        );

        if ($row === []) {
            return [];
        }

        $degreeList = trim((string) ($row['degree_list'] ?? ''));
        $degrees = $degreeList === ''
            ? []
            : array_values(array_filter(array_map('trim', explode('||', $degreeList))));

        return [
            'department' => (string) ($row['department'] ?? ''),
            'faculty' => (string) ($row['faculty'] ?? ''),
            'degrees' => $degrees,
            'overview' => (string) ($row['overview'] ?? ''),
            'requirement' => (string) ($row['requirement'] ?? ''),
            'contentoverview' => (string) ($row['contentoverview'] ?? ''),
            'link' => (string) ($row['link'] ?? ''),
            'ngrequirement' => (string) ($row['ngrequirement'] ?? ''),
            'foreignrequirement' => (string) ($row['foreignrequirement'] ?? ''),
            'career' => (string) ($row['career'] ?? ''),
            'status' => (int) ($row['status'] ?? 0)
        ];
    }

    public function getFacilityById(int $id): array
    {
        return $this->fetchPreparedSingleRow(
            'SELECT * FROM facilities_site WHERE id = :id LIMIT 1',
            ['id' => $id]
        );
    }

    public function createFacility(string $imageName, string $course, string $header, string $content): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO facilities_site (image, course, header, content) VALUES (:image, :course, :header, :content)'
        );
        $statement->execute([
            'image' => $imageName,
            'course' => $course,
            'header' => $header,
            'content' => $content
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateFacility(
        int $id,
        string $course,
        string $header,
        string $content,
        ?string $imageName = null
    ): void {
        if ($imageName !== null) {
            $statement = $this->pdo->prepare(
                'UPDATE facilities_site SET image = :image, course = :course, header = :header, content = :content WHERE id = :id'
            );
            $statement->execute([
                'id' => $id,
                'image' => $imageName,
                'course' => $course,
                'header' => $header,
                'content' => $content
            ]);
            return;
        }

        $statement = $this->pdo->prepare(
            'UPDATE facilities_site SET course = :course, header = :header, content = :content WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'course' => $course,
            'header' => $header,
            'content' => $content
        ]);
    }

    public function deleteFacility(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM facilities_site WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function getSupervisorById(int $id): array
    {
        return $this->fetchPreparedSingleRow(
            'SELECT * FROM supervisor_site WHERE id = :id LIMIT 1',
            ['id' => $id]
        );
    }

    public function createSupervisor(
        string $imageName,
        string $course,
        string $name,
        string $title,
        string $link,
        string $about
    ): int {
        $statement = $this->pdo->prepare(
            'INSERT INTO supervisor_site (image, course, name, link, title, about) VALUES (:image, :course, :name, :link, :title, :about)'
        );
        $statement->execute([
            'image' => $imageName,
            'course' => $course,
            'name' => $name,
            'link' => $link,
            'title' => $title,
            'about' => $about
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateSupervisor(
        int $id,
        string $course,
        string $name,
        string $title,
        string $link,
        string $about,
        ?string $imageName = null
    ): void {
        if ($imageName !== null) {
            $statement = $this->pdo->prepare(
                'UPDATE supervisor_site SET image = :image, course = :course, name = :name, title = :title, link = :link, about = :about WHERE id = :id'
            );
            $statement->execute([
                'id' => $id,
                'image' => $imageName,
                'course' => $course,
                'name' => $name,
                'title' => $title,
                'link' => $link,
                'about' => $about
            ]);
            return;
        }

        $statement = $this->pdo->prepare(
            'UPDATE supervisor_site SET course = :course, name = :name, title = :title, link = :link, about = :about WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'course' => $course,
            'name' => $name,
            'title' => $title,
            'link' => $link,
            'about' => $about
        ]);
    }

    public function deleteSupervisor(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM supervisor_site WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function updateWhyContent(string $intro, string $award, string $speechIntro): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE why_site SET intro = :intro, award = :award, speechintro = :speechintro'
        );
        $statement->execute([
            'intro' => $intro,
            'award' => $award,
            'speechintro' => $speechIntro
        ]);
    }

    public function getStudentSpeechById(int $id): array
    {
        return $this->fetchPreparedSingleRow(
            'SELECT * FROM student_site WHERE id = :id LIMIT 1',
            ['id' => $id]
        );
    }

    public function createStudentSpeech(string $imageName, string $quote, string $content): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO student_site (image, quote, content) VALUES (:image, :quote, :content)'
        );
        $statement->execute([
            'image' => $imageName,
            'quote' => $quote,
            'content' => $content
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateStudentSpeech(int $id, string $quote, string $content, ?string $imageName = null): void
    {
        if ($imageName !== null) {
            $statement = $this->pdo->prepare(
                'UPDATE student_site SET image = :image, quote = :quote, content = :content WHERE id = :id'
            );
            $statement->execute([
                'id' => $id,
                'image' => $imageName,
                'quote' => $quote,
                'content' => $content
            ]);
            return;
        }

        $statement = $this->pdo->prepare(
            'UPDATE student_site SET quote = :quote, content = :content WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'quote' => $quote,
            'content' => $content
        ]);
    }

    public function deleteStudentSpeech(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM student_site WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function updateFeesContent(string $intro, string $fees, string $fundings): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE fees_site SET intro = :intro, fees = :fees, fundings = :fundings'
        );
        $statement->execute([
            'intro' => $intro,
            'fees' => $fees,
            'fundings' => $fundings
        ]);
    }

    public function updateAdmissionsContent(
        string $heading,
        string $intro,
        string $guide,
        string $after,
        string $fee,
        string $link
    ): void {
        $statement = $this->pdo->prepare(
            'UPDATE admission_site
             SET headings = :heading,
                 intro = :intro,
                 guide_intro = :guide,
                 after_intro = :after,
                 acceptance_intro = :fee,
                 acceptance_link = :link'
        );
        $statement->execute([
            'heading' => $heading,
            'intro' => $intro,
            'guide' => $guide,
            'after' => $after,
            'fee' => $fee,
            'link' => $link
        ]);
    }

    public function updateAdmissionsGuideContent(
        string $intro,
        string $rules,
        string $type,
        string $form,
        string $guidelines,
        string $criteria,
        string $requirement,
        string $link
    ): void {
        $statement = $this->pdo->prepare(
            'UPDATE guide_site
             SET intro = :intro,
                 rules_regulation = :rules,
                 application_type = :type,
                 application_form = :form,
                 guidelines = :guidelines,
                 criteria = :criteria,
                 requirement = :requirement,
                 link = :link'
        );
        $statement->execute([
            'intro' => $intro,
            'rules' => $rules,
            'type' => $type,
            'form' => $form,
            'guidelines' => $guidelines,
            'criteria' => $criteria,
            'requirement' => $requirement,
            'link' => $link
        ]);
    }

    public function updateAdmissionsAfterContent(
        string $intro,
        string $track,
        string $tpe,
        string $fee,
        string $clearance
    ): void {
        $statement = $this->pdo->prepare(
            'UPDATE after_site
             SET intro = :intro,
                 track = :track,
                 tpe = :tpe,
                 fee = :fee,
                 clearance = :clearance'
        );
        $statement->execute([
            'intro' => $intro,
            'track' => $track,
            'tpe' => $tpe,
            'fee' => $fee,
            'clearance' => $clearance
        ]);
    }

    public function updateInternationalContent(
        string $intro,
        string $generalRequirement,
        string $languageRequirement,
        string $visaGuide
    ): void {
        $statement = $this->pdo->prepare(
            'UPDATE international_site
             SET intro = :intro,
                 gen_req = :generalRequirement,
                 lang_req = :languageRequirement,
                 visa_guide = :visaGuide'
        );
        $statement->execute([
            'intro' => $intro,
            'generalRequirement' => $generalRequirement,
            'languageRequirement' => $languageRequirement,
            'visaGuide' => $visaGuide
        ]);
    }

    public function updateContactContent(
        string $phone,
        string $email,
        string $email2,
        string $facebook,
        string $twitter,
        string $instagram
    ): void {
        $statement = $this->pdo->prepare(
            'UPDATE contact_site
             SET phone = :phone,
                 email = :email,
                 email2 = :email2,
                 facebook = :facebook,
                 twitter = :twitter,
                 instagram = :instagram'
        );
        $statement->execute([
            'phone' => $phone,
            'email' => $email,
            'email2' => $email2,
            'facebook' => $facebook,
            'twitter' => $twitter,
            'instagram' => $instagram
        ]);
    }

    public function getCarouselById(int $id): array
    {
        return $this->fetchPreparedSingleRow(
            'SELECT * FROM carousel_site WHERE id = :id LIMIT 1',
            ['id' => $id]
        );
    }

    public function createCarousel(string $imageName, string $imageLink): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO carousel_site (image, imagelink) VALUES (:image, :imagelink)'
        );
        $statement->execute([
            'image' => $imageName,
            'imagelink' => $imageLink
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateCarousel(int $id, string $imageLink, ?string $imageName = null): void
    {
        if ($imageName !== null) {
            $statement = $this->pdo->prepare(
                'UPDATE carousel_site SET image = :image, imagelink = :imagelink WHERE id = :id'
            );
            $statement->execute([
                'id' => $id,
                'image' => $imageName,
                'imagelink' => $imageLink
            ]);
            return;
        }

        $statement = $this->pdo->prepare(
            'UPDATE carousel_site SET imagelink = :imagelink WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'imagelink' => $imageLink
        ]);
    }

    public function deleteCarousel(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM carousel_site WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function getFlagshipProgramById(int $id): array
    {
        return $this->fetchPreparedSingleRow(
            'SELECT * FROM events_site WHERE id = :id LIMIT 1',
            ['id' => $id]
        );
    }

    public function createFlagshipProgram(string $imageName, string $department, string $programTitle, string $introText): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO events_site (image, title, text, program) VALUES (:image, :title, :text, :program)'
        );
        $statement->execute([
            'image' => $imageName,
            'title' => $department,
            'text' => $introText,
            'program' => $programTitle
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateFlagshipProgram(
        int $id,
        string $department,
        string $programTitle,
        string $introText,
        ?string $imageName = null
    ): void {
        if ($imageName !== null) {
            $statement = $this->pdo->prepare(
                'UPDATE events_site SET image = :image, title = :title, text = :text, program = :program WHERE id = :id'
            );
            $statement->execute([
                'id' => $id,
                'image' => $imageName,
                'title' => $department,
                'text' => $introText,
                'program' => $programTitle
            ]);
            return;
        }

        $statement = $this->pdo->prepare(
            'UPDATE events_site SET title = :title, text = :text, program = :program WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'title' => $department,
            'text' => $introText,
            'program' => $programTitle
        ]);
    }

    public function deleteFlagshipProgram(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM events_site WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function updateProvostSpeech(string $speech, ?string $imageName = null): void
    {
        if ($imageName !== null) {
            $statement = $this->pdo->prepare(
                'UPDATE provost_site SET image = :image, speech = :speech'
            );
            $statement->execute([
                'image' => $imageName,
                'speech' => $speech
            ]);
            return;
        }

        $statement = $this->pdo->prepare(
            'UPDATE provost_site SET speech = :speech'
        );
        $statement->execute([
            'speech' => $speech
        ]);
    }

    public function updateAcademicImage(string $imageName): void
    {
        $statement = $this->pdo->prepare('UPDATE academic_site SET image = :image');
        $statement->execute([
            'image' => $imageName
        ]);
    }

    public function updateAcademicContent(string $head, string $intro, string $finder): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE academic_site SET head = :head, intro = :intro, finder = :finder'
        );
        $statement->execute([
            'head' => $head,
            'intro' => $intro,
            'finder' => $finder
        ]);
    }

    public function createProgramme(
        string $department,
        string $overview,
        string $requirement,
        string $contentOverview,
        string $link,
        string $ngRequirement,
        string $foreignRequirement,
        string $career
    ): void {
        $statement = $this->pdo->prepare(
            'INSERT INTO courses_site (department, overview, requirement, contentoverview, link, ngrequirement, foreignrequirement, career)
             VALUES (:department, :overview, :requirement, :contentoverview, :link, :ngrequirement, :foreignrequirement, :career)'
        );
        $statement->execute([
            'department' => $department,
            'overview' => $overview,
            'requirement' => $requirement,
            'contentoverview' => $contentOverview,
            'link' => $link,
            'ngrequirement' => $ngRequirement,
            'foreignrequirement' => $foreignRequirement,
            'career' => $career
        ]);
    }

    public function updateProgramme(
        string $originalDepartment,
        string $overview,
        string $requirement,
        string $contentOverview,
        string $link,
        string $ngRequirement,
        string $foreignRequirement,
        string $career
    ): void {
        $statement = $this->pdo->prepare(
            'UPDATE courses_site
             SET overview = :overview,
                 requirement = :requirement,
                 contentoverview = :contentoverview,
                 link = :link,
                 ngrequirement = :ngrequirement,
                 foreignrequirement = :foreignrequirement,
                 career = :career
             WHERE department = :department'
        );
        $statement->execute([
            'overview' => $overview,
            'requirement' => $requirement,
            'contentoverview' => $contentOverview,
            'link' => $link,
            'ngrequirement' => $ngRequirement,
            'foreignrequirement' => $foreignRequirement,
            'career' => $career,
            'department' => $originalDepartment
        ]);
    }

    public function toggleProgrammeStatus(string $department): int
    {
        $course = $this->fetchPreparedSingleRow(
            'SELECT status FROM courses_site WHERE department = :department LIMIT 1',
            ['department' => $department]
        );

        if ($course === []) {
            return 0;
        }

        $nextStatus = ((int) ($course['status'] ?? 0)) === 1 ? 0 : 1;

        $statement = $this->pdo->prepare(
            'UPDATE courses_site SET status = :status WHERE department = :department'
        );
        $statement->execute([
            'status' => $nextStatus,
            'department' => $department
        ]);

        return $nextStatus;
    }

    public function getAcademicsPayload(): array
    {
        return [
            'academic' => $this->fetchSingleRow('SELECT * FROM academic_site LIMIT 1'),
            'departments' => $this->fetchColumn(
                "SELECT DISTINCT department FROM courses_site WHERE status = '1' ORDER BY department"
            )
        ];
    }

    public function getAdmissionsPayload(): array
    {
        return [
            'academic' => $this->fetchSingleRow('SELECT * FROM academic_site LIMIT 1'),
            'admissions' => $this->fetchSingleRow('SELECT * FROM admission_site LIMIT 1'),
            'guide' => $this->fetchSingleRow('SELECT * FROM guide_site LIMIT 1'),
            'after' => $this->fetchSingleRow('SELECT * FROM after_site LIMIT 1')
        ];
    }

    public function getWhyPayload(): array
    {
        return [
            'academic' => $this->fetchSingleRow('SELECT * FROM academic_site LIMIT 1'),
            'why' => $this->fetchSingleRow('SELECT * FROM why_site LIMIT 1'),
            'facilities' => $this->fetchAllRows('SELECT * FROM facilities_site ORDER BY RAND() LIMIT 4'),
            'experts' => $this->fetchAllRows('SELECT * FROM supervisor_site ORDER BY RAND() LIMIT 4'),
            'students' => $this->fetchAllRows('SELECT * FROM student_site ORDER BY RAND() LIMIT 4')
        ];
    }

    public function getResearchAreasPayload(): array
    {
        return [
            'academic' => $this->fetchSingleRow('SELECT * FROM academic_site LIMIT 1'),
            'faculties' => $this->fetchAllRows("SELECT id, faculty FROM fac_new ORDER BY faculty")
        ];
    }

    public function getResearchAreaOptions(int $facultyId): array
    {
        $departments = $this->fetchPreparedAllRows(
            "SELECT DISTINCT d.id, d.department
             FROM fieldofinterest5 f
             INNER JOIN dept_new d ON d.id = f.dept
             WHERE f.fac = :facultyId
             ORDER BY d.department",
            ['facultyId' => $facultyId]
        );

        $degrees = $this->fetchPreparedAllRows(
            "SELECT DISTINCT dg.id, dg.degree
             FROM fieldofinterest5 f
             INNER JOIN degree_new dg ON dg.id = f.degree
             WHERE f.fac = :facultyId
             ORDER BY dg.degree",
            ['facultyId' => $facultyId]
        );

        return [
            'departments' => $departments,
            'degrees' => $degrees
        ];
    }

    public function getResearchAreaResults(int $facultyId, int $departmentId, int $degreeId): array
    {
        $faculty = $this->fetchPreparedSingleRow(
            "SELECT id, faculty FROM fac_new WHERE id = :facultyId LIMIT 1",
            ['facultyId' => $facultyId]
        );
        $department = $this->fetchPreparedSingleRow(
            "SELECT id, department FROM dept_new WHERE id = :departmentId LIMIT 1",
            ['departmentId' => $departmentId]
        );
        $degree = $this->fetchPreparedSingleRow(
            "SELECT id, degree FROM degree_new WHERE id = :degreeId LIMIT 1",
            ['degreeId' => $degreeId]
        );

        $degreeName = (string) ($degree['degree'] ?? '');
        $isCourseList = in_array($degreeName, ['M.Sc', 'M.A', 'M.Ed'], true);
        $isFieldInterestList = in_array($degreeName, ['M.Phil/Ph.D', 'Ph.D', 'M.Phil'], true);

        $items = [];
        $message = 'Here is the list of the department/programme under this faculty.';
        $pageTitle = 'Research Areas / Departments';

        if ($isCourseList) {
            $pageTitle = 'Programmes';
            $message = 'Here is the list of the programme under this department.';
            $narations = $this->fetchPreparedAllRows(
                "SELECT DISTINCT naration FROM rendition WHERE dept = :departmentId ORDER BY naration",
                ['departmentId' => $departmentId]
            );

            foreach ($narations as $row) {
                $naration = (string) ($row['naration'] ?? '');

                if ($naration === '') {
                    continue;
                }

                $items[] = [
                    'label' => $naration,
                    'href' => '/masterprogram?faculty=' . urlencode((string) ($faculty['faculty'] ?? '')) .
                        '&department=' . urlencode((string) ($department['department'] ?? '')) .
                        '&degree=' . urlencode($degreeName) .
                        '&naration=' . urlencode($naration)
                ];
            }
        } elseif ($isFieldInterestList) {
            $message = 'Here is the list of field of interests/department under this programme.';
            $fields = $this->fetchPreparedAllRows(
                "SELECT DISTINCT field_new.field_title
                 FROM fieldofinterest5
                 INNER JOIN field_new ON field_new.id = fieldofinterest5.field
                 WHERE fieldofinterest5.fac = :facultyId AND fieldofinterest5.dept = :departmentId
                 ORDER BY field_new.field_title",
                ['facultyId' => $facultyId, 'departmentId' => $departmentId]
            );

            foreach ($fields as $row) {
                $interest = (string) ($row['field_title'] ?? '');

                if ($interest === '') {
                    continue;
                }

                $label = $interest === 'Not Applicable'
                    ? (string) ($department['department'] ?? 'Department')
                    : $interest;

                $items[] = [
                    'label' => $label,
                    'href' => '/postgraduateprogram?faculty=' . urlencode((string) ($faculty['faculty'] ?? '')) .
                        '&department=' . urlencode((string) ($department['department'] ?? '')) .
                        '&degree=' . urlencode($degreeName) .
                        '&interest=' . urlencode($interest)
                ];
            }
        } else {
            $narations = $this->fetchPreparedAllRows(
                "SELECT DISTINCT naration FROM rendition WHERE dept = :departmentId ORDER BY naration",
                ['departmentId' => $departmentId]
            );

            foreach ($narations as $row) {
                $naration = (string) ($row['naration'] ?? '');

                if ($naration === '') {
                    continue;
                }

                $items[] = [
                    'label' => $naration,
                    'href' => '/masterprogram?faculty=' . urlencode((string) ($faculty['faculty'] ?? '')) .
                        '&department=' . urlencode((string) ($department['department'] ?? '')) .
                        '&degree=' . urlencode($degreeName) .
                        '&naration=' . urlencode($naration)
                ];
            }
        }

        return [
            'academic' => $this->fetchSingleRow('SELECT * FROM academic_site LIMIT 1'),
            'faculty' => $faculty,
            'department' => $department,
            'degree' => $degree,
            'pageTitle' => $pageTitle,
            'message' => $message,
            'items' => $items
        ];
    }

    public function getProgrammeDetailPayload(string $department): array
    {
        return [
            'academic' => $this->fetchSingleRow('SELECT * FROM academic_site LIMIT 1'),
            'guide' => $this->fetchSingleRow('SELECT * FROM guide_site LIMIT 1'),
            'course' => $this->fetchPreparedSingleRow(
                'SELECT * FROM courses_site WHERE department = :department LIMIT 1',
                ['department' => $department]
            ),
            'facilities' => $this->fetchPreparedAllRows(
                'SELECT * FROM facilities_site WHERE course = :department ORDER BY RAND() LIMIT 2',
                ['department' => $department]
            ),
            'supervisors' => $this->fetchPreparedAllRows(
                'SELECT * FROM supervisor_site WHERE course = :department ORDER BY RAND() LIMIT 2',
                ['department' => $department]
            )
        ];
    }

    public function getProgrammesPayload(int $page, string $search): array
    {
        $itemsPerPage = 10;
        $page = max(1, $page);
        $search = trim($search);
        $offset = ($page - 1) * $itemsPerPage;
        $where = '';
        $params = [];

        if ($search !== '') {
            $where = ' WHERE department LIKE :search';
            $params['search'] = '%' . $search . '%';
        }

        $countSql = 'SELECT COUNT(*) AS total FROM dept_new' . $where;
        $totalRow = $this->fetchPreparedSingleRow($countSql, $params);
        $totalItems = (int) ($totalRow['total'] ?? 0);
        $totalPages = max(1, (int) ceil($totalItems / $itemsPerPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $itemsPerPage;

        $sql = 'SELECT * FROM dept_new' . $where . ' ORDER BY department LIMIT ' . $itemsPerPage . ' OFFSET ' . $offset;
        $rows = $where === '' ? $this->fetchAllRows($sql) : $this->fetchPreparedAllRows($sql, $params);

        $items = array_map(function (array $row): array {
            $departmentId = (int) ($row['id'] ?? 0);

            return [
                'id' => $departmentId,
                'department' => $row['department'] ?? '',
                'degrees' => $this->fetchDegreeNamesByDepartmentId($departmentId)
            ];
        }, $rows);

        return [
            'academic' => $this->fetchSingleRow('SELECT * FROM academic_site LIMIT 1'),
            'items' => $items,
            'pagination' => [
                'page' => $page,
                'itemsPerPage' => $itemsPerPage,
                'totalItems' => $totalItems,
                'totalPages' => $totalPages
            ],
            'search' => $search
        ];
    }

    public function getProgramOverviewPayload(string $department): array
    {
        $departmentRow = $this->fetchPreparedSingleRow(
            'SELECT id, department FROM dept_new WHERE department = :department LIMIT 1',
            ['department' => $department]
        );
        $departmentId = (int) ($departmentRow['id'] ?? 0);

        return [
            'academic' => $this->fetchSingleRow('SELECT * FROM academic_site LIMIT 1'),
            'guide' => $this->fetchSingleRow('SELECT * FROM guide_site LIMIT 1'),
            'course' => $this->fetchPreparedSingleRow(
                'SELECT * FROM courses_site WHERE department = :department LIMIT 1',
                ['department' => $department]
            ),
            'department' => $departmentRow,
            'faculty' => $departmentId > 0 ? $this->fetchDepartmentFaculty($departmentId) : [],
            'degrees' => $departmentId > 0 ? $this->fetchDegreeNamesByDepartmentId($departmentId) : [],
            'narrations' => $departmentId > 0 ? $this->fetchNarrationsByDepartmentId($departmentId) : [],
            'fields' => $departmentId > 0 ? $this->fetchFieldTitlesByDepartmentId($departmentId) : [],
            'facilities' => $this->fetchPreparedAllRows(
                'SELECT * FROM facilities_site WHERE course = :department ORDER BY RAND() LIMIT 2',
                ['department' => $department]
            ),
            'supervisors' => $this->fetchPreparedAllRows(
                'SELECT * FROM supervisor_site WHERE course = :department ORDER BY RAND() LIMIT 2',
                ['department' => $department]
            )
        ];
    }

    public function getFeesPayload(): array
    {
        return [
            'academic' => $this->fetchSingleRow('SELECT * FROM academic_site LIMIT 1'),
            'fees' => $this->fetchSingleRow('SELECT * FROM fees_site LIMIT 1'),
            'international' => $this->fetchSingleRow('SELECT * FROM international_site LIMIT 1')
        ];
    }

    public function getInternationalStudentsPayload(): array
    {
        return [
            'academic' => $this->fetchSingleRow('SELECT * FROM academic_site LIMIT 1'),
            'international' => $this->fetchSingleRow('SELECT * FROM international_site LIMIT 1')
        ];
    }

    public function getSetupFeePayload(): array
    {
        return [
            'academic' => $this->fetchSingleRow('SELECT * FROM academic_site LIMIT 1'),
            'faculties' => $this->fetchAllRows("SELECT id, faculty FROM fac_new ORDER BY faculty")
        ];
    }

    public function getSetupFeeOptions(int $facultyId): array
    {
        $departments = $this->fetchPreparedAllRows(
            "SELECT DISTINCT dept_new.id, dept_new.department
             FROM fieldofinterest5
             INNER JOIN dept_new ON dept_new.id = fieldofinterest5.dept
             WHERE fieldofinterest5.fac = :facultyId
               AND dept_new.status = '0'
             ORDER BY dept_new.department",
            ['facultyId' => $facultyId]
        );

        $firstDepartmentId = (int) ($departments[0]['id'] ?? 0);

        $degrees = $firstDepartmentId > 0
            ? $this->fetchPreparedAllRows(
                "SELECT DISTINCT degree_new.id, degree_new.degree
                 FROM fieldofinterest5
                 INNER JOIN degree_new ON degree_new.id = fieldofinterest5.degree
                 WHERE fieldofinterest5.dept = :departmentId
                 ORDER BY degree_new.degree",
                ['departmentId' => $firstDepartmentId]
            )
            : [];

        return [
            'departments' => $departments,
            'degrees' => $degrees
        ];
    }

    public function getSetupFeeDegrees(int $departmentId): array
    {
        return $this->fetchPreparedAllRows(
            "SELECT DISTINCT degree_new.id, degree_new.degree
             FROM fieldofinterest5
             INNER JOIN degree_new ON degree_new.id = fieldofinterest5.degree
             WHERE fieldofinterest5.dept = :departmentId
             ORDER BY degree_new.degree",
            ['departmentId' => $departmentId]
        );
    }

    public function getSetupFeeCalculation(
        int $facultyId,
        int $departmentId,
        int $degreeId,
        string $mode,
        string $type,
        string $paymentType,
        string $installmentType = ''
    ): array {
        $faculty = $this->fetchPreparedSingleRow(
            'SELECT id, faculty FROM fac_new WHERE id = :facultyId LIMIT 1',
            ['facultyId' => $facultyId]
        );
        $department = $this->fetchPreparedSingleRow(
            'SELECT id, department FROM dept_new WHERE id = :departmentId LIMIT 1',
            ['departmentId' => $departmentId]
        );
        $degree = $this->fetchPreparedSingleRow(
            'SELECT id, degree FROM degree_new WHERE id = :degreeId LIMIT 1',
            ['degreeId' => $degreeId]
        );
        $field = $this->fetchPreparedSingleRow(
            'SELECT pay_id FROM fieldofinterest5 WHERE degree = :degreeId LIMIT 1',
            ['degreeId' => $degreeId]
        );

        $payId = (int) ($field['pay_id'] ?? 0);
        $modeNumber = $mode === 'Full Time' ? 1 : 2;
        $tuition = $this->fetchPreparedSingleRow(
            'SELECT * FROM tuition_fee2 WHERE dept_id = :departmentId AND degree_id = :degreeId AND mode = :mode LIMIT 1',
            [
                'departmentId' => $departmentId,
                'degreeId' => $degreeId,
                'mode' => $mode
            ]
        );
        $feeSet = $this->fetchPreparedSingleRow(
            'SELECT fee_items FROM fee_set WHERE fac = :facultyId AND dept = :departmentId AND degree = :degreeId AND mode = :mode LIMIT 1',
            [
                'facultyId' => $facultyId,
                'departmentId' => $departmentId,
                'degreeId' => $degreeId,
                'mode' => $modeNumber
            ]
        );

        $category = in_array($payId, [1, 2, 6, 7, 11, 16], true)
            ? 'master'
            : (in_array($payId, [3, 4, 5, 9], true) ? 'phd' : '');
        $normalizedType = strtolower($type) === 'renewal' ? 'renewal' : 'new';
        $normalizedPaymentType = strtolower($paymentType) === 'install' ? 'install' : 'full';
        $normalizedInstallment = strtolower($installmentType) === 'second' ? 'second' : 'first';
        $feeColumn = $category === ''
            ? ''
            : sprintf(
                '%s_%s_%s',
                $category,
                $normalizedType,
                $normalizedPaymentType === 'install' ? $normalizedInstallment : 'full'
            );

        $registrationFee = 0.0;

        if ($normalizedPaymentType === 'full' || $normalizedInstallment === 'first') {
            $registrationFee = $normalizedType === 'new'
                ? (float) ($tuition['newstud_fee'] ?? 0)
                : (float) ($tuition['retstud_fee'] ?? 0);
        }

        $items = [];

        if ($registrationFee > 0) {
            $items[] = [
                'label' => 'Registration-Tuition Fee',
                'amount' => $registrationFee
            ];
        }

        $feeIds = array_values(
            array_filter(
                array_map('trim', explode(',', (string) ($feeSet['fee_items'] ?? ''))),
                static fn(string $value): bool => $value !== ''
            )
        );

        $subtotal = 0.0;

        foreach ($feeIds as $feeId) {
            if ($feeColumn === '') {
                continue;
            }

            $row = $this->fetchPreparedSingleRow(
                "SELECT description, {$feeColumn} AS amount FROM new_fee WHERE fee_id = :feeId LIMIT 1",
                ['feeId' => $feeId]
            );

            $amount = (float) ($row['amount'] ?? 0);

            if ($amount <= 0) {
                continue;
            }

            $items[] = [
                'label' => (string) ($row['description'] ?? 'Fee item'),
                'amount' => $amount
            ];
            $subtotal += $amount;
        }

        return [
            'faculty' => $faculty,
            'department' => $department,
            'degree' => $degree,
            'mode' => $mode,
            'type' => $type,
            'paymentType' => strtoupper($paymentType),
            'installmentType' => $normalizedPaymentType === 'install' ? strtoupper($normalizedInstallment) : '',
            'payId' => $payId,
            'items' => $items,
            'total' => $registrationFee + $subtotal
        ];
    }

    public function getInternationalSetupFeePayload(): array
    {
        return [
            'faculties' => $this->fetchAllRows("SELECT id, faculty FROM fac_new ORDER BY faculty")
        ];
    }

    public function getInternationalSetupFeeCalculation(
        int $facultyId,
        int $departmentId,
        int $degreeId,
        string $type
    ): array {
        $faculty = $this->fetchPreparedSingleRow(
            'SELECT id, faculty FROM fac_new WHERE id = :facultyId LIMIT 1',
            ['facultyId' => $facultyId]
        );
        $department = $this->fetchPreparedSingleRow(
            'SELECT id, department FROM dept_new WHERE id = :departmentId LIMIT 1',
            ['departmentId' => $departmentId]
        );
        $degree = $this->fetchPreparedSingleRow(
            'SELECT id, degree FROM degree_new WHERE id = :degreeId LIMIT 1',
            ['degreeId' => $degreeId]
        );
        $tuition = $this->fetchSingleRow('SELECT * FROM tuition_foreign LIMIT 1');
        $modeNumber = 1;
        $degreeType = in_array($degreeId, [5, 3, 4], true) ? 'phd' : 'master';
        $normalizedType = strtolower($type) === 'renewal' ? 'Renewal' : 'New';
        $baseFee = $normalizedType === 'New'
            ? (float) ($tuition['new_' . $degreeType] ?? 0)
            : (float) ($tuition['renewal_' . $degreeType] ?? 0);

        $feeColumnMap = [
            'master' => [
                'New' => 'foreign_master_new',
                'Renewal' => 'foreign_master_renewal'
            ],
            'phd' => [
                'New' => 'foreign_phd_new',
                'Renewal' => 'foreign_phd_renewal'
            ]
        ];

        $feeColumn = $feeColumnMap[$degreeType][$normalizedType] ?? '';
        $feeSet = $this->fetchPreparedSingleRow(
            'SELECT fee_items FROM fee_set WHERE fac = :facultyId AND dept = :departmentId AND degree = :degreeId AND mode = :mode LIMIT 1',
            [
                'facultyId' => $facultyId,
                'departmentId' => $departmentId,
                'degreeId' => $degreeId,
                'mode' => $modeNumber
            ]
        );

        $items = [
            [
                'label' => 'Registration-Tuition Fee',
                'amount' => $baseFee
            ]
        ];
        $subtotal = 0.0;
        $feeIds = array_values(
            array_filter(
                array_map('trim', explode(',', (string) ($feeSet['fee_items'] ?? ''))),
                static fn(string $value): bool => $value !== ''
            )
        );

        foreach ($feeIds as $feeId) {
            if ($feeColumn === '') {
                continue;
            }

            $row = $this->fetchPreparedSingleRow(
                "SELECT description, {$feeColumn} AS amount FROM new_fee WHERE fee_id = :feeId LIMIT 1",
                ['feeId' => $feeId]
            );

            $amount = (float) ($row['amount'] ?? 0);

            if ($amount <= 0) {
                continue;
            }

            $items[] = [
                'label' => (string) ($row['description'] ?? 'Fee item'),
                'amount' => $amount
            ];
            $subtotal += $amount;
        }

        return [
            'faculty' => $faculty,
            'department' => $department,
            'degree' => $degree,
            'mode' => 'Full Time',
            'type' => $normalizedType,
            'paymentType' => 'FULL',
            'items' => $items,
            'total' => $baseFee + $subtotal
        ];
    }

    public function getStatistics(string $session): array
    {
        $cacheDir = storage_path('app/data');
        $cacheKey = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $session) ?: 'default';
        $cacheFile = $cacheDir . '/stats_' . $cacheKey . '.json';
        $cacheLifetime = 3600;

        if (is_file($cacheFile) && (time() - filemtime($cacheFile) < $cacheLifetime)) {
            $cached = file_get_contents($cacheFile);

            if ($cached !== false) {
                $decoded = json_decode($cached, true);

                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        $stats = [
            'registered' => $this->countRegisteredStudents($session),
            'applications' => $this->countApplications($session),
            'admitted' => $this->countAdmittedStudents($session),
            'graduating' => 0
        ];

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        file_put_contents($cacheFile, json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $stats;
    }

    private function fetchSingleRow(string $sql): array
    {
        $statement = $this->pdo->query($sql);
        $row = $statement->fetch();

        return is_array($row) ? $row : [];
    }

    private function fetchAllRows(string $sql): array
    {
        $statement = $this->pdo->query($sql);
        $rows = $statement->fetchAll();

        return is_array($rows) ? $rows : [];
    }

    private function fetchPreparedAllRows(string $sql, array $params): array
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        $rows = $statement->fetchAll();

        return is_array($rows) ? $rows : [];
    }

    private function fetchPreparedSingleRow(string $sql, array $params): array
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        $row = $statement->fetch();

        return is_array($row) ? $row : [];
    }

    private function fetchDegreeNamesByDepartmentId(int $departmentId): array
    {
        if ($departmentId <= 0) {
            return [];
        }

        $rows = $this->fetchPreparedAllRows(
            'SELECT DISTINCT degree_new.degree
             FROM fieldofinterest5
             INNER JOIN degree_new ON degree_new.id = fieldofinterest5.degree
             WHERE fieldofinterest5.dept = :departmentId
             ORDER BY degree_new.degree',
            ['departmentId' => $departmentId]
        );

        return array_values(array_filter(array_map(static fn(array $row): string => (string) ($row['degree'] ?? ''), $rows)));
    }

    private function fetchNarrationsByDepartmentId(int $departmentId): array
    {
        $rows = $this->fetchPreparedAllRows(
            'SELECT DISTINCT naration FROM rendition WHERE dept = :departmentId ORDER BY naration',
            ['departmentId' => $departmentId]
        );

        return array_values(array_filter(array_map(static fn(array $row): string => (string) ($row['naration'] ?? ''), $rows)));
    }

    private function fetchFieldTitlesByDepartmentId(int $departmentId): array
    {
        $rows = $this->fetchPreparedAllRows(
            'SELECT DISTINCT field_new.field_title
             FROM fieldofinterest5
             INNER JOIN field_new ON field_new.id = fieldofinterest5.field
             WHERE fieldofinterest5.dept = :departmentId
             ORDER BY field_new.field_title',
            ['departmentId' => $departmentId]
        );

        return array_values(array_filter(array_map(static fn(array $row): string => (string) ($row['field_title'] ?? ''), $rows)));
    }

    private function fetchDepartmentFaculty(int $departmentId): array
    {
        return $this->fetchPreparedSingleRow(
            'SELECT DISTINCT fac_new.faculty, dept_new.department
             FROM fieldofinterest5
             INNER JOIN fac_new ON fac_new.id = fieldofinterest5.fac
             INNER JOIN dept_new ON dept_new.id = fieldofinterest5.dept
             WHERE dept_new.id = :departmentId
             LIMIT 1',
            ['departmentId' => $departmentId]
        );
    }

    private function fetchColumn(string $sql): array
    {
        $statement = $this->pdo->query($sql);

        return $statement->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    private function countRegisteredStudents(string $session): int
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(DISTINCT r.appno) AS total
              FROM reginvoice r
              WHERE r.sessioned = :session'
        );
        $statement->execute(['session' => $session]);

        return (int) ($statement->fetchColumn() ?: 0);
    }

    private function countApplications(string $session): int
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(DISTINCT a.appno) AS total
              FROM appinvoice a
              WHERE a.sessioned = :session'
        );
        $statement->execute(['session' => $session]);

        return (int) ($statement->fetchColumn() ?: 0);
    }

    private function countAdmittedStudents(string $session): int
    {
        $statement = $this->pdo->prepare(
            "SELECT COUNT(DISTINCT ads.user_id) AS total
             FROM admission_status ads
             INNER JOIN new n ON n.id = ads.user_id
             WHERE ads.clearance = 'Yes'
               AND ads.clearance_date IS NOT NULL
               AND ads.clearance_date != ''
               AND ads.clearance_staff IS NOT NULL
               AND ads.clearance_staff != ''
               AND EXISTS (
                   SELECT 1
                   FROM appinvoice a
                   WHERE a.appno = n.numeration
                     AND a.sessioned = :session
               )"
        );
        $statement->execute(['session' => $session]);

        return (int) ($statement->fetchColumn() ?: 0);
    }

    private function getQuickLinks(): array
    {
        // Load external links from JSON
        $externalLinksPath = storage_path('app/data/external-links.json');
        $externalLinks = [];
        if (is_file($externalLinksPath)) {
            $json = file_get_contents($externalLinksPath);
            $externalLinks = json_decode($json, true) ?: [];
        }

        $links = [
            [
                'label' => 'Admissions/Application Portal',
                'href' => $externalLinks['admissions'] ?? 'https://pgcollege.ui.edu.ng/application/',
                'type' => 'external',
                'image' => 'istockphoto-1288216799-170667a.jpg'
            ],
            [
                'label' => 'Transcripts',
                'href' => $externalLinks['transcripts'] ?? 'http://app.pgcollegeui.com:8080/transcript/',
                'type' => 'external',
                'image' => 'application-2076445_640.png'
            ],
            [
                'label' => 'Registration/Student Portal',
                'href' => $externalLinks['studentPortal'] ?? 'https://registration.pgcollege.ui.edu.ng/',
                'type' => 'external',
                'image' => 'registration-4519979_640.jpg'
            ],
            [
                'label' => 'Personnel login',
                'type' => 'menu',
                'image' => 'personel.jpg',
                'items' => [
                    [
                        'label' => 'CGPA Portal',
                        'href' => $externalLinks['cgpaPortal'] ?? 'http://pguicgpa.ui.edu.ng/pguicgpa/'
                    ],
                    [
                        'label' => 'Departmental Portal',
                        'href' => $externalLinks['departmentalPortal'] ?? 'https://app.pgcollege.ui.edu.ng/pgdev/public/'
                    ]
                ]
            ]
        ];

        return array_values(array_filter($links, static function (array $link): bool {
            if (($link['type'] ?? '') === 'menu') {
                return !empty($link['items']);
            }

            return ($link['href'] ?? '#') !== '#';
        }));
    }

    private function normalizeLegacyPath(string $value): string
    {
        if (preg_match('/^https?:\/\//i', $value) === 1) {
            return $value;
        }

        if (str_starts_with($value, '../')) {
            return '/' . ltrim(substr($value, 3), '/');
        }

        return $value;
    }
}

