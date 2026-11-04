<?php
require __DIR__ . '/../src/bootstrap.php';
$error = null;
$connectionError = false;
try {
    $pdo = db();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
            http_response_code(403);
            $error = 'Die Sitzung ist abgelaufen. Bitte lade die Seite neu.';
        } else {
            $action = $_POST['action'] ?? '';
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($action === 'save') {
                $title = trim((string) ($_POST['title'] ?? ''));
                $description = trim((string) ($_POST['description'] ?? ''));
                $color = (string) ($_POST['color'] ?? '');
                $start = parseDate((string) ($_POST['starts_at'] ?? ''));
                $due = parseDate((string) ($_POST['due_at'] ?? ''));
                $mainInput = (string) ($_POST['main_at'] ?? '');
                $main = $mainInput === '' ? null : parseDate($mainInput);
                if ($title === '' || strlen($title) > 640 || preg_match_all('/./us', $title) > 160 || strlen($description) > 10000 || !preg_match('/^#[a-fA-F0-9]{6}$/', $color) || !$start || !$due || $due <= $start || ($mainInput !== '' && (!$main || $main < $due))) {
                    http_response_code(422);
                    $error = 'Bitte prüfe deine Eingaben: Titel bis 160 Zeichen, Abgabe nach dem Start und Haupttermin frühestens zur Abgabe.';
                } else {
                    $values = [$title, $description, $color, $start->format('Y-m-d H:i:s'), $due->format('Y-m-d H:i:s'), $main?->format('Y-m-d H:i:s')];
                    if ($id) {
                        $values[] = $id;
                        $pdo->prepare('UPDATE todos SET title=?, description=?, color=?, starts_at=?, due_at=?, main_at=? WHERE id=?')->execute($values);
                    } else {
                        $pdo->prepare('INSERT INTO todos (title,description,color,starts_at,due_at,main_at) VALUES (?,?,?,?,?,?)')->execute($values);
                    }
                }
            } elseif ($id && in_array($action, ['toggle', 'delete'], true)) {
                $pdo->prepare($action === 'delete' ? 'DELETE FROM todos WHERE id=?' : 'UPDATE todos SET completed_at=IF(completed_at IS NULL, NOW(), NULL) WHERE id=?')->execute([$id]);
            } else {
                http_response_code(400);
                $error = 'Ungültige Aktion.';
            }
            if (!$error) { header('Location: /', true, 303); exit; }
        }
    }
    $todos = $pdo->query('SELECT * FROM todos ORDER BY completed_at IS NOT NULL, due_at, id DESC')->fetchAll();
} catch (PDOException $ex) {
    error_log($ex->getMessage());
    http_response_code(503);
    $connectionError = true;
    $error = 'Die Datenbank ist gerade nicht erreichbar. Bitte versuche es gleich erneut.';
    $todos = [];
}
$now = new DateTimeImmutable();
$active = array_filter($todos, fn($t) => !$t['completed_at']);
$overdue = count(array_filter($active, fn($t) => new DateTimeImmutable($t['due_at']) < $now));
$done = count($todos) - count($active);
function localInput(string $value): string { return (new DateTimeImmutable($value))->format('Y-m-d\TH:i'); }
?>
<!doctype html>
<html lang="de">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Deadline — Dein Überblick</title><link rel="stylesheet" href="/style.css"><script src="/app.js" defer></script></head>
<body>
<aside class="sidebar"><a class="brand" href="/" aria-label="Deadline Startseite"><span class="brand-icon">◷</span> deadline<span class="brand-dot">.</span></a><div class="workspace">DEIN ARBEITSPLATZ</div><a class="nav active" href="/"><span>▦</span> Meine To-dos <span class="nav-count"><?= count($active) ?></span></a><div class="sidebar-bottom"><span class="little-dot"></span> Ein Schritt nach dem anderen.<br><small>Du hast die Zeit im Blick.</small></div></aside>
<main><header><div class="breadcrumb">Arbeitsplatz <span>/</span> Meine To-dos</div><span class="today"><?= $now->format('d.m.Y') ?></span></header>
<section class="heading"><div><div class="eyebrow">PLATZ FÜR DEINE PLÄNE</div><h1>Alles zu seiner Zeit<span>.</span></h1><p>Deine Aufgaben, deine Termine. Ein klarer Überblick.</p></div><button class="primary" id="add-task" <?= $connectionError ? 'disabled' : '' ?>><b>＋</b> Neues To-do</button></section>
<?php if ($error): ?><div class="error" role="alert"><?= e($error) ?></div><?php endif ?>
<section class="stats" aria-label="Zusammenfassung"><div><span class="stat-icon purple">▤</span><div><span>Offene To-dos</span><strong><?= count($active) ?></strong></div></div><div><span class="stat-icon orange">◷</span><div><span>Überfällig</span><strong><?= $overdue ?></strong></div></div><div><span class="stat-icon green">✓</span><div><span>Abgeschlossen</span><strong><?= $done ?></strong></div></div></section>
<section class="task-section"><div class="list-toolbar"><div class="tabs" role="group" aria-label="Aufgaben filtern"><button class="selected" data-filter="active">Offen <span><?= count($active) ?></span></button><button data-filter="done">Abgeschlossen <span><?= $done ?></span></button><button data-filter="all">Alle</button></div><label class="search"><span>⌕</span><input id="search" type="search" placeholder="To-dos durchsuchen …" aria-label="To-dos durchsuchen"></label></div>
<div class="list-caption"><span>DEINE TO-DOS</span><span>Nach Abgabetermin sortiert</span></div>
<div class="cards">
<?php foreach ($todos as $todo):
$start = new DateTimeImmutable($todo['starts_at']); $due = new DateTimeImmutable($todo['due_at']); $completed = (bool) $todo['completed_at'];
$edit = array_intersect_key($todo, array_flip(['id','title','description','color']));
foreach (['starts_at','due_at','main_at'] as $key) $edit[$key] = $todo[$key] ? localInput($todo[$key]) : '';
?>
<article class="task-card <?= $completed ? 'completed' : '' ?>" style="--task-color:<?= e($todo['color']) ?>" data-done="<?= $completed ? '1' : '0' ?>" data-title="<?= e($todo['title']) ?>" data-start="<?= $start->getTimestamp() ?>" data-due="<?= $due->getTimestamp() ?>">
<div class="card-top"><form method="post" class="toggle-form"><input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>"><input type="hidden" name="id" value="<?= $todo['id'] ?>"><input type="hidden" name="action" value="toggle"><button class="check" aria-label="<?= $completed ? 'Wieder öffnen' : 'Als erledigt markieren' ?>: <?= e($todo['title']) ?>"><?= $completed ? '✓' : '' ?></button></form><h2><?= e($todo['title']) ?></h2><button class="edit-button" data-task="<?= e(json_encode($edit, JSON_UNESCAPED_UNICODE)) ?>" aria-label="To-do bearbeiten">Bearbeiten ↗</button></div>
<?php if ($todo['description']): ?><p class="description"><?= nl2br(e($todo['description'])) ?></p><?php endif ?>
<div class="progress-label"><span class="time-left"><?= $completed ? 'Abgeschlossen' : 'Restzeit wird berechnet …' ?></span><span class="percent"></span></div><div class="progress-track" role="progressbar" aria-label="Verbleibende Zeit bis zur Abgabe" aria-valuemin="0" aria-valuemax="100"><div class="progress-fill"></div></div>
<div class="card-footer"><div><span class="date-label">↳ Abgabe</span><time datetime="<?= $due->format('c') ?>"><?= $due->format('d.m.Y · H:i') ?></time></div><?php if ($todo['main_at']): $main = new DateTimeImmutable($todo['main_at']); ?><div><span class="date-label">⚑ Haupttermin</span><time datetime="<?= $main->format('c') ?>"><?= $main->format('d.m.Y · H:i') ?></time></div><?php else: ?><span class="no-main">Kein Haupttermin</span><?php endif ?></div>
</article>
<?php endforeach ?>
</div><div id="empty" class="empty" hidden><div class="empty-icon">✓</div><h2><?= $todos ? 'Hier ist alles übersichtlich.' : 'Dein nächster Schritt beginnt hier.' ?></h2><p id="empty-copy">Lege dein erstes To-do an und behalte deine Termine im Blick.</p></div>
</section><footer class="page-footer"><span class="little-dot"></span> Mehr Überblick. Weniger Kopfchaos.<span>Deine Zeit zählt.</span></footer>
</main>
<dialog id="task-dialog"><form method="post" id="task-form"><div class="dialog-heading"><div><div class="eyebrow">GUT GEPLANT IST HALB GESCHAFFT</div><h2 id="dialog-title">Neues To-do</h2></div><button type="button" id="close-dialog" class="close" aria-label="Schließen">×</button></div><input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value=""><label>Titel<input name="title" maxlength="160" required placeholder="Was möchtest du erledigen?"></label><label>Notizen <span class="optional">optional</span><textarea name="description" maxlength="10000" rows="3" placeholder="Hier ist Platz für die Details …"></textarea></label><div class="date-fields"><label>Start<input name="starts_at" type="datetime-local" required></label><label>Abgabetermin<input name="due_at" type="datetime-local" required></label></div><label>Haupttermin <span class="optional">optional</span><input name="main_at" type="datetime-local"></label><p class="field-help">Die Abgabe ist deine Deadline. Der Haupttermin ist zum Beispiel die Präsentation oder Veranstaltung danach.</p><label class="color-label">Deine Farbe <input type="color" name="color" value="#7560df"></label><p id="form-error" class="form-error" role="alert"></p><div class="dialog-actions"><button type="button" id="delete-task" class="danger" hidden>Löschen</button><button type="submit" class="primary">To-do speichern</button></div></form></dialog>
</body></html>
