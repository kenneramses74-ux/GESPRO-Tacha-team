<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/functions.php';
protegerPage();

$pdo = db();
$uid = (int)$_SESSION['user_id'];

$pdo->prepare('UPDATE notifications SET lu = 1 WHERE id_utilisateur = ?')->execute([$uid]);

$stmt = $pdo->prepare('SELECT * FROM notifications WHERE id_utilisateur = ? ORDER BY id_notification DESC LIMIT 100');
$stmt->execute([$uid]);
$notifications = $stmt->fetchAll();

$pageTitle = 'Notifications';
require __DIR__ . '/../includes/header.php';
?>
<section class="content">
  <div class="page-header">
    <div><span class="eyebrow">ACTIVITÉ</span><h1>🔔 Notifications</h1></div>
  </div>
  <?php if (!$notifications): ?>
    <div class="card empty">Aucune notification pour le moment.</div>
  <?php else: ?>
    <?php foreach ($notifications as $n): ?>
      <div class="card">
        <strong><?= e($n['message']) ?></strong>
        <p><?= e(date('d/m/Y à H:i', strtotime($n['date_creation']))) ?></p>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
