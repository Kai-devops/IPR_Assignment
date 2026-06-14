<?php
//  performance.php  –  Student Performance Module (30 Marks)
//  Campus Management System


require_once 'functions.php';
session_start();

//  Pre-seeded default students (min 4 students, 6 marks each) 
$defaultStudents = [
    ['name' => 'Kai',  'marks' => [78, 85, 90, 72, 88, 95]],
    ['name' => 'Tahiel',     'marks' => [55, 49, 62, 58, 45, 101]],  // 101 = invalid
    ['name' => 'Shriya',  'marks' => [92, 88, 96, 94, 90, 97]],
    ['name' => 'Aayush',  'marks' => [35, 42, 28, 50, 'abc', 38]], // 'abc' = invalid
    ['name' => 'Luke','marks' => [68, 72, 65, 70, 74, 69]],
    ['name' => 'Rylan','marks' => [48, 52, 45, 55, 50, 47]],
];

$messages = [];
$errors   = [];
$results  = null;
$rawInput = null;

// Process 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['analyse_default'])) {
        $rawInput = $defaultStudents;

    } elseif (isset($_POST['analyse_custom'])) {
        $rawInput = [];
        $names = $_POST['sname'] ?? [];
        foreach ($names as $i => $sname) {
            $sname = trim($sname);
            if ($sname === '') continue;
            $rawMarks = [];
            for ($m = 0; $m < 6; $m++) {
                $rawMarks[] = $_POST["mark_{$i}_{$m}"] ?? '';
            }
            $rawInput[] = ['name' => $sname, 'marks' => $rawMarks];
        }
        if (count($rawInput) < 4) {
            $errors[] = 'Please enter at least 4 students.';
            $rawInput = null;
        }
    }

    if ($rawInput !== null) {
        $results = processStudents($rawInput);
        if (!empty($results['errors'])) {
            foreach ($results['errors'] as $e) $errors[] = $e;
        }
        if (!empty($results['students'])) {
            $messages[] = 'Analysis complete for ' . count($results['students']) . ' student(s).';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Performance – CMS</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Syne:wght@400;600;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap');

  :root {
    --bg:      #0b0f1a;
    --surface: #131929;
    --surface2:#182035;
    --border:  #1f2d47;
    --accent:  #f59e0b;
    --blue:    #3b82f6;
    --green:   #10b981;
    --danger:  #ef4444;
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
    background-image: radial-gradient(ellipse 70% 35% at 50% 0%, rgba(245,158,11,.10) 0%, transparent 60%);
  }
  .wrap { max-width: 1100px; margin: 0 auto; }

  .topnav { display: flex; align-items: center; gap: 12px; margin-bottom: 36px; }
  .topnav a { color: var(--muted); text-decoration: none; font-size: .88rem; transition: color .2s; }
  .topnav a:hover { color: var(--accent); }
  .topnav span { color: var(--border); }
  .topnav .current { color: var(--accent); font-weight: 500; }

  .page-header { margin-bottom: 36px; }
  .module-tag {
    display: inline-block; font-family: 'Syne', sans-serif;
    font-size: 11px; letter-spacing: 3px; text-transform: uppercase;
    color: var(--accent); background: rgba(245,158,11,.1);
    border: 1px solid rgba(245,158,11,.25); padding: 5px 14px;
    border-radius: 50px; margin-bottom: 16px;
  }
  h1 { font-family: 'Syne', sans-serif; font-size: 2.2rem; font-weight: 800; letter-spacing: -1px; margin-bottom: 8px; }
  .subtitle { color: var(--muted); font-size: .95rem; line-height: 1.65; }

  /* alerts */
  .alert { padding: 11px 16px; border-radius: 9px; margin-bottom: 8px; font-size: .88rem; }
  .alert.success { background: rgba(16,185,129,.1); border: 1px solid rgba(16,185,129,.3); color: #6ee7b7; }
  .alert.error   { background: rgba(239,68,68,.1);  border: 1px solid rgba(239,68,68,.3);  color: #fca5a5; }
  .alert.warn    { background: rgba(245,158,11,.1); border: 1px solid rgba(245,158,11,.3); color: #fcd34d; }
  .alerts { margin-bottom: 24px; }

  /* card */
  .card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--r); padding: 28px 30px 32px; margin-bottom: 24px; }
  .card h2 { font-family: 'Syne', sans-serif; font-size: 1.05rem; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }

  /* grade pills */
  .grade { display: inline-block; padding: 3px 12px; border-radius: 50px; font-family: 'Syne', sans-serif; font-size: .8rem; font-weight: 700; letter-spacing: .5px; }
  .g-distinction { background: rgba(245,158,11,.2); color: #fcd34d; border: 1px solid rgba(245,158,11,.4); }
  .g-pass        { background: rgba(16,185,129,.15); color: #6ee7b7; border: 1px solid rgba(16,185,129,.3); }
  .g-fail        { background: rgba(239,68,68,.15);  color: #fca5a5; border: 1px solid rgba(239,68,68,.3); }

  /* result table */
  table.dt { width: 100%; border-collapse: collapse; font-size: .88rem; }
  .dt th { font-size: .75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--muted); padding: 9px 13px; border-bottom: 1px solid var(--border); text-align: left; }
  .dt td { padding: 11px 13px; border-bottom: 1px solid rgba(31,45,71,.5); vertical-align: middle; }
  .dt tr:last-child td { border-bottom: none; }
  .dt .top-row { background: rgba(245,158,11,.04); }

  .avg-bar-wrap { display: flex; align-items: center; gap: 10px; }
  .avg-bar { height: 6px; flex: 1; background: var(--border); border-radius: 3px; overflow: hidden; }
  .avg-fill { height: 100%; border-radius: 3px; }
  .fill-distinction { background: var(--accent); }
  .fill-pass        { background: var(--green); }
  .fill-fail        { background: var(--danger); }
  .avg-num { font-family: 'Syne', sans-serif; font-weight: 700; font-size: .9rem; min-width: 44px; }

  /* stats row */
  .stats4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 28px; }
  @media(max-width:700px){ .stats4 { grid-template-columns: repeat(2,1fr); } }
  .stat { background: var(--surface); border: 1px solid var(--border); border-radius: var(--r); padding: 20px 22px; }
  .stat .label { font-size: .75rem; color: var(--muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
  .stat .value { font-family: 'Syne', sans-serif; font-size: 1.6rem; font-weight: 800; }
  .stat.amber .value { color: var(--accent); }
  .stat.green .value { color: var(--green); }
  .stat.blue  .value { color: var(--blue); }
  .stat.red   .value { color: var(--danger); }

  /* marks chips */
  .mark-chips { display: flex; flex-wrap: wrap; gap: 4px; }
  .chip { display: inline-block; padding: 2px 8px; border-radius: 5px; font-size: .78rem; background: var(--surface2); border: 1px solid var(--border); color: var(--text); }
  .chip.invalid { background: rgba(239,68,68,.1); border-color: rgba(239,68,68,.3); color: #fca5a5; text-decoration: line-through; }

  /* top student banner */
  .top-banner {
    background: linear-gradient(135deg, rgba(245,158,11,.15) 0%, rgba(245,158,11,.05) 100%);
    border: 1px solid rgba(245,158,11,.3);
    border-radius: var(--r); padding: 20px 24px; margin-bottom: 24px;
    display: flex; align-items: center; gap: 16px;
  }
  .top-banner .trophy { font-size: 2.5rem; }
  .top-banner h3 { font-family: 'Syne', sans-serif; font-size: 1.15rem; font-weight: 800; color: #fcd34d; }
  .top-banner p { color: var(--muted); font-size: .88rem; margin-top: 3px; }

  /* form grid */
  .form-grid-input { display: grid; grid-template-columns: 200px repeat(6, 1fr); gap: 8px; align-items: center; margin-bottom: 8px; }
  @media(max-width:800px){ .form-grid-input { grid-template-columns: 1fr repeat(3,1fr); } }
  .form-grid-input input {
    background: var(--surface2); border: 1px solid var(--border);
    border-radius: 7px; padding: 8px 10px; color: var(--text);
    font-family: 'DM Sans', sans-serif; font-size: .85rem; width: 100%;
    outline: none; transition: border-color .2s;
  }
  .form-grid-input input:focus { border-color: var(--accent); }
  .form-header { display: grid; grid-template-columns: 200px repeat(6, 1fr); gap: 8px; margin-bottom: 8px; }
  .form-header span { font-size: .72rem; text-transform: uppercase; letter-spacing: .8px; color: var(--muted); padding: 0 10px; }
  @media(max-width:800px){ .form-header { display: none; } }

  .btn { padding: 10px 22px; border-radius: 9px; font-family: 'DM Sans', sans-serif; font-size: .9rem; font-weight: 500; cursor: pointer; border: none; transition: opacity .2s, transform .15s; }
  .btn:hover { opacity: .85; transform: translateY(-1px); }
  .btn-primary { background: var(--accent); color: #0b0f1a; font-weight: 700; }
  .btn-secondary { background: var(--surface2); color: var(--text); border: 1px solid var(--border); }
  .btn-row { display: flex; gap: 10px; margin-top: 18px; flex-wrap: wrap; }

  .grade-key { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; }
  .key-item { display: flex; align-items: center; gap: 6px; font-size: .82rem; color: var(--muted); }
</style>
</head>
<body>
<div class="wrap">

  <nav class="topnav">
    <a href="index.php">🏫 Home</a>
    <span>/</span>
    <span class="current">📊 Performance</span>
  </nav>

  <div class="page-header">
    <h1>📊 Student Performance Analytics</h1>
    <p class="subtitle">Calculate averages, assign Pass / Fail / Distinction, identify the top student, and generate class statistics. Invalid marks are flagged and excluded gracefully.</p>
  </div>

  <?php if ($messages || $errors): ?>
  <div class="alerts">
    <?php foreach ($messages as $m): ?><div class="alert success">✅ <?= htmlspecialchars($m) ?></div><?php endforeach; ?>
    <?php foreach ($errors   as $e): ?><div class="alert error">⚠️ <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Grading key -->
  <div class="grade-key">
    <div class="key-item"><span class="grade g-distinction">Distinction</span> Average ≥ 75%</div>
    <div class="key-item"><span class="grade g-pass">Pass</span> Average 50–74%</div>
    <div class="key-item"><span class="grade g-fail">Fail</span> Average &lt; 50%</div>
  </div>

  <!-- Input forms -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:28px;">
    <!-- Demo data -->
    <div class="card">
      <h2>🎓 Demo Dataset</h2>
      <p style="font-size:.88rem;color:var(--muted);margin-bottom:14px;line-height:1.65;">
        Uses 6 pre-seeded students with 6 marks each. Includes intentionally invalid marks (out-of-range and non-numeric) to demonstrate validation.
      </p>
      <form method="POST">
        <button type="submit" name="analyse_default" value="1" class="btn btn-primary">▶ Run Demo Analysis</button>
      </form>
    </div>

    <!-- Custom data -->
    <div class="card">
      <h2>✏️ Custom Input</h2>
      <p style="font-size:.88rem;color:var(--muted);margin-bottom:16px;">Enter at least 4 students with 6 marks each (0–100).</p>
      <form method="POST">
        <div class="form-header">
          <span>Student Name</span>
          <span>Mark 1</span><span>Mark 2</span><span>Mark 3</span>
          <span>Mark 4</span><span>Mark 5</span><span>Mark 6</span>
        </div>
        <?php for ($i = 0; $i < 5; $i++): ?>
        <div class="form-grid-input">
          <input type="text" name="sname[]" placeholder="Student name">
          <?php for ($m = 0; $m < 6; $m++): ?>
          <input type="text" name="mark_<?= $i ?>_<?= $m ?>" placeholder="0-100">
          <?php endfor; ?>
        </div>
        <?php endfor; ?>
        <div class="btn-row">
          <button type="submit" name="analyse_custom" value="1" class="btn btn-primary">▶ Analyse Custom Data</button>
        </div>
      </form>
    </div>
  </div>

  <!-- ── RESULTS ── -->
  <?php if ($results && !empty($results['students'])): ?>

    <?php
      $students = $results['students'];
      $stats    = $results['stats'];
    ?>

    <!-- Class stats -->
    <div class="stats4">
      <div class="stat amber"><div class="label">Class Average</div><div class="value"><?= $stats['class_avg'] ?>%</div></div>
      <div class="stat green"><div class="label">Highest Average</div><div class="value"><?= $stats['highest_avg'] ?>%</div></div>
      <div class="stat red">  <div class="label">Lowest Average</div><div class="value"><?= $stats['lowest_avg'] ?>%</div></div>
      <div class="stat blue"> <div class="label">Students Analysed</div><div class="value"><?= $stats['total_students'] ?></div></div>
    </div>

    <!-- Top student banner -->
    <div class="top-banner">
      <div class="trophy">🏆</div>
      <div>
        <h3>Top Performer: <?= htmlspecialchars($stats['top_student']) ?></h3>
        <p>Highest class average of <?= $stats['highest_avg'] ?>% — awarded Distinction</p>
      </div>
    </div>

    <!-- Student results table -->
    <div class="card">
      <h2>📋 Student Results</h2>
      <table class="dt">
        <thead>
          <tr>
            <th>#</th>
            <th>Student</th>
            <th>Marks</th>
            <th>Average</th>
            <th>Grade</th>
          </tr>
        </thead>
        <tbody>
          <?php
            // Sort by average descending for display
            usort($students, fn($a,$b) => $b['average'] <=> $a['average']);
            foreach ($students as $rank => $s):
              $gradeClass = 'g-' . strtolower($s['grade']);
              $fillClass  = 'fill-' . strtolower($s['grade']);
              $isTop      = ($s['name'] === $stats['top_student']);
          ?>
          <tr class="<?= $isTop ? 'top-row' : '' ?>">
            <td style="color:var(--muted);font-size:.85rem;"><?= $rank+1 ?><?= $isTop ? ' 🏆' : '' ?></td>
            <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
            <td>
              <div class="mark-chips">
                <?php foreach ($s['marks'] as $mk): ?>
                  <span class="chip"><?= $mk ?></span>
                <?php endforeach; ?>
              </div>
            </td>
            <td>
              <div class="avg-bar-wrap">
                <div class="avg-bar"><div class="avg-fill <?= $fillClass ?>" style="width:<?= $s['average'] ?>%"></div></div>
                <span class="avg-num"><?= $s['average'] ?>%</span>
              </div>
            </td>
            <td><span class="grade <?= $gradeClass ?>"><?= $s['grade'] ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Grade distribution -->
    <?php
      $dist = ['Distinction' => 0, 'Pass' => 0, 'Fail' => 0];
      foreach ($students as $s) $dist[$s['grade']]++;
      $total = count($students);
    ?>
    <div class="card">
      <h2>📈 Grade Distribution</h2>
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;">
        <?php foreach ($dist as $grade => $count): ?>
        <div style="text-align:center;padding:20px;background:var(--surface2);border-radius:10px;border:1px solid var(--border);">
          <div style="font-family:'Syne',sans-serif;font-size:2rem;font-weight:800;color:<?= $grade==='Distinction'?'var(--accent)':($grade==='Pass'?'var(--green)':'var(--danger)') ?>;"><?= $count ?></div>
          <div style="font-size:.8rem;color:var(--muted);margin-top:4px;text-transform:uppercase;letter-spacing:1px;"><?= $grade ?></div>
          <div style="font-size:.75rem;color:var(--muted);margin-top:2px;"><?= $total > 0 ? round(($count/$total)*100) : 0 ?>%</div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

  <?php elseif ($results !== null): ?>
    <div class="alert warn">⚠️ No valid students could be processed. Check validation errors above.</div>
  <?php else: ?>
    <!-- Placeholder -->
    <div class="card" style="text-align:center;padding:52px 32px;">
      <div style="font-size:3rem;margin-bottom:16px;">📊</div>
      <h3 style="font-family:'Syne',sans-serif;font-size:1.2rem;font-weight:700;margin-bottom:8px;">No Analysis Yet</h3>
      <p style="color:var(--muted);font-size:.9rem;">Run the demo dataset or enter custom student data above to see results.</p>
    </div>
  <?php endif; ?>

</div>
</body>
</html>
