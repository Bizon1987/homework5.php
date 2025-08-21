<?php
declare(strict_types=1);


/**
 * Получение количества дней в месяце
 */
function getDaysInMonth(int $year, int $month): int {
    return (int)(new DateTime("$year-$month-01"))->format('t');
}

/**
 * Генерация расписания работы сотрудника
 */
function generateWorkSchedule(int $year, int $month, bool $isWorkDay = true, int $consecutiveDaysOff = 0): array {
    $daysInMonth = getDaysInMonth($year, $month);
    $schedule = [];

    for ($day = 1; $day <= $daysInMonth; $day++) {
        $date = new DateTime("$year-$month-$day");
        $dayOfWeek = (int)$date->format('N'); // 1 = Пн, 7 = Вс

        if ($isWorkDay) {
            if ($dayOfWeek >= 6) {
                $schedule[] = [
                    'day' => $day,
                    'date' => $date->format('Y-m-d'),
                    'is_work_day' => false,
                    'day_of_week' => $dayOfWeek
                ];
            } else {
                $schedule[] = [
                    'day' => $day,
                    'date' => $date->format('Y-m-d'),
                    'is_work_day' => true,
                    'day_of_week' => $dayOfWeek
                ];
                $isWorkDay = false;
                $consecutiveDaysOff = 0;
            }
        } else {
            $schedule[] = [
                'day' => $day,
                'date' => $date->format('Y-m-d'),
                'is_work_day' => false,
                'day_of_week' => $dayOfWeek
            ];
            $consecutiveDaysOff++;
            if ($consecutiveDaysOff >= 2) {
                $isWorkDay = true;
                $consecutiveDaysOff = 0;
            }
        }
    }

    return $schedule;
}

/**
 * Отображение расписания
 */
function displayScheduleArray(array $schedule, int $year, int $month): void {
    $monthNames = [
        1 => 'Январь', 2 => 'Февраль', 3 => 'Март', 4 => 'Апрель',
        5 => 'Май', 6 => 'Июнь', 7 => 'Июль', 8 => 'Август',
        9 => 'Сентябрь', 10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь'
    ];
    $dayNames = [
        1 => 'Пн', 2 => 'Вт', 3 => 'Ср', 4 => 'Чт',
        5 => 'Пт', 6 => 'Сб', 7 => 'Вс'
    ];

    echo "\n=== Расписание работы на {$monthNames[$month]} $year ===\n\n";

    foreach ($schedule as $dayInfo) {
        $dayOfWeekName = $dayNames[$dayInfo['day_of_week']];
        if ($dayInfo['is_work_day']) {
            echo sprintf("\033[32m%2d %s (+) - РАБОЧИЙ ДЕНЬ\033[0m\n", $dayInfo['day'], $dayOfWeekName);
        } else {
            echo sprintf("%2d %s\n", $dayInfo['day'], $dayOfWeekName);
        }
    }

    $workDays = array_filter($schedule, fn($day) => $day['is_work_day']);
    echo "\nВсего рабочих дней в месяце: " . count($workDays) . "\n";
}

/**
 * Обёртка для одномесячного отображения
 */
function displaySchedule(int $year, int $month): void {
    $schedule = generateWorkSchedule($year, $month, true, 0);
    displayScheduleArray($schedule, $year, $month);
}

/**
 * Генерация расписания на несколько месяцев
 */
function generateMultiMonthSchedule(int $startYear, int $startMonth, int $monthCount): void {
    $currentYear = $startYear;
    $currentMonth = $startMonth;
    $isWorkDay = true;
    $consecutiveDaysOff = 0;

    for ($i = 0; $i < $monthCount; $i++) {
        if ($currentMonth > 12) {
            $currentYear++;
            $currentMonth = 1;
        }

        $schedule = generateWorkSchedule($currentYear, $currentMonth, $isWorkDay, $consecutiveDaysOff);
        displayScheduleArray($schedule, $currentYear, $currentMonth);

        $lastDay = end($schedule);
        if ($lastDay['is_work_day']) {
            $isWorkDay = false;
            $consecutiveDaysOff = 0;
        } else {
            $consecutiveDaysOff = 0;
            for ($j = count($schedule) - 1; $j >= 0; $j--) {
                if (!$schedule[$j]['is_work_day']) $consecutiveDaysOff++;
                else break;
            }
            $isWorkDay = ($consecutiveDaysOff >= 2);
        }

        $currentMonth++;
        echo "\n" . str_repeat("-", 50) . "\n";
    }
}

// Основной блок
if ($argc > 1) {
    $year = (int)($argv[1] ?? date('Y'));
    $month = (int)($argv[2] ?? date('m'));
    $monthCount = (int)($argv[3] ?? 1);

    if ($monthCount > 1) generateMultiMonthSchedule($year, $month, $monthCount);
    else displaySchedule($year, $month);
} else {
    displaySchedule((int)date('Y'), (int)date('m'));
}

echo "\nПример использования:\n";
echo "php schedule_generator.php - текущий месяц\n";
echo "php schedule_generator.php 2024 3 - март 2024\n";
echo "php schedule_generator.php 2024 3 6 - с марта 2024 на 6 месяцев\n";
?>
