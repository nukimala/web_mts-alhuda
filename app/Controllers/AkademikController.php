<?php

require_once __DIR__ . '/../Models/Akademik.php';

/**
 * Controller Halaman Program Akademik & Jadwal Pelajaran Publik.
 */
class AkademikController
{
    private Akademik $akademikModel;

    public function __construct(?Akademik $akademikModel = null)
    {
        $this->akademikModel = $akademikModel ?? new Akademik();
    }

    public function index(): void
    {
        $year = $this->akademikModel->getActiveYear();
        $yearId = (int) ($year['id'] ?? 0);

        $classes = $this->akademikModel->getClassesByYear($yearId);
        $requestedClassId = isset($_GET['kelas']) ? (int) $_GET['kelas'] : 0;
        $selectedClass = $this->akademikModel->resolveSelectedClass($classes, $requestedClassId);
        $selectedClassId = (int) ($selectedClass['id'] ?? 0);

        $allClassBoards = $this->akademikModel->getAllClassBoardsByYear($classes, $yearId);
        $activeBoardData = $allClassBoards[$selectedClassId] ?? null;

        $weeklyBoard = [];
        if ($activeBoardData !== null) {
            foreach ($activeBoardData['weekly_board'] as $dayInfo) {
                $weeklyBoard[(int) $dayInfo['day_num']] = $dayInfo;
            }
        }

        $groupedSubjects = $this->akademikModel->getGroupedSubjects();
        $teachers = $this->akademikModel->getAllTeachers();

        $data = [
            'title' => 'Program Akademik & Jadwal Pelajaran | MTs Al-Huda Gondang',
            'year' => $year,
            'classes' => $classes,
            'selected_class' => $selectedClass,
            'selected_class_id' => $selectedClassId,
            'all_class_boards' => $allClassBoards,
            'weekly_board' => $weeklyBoard,
            'flat_merged_rows' => $activeBoardData['flat_rows'] ?? [],
            'total_weekly_jp' => (int) ($activeBoardData['total_jp'] ?? 0),
            'has_schedules' => (bool) ($activeBoardData['has_schedules'] ?? false),
            'grouped_subjects' => $groupedSubjects,
            'teacher_count' => count($teachers),
            'settings' => $this->akademikModel->getPublicSettings(),
        ];

        require __DIR__ . '/../Views/akademik/index.php';
    }
}
