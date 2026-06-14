<?php
//  functions.php  –  Shared utility functions
//  Campus Management System
//Parking helpers 

/**
 * Returns the price constant for a given permit type.
 */
function getPermitPrice(string $type): float {
    $prices = [
        'Student' => PERMIT_STUDENT,
        'Staff'   => PERMIT_STAFF,
        'Visitor' => PERMIT_VISITOR,
    ];
    return $prices[$type] ?? 0.0;
}

/**
 * Validates and issues a parking permit.
 * Returns ['success'=>bool, 'message'=>string, 'permit'=>array|null]
 */
function issuePermit(array &$permits, string $name, string $type, int $age): array {
    if ($age < 18) {
        return ['success' => false, 'message' => "Permit denied for $name: applicant must be 18 or older.", 'permit' => null];
    }
    $validTypes = ['Student', 'Staff', 'Visitor'];
    if (!in_array($type, $validTypes)) {
        return ['success' => false, 'message' => "Invalid permit type '$type'.", 'permit' => null];
    }
    $totalSold = array_sum(array_column($permits, 'count'));
    if ($totalSold >= MAX_PARKING_CAPACITY) {
        return ['success' => false, 'message' => "Parking lot is full. No permits available.", 'permit' => null];
    }

    $price = getPermitPrice($type);
    $permit = [
        'name'  => htmlspecialchars($name),
        'type'  => $type,
        'price' => $price,
        'date'  => date('Y-m-d'),
    ];
    $permits[] = $permit;
    return ['success' => true, 'message' => "Permit issued to $name ($type) for R$price.", 'permit' => $permit];
}

/**
 * Generates a summary of permits sold per category and total revenue.
 */
function generateParkingSummary(array $permits): array {
    $summary = ['Student' => ['count' => 0, 'revenue' => 0.0],
                 'Staff'   => ['count' => 0, 'revenue' => 0.0],
                 'Visitor' => ['count' => 0, 'revenue' => 0.0]];
    foreach ($permits as $p) {
        $summary[$p['type']]['count']++;
        $summary[$p['type']]['revenue'] += $p['price'];
    }
    return $summary;
}


// Library helpers

/**
 * Calculate fine for a late return.
 * $daysLate  = number of days past due date (0 = on time)
 */
function calculateFine(string $category, int $daysLate): float {
    if ($daysLate <= 0) return 0.0;
    $rates = [
        'Textbook'      => FINE_TEXTBOOK,
        'Journal'       => FINE_JOURNAL,
        'Reference Book'=> FINE_REFERENCE,
    ];
    $rate = $rates[$category] ?? 0.0;
    return round($rate * $daysLate, 2);
}

/**
 * Borrow a book for a user.
 * Prevents borrowing if outstanding fines > MAX_FINE_THRESHOLD.
 */
function borrowBook(array &$library, string $userId, string $bookTitle, string $category, string $dueDate): array {
    // Check if user exists
    if (!isset($library[$userId])) {
        return ['success' => false, 'message' => "User '$userId' not found."];
    }
    // Check outstanding fines
    $outstandingFine = $library[$userId]['outstanding_fine'];
    if ($outstandingFine > MAX_FINE_THRESHOLD) {
        return ['success' => false,
                'message' => "Cannot borrow: {$library[$userId]['name']} has an outstanding fine of R$outstandingFine (limit R" . MAX_FINE_THRESHOLD . ")."];
    }
    // Check valid category
    $validCats = ['Textbook', 'Journal', 'Reference Book'];
    if (!in_array($category, $validCats)) {
        return ['success' => false, 'message' => "Invalid book category '$category'."];
    }
    // Issue borrow
    $borrowId = uniqid('BRW_');
    $library[$userId]['borrowed_books'][$borrowId] = [
        'title'      => htmlspecialchars($bookTitle),
        'category'   => $category,
        'borrow_date'=> date('Y-m-d'),
        'due_date'   => $dueDate,
        'returned'   => false,
        'fine'       => 0.0,
    ];
    return ['success' => true,
            'message' => "{$library[$userId]['name']} borrowed '$bookTitle' (due $dueDate).",
            'borrow_id' => $borrowId];
}

/**
 * Return a book and calculate any fine.
 */
function returnBook(array &$library, string $userId, string $borrowId, string $returnDate): array {
    if (!isset($library[$userId]['borrowed_books'][$borrowId])) {
        return ['success' => false, 'message' => "Borrow record not found."];
    }
    $book = &$library[$userId]['borrowed_books'][$borrowId];
    if ($book['returned']) {
        return ['success' => false, 'message' => "Book '{$book['title']}' was already returned."];
    }

    $due    = new DateTime($book['due_date']);
    $ret    = new DateTime($returnDate);
    $diff   = $ret->diff($due);
    $daysLate = ($ret > $due) ? (int)$diff->days : 0;

    $fine = calculateFine($book['category'], $daysLate);
    $book['returned']    = true;
    $book['return_date'] = $returnDate;
    $book['fine']        = $fine;
    $book['days_late']   = $daysLate;

    $library[$userId]['outstanding_fine'] = round($library[$userId]['outstanding_fine'] + $fine, 2);

    $msg = "'{$book['title']}' returned by {$library[$userId]['name']}.";
    $msg .= $fine > 0 ? " Late by $daysLate day(s). Fine: R$fine." : " Returned on time. No fine.";
    return ['success' => true, 'message' => $msg, 'fine' => $fine, 'days_late' => $daysLate];
}

/**
 * Print a formatted user borrowing summary.
 */
function printUserSummary(array $library, string $userId): string {
    if (!isset($library[$userId])) return "<p>User not found.</p>";
    $user  = $library[$userId];
    $books = $user['borrowed_books'];
    $html  = "<div class='user-summary'>";
    $html .= "<h4>📋 " . htmlspecialchars($user['name']) . " (" . htmlspecialchars($userId) . ")</h4>";
    $html .= "<p>Outstanding Fine: <strong>R{$user['outstanding_fine']}</strong></p>";

    if (empty($books)) {
        $html .= "<p><em>No borrowing history.</em></p>";
    } else {
        $html .= "<table class='summary-table'><thead><tr>
                    <th>Title</th><th>Category</th><th>Due Date</th>
                    <th>Status</th><th>Fine</th></tr></thead><tbody>";
        foreach ($books as $bid => $b) {
            $status = $b['returned'] ? '✅ Returned' : '📖 Borrowed';
            $fine   = $b['fine'] > 0 ? "R{$b['fine']}" : '—';
            $html .= "<tr>
                        <td>" . htmlspecialchars($b['title']) . "</td>
                        <td>{$b['category']}</td>
                        <td>{$b['due_date']}</td>
                        <td>$status</td>
                        <td>$fine</td>
                      </tr>";
        }
        $html .= "</tbody></table>";
    }
    $html .= "</div>";
    return $html;
}


//Performance helpers 

/**
 * Validate a single mark (0–100).
 * Returns ['valid'=>bool, 'value'=>float, 'error'=>string]
 */
function validateMark(mixed $mark): array {
    if (!is_numeric($mark)) {
        return ['valid' => false, 'value' => 0, 'error' => "Mark '$mark' is not numeric."];
    }
    $m = (float)$mark;
    if ($m < 0 || $m > 100) {
        return ['valid' => false, 'value' => 0, 'error' => "Mark $m is out of range (0–100)."];
    }
    return ['valid' => true, 'value' => $m, 'error' => ''];
}

/**
 * Calculate the average of an array of marks.
 */
function calculateAverage(array $marks): float {
    if (empty($marks)) return 0.0;
    return round(array_sum($marks) / count($marks), 2);
}

/**
 * Assign a grade based on average.
 */
function assignGrade(float $average): string {
    if ($average >= 75) return 'Distinction';
    if ($average >= 50) return 'Pass';
    return 'Fail';
}

/**
 * Process all students: validate marks, compute averages, assign grades.
 * Returns ['students'=>array, 'errors'=>array, 'stats'=>array]
 */
function processStudents(array $rawStudents): array {
    $processed = [];
    $errors    = [];

    foreach ($rawStudents as $student) {
        $validMarks = [];
        foreach ($student['marks'] as $i => $m) {
            $v = validateMark($m);
            if ($v['valid']) {
                $validMarks[] = $v['value'];
            } else {
                $errors[] = "Student '{$student['name']}', mark #" . ($i + 1) . ": " . $v['error'];
            }
        }
        if (count($validMarks) < 1) {
            $errors[] = "Student '{$student['name']}' has no valid marks – skipped.";
            continue;
        }
        $avg   = calculateAverage($validMarks);
        $grade = assignGrade($avg);
        $processed[] = [
            'name'    => htmlspecialchars($student['name']),
            'marks'   => $validMarks,
            'average' => $avg,
            'grade'   => $grade,
        ];
    }

    // Class statistics
    $stats = [];
    if (!empty($processed)) {
        $avgs = array_column($processed, 'average');
        $maxAvg = max($avgs);
        $minAvg = min($avgs);
        $classAvg = round(array_sum($avgs) / count($avgs), 2);

        // Top performer (first in case of tie)
        $topIndex = array_search($maxAvg, $avgs);
        $stats = [
            'highest_avg'  => $maxAvg,
            'lowest_avg'   => $minAvg,
            'class_avg'    => $classAvg,
            'top_student'  => $processed[$topIndex]['name'],
            'total_students' => count($processed),
        ];
    }

    return ['students' => $processed, 'errors' => $errors, 'stats' => $stats];
}
