<?php
declare(strict_types=1);

/**
 * /private/functions/igbo-calendar_render.php
 *
 * Compact 4-column month grid (Eke/Orie/Afo/Nkwo) renderer.
 *
 * Marketday checkpoint anchor (confirmed truth):
 * - 2026-01-07 = Nkwo
 *
 * IMPORTANT:
 * - We recompute marketday for each date using the checkpoint.
 * - This prevents drift.
 *
 * NOTE:
 * - This file renders HTML.
 * - Styling must come from: /public/igbo-calendar/igbo-calendar.css
 */

if (!function_exists('h')) {
  function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
}

if (!class_exists('IgboCalendarYear')) {
  if (defined('PRIVATE_PATH')) {
    require_once PRIVATE_PATH . '/calendar/IgboCalendarYear.php';
  }
}

/* -----------------------------
   Market anchor helpers
----------------------------- */
function mk_market_anchor(): array {
  return [
    'date' => new DateTimeImmutable('2026-01-07 00:00:00', new DateTimeZone('UTC')),
    'idx'  => 3, // 0=Eke,1=Orie,2=Afo,3=Nkwo
  ];
}

function mk_marketday_idx_from_iso(string $isoYmd): int {
  $tz = new DateTimeZone('UTC');
  $anchor = mk_market_anchor();

  $d0 = new DateTimeImmutable($anchor['date']->format('Y-m-d') . ' 00:00:00', $tz);
  $d1 = new DateTimeImmutable($isoYmd . ' 00:00:00', $tz);

  $diff = (int)(($d1->getTimestamp() - $d0->getTimestamp()) / 86400);
  $idx = ($anchor['idx'] + ($diff % 4)) % 4;
  if ($idx < 0) $idx += 4;
  return $idx;
}

function mk_marketday_name(int $idx): string {
  $idx = ($idx % 4 + 4) % 4;
  return ['Eke', 'Orie', 'Afo', 'Nkwo'][$idx];
}

function mk_marketday_css(string $name): string {
  $n = function_exists('mb_strtolower') ? mb_strtolower(trim($name)) : strtolower(trim($name));
  if ($n === 'eke') return 'eke';
  if ($n === 'orie' || $n === 'ọrie') return 'orie';
  if ($n === 'afo' || $n === 'afọ') return 'afo';
  if ($n === 'nkwo' || $n === 'ńkwọ' || $n === 'kwọ') return 'nkwo';
  return 'eke';
}

/**
 * Render a full year (months) from an approx start date.
 */
function render_igbo_calendar(DateTimeImmutable $approxStart, int $igboYearIndex, string $anchorMarketDay = 'Nkwo'): string {
  if (!class_exists('IgboCalendarYear')) {
    return '<div class="mk-cal"><div class="mk-cal__note muted">Calendar engine missing (IgboCalendarYear).</div></div>';
  }

  $year = new IgboCalendarYear($approxStart, $igboYearIndex, $anchorMarketDay);

  $tz = new DateTimeZone('UTC');
  $todayIso = (new DateTimeImmutable('now', $tz))->format('Y-m-d');

  $cols = ['Eke', 'Orie', 'Afo', 'Nkwo'];

  ob_start();
  ?>
<section class="igbo-calendar-app" aria-label="Igbo Calendar" data-app="igbo-calendar">

  <header class="igbo-calendar-header">
    <h1><?= h($year->getYearLabel()) ?></h1>
    <p class="subtitle">Igbo Calendar · <?= $year->isLeapYear() ? 'Gregorian Leap Year' : 'Gregorian Standard Year' ?></p>
    <p class="subtitle small">Checkpoint: <strong>2026-01-07 = Nkwo</strong></p>
  </header>

  <div class="mk-cal">
    <div class="mk-cal__note muted">
      4-day week: Eke / Orie / Afo / Nkwo. Month start aligns to prior month; blanks are intentional.
    </div>

    <div class="months-container">
      <?php
        $monthNo = 0;
        foreach ($year->getMonths() as $month):
          $monthNo++;

          // Normalize month day data with corrected marketdays (checkpoint-based).
          $days = [];
          foreach (($month['days'] ?? []) as $day) {
            $greg = (string)($day['gregorian'] ?? '');
            if ($greg === '') continue;

            $idx = mk_marketday_idx_from_iso($greg);
            $market = mk_marketday_name($idx);

            $days[] = [
              'igboDay'     => (string)($day['igboDay'] ?? ''),
              'gregorian'   => $greg,
              'weekday'     => (string)($day['weekday'] ?? ''),
              'marketDay'   => $market,
              'moonSymbol'  => (string)($day['moonSymbol'] ?? ''),
              'moonStage'   => (string)($day['moonStage'] ?? ''),
            ];
          }

          $daysInMonth = count($days);
          $startCol = ($daysInMonth > 0) ? mk_marketday_idx_from_iso($days[0]['gregorian']) : 0;

          $totalCells = $startCol + $daysInMonth;
          $rows = (int)ceil($totalCells / 4);

          $monthName = (string)($month['name'] ?? ('Month ' . $monthNo));
          $gregStart = (string)($month['gregorianRef']['start'] ?? '');
          $gregEnd   = (string)($month['gregorianRef']['end'] ?? '');
      ?>

      <section class="igcal-month mk-cal-month"
         data-month="<?= (int)$monthNo ?>"
         data-ig-month="<?= (int)$monthNo ?>"
         data-month-index="<?= (int)$monthNo ?>"
         aria-label="<?= h($monthName) ?>"
         <?php if ($monthNo !== 1) echo 'hidden'; ?>>

        <div class="mk-cal-month__head">
          <h2 class="mk-cal-month__title"><?= h($monthName) ?></h2>
          <div class="muted mk-cal-month__meta">
            <span class="pill">Month <?= (int)$monthNo ?></span>
            <span class="pill"><?= (int)$daysInMonth ?> days</span>
            <span class="pill">Gregorian: <?= h($gregStart) ?> – <?= h($gregEnd) ?></span>
          </div>

          <div class="mk-cal-legend" aria-hidden="true">
            <span class="mk-cal-legend__item"><strong>Eke</strong> = Fire</span>
            <span class="mk-cal-legend__dot">•</span>
            <span class="mk-cal-legend__item"><strong>Orie</strong> = Water</span>
            <span class="mk-cal-legend__dot">•</span>
            <span class="mk-cal-legend__item"><strong>Afo</strong> = Earth</span>
            <span class="mk-cal-legend__dot">•</span>
            <span class="mk-cal-legend__item"><strong>Nkwo</strong> = Air</span>
          </div>
        </div>

        <div class="mk-cal-table-wrap">
          <table class="mk-cal-grid" aria-label="<?= h($monthName) ?> month">
            <thead>
              <tr>
                <?php foreach ($cols as $c): ?>
                  <th scope="col"><?= h($c) ?></th>
                <?php endforeach; ?>
              </tr>
            </thead>
            <tbody>
              <?php for ($r = 0; $r < $rows; $r++): ?>
                <tr class="igcal-week" data-ig-row="<?= (int)($r + 1) ?>">
                  <?php for ($c = 0; $c < 4; $c++):
                    $cellNumber = ($r * 4) + $c;

                    if ($cellNumber < $startCol) {
                      echo '<td class="mk-cal-empty">&nbsp;</td>';
                      continue;
                    }

                    $effective = $cellNumber - $startCol;

                    if ($effective >= 0 && $effective < $daysInMonth) {
                      $d = $days[$effective];
                      $iso = (string)$d['gregorian'];
                      $isToday = ($todayIso === $iso);
                      $marketCss = mk_marketday_css($d['marketDay']);

                      echo '<td class="' . ($isToday ? 'mk-cal-cell--today ' : '') . 'market-' . h($marketCss) . '"'
                         . ' data-iso="' . h($iso) . '"'
                         . ' data-igbo-day="' . h($d['igboDay']) . '"'
                         . ' data-market="' . h($d['marketDay']) . '">';

                      echo '  <div class="mk-cal-cell">';
                      echo '    <div class="mk-cal-cell__top">';
                      echo '      <div class="mk-cal-cell__day">Day ' . h($d['igboDay']) . '</div>';
                      echo '      <div class="mk-cal-cell__date muted">' . h($iso) . '</div>';
                      echo '    </div>';

                      if ($isToday) {
                        echo '    <div class="mk-cal-today-badge" aria-label="Today">TODAY</div>';
                      }

                      echo '    <div class="mk-cal-kv">';
                      echo '      <div><strong>Weekday:</strong> ' . h($d['weekday']) . '</div>';
                      echo '      <div><strong>Moon:</strong> ' . h($d['moonSymbol']) . '</div>';
                      echo '      <div><strong>Stage:</strong> ' . h($d['moonStage']) . '</div>';
                      echo '    </div>';
                      echo '  </div>';

                      echo '</td>';
                    } else {
                      echo '<td class="mk-cal-empty">&nbsp;</td>';
                    }
                  endfor; ?>
                </tr>
              <?php endfor; ?>
            </tbody>
          </table>
        </div>

      </section>

      <?php endforeach; ?>
    </div>
  </div>

  <script>
  (function(){
    // Smooth-scroll to today once per load (if present)
    var el = document.querySelector('.igbo-calendar-app .mk-cal-cell--today');
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
  })();
  </script>

</section>
<?php
  return ob_get_clean();
}
