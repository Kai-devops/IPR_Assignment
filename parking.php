<?php
//  parking.php  –  Parking Permit Module (30 Marks)
//  Campus Management System


// Constants 
define('PERMIT_STUDENT',    450.00);
define('PERMIT_STAFF',      750.00);
define('PERMIT_VISITOR',    100.00);
define('MAX_PARKING_CAPACITY', 50);

require_once 'functions.php';

//  Session-based permit storage 
session_start();
if (!isset($_SESSION['permits'])) {
    $_SESSION['permits'] = [];
}
$permits = &$_SESSION['permits'];

$messages = [];
$errors   = [];

// Process form 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['reset'])) {
        $_SESSION['permits'] = [];
        $messages[] = 'All permit records cleared.';
        $permits = &$_SESSION['permits'];

    } elseif (isset($_POST['add_permit'])) {
        // Could be a batch (multiple rows) or single
        $names = $_POST['name']  ?? [];
        $types = $_POST['type']  ?? [];
        $ages  = $_POST['age']   ?? [];

        foreach ($names as $i => $name) {
            $name = trim($name);
            $type = $types[$i] ?? '';
            $age  = (int)($ages[$i] ?? 0);

            if ($name === '') {
                $errors[] = "Row " . ($i + 1) . ": Name cannot be empty.";
                continue;
            }

            $result = issuePermit($permits, $name, $type, $age);
            if ($result['success']) {
                $messages[] = $result['message'];
            } else {
                $errors[] = $result['message'];
            }
        }
    }
}

$summary     = generateParkingSummary($permits);
$totalSold   = array_sum(array_column($permits, 'count'));
$totalCount  = count($permits);
$totalRev    = array_sum(array_column($permits, 'price'));
$spotsLeft   = MAX_PARKING_CAPACITY - $totalCount;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Parking Permits – CMS</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Syne:wght@400;600;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap');

  :root {
    --bg:      #0b0f1a;
    --surface: #131929;
    --surface2:#182035;
    --border:  #1f2d47;
    --accent:  #3b82f6;
    --success: #10b981;
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
    background-image: radial-gradient(ellipse 70% 35% at 50% 0%, rgba(59,130,246,.12) 0%, transparent 60%);
  }
  .wrap { max-width: 1060px; margin: 0 auto; }

  /* ── Top nav ── */
  .topnav {
    display: flex; align-items: center; gap: 12px;
    margin-bottom: 36px;
  }
  .topnav a {
    color: var(--muted); text-decoration: none; font-size: .88rem;
    display: flex; align-items: center; gap: 6px;
    transition: color .2s;
  }
  .topnav a:hover { color: var(--accent); }
  .topnav span { color: var(--border); }
  .topnav .current { color: var(--accent); font-weight: 500; }

  /* ── Page title ── */
  .page-header { margin-bottom: 40px; }
  .module-tag {
    display: inline-block; font-family: 'Syne', sans-serif;
    font-size: 11px; letter-spacing: 3px; text-transform: uppercase;
    color: var(--accent); background: rgba(59,130,246,.1);
    border: 1px solid rgba(59,130,246,.25); padding: 5px 14px;
    border-radius: 50px; margin-bottom: 16px;
  }
  h1 { font-family: 'Syne', sans-serif; font-size: 2.2rem; font-weight: 800; letter-spacing: -1px; margin-bottom: 8px; }
  .subtitle { color: var(--muted); font-size: .95rem; line-height: 1.65; }

  /* ── Stat cards ── */
  .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 36px; }
  .stat {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--r); padding: 22px 24px;
  }
  .stat .label { font-size: .8rem; color: var(--muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; }
  .stat .value { font-family: 'Syne', sans-serif; font-size: 1.8rem; font-weight: 800; }
  .stat.blue  .value { color: var(--accent); }
  .stat.green .value { color: var(--success); }
  .stat.amber .value { color: var(--warn); }
  .stat.red   .value { color: var(--danger); }

  /* ── Alerts ── */
  .alert { padding: 12px 18px; border-radius: 10px; margin-bottom: 10px; font-size: .9rem; }
  .alert.success { background: rgba(16,185,129,.1); border: 1px solid rgba(16,185,129,.3); color: #6ee7b7; }
  .alert.error   { background: rgba(239,68,68,.1);  border: 1px solid rgba(239,68,68,.3);  color: #fca5a5; }
  .alerts { margin-bottom: 24px; }

  /* ── Two col layout ── */
  .cols { display: grid; grid-template-columns: 1fr 1fr; gap: 28px; }
  @media(max-width:700px){ .cols { grid-template-columns: 1fr; } }

  /* ── Card ── */
  .card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--r); padding: 28px 28px 32px;
  }
  .card h2 { font-family: 'Syne', sans-serif; font-size: 1.1rem; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }

  /* ── Form ── */
  .batch-table { width: 100%; border-collapse: collapse; }
  .batch-table th { font-size: .78rem; text-transform: uppercase; letter-spacing: 1px; color: var(--muted); padding: 8px 10px; border-bottom: 1px solid var(--border); text-align: left; }
  .batch-table td { padding: 8px 6px; }
  .batch-table input, .batch-table select {
    width: 100%; background: var(--surface2); border: 1px solid var(--border);
    border-radius: 8px; padding: 8px 12px; color: var(--text);
    font-family: 'DM Sans', sans-serif; font-size: .9rem;
    outline: none; transition: border-color .2s;
  }
  .batch-table input:focus, .batch-table select:focus { border-color: var(--accent); }

  .btn {
    padding: 10px 22px; border-radius: 9px; font-family: 'DM Sans', sans-serif;
    font-size: .9rem; font-weight: 500; cursor: pointer; border: none;
    transition: opacity .2s, transform .15s;
  }
  .btn:hover { opacity: .85; transform: translateY(-1px); }
  .btn-primary { background: var(--accent); color: #fff; }
  .btn-danger  { background: rgba(239,68,68,.15); color: var(--danger); border: 1px solid rgba(239,68,68,.3); }
  .btn-row { display: flex; gap: 10px; margin-top: 16px; }

  /* ── Permit constants ── */
  .constants { display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px; }
  .const-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 16px; background: var(--surface2); border-radius: 9px; border: 1px solid var(--border); }
  .const-item .cn { font-size: .88rem; color: var(--muted); }
  .const-item .cv { font-family: 'Syne', sans-serif; font-size: 1rem; font-weight: 700; color: var(--accent); }

  /* ── Summary table ── */
  table.data-table { width: 100%; border-collapse: collapse; margin-top: 12px; }
  .data-table th { font-size: .78rem; text-transform: uppercase; letter-spacing: 1px; color: var(--muted); padding: 10px 14px; border-bottom: 1px solid var(--border); text-align: left; }
  .data-table td { padding: 11px 14px; border-bottom: 1px solid rgba(31,45,71,.6); font-size: .9rem; }
  .data-table tr:last-child td { border-bottom: none; }
  .data-table .total-row td { font-weight: 700; color: var(--accent); border-top: 1px solid var(--border); }

  .badge-pill { display: inline-block; padding: 3px 10px; border-radius: 50px; font-size: .78rem; font-weight: 600; }
  .badge-student { background: rgba(59,130,246,.15); color: #93c5fd; }
  .badge-staff   { background: rgba(16,185,129,.15);  color: #6ee7b7; }
  .badge-visitor { background: rgba(245,158,11,.15);  color: #fcd34d; }

  /* ── Log ── */
  .log-list { list-style: none; display: flex; flex-direction: column; gap: 8px; max-height: 340px; overflow-y: auto; }
  .log-list li { background: var(--surface2); border-radius: 9px; padding: 10px 14px; font-size: .88rem; display: flex; gap: 10px; align-items: flex-start; border: 1px solid var(--border); }
  .log-list .li-icon { font-size: 1rem; flex-shrink: 0; margin-top: 1px; }
  .no-data { color: var(--muted); font-size: .9rem; padding: 16px 0; }

  /* capacity bar */
  .cap-bar { height: 6px; background: var(--border); border-radius: 3px; margin-top: 8px; overflow: hidden; }
  .cap-fill { height: 100%; background: var(--accent); border-radius: 3px; transition: width .4s; }
  .cap-fill.warn { background: var(--warn); }
  .cap-fill.full { background: var(--danger); }
</style>
</head>
<body>
<div class="wrap">

  <!-- Breadcrumb -->
  <nav class="topnav">
    <a href="index.php">🏫 Home</a>
    <span>/</span>
    <span class="current">🚗 Parking Permits</span>
  </nav>

  <div class="page-header">
    <h1>🚗 Parking Permit Management</h1>
    <p class="subtitle">Issue permits for students, staff, and visitors. Capacity is capped at <?= MAX_PARKING_CAPACITY ?> vehicles. No permits for applicants under 18.</p>
  </div>

  <!-- Stats -->
  <div class="stats">
    <div class="stat blue">
      <div class="label">Total Permits Issued</div>
      <div class="value"><?= $totalCount ?></div>
    </div>
    <div class="stat green">
      <div class="label">Total Revenue</div>
      <div class="value">R<?= number_format($totalRev, 2) ?></div>
    </div>
    <div class="stat amber">
      <div class="label">Spots Remaining</div>
      <div class="value"><?= max(0, $spotsLeft) ?></div>
      <div class="cap-bar"><div class="cap-fill <?= $totalCount >= MAX_PARKING_CAPACITY ? 'full' : ($totalCount >= MAX_PARKING_CAPACITY * .8 ? 'warn' : '') ?>" style="width:<?= min(100, ($totalCount / MAX_PARKING_CAPACITY) * 100) ?>%"></div></div>
    </div>
    <div class="stat">
      <div class="label">Capacity</div>
      <div class="value"><?= MAX_PARKING_CAPACITY ?></div>
    </div>
  </div>

  <!-- Alerts -->
  <?php if ($messages || $errors): ?>
  <div class="alerts">
    <?php foreach ($messages as $m): ?>
      <div class="alert success">✅ <?= htmlspecialchars($m) ?></div>
    <?php endforeach; ?>
    <?php foreach ($errors as $e): ?>
      <div class="alert error">⚠️ <?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div class="cols">

    <!-- Left: Issue form + constants -->
    <div>
      <!-- Price constants -->
      <div class="card" style="margin-bottom:24px;">
        <h2>💰 Permit Price Constants</h2>
        <div class="constants">
          <div class="const-item"><span class="cn">PERMIT_STUDENT</span><span class="cv">R<?= number_format(PERMIT_STUDENT, 2) ?></span></div>
          <div class="const-item"><span class="cn">PERMIT_STAFF</span><span class="cv">R<?= number_format(PERMIT_STAFF, 2) ?></span></div>
          <div class="const-item"><span class="cn">PERMIT_VISITOR</span><span class="cv">R<?= number_format(PERMIT_VISITOR, 2) ?></span></div>
          <div class="const-item"><span class="cn">MAX_PARKING_CAPACITY</span><span class="cv"><?= MAX_PARKING_CAPACITY ?></span></div>
        </div>
      </div>

      <!-- Issue form -->
      <div class="card">
        <h2>➕ Issue Permits (Batch)</h2>
        <form method="POST">
          <table class="batch-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Full Name</th>
                <th>Permit Type</th>
                <th>Age</th>
              </tr>
            </thead>
            <tbody>
              <?php for ($r = 0; $r < 4; $r++): ?>
              <tr>
                <td style="width:32px;color:var(--muted);font-size:.85rem;"><?= $r+1 ?></td>
                <td><input type="text" name="name[]" placeholder="Full name"></td>
                <td>
                  <select name="type[]">
                    <option value="">-- Select --</option>
                    <option value="Student">Student (R<?= number_format(PERMIT_STUDENT, 0) ?>)</option>
                    <option value="Staff">Staff (R<?= number_format(PERMIT_STAFF, 0) ?>)</option>
                    <option value="Visitor">Visitor (R<?= number_format(PERMIT_VISITOR, 0) ?>)</option>
                  </select>
                </td>
                <td style="width:80px;"><input type="number" name="age[]" placeholder="Age" min="0" max="120"></td>
              </tr>
              <?php endfor; ?>
            </tbody>
          </table>
          <div class="btn-row">
            <button type="submit" name="add_permit" value="1" class="btn btn-primary">Issue Permits</button>
            <button type="submit" name="reset" value="1" class="btn btn-danger">Reset All</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Right: Summary + log -->
    <div>
      <!-- Category summary -->
      <div class="card" style="margin-bottom:24px;">
        <h2>📊 Permits Summary</h2>
        <table class="data-table">
          <thead>
            <tr><th>Category</th><th>Count</th><th>Price Each</th><th>Revenue</th></tr>
          </thead>
          <tbody>
            <?php foreach (['Student','Staff','Visitor'] as $cat): ?>
            <tr>
              <td><span class="badge-pill badge-<?= strtolower($cat) ?>"><?= $cat ?></span></td>
              <td><?= $summary[$cat]['count'] ?></td>
              <td>R<?= number_format(getPermitPrice($cat), 2) ?></td>
              <td>R<?= number_format($summary[$cat]['revenue'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr class="total-row">
              <td><strong>TOTAL</strong></td>
              <td><?= $totalCount ?></td>
              <td>—</td>
              <td>R<?= number_format($totalRev, 2) ?></td>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- Permit log -->
      <div class="card">
        <h2>📋 Permit Log</h2>
        <?php if (empty($permits)): ?>
          <p class="no-data">No permits issued yet.</p>
        <?php else: ?>
          <ul class="log-list">
            <?php foreach (array_reverse($permits) as $p): ?>
            <li>
              <span class="li-icon"><?= $p['type'] === 'Student' ? '🎓' : ($p['type'] === 'Staff' ? '👔' : '👤') ?></span>
              <div>
                <strong><?= htmlspecialchars($p['name']) ?></strong>
                <span class="badge-pill badge-<?= strtolower($p['type']) ?>" style="margin-left:6px;"><?= $p['type'] ?></span><br>
                <span style="color:var(--muted);font-size:.82rem;">R<?= number_format($p['price'], 2) ?> &nbsp;·&nbsp; <?= $p['date'] ?></span>
              </div>
            </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- /cols -->

</div>
</body>
</html>
