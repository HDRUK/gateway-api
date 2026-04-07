<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+3:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
 
        :root {
            --bg:             #f0f2f5;
            --card:           #ffffff;
            --border:         #e2e6ea;
            --text-primary:   #1a1d23;
            --text-secondary: #6b7280;
            --accent:         #2d5a8e;
            --tag-bg:         #1a6b4a;
            --tag-text:       #fff;
            --radius:         10px;
            --shadow:         0 1px 4px rgba(0, 0, 0, 0.07);
        }
 
        body {
            font-family: 'Source Sans 3', sans-serif;
            background: var(--bg);
            color: var(--text-primary);
            min-height: 100vh;
        }
 
        .dashboard {
            max-width: 800px;
            margin: 0 auto;
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
 
        /* ── Resources Card ── */
        .resources-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: var(--shadow);
        }
 
        .resources-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
 
        .resources-header h2 {
            font-size: 16px;
            font-weight: 600;
        }
 
        .period-picker {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 13px;
            font-weight: 500;
        }
 
        .period-picker .period-label {
            color: var(--text-secondary);
            font-weight: 600;
            margin-right: 4px;
        }
 
        .period-picker .period-range {
            color: var(--text-secondary);
            font-weight: 400;
            letter-spacing: 0.01em;
        }
 
        /* ── Stats Grid ── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }
 
        .stat-cell {
            background: var(--bg);
            border-radius: 8px;
            padding: 16px 14px 14px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            justify-content: flex-start;
        }
 
        .stat-cell .num {
            font-size: 32px;
            font-weight: 600;
            color: var(--text-primary);
            line-height: 1;
        }
 
        .stat-cell .label {
            font-size: 12px;
            color: var(--text-secondary);
            font-weight: 400;
        }
 
        .stat-cell .badge {
            margin-top: 6px;
            padding: 3px 10px;
            border-radius: 20px;
            background: var(--tag-bg);
            color: var(--tag-text);
            font-size: 11px;
            font-weight: 500;
            white-space: nowrap;
        }
 
        .stat-cell .badge-placeholder {
            display: block;
            height: 23px;
            margin-top: 6px;
            visibility: hidden;
        }
 
        /* ── Two-column layout ── */
        .two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
 
        /* ── Panel ── */
        .panel {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: var(--shadow);
        }
 
        .panel h3 {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 12px;
        }
 
        /* ── Chart ── */
        .chart-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
        }
 
        .chart-header h3 {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 0;
        }
 
        .chart-area {
            position: relative;
            height: 170px;
        }
 
        .chart-area img {
            width: 100%;
            height: 100%;
            object-fit: fill;
        }
 
        /* ── List rows ── */
        .list-rows {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: 4px;
        }
 
        .list-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--bg);
            border-radius: 7px;
            padding: 12px 14px;
        }
 
        .list-row .row-label {
            font-size: 13px;
            color: var(--text-primary);
        }
 
        .list-row .row-num {
            font-size: 20px;
            font-weight: 600;
            color: var(--text-primary);
        }
    </style>
</head>
<body>
<div class="dashboard">
 
    {{-- ── Resources Card ── --}}
    <div class="resources-card">
        <div class="resources-header">
            <h2>Your resources</h2>
            <div class="period-picker">
                <span class="period-label">Period</span>
                <span class="period-range">{{ $startDate }} – {{ $endDate }}</span>
            </div>
        </div>
 
        <div class="stats-grid">
            <div class="stat-cell">
                <span class="num">235</span>
                <span class="label">Datasets</span>
                <span class="badge">5 added this period</span>
            </div>
            <div class="stat-cell no-badge">
                <span class="num">10</span>
                <span class="label">Data Uses</span>
                    <span class="badge-placeholder">placeholder</span>
            </div>
            <div class="stat-cell">
                <span class="num">34</span>
                <span class="label">Analysis Scripts</span>
                <span class="badge">1 added this period</span>
            </div>
            <div class="stat-cell">
                <span class="num">12</span>
                <span class="label">Publications</span>
                <span class="badge">2 added this period</span>
            </div>
            <div class="stat-cell no-badge">
                <span class="num">12</span>
                <span class="label">Collections</span>
                    <span class="badge-placeholder">placeholder</span>
            </div>
        </div>
    </div>
 
    {{-- ── Charts ── --}}
    <div class="two-col">
        <div class="panel">
            <div class="chart-header">
                <h3>360 Dataset views</h3>
            </div>
            <div class="chart-area">
                 <div class="chart-svg">{!! $lineChartSvg !!}</div>
            </div>
        </div>
 
        <div class="panel">
            <div class="chart-header">
                <h3>Most Dataset Views</h3>
            </div>
            <div class="chart-area">
                <div class="chart-svg">{!! $barChartSvg !!}</div>
            </div>
        </div>
    </div>
 
    {{-- ── Other Views + Enquiries ── --}}
    <div class="two-col">
        <div class="panel">
      <h3>Other views</h3>
      <div class="list-rows">
        <div class="list-row">
          <span class="row-label">Collections</span>
          <span class="row-num">12</span>
        </div>
        <div class="list-row">
          <span class="row-label">Data Custodian page</span>
          <span class="row-num">123</span>
        </div>
      </div>
    </div>
 
        <div class="panel">
      <h3>Enquiries and requests</h3>
      <div class="list-rows">
        <div class="list-row">
          <span class="row-label">General enquiries</span>
          <span class="row-num">12</span>
        </div>
        <div class="list-row">
          <span class="row-label">Feasibility enquiries</span>
          <span class="row-num">14</span>
        </div>
        <div class="list-row">
          <span class="row-label">Data Access Requests</span>
          <span class="row-num">16</span>
        </div>
      </div>
    </div>
    </div>
 
</div>
</body>
</html>