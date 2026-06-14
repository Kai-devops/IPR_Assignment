<?php
//  index.php  –  Main Menu
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Campus Management System</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Syne:wght@400;600;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap');

  :root {
    --bg:       #0b0f1a;
    --surface:  #131929;
    --border:   #1f2d47;
    --accent1:  #3b82f6;
    --accent2:  #10b981;
    --accent3:  #f59e0b;
    --text:     #e2e8f0;
    --muted:    #64748b;
    --radius:   16px;
  }

  * { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'DM Sans', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
    padding: 60px 20px 40px;
    background-image:
      radial-gradient(ellipse 80% 40% at 50% 0%, rgba(59,130,246,.15) 0%, transparent 60%);
  }

  header { text-align: center; margin-bottom: 56px; }
  header .badge {
    display: inline-block;
    font-family: 'Syne', sans-serif;
    font-size: 11px;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: var(--accent1);
    background: rgba(59,130,246,.12);
    border: 1px solid rgba(59,130,246,.3);
    padding: 6px 18px;
    border-radius: 50px;
    margin-bottom: 24px;
  }
  h1 {
    font-family: 'Syne', sans-serif;
    font-size: clamp(2rem, 5vw, 3.4rem);
    font-weight: 800;
    letter-spacing: -1px;
    line-height: 1.1;
    margin-bottom: 14px;
  }
  h1 span { color: var(--accent1); }
  header p {
    font-size: 1.05rem;
    color: var(--muted);
    max-width: 480px;
    margin: 0 auto;
    line-height: 1.7;
  }

  .grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 24px;
    width: 100%;
    max-width: 980px;
  }

  .card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 36px 32px;
    text-decoration: none;
    color: inherit;
    display: flex;
    flex-direction: column;
    gap: 14px;
    transition: transform .25s ease, border-color .25s ease, box-shadow .25s ease;
    position: relative;
    overflow: hidden;
  }
  .card::before {
    content: '';
    position: absolute;
    inset: 0;
    opacity: 0;
    transition: opacity .3s ease;
    border-radius: var(--radius);
  }
  .card:hover { transform: translateY(-6px); box-shadow: 0 24px 60px rgba(0,0,0,.4); }
  .card.blue:hover  { border-color: var(--accent1); box-shadow: 0 24px 60px rgba(59,130,246,.2); }
  .card.green:hover { border-color: var(--accent2); box-shadow: 0 24px 60px rgba(16,185,129,.2); }
  .card.amber:hover { border-color: var(--accent3); box-shadow: 0 24px 60px rgba(245,158,11,.2); }

  .icon {
    font-size: 2.4rem;
    width: 60px; height: 60px;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-style: normal;
  }
  .blue  .icon { background: rgba(59,130,246,.15); }
  .green .icon { background: rgba(16,185,129,.15); }
  .amber .icon { background: rgba(245,158,11,.15);  }

  .card-num {
    font-family: 'Syne', sans-serif;
    font-size: 11px;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--muted);
  }
  .blue  .card-num { color: var(--accent1); }
  .green .card-num { color: var(--accent2); }
  .amber .card-num { color: var(--accent3); }

  h3 {
    font-family: 'Syne', sans-serif;
    font-size: 1.25rem;
    font-weight: 700;
  }
  .card p {
    font-size: .92rem;
    color: var(--muted);
    line-height: 1.65;
    flex: 1;
  }

  .arrow {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: .88rem;
    font-weight: 500;
    margin-top: 8px;
  }
  .blue  .arrow { color: var(--accent1); }
  .green .arrow { color: var(--accent2); }
  .amber .arrow { color: var(--accent3); }

  footer {
    margin-top: 56px;
    font-size: .82rem;
    color: var(--muted);
    text-align: center;
    border-top: 1px solid var(--border);
    padding-top: 24px;
    width: 100%;
    max-width: 980px;
  }
</style>
</head>
<body>

<header>
  <div class="badge">IPR 621 &nbsp;·&nbsp; Assignment</div>
  <h1>Campus <span>Management</span><br>System</h1>
  <p>A unified platform for parking permits, library borrowing &amp; fines, and student performance analytics.</p>
</header>

<div class="grid">

  <a href="parking.php" class="card blue">
    <div class="icon">🚗</div>
    <h3>Parking Permit<br>Management</h3>
    <p>Issue and track Student, Staff, and Visitor parking permits with capacity controls and revenue reporting.</p>
    <span class="arrow">Open Module &rarr;</span>
  </a>

  <a href="library.php" class="card green">
    <div class="icon">📚</div>
    <h3>Library Borrowing<br>&amp; Fines</h3>
    <p>Manage book loans across Textbooks, Journals, and Reference Books with automatic fine calculation and borrowing restrictions.</p>
    <span class="arrow">Open Module &rarr;</span>
  </a>

  <a href="performance.php" class="card amber">
    <div class="icon">📊</div>
    <h3>Student Performance<br>Analytics</h3>
    <p>Analyse student marks, assign Pass / Fail / Distinction grades, identify top performers, and generate class-wide statistics.</p>
    <span class="arrow">Open Module &rarr;</span>
  </a>

</div>

<footer>
  &copy; <?php echo date('Y'); ?> Campus Management System &nbsp;·&nbsp; IPR 621 &nbsp;·&nbsp; Richfield

</body>
</html>
