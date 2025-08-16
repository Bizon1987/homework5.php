<?php
declare(strict_types=1);

/**
 * Функция для генерации расписания работы сотрудника
 * @param int $year Год
 * @param int $month Месяц
 * @return array Массив с информацией о рабочих днях
 */
function generateWorkSchedule(int $year, int $month): array {
    // Получаем количество дней в месяце
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    
    $schedule = [];
    $isWorkDay = true; // Первое число - рабочий день
    $consecutiveDaysOff = 0; // Счетчик выходных дней подряд
    
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $date = new DateTime("$year-$month-$day");
        $dayOfWeek = (int)$date->format('N'); // 1 = понедельник, 7 = воскресенье
        
        if ($isWorkDay) {
            // Если рабочий день выпадает на выходные (суббота или воскресенье)
            if ($dayOfWeek == 6 || $dayOfWeek == 7) {
                // Переносим на понедельник
                while ($dayOfWeek != 1 && $day <= $daysInMonth) {
                    $schedule[] = [
                        'day' => $day,
                        'date' => $date->format('Y-m-d'),
                        'is_work_day' => false,
                        'day_of_week' => $dayOfWeek
                    ];
                    $day++;
                    if ($day <= $daysInMonth) {
                        $date = new DateTime("$year-$month-$day");
                        $dayOfWeek = (int)$date->format('N');
                    }
                }
                
                // Если не вышли за пределы месяца, отмечаем понедельник как рабочий
                if ($day <= $daysInMonth) {
                    $schedule[] = [
                        'day' => $day,
                        'date' => $date->format('Y-m-d'),
                        'is_work_day' => true,
                        'day_of_week' => $dayOfWeek
                    ];
                }
            } else {
                // Обычный рабочий день
                $schedule[] = [
                    'day' => $day,
                    'date' => $date->format('Y-m-d'),
                    'is_work_day' => true,
                    'day_of_week' => $dayOfWeek
                ];
            }
            
            $isWorkDay = false;
            $consecutiveDaysOff = 0;
        } else {
            // Выходной день
            $schedule[] = [
                'day' => $day,
                'date' => $date->format('Y-m-d'),
                'is_work_day' => false,
                'day_of_week' => $dayOfWeek
            ];
            
            $consecutiveDaysOff++;
            
            // После двух выходных дней следующий день - рабочий
            if ($consecutiveDaysOff >= 2) {
                $isWorkDay = true;
            }
        }
    }
    
    return $schedule;
}

/**
 * Функция для отображения расписания
 * @param int $year Год
 * @param int $month Месяц
 */
function displaySchedule(int $year, int $month): void {
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
    
    $schedule = generateWorkSchedule($year, $month);
    
    foreach ($schedule as $dayInfo) {
        $dayOfWeekName = $dayNames[$dayInfo['day_of_week']];
        
        if ($dayInfo['is_work_day']) {
            // Выделяем рабочие дни зеленым цветом и знаком (+)
            echo sprintf("\033[32m%2d %s (+) - РАБОЧИЙ ДЕНЬ\033[0m\n", 
                $dayInfo['day'], $dayOfWeekName);
        } else {
            // Обычные выходные дни
            echo sprintf("%2d %s\n", $dayInfo['day'], $dayOfWeekName);
        }
    }
    
    // Подсчет рабочих дней
    $workDays = array_filter($schedule, function($day) {
        return $day['is_work_day'];
    });
    
    echo "\nВсего рабочих дней в месяце: " . count($workDays) . "\n";
}

/**
 * Функция для генерации расписания на несколько месяцев
 * @param int $startYear Начальный год
 * @param int $startMonth Начальный месяц
 * @param int $monthCount Количество месяцев
 */
function generateMultiMonthSchedule(int $startYear, int $startMonth, int $monthCount): void {
    $currentYear = $startYear;
    $currentMonth = $startMonth;
    $isWorkDay = true; // Первое число первого месяца - рабочий день
    $consecutiveDaysOff = 0;
    
    for ($monthIndex = 0; $monthIndex < $monthCount; $monthIndex++) {
        // Корректируем год, если месяц превышает 12
        if ($currentMonth > 12) {
            $currentYear++;
            $currentMonth = 1;
        }
        
        displaySchedule($currentYear, $currentMonth);
        
        // Определяем состояние на конец месяца для следующего месяца
        $schedule = generateWorkSchedule($currentYear, $currentMonth);
        $lastDay = end($schedule);
        
        // Логика для определения первого дня следующего месяца
        if ($lastDay['is_work_day']) {
            $isWorkDay = false;
            $consecutiveDaysOff = 0;
        } else {
            $consecutiveDaysOff++;
            if ($consecutiveDaysOff >= 2) {
                $isWorkDay = true;
            }
        }
        
        $currentMonth++;
        echo "\n" . str_repeat("-", 50) . "\n";
    }
}

// Основная логика программы
if ($argc > 1) {
    // Если переданы аргументы командной строки
    $year = isset($argv[1]) ? (int)$argv[1] : (int)date('Y');
    $month = isset($argv[2]) ? (int)$argv[2] : (int)date('m');
    $monthCount = isset($argv[3]) ? (int)$argv[3] : 1;
    
    if ($monthCount > 1) {
        generateMultiMonthSchedule($year, $month, $monthCount);
    } else {
        displaySchedule($year, $month);
    }
} else {
    // Используем текущий год и месяц
    $currentYear = (int)date('Y');
    $currentMonth = (int)date('m');
    
    displaySchedule($currentYear, $currentMonth);
}

echo "\nПример использования:\n";
echo "php schedule_generator.php - текущий месяц\n";
echo "php schedule_generator.php 2024 3 - март 2024\n";
echo "php schedule_generator.php 2024 3 6 - с марта 2024 на 6 месяцев\n";
