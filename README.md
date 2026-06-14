Campus Management System
IPR 621 Assignment — Private Higher Education Institution



System Overview

A PHP-based Campus Management System consisting of three integrated modules:

|Module|File|
|--------|------|
|Parking Permit Management| `parking.php`|
|Library Borrowing & Fines| `library.php`| 
|Student Performance Analytics| `performance.php`|

All modules share `functions.php` for common logic and use PHP sessions to persist data within a browsing session.



[Requirements]

- **XAMPP** (PHP 7.4+ or 8.x) running Apache
- Browser: Chrome, Firefox, Edge (any modern browser)



[Installation & Setup]

1. Download/unzip the project folder into your XAMPP `htdocs` directory:
   C:\xampp\htdocs\cms\       (Windows)

2. Start XAMPP — ensure Apache is running (MySQL not required).

3. Open your browser and navigate to:
   http://localhost/cms/index.php

4. The main menu will load with links to all three sections.



[File Structure]
cms/
├── index.php          Main menu / landing page
├── parking.php        Module 1 — Parking Permit Management
├── library.php        Module 2 — Library Borrowing & Fines
├── performance.php    Module 3 — Student Performance Analytics
├── functions.php      Shared functions used by all modules
└── README.md          This file

[Module 1 — Parking Permits (`parking.php`)]

Constants (defined in `parking.php`)
| Constant | Value |
|----------|-------|
| `PERMIT_STUDENT` | R450.00 |
| `PERMIT_STAFF` | R750.00 |
| `PERMIT_VISITOR` | R100.00 |
| `MAX_PARKING_CAPACITY` | 50 |

[Business Rules]
- Applicants under 18 are denied a permit.
- No more than 50 permits can be issued (capacity limit enforced).
- Permits may only be of type: Student, Staff, or Visitor.

[How to Use]
1. Fill in the batch form (up to 4 applicants at once) with Name, Type, and Age.
2. Click Issue Permits — each row is processed in a loop.
3. Valid applicants receive permits; invalid ones show an error message.
4. The Permits Summary table shows count and revenue per category.
5. The Permit Log shows all issued permits.
6. Click Reset All to clear all permit data.

[Module 2 — Library Borrowing & Fines (`library.php`)]

Constants (defined in `library.php`)
| Constant | Value |
|----------|-------|
| `FINE_TEXTBOOK` | R5.00/day |
| `FINE_JOURNAL` | R3.00/day |
| `FINE_REFERENCE` | R10.00/day |
| `MAX_FINE_THRESHOLD` | R200.00 |

[Business Rules]
- Users with outstanding fines > R200 cannot borrow new books.
- Late fines are calculated as: fine = daily_rate × days_late
- Only valid categories accepted: Textbook, Journal, Reference Book

Shared Functions (in `functions.php`)
- `calculateFine($category, $daysLate)` — returns fine amount
- `borrowBook(&$library, $userId, $title, $category, $dueDate)` — issues a loan or denies it
- `returnBook(&$library, $userId, $borrowId, $returnDate)` — processes a return and levies fines
- `printUserSummary($library, $userId)` — renders a full HTML borrowing summary

[How to Use]

Borrow a Book
1. Click the Borrow Book tab.
2. Select a user (blocked users are shown but disabled).
3. Enter the book title, select a category, and set a due date.
4. Click Borrow Book.

Return a Book
1. Click the Return Book tab.
2. Select the borrowed book from the dropdown.
3. Set the return date (may be past the due date).
4. Click Process Return — fines are calculated automatically.

manage Fines
1. Click the Fines tab to see all outstanding fines.
2. Click Pay Fine to clear a user's outstanding balance.

Users
1. Click the Users tab to add new users or see all registered users.

Summary
1. Click the Summary tab for a full borrowing history per user.


[Module 3 — Student Performance Analytics (`performance.php`)]

Grading Scale
| Grade | Average |
|-------|---------|
| Distinction | ≥ 75% |
| Pass | 50–74% |
| Fail | < 50% |

Functions (in `functions.php`)
- `validateMark($mark)` — checks mark is numeric and in range 0–100
- `calculateAverage($marks)` — returns mean of valid marks array
- `assignGrade($average)` — returns Distinction / Pass / Fail
- `processStudents($rawStudents)` — validates, computes averages, grades all students; returns processed results + class statistics

[How to Use]

Demo Dataset
1. Click Run Demo Analysis.
2. 6 pre-seeded students load with intentional invalid marks to demonstrate validation.
3. Results show: averages, grades, top performer, class statistics, grade distribution.

Custom Input
1. Fill in at least 4 student names with 6 marks each (0–100).
2. Click analyse Custom Data.
3. Invalid marks (non-numeric or out of range) are flagged with error messages; valid marks are processed.


[Data Persistence]

All data is stored in PHP sessions (`$_SESSION`). Data resets when you close the browser or the session expires. This is intentional for an assignment context — no database is required.

Validation Summary

| Module | Validation Applied |
|--------|--------------------|
| Parking | Age ≥ 18, valid type, capacity check |
| Library | User exists, fine threshold, valid category, valid dates |
| Performance | Numeric marks, range 0–100, min 4 students, min 1 valid mark per student |



*Campus Management System — IPR 621 — All rights reserved.*
>_<
