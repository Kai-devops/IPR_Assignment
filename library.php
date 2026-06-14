<?php
//  library.php  –  Library Borrowing & Fine Module (40 Marks)
//  Campus Management System


// ── Constants ────────────────────────────────────────────────
define('FINE_TEXTBOOK',    5.00);   // R5/day
define('FINE_JOURNAL',     3.00);   // R3/day
define('FINE_REFERENCE',   10.00);  // R10/day
define('MAX_FINE_THRESHOLD', 200.00);

require_once 'functions.php';
session_start();

// ── Initialise library data in session ───────────────────────
if (!isset($_SESSION['library'])) {
    // Pre-seed with 4 sample users
    $_SESSION['library'] = [
        'U001' => ['name' => 'Aayush Kussial',   'outstanding_fine' => 0.0, 'borrowed_books' => []],
        'U002' => ['name' => 'Tahiel Hirilall',       'outstanding_fine' => 0.0, 'borrowed_books' => []],
        'U003' => ['name' => 'Kai Ackerman',    'outstanding_fine' => 0.0, 'borrowed_books' => []],
        'U004' => ['name' => 'Yuta Okkotsu',    'outstanding_fine' => 0.0, 'borrowed_books' => []],
    ];
}
$library  = &$_SESSION['library'];

$messages = [];
$errors   = [];
$tabActive = $_GET['tab'] ?? 'borrow';

//Process actions 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Add new user
    if (isset($_POST['add_user'])) {
        $uid  = trim($_POST['user_id']  ?? '');
        $uname= trim($_POST['user_name'] ?? '');
        if ($uid === '' || $uname === '') {
            $errors[] = 'User ID and Name are required.';
        } elseif (isset($library[$uid])) {
            $errors[] = "User ID '$uid' already exists.";
        } else {
            $library[$uid] = ['name' => htmlspecialchars($uname), 'outstanding_fine' => 0.0, 'borrowed_books' => []];
            $messages[] = "User '$uname' ($uid) added.";
        }
        $tabActive = 'users';
    }

    // Borrow book
    if (isset($_POST['borrow_book'])) {
        $uid      = trim($_POST['borrow_user'] ?? '');
        $title    = trim($_POST['book_title']  ?? '');
        $category = $_POST['book_category']    ?? '';
        $dueDate  = $_POST['due_date']          ?? '';
        if ($uid === '' || $title === '' || $category === '' || $dueDate === '') {
            $errors[] = 'All fields are required to borrow a book.';
        } else {
            $result = borrowBook($library, $uid, $title, $category, $dueDate);
            if ($result['success']) {
                $messages[] = $result['message'];
            } else {
                $errors[] = $result['message'];
            }
        }
        $tabActive = 'borrow';
    }

    // Return book
    if (isset($_POST['return_book'])) {
        $uid       = trim($_POST['return_user']   ?? '');
        $borrowId  = trim($_POST['borrow_id']     ?? '');
        $returnDate= $_POST['return_date']          ?? date('Y-m-d');
        if ($uid === '' || $borrowId === '') {
            $errors[] = 'User and Borrow ID are required to return a book.';
        } else {
            $result = returnBook($library, $uid, $borrowId, $returnDate);
            if ($result['success']) {
                $messages[] = $result['message'];
            } else {
                $errors[] = $result['message'];
            }
        }
        $tabActive = 'return';
    }

    // Pay fine
    if (isset($_POST['pay_fine'])) {
        $uid = trim($_POST['fine_user'] ?? '');
        if (isset($library[$uid])) {
            $paid = $library[$uid]['outstanding_fine'];
            $library[$uid]['outstanding_fine'] = 0.0;
            $messages[] = "Fine of R" . number_format($paid, 2) . " cleared for {$library[$uid]['name']}.";
        } else {
            $errors[] = "User not found.";
        }
        $tabActive = 'fines';
    }

    // Reset
    if (isset($_POST['reset_library'])) {
        unset($_SESSION['library']);
        header('Location: library.php');
        exit;
    }
}

// Collect all borrow records for return dropdown
$allBorrows = [];
foreach ($library as $uid => $user) {
    foreach ($user['borrowed_books'] as $bid => $book) {
        if (!$book['returned']) {
            $allBorrows[] = ['uid' => $uid, 'uname' => $user['name'], 'bid' => $bid, 'title' => $book['title'], 'due' => $book['due_date']];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Library – CMS</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Syne:wght@400;600;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap');

  :root {
    --bg:      #0b0f1a;
    --surface: #131929;
    --surface2:#182035;
    --border:  #1f2d47;
    --accent:  #10b981;
    --blue:    #3b82f6;
    --danger:  #ef4444;
    --warn:    #f59e0b;
    --text:    #e2e8f0;
    --muted:   #64748b;
    --r:       14px;
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: 'DM Sans', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    padding: 40px 20px 60px;
    background-image: radial-gradient(ellipse 70% 35% at 50% 0%, rgba(16,185,129,.10) 0%, transparent 60%);
  }
  .wrap { max-width: 1100px; margin: 0 auto; }

  .topnav { display: flex; align-items: center; gap: 12px; margin-bottom: 36px; }
  .topnav a { color: var(--muted); text-decoration: none; font-size: .88rem; display: flex; align-items: center; gap: 6px; transition: color .2s; }
  .topnav a:hover { color: var(--accent); }
  .topnav span { color: var(--border); }
  .topnav .current { color: var(--accent); font-weight: 500; }

  .page-header { margin-bottom: 36px; }
  .module-tag {
    display: inline-block; font-family: 'Syne', sans-serif;
    font-size: 11px; letter-spacing: 3px; text-transform: uppercase;
    color: var(--accent); background: rgba(16,185,129,.1);
    border: 1px solid rgba(16,185,129,.25); padding: 5px 14px;
    border-radius: 50px; margin-bottom: 16px;
  }
  h1 { font-family: 'Syne', sans-serif; font-size: 2.2rem; font-weight: 800; letter-spacing: -1px; margin-bottom: 8px; }
  .subtitle { color: var(--muted); font-size: .95rem; line-height: 1.65; }

  /* stats */
  .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 14px; margin-bottom: 32px; }
  .stat { background: var(--surface); border: 1px solid var(--border); border-radius: var(--r); padding: 20px 22px; }
  .stat .label { font-size: .78rem; color: var(--muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
  .stat .value { font-family: 'Syne', sans-serif; font-size: 1.7rem; font-weight: 800; }
  .stat.green .value { color: var(--accent); }
  .stat.blue  .value { color: var(--blue); }
  .stat.warn  .value { color: var(--warn); }
  .stat.red   .value { color: var(--danger); }

  /* alerts */
  .alert { padding: 11px 16px; border-radius: 9px; margin-bottom: 8px; font-size: .88rem; }
  .alert.success { background: rgba(16,185,129,.1); border: 1px solid rgba(16,185,129,.3); color: #6ee7b7; }
  .alert.error   { background: rgba(239,68,68,.1);  border: 1px solid rgba(239,68,68,.3);  color: #fca5a5; }
  .alerts { margin-bottom: 24px; }

  /* tabs */
  .tabs { display: flex; gap: 4px; border-bottom: 1px solid var(--border); margin-bottom: 28px; flex-wrap: wrap; }
  .tab-btn {
    background: none; border: none; color: var(--muted); font-family: 'DM Sans', sans-serif;
    font-size: .9rem; padding: 10px 18px; cursor: pointer; border-bottom: 2px solid transparent;
    margin-bottom: -1px; transition: color .2s, border-color .2s; white-space: nowrap;
  }
  .tab-btn.active { color: var(--accent); border-color: var(--accent); }
  .tab-btn:hover:not(.active) { color: var(--text); }

  .tab-panel { display: none; }
  .tab-panel.active { display: block; }

  /* card */
  .card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--r); padding: 26px 28px 30px; margin-bottom: 24px; }
  .card h2 { font-family: 'Syne', sans-serif; font-size: 1.05rem; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }

  /* form elements */
  .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
  @media(max-width:600px){ .form-grid { grid-template-columns: 1fr; } }
  .field { display: flex; flex-direction: column; gap: 5px; }
  .field label { font-size: .8rem; color: var(--muted); text-transform: uppercase; letter-spacing: .8px; }
  .field input, .field select {
    background: var(--surface2); border: 1px solid var(--border);
    border-radius: 8px; padding: 9px 13px; color: var(--text);
    font-family: 'DM Sans', sans-serif; font-size: .9rem;
    outline: none; transition: border-color .2s;
  }
  .field input:focus, .field select:focus { border-color: var(--accent); }

  .btn { padding: 10px 22px; border-radius: 9px; font-family: 'DM Sans', sans-serif; font-size: .9rem; font-weight: 500; cursor: pointer; border: none; transition: opacity .2s, transform .15s; }
  .btn:hover { opacity: .85; transform: translateY(-1px); }
  .btn-primary { background: var(--accent); color: #fff; }
  .btn-blue    { background: var(--blue); color: #fff; }
  .btn-danger  { background: rgba(239,68,68,.15); color: var(--danger); border: 1px solid rgba(239,68,68,.3); }
  .btn-warn    { background: rgba(245,158,11,.15); color: var(--warn); border: 1px solid rgba(245,158,11,.3); }
  .btn-row     { display: flex; gap: 10px; margin-top: 18px; flex-wrap: wrap; }

  /* tables */
  table.dt { width: 100%; border-collapse: collapse; font-size: .88rem; }
  .dt th { font-size: .75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--muted); padding: 9px 13px; border-bottom: 1px solid var(--border); text-align: left; }
  .dt td { padding: 10px 13px; border-bottom: 1px solid rgba(31,45,71,.5); }
  .dt tr:last-child td { border-bottom: none; }

  .pill { display: inline-block; padding: 2px 9px; border-radius: 50px; font-size: .75rem; font-weight: 600; }
  .p-textbook  { background: rgba(59,130,246,.15);  color: #93c5fd; }
  .p-journal   { background: rgba(16,185,129,.15);   color: #6ee7b7; }
  .p-reference { background: rgba(245,158,11,.15);  color: #fcd34d; }
  .p-returned  { background: rgba(100,116,139,.15); color: #94a3b8; }
  .p-borrowed  { background: rgba(16,185,129,.15);   color: #6ee7b7; }
  .p-blocked   { background: rgba(239,68,68,.15);    color: #fca5a5; }
  .p-clear     { background: rgba(16,185,129,.15);   color: #6ee7b7; }

  .fine-badge { font-family: 'Syne', sans-serif; font-weight: 700; }
  .fine-zero { color: var(--accent); }
  .fine-warn { color: var(--warn); }
  .fine-over { color: var(--danger); }

  /* user summary accordion */
  .user-summary { background: var(--surface2); border: 1px solid var(--border); border-radius: 10px; padding: 14px 16px; margin-bottom: 10px; }
  .user-summary h4 { font-family: 'Syne', sans-serif; font-size: .95rem; font-weight: 700; margin-bottom: 6px; }
  .user-summary p  { font-size: .85rem; color: var(--muted); margin-bottom: 8px; }
  .summary-table   { width: 100%; border-collapse: collapse; font-size: .82rem; }
  .summary-table th { font-size: .72rem; text-transform: uppercase; letter-spacing: .8px; color: var(--muted); padding: 7px 10px; border-bottom: 1px solid var(--border); text-align: left; }
  .summary-table td { padding: 7px 10px; border-bottom: 1px solid rgba(31,45,71,.4); }
  .summary-table tr:last-child td { border-bottom: none; }

  .rate-table { width: 100%; border-collapse: collapse; }
  .rate-table td { padding: 8px 0; border-bottom: 1px solid var(--border); font-size: .9rem; }
  .rate-table tr:last-child td { border-bottom: none; }
  .rate-table .rt { color: var(--muted); }
  .rate-table .rv { font-weight: 600; text-align: right; }
</style>
</head>
<body>
<div class="wrap">

  <nav class="topnav">
    <a href="index.php">🏫 Home</a>
    <span>/</span>
    <span class="current">📚 Library</span>
  </nav>

  <div class="page-header">
    
    <h1>📚 Library Borrowing &amp; Fines</h1>
    <p class="subtitle">Manage book loans with automatic fine calculation. Users with fines over R<?= MAX_FINE_THRESHOLD ?> are blocked from borrowing.</p>
  </div>

  <?php
    // Compute stats
    $totalBorrowed = 0; $totalReturned = 0; $totalFines = 0.0; $blockedCount = 0;
    foreach ($library as $uid => $u) {
        foreach ($u['borrowed_books'] as $b) {
            $totalBorrowed++;
            if ($b['returned']) $totalReturned++;
        }
        $totalFines += $u['outstanding_fine'];
        if ($u['outstanding_fine'] > MAX_FINE_THRESHOLD) $blockedCount++;
    }
    $totalActive = $totalBorrowed - $totalReturned;
  ?>

  <div class="stats">
    <div class="stat green"><div class="label">Registered Users</div><div class="value"><?= count($library) ?></div></div>
    <div class="stat blue"> <div class="label">Books Borrowed</div><div class="value"><?= $totalActive ?></div></div>
    <div class="stat warn"> <div class="label">Total Fines Outstanding</div><div class="value">R<?= number_format($totalFines, 2) ?></div></div>
    <div class="stat red">  <div class="label">Blocked Users</div><div class="value"><?= $blockedCount ?></div></div>
  </div>

  <?php if ($messages || $errors): ?>
  <div class="alerts">
    <?php foreach ($messages as $m): ?><div class="alert success">✅ <?= htmlspecialchars($m) ?></div><?php endforeach; ?>
    <?php foreach ($errors as $e): ?><div class="alert error">⚠️ <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Tabs -->
  <div class="tabs">
    <button class="tab-btn <?= $tabActive==='borrow' ?'active':'' ?>" onclick="switchTab('borrow')">📖 Borrow Book</button>
    <button class="tab-btn <?= $tabActive==='return' ?'active':'' ?>" onclick="switchTab('return')">↩️ Return Book</button>
    <button class="tab-btn <?= $tabActive==='fines'  ?'active':'' ?>" onclick="switchTab('fines')">💸 Fines</button>
    <button class="tab-btn <?= $tabActive==='users'  ?'active':'' ?>" onclick="switchTab('users')">👥 Users</button>
    <button class="tab-btn <?= $tabActive==='summary'?'active':'' ?>" onclick="switchTab('summary')">📋 Summary</button>
  </div>

  <!-- ===== BORROW TAB ===== -->
  <div id="tab-borrow" class="tab-panel <?= $tabActive==='borrow'?'active':'' ?>">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
      <div>
        <div class="card">
          <h2>📖 Borrow a Book</h2>
          <form method="POST">
            <div class="form-grid">
              <div class="field">
                <label>Select User</label>
                <select name="borrow_user" required>
                  <option value="">-- Select User --</option>
                  <?php foreach ($library as $uid => $u):
                    $blocked = $u['outstanding_fine'] > MAX_FINE_THRESHOLD; ?>
                  <option value="<?= htmlspecialchars($uid) ?>" <?= $blocked ? 'disabled' : '' ?>>
                    <?= htmlspecialchars($u['name']) ?> (<?= $uid ?>)<?= $blocked ? ' ⛔ BLOCKED' : '' ?>
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="field">
                <label>Book Title</label>
                <input type="text" name="book_title" placeholder="e.g. Data Structures" required>
              </div>
              <div class="field">
                <label>Category</label>
                <select name="book_category" required>
                  <option value="">-- Select --</option>
                  <option value="Textbook">Textbook (R<?= FINE_TEXTBOOK ?>/day late)</option>
                  <option value="Journal">Journal (R<?= FINE_JOURNAL ?>/day late)</option>
                  <option value="Reference Book">Reference Book (R<?= FINE_REFERENCE ?>/day late)</option>
                </select>
              </div>
              <div class="field">
                <label>Due Date</label>
                <input type="date" name="due_date" required min="<?= date('Y-m-d') ?>">
              </div>
            </div>
            <div class="btn-row">
              <button type="submit" name="borrow_book" value="1" class="btn btn-primary">Borrow Book</button>
            </div>
          </form>
        </div>
      </div>

      <div class="card">
        <h2>📐 Fine Rate Constants</h2>
        <table class="rate-table">
          <tr><td class="rt">Textbook fine</td>      <td class="rv">R<?= number_format(FINE_TEXTBOOK,  2) ?>/day</td></tr>
          <tr><td class="rt">Journal fine</td>       <td class="rv">R<?= number_format(FINE_JOURNAL,   2) ?>/day</td></tr>
          <tr><td class="rt">Reference Book fine</td><td class="rv">R<?= number_format(FINE_REFERENCE, 2) ?>/day</td></tr>
          <tr><td class="rt">Borrow block threshold</td><td class="rv">R<?= number_format(MAX_FINE_THRESHOLD, 2) ?></td></tr>
        </table>

        <h2 style="margin-top:24px;">📚 Active Loans</h2>
        <?php if (empty($allBorrows)): ?>
          <p style="color:var(--muted);font-size:.88rem;">No active loans.</p>
        <?php else: ?>
          <table class="dt">
            <thead><tr><th>User</th><th>Title</th><th>Due</th></tr></thead>
            <tbody>
            <?php foreach ($allBorrows as $ab): ?>
              <tr>
                <td><?= htmlspecialchars($ab['uname']) ?></td>
                <td><?= htmlspecialchars($ab['title']) ?></td>
                <td><?= $ab['due'] ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- RETURN TAB -->
  <div id="tab-return" class="tab-panel <?= $tabActive==='return'?'active':'' ?>">
    <div class="card" style="max-width:600px;">
      <h2>↩️ Return a Book</h2>
      <?php if (empty($allBorrows)): ?>
        <p style="color:var(--muted);">No books currently on loan.</p>
      <?php else: ?>
      <form method="POST">
        <div class="form-grid">
          <div class="field" style="grid-column:1/-1;">
            <label>Select Book to Return</label>
            <select name="borrow_id" onchange="setReturnUser(this)" required>
              <option value="">-- Select Book --</option>
              <?php foreach ($allBorrows as $ab): ?>
              <option value="<?= $ab['bid'] ?>" data-uid="<?= htmlspecialchars($ab['uid']) ?>">
                <?= htmlspecialchars($ab['uname']) ?> — "<?= htmlspecialchars($ab['title']) ?>" (due <?= $ab['due'] ?>)
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <input type="hidden" name="return_user" id="return_user_hidden" value="">
          <div class="field">
            <label>Return Date</label>
            <input type="date" name="return_date" value="<?= date('Y-m-d') ?>" required>
          </div>
        </div>
        <div class="btn-row">
          <button type="submit" name="return_book" value="1" class="btn btn-blue">Process Return</button>
        </div>
      </form>
      <?php endif; ?>
    </div>
  </div>

  <!--FINES TAB-->
  <div id="tab-fines" class="tab-panel <?= $tabActive==='fines'?'active':'' ?>">
    <div class="card">
      <h2>💸 Outstanding Fines</h2>
      <table class="dt">
        <thead><tr><th>User ID</th><th>Name</th><th>Fine</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
          <?php foreach ($library as $uid => $u):
            $f = $u['outstanding_fine'];
            $cls = $f == 0 ? 'fine-zero' : ($f > MAX_FINE_THRESHOLD ? 'fine-over' : 'fine-warn');
            $status = $f == 0 ? '<span class="pill p-clear">Clear</span>' : ($f > MAX_FINE_THRESHOLD ? '<span class="pill p-blocked">Blocked</span>' : '<span class="pill p-journal">Has Fine</span>');
          ?>
          <tr>
            <td><?= htmlspecialchars($uid) ?></td>
            <td><?= htmlspecialchars($u['name']) ?></td>
            <td><span class="fine-badge <?= $cls ?>">R<?= number_format($f, 2) ?></span></td>
            <td><?= $status ?></td>
            <td>
              <?php if ($f > 0): ?>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="fine_user" value="<?= htmlspecialchars($uid) ?>">
                <button type="submit" name="pay_fine" value="1" class="btn btn-warn" style="padding:6px 14px;font-size:.82rem;">Pay Fine</button>
              </form>
              <?php else: ?>
              <span style="color:var(--muted);font-size:.82rem;">—</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!--USERS TAB-->
  <div id="tab-users" class="tab-panel <?= $tabActive==='users'?'active':'' ?>">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
      <div class="card">
        <h2>➕ Add New User</h2>
        <form method="POST">
          <div class="form-grid">
            <div class="field">
              <label>User ID</label>
              <input type="text" name="user_id" placeholder="e.g. U005" required>
            </div>
            <div class="field">
              <label>Full Name</label>
              <input type="text" name="user_name" placeholder="Full name" required>
            </div>
          </div>
          <div class="btn-row">
            <button type="submit" name="add_user" value="1" class="btn btn-primary">Add User</button>
          </div>
        </form>
      </div>

      <div class="card">
        <h2>👥 Registered Users</h2>
        <table class="dt">
          <thead><tr><th>ID</th><th>Name</th><th>Books</th><th>Fine</th></tr></thead>
          <tbody>
            <?php foreach ($library as $uid => $u):
              $bc = count($u['borrowed_books']);
            ?>
            <tr>
              <td><?= htmlspecialchars($uid) ?></td>
              <td><?= htmlspecialchars($u['name']) ?></td>
              <td><?= $bc ?></td>
              <td class="fine-badge <?= $u['outstanding_fine'] > MAX_FINE_THRESHOLD ? 'fine-over' : ($u['outstanding_fine'] > 0 ? 'fine-warn' : 'fine-zero') ?>">
                R<?= number_format($u['outstanding_fine'], 2) ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <div class="btn-row">
          <form method="POST"><button type="submit" name="reset_library" value="1" class="btn btn-danger">Reset All Data</button></form>
        </div>
      </div>
    </div>
  </div>

  <!--SUMMARY TAB-->
  <div id="tab-summary" class="tab-panel <?= $tabActive==='summary'?'active':'' ?>">
    <h2 style="font-family:'Syne',sans-serif;font-size:1.1rem;font-weight:700;margin-bottom:20px;">📋 User Borrowing Summaries</h2>
    <?php foreach ($library as $uid => $u): ?>
      <?= printUserSummary($library, $uid) ?>
    <?php endforeach; ?>
  </div>

</div>

<script>
function switchTab(name) {
  document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.getElementById('tab-' + name).classList.add('active');
  document.querySelectorAll('.tab-btn').forEach(b => {
    if (b.getAttribute('onclick') === "switchTab('" + name + "')") b.classList.add('active');
  });
}
function setReturnUser(sel) {
  const uid = sel.options[sel.selectedIndex].getAttribute('data-uid') || '';
  document.getElementById('return_user_hidden').value = uid;
}
</script>
</body>
</html>
