<?php
declare(strict_types=1);

$cfg = require __DIR__ . '/../config.php';

try {
    $pdo = new PDO(
        "mysql:host={$cfg['host']};dbname={$cfg['db']};charset=utf8mb4",
        $cfg['user'],
        $cfg['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('Erreur de connexion à la base de données.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    if ($action === 'add') {
        $titre = trim((string)($_POST['titre'] ?? ''));
        if ($titre !== '' && mb_strlen($titre) <= 150) {
            $pdo->prepare('INSERT INTO taches (titre) VALUES (?)')->execute([$titre]);
        }
    } elseif ($action === 'toggle' && $id > 0) {
        $pdo->prepare('UPDATE taches SET fait = 1 - fait WHERE id = ?')->execute([$id]);
    } elseif ($action === 'delete' && $id > 0) {
        $pdo->prepare('DELETE FROM taches WHERE id = ?')->execute([$id]);
    }
    header('Location: /');
    exit;
}

$taches = $pdo->query('SELECT id, titre, fait, cree_le FROM taches ORDER BY id DESC')->fetchAll();
$h = fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gestionnaire de tâches</title>
  <style>
    body { font-family: Arial, sans-serif; max-width: 640px; margin: 2rem auto; padding: 0 1rem; color: #1f2937; }
    h1 { color: #1f4e9e; }
    form.add { display: flex; gap: .5rem; margin-bottom: 1.5rem; }
    input[type=text] { flex: 1; padding: .5rem; }
    button { padding: .4rem .8rem; cursor: pointer; }
    ul { list-style: none; padding: 0; }
    li { display: flex; align-items: center; gap: .5rem; padding: .5rem 0; border-bottom: 1px solid #e5e7eb; }
    li .titre { flex: 1; }
    li.fait .titre { text-decoration: line-through; color: #6b7280; }
    small { color: #6b7280; }
  </style>
</head>
<body>
  <h1>Gestionnaire de tâches</h1>
  <form class="add" method="post">
    <input type="hidden" name="action" value="add">
    <input type="text" name="titre" maxlength="150" placeholder="Nouvelle tâche" required>
    <button type="submit">Ajouter</button>
  </form>
  <p><?= count($taches) ?> tâche(s) en base.</p>
  <ul>
  <?php foreach ($taches as $t): ?>
    <li class="<?= $t['fait'] ? 'fait' : '' ?>">
      <span class="titre"><?= $h($t['titre']) ?></span>
      <small><?= $h($t['cree_le']) ?></small>
      <form method="post"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$t['id'] ?>"><button><?= $t['fait'] ? 'Rouvrir' : 'Terminer' ?></button></form>
      <form method="post"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$t['id'] ?>"><button>Supprimer</button></form>
    </li>
  <?php endforeach; ?>
  </ul>
</body>
</html>
