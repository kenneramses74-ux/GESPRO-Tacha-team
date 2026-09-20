<?php
/**
 * Mon équipe.
 *
 * L'ancien Mon_equipe.php permettait à un chef d'ajouter directement
 * n'importe quel utilisateur de la base à un projet, sans invitation ni
 * consentement, tout en utilisant une colonne inexistante
 * (`utilisateurs.id_utilisateur` au lieu de `id`), ce qui provoquait une
 * erreur SQL immédiate. Il coexistait avec un système d'invitation
 * (auth/invitation.php) jamais réellement utilisable, car aucune page
 * ne permettait de créer une invitation.
 *
 * Cette page unifie tout : le chef constitue son équipe via des liens
 * d'invitation (table `equipes` + `invitations`), et c'est depuis la
 * page d'un projet (projet.php) qu'il choisit qui, parmi son équipe,
 * travaille sur quel projet.
 */
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/functions.php';
protegerChef();

$pdo = db();
$uid = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT * FROM equipes WHERE id_chef = ? LIMIT 1');
$stmt->execute([$uid]);
$equipe = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCsrf();

    if (isset($_POST['creer_equipe'])) {
        $nom = trim($_POST['nom_equipe'] ?? '') ?: ('Équipe de ' . $_SESSION['prenom']);
        if (!$equipe) {
            $pdo->prepare('INSERT INTO equipes (nom_equipe, id_chef) VALUES (?, ?)')->execute([$nom, $uid]);
            setFlash('Équipe créée avec succès.');
        }
    } elseif (isset($_POST['renommer_equipe']) && $equipe) {
        $nom = trim($_POST['nom_equipe'] ?? '');
        if ($nom !== '') {
            $pdo->prepare('UPDATE equipes SET nom_equipe = ? WHERE id_equipe = ?')->execute([$nom, $equipe['id_equipe']]);
            setFlash('Équipe renommée.');
        }
    } elseif (isset($_POST['creer_invitation']) && $equipe) {
        $email = trim($_POST['email'] ?? '') ?: null;
        $token = bin2hex(random_bytes(16));
        $expiration = (new DateTime('+7 days'))->format('Y-m-d H:i:s');
        $pdo->prepare(
            'INSERT INTO invitations (token, email, id_equipe, id_chef, date_expiration) VALUES (?, ?, ?, ?, ?)'
        )->execute([$token, $email, $equipe['id_equipe'], $uid, $expiration]);
        setFlash('Invitation créée. Partagez le lien ci-dessous.');
    } elseif (isset($_POST['annuler_invitation']) && $equipe) {
        $idInvitation = (int)($_POST['id_invitation'] ?? 0);
        $pdo->prepare('DELETE FROM invitations WHERE id_invitation = ? AND id_equipe = ?')->execute([$idInvitation, $equipe['id_equipe']]);
        setFlash('Invitation annulée.');
    } elseif (isset($_POST['retirer_membre']) && $equipe) {
        $idMembre = (int)($_POST['id_utilisateur'] ?? 0);
        $pdo->prepare('DELETE FROM membres_equipes WHERE id_equipe = ? AND id_utilisateur = ?')->execute([$equipe['id_equipe'], $idMembre]);
        // Cohérence : on retire aussi la personne des projets de ce chef.
        $pdo->prepare(
            'DELETE pm FROM projet_membres pm INNER JOIN projets p ON p.id_projet = pm.id_projet
             WHERE p.id_chef = ? AND pm.id_utilisateur = ?'
        )->execute([$uid, $idMembre]);
        setFlash('Membre retiré de l’équipe.');
    }

    header('Location: equipe.php');
    exit;
}

$membres = $invitations = [];
if ($equipe) {
    $stmt = $pdo->prepare(
        'SELECT u.id_utilisateur, u.nom, u.prenom, u.email
         FROM menbres_equipes me INNER JOIN utilisateurs u ON u.id_utilisateur = me.id_utilisateur
         WHERE me.id_equipe = ? ORDER BY u.nom'
    );
    $stmt->execute([$equipe['id_equipe']]);
    $membres = $stmt->fetchAll();

    $stmt = $pdo->prepare(
        "SELECT * FROM invitations WHERE id_chef = ? AND utilise = 0
         AND (date_expiration IS NULL OR date_expiration > NOW()) ORDER BY id_invitation DESC"
    );
    $stmt->execute([$equipe['id_chef']]);
    $invitations = $stmt->fetchAll();
}

$pageTitle = 'Mon équipe';
require __DIR__ . '/../includes/header.php';
?>
<section class="content">
  <div class="page-header">
    <div>
      <span class="eyebrow">ÉQUIPE</span>
      <h1>👥 Mon équipe</h1>
      <p>Invitez des personnes à rejoindre votre équipe, puis affectez-les à vos projets.</p>
    </div>
  </div>

  <?php if (!$equipe): ?>
    <div class="card">
      <h2>Créer votre équipe</h2>
      <p>Vous n'avez pas encore d'équipe. Donnez-lui un nom pour commencer à inviter des membres.</p>
      <form method="post" class="inline-form">
        <?= csrfField() ?>
        <input type="text" name="nom_equipe" placeholder="Ex : Équipe Marketing">
        <button class="btn btn-primary" name="creer_equipe">Créer mon équipe</button>
      </form>
    </div>
  <?php else: ?>

    <div class="card">
      <h2>📛 <?= e($equipe['nom_equipe']) ?></h2>
      <form method="post" class="inline-form">
        <?= csrfField() ?>
        <input type="text" name="nom_equipe" value="<?= e($equipe['nom_equipe']) ?>">
        <button class="btn btn-secondary" name="renommer_equipe">Renommer</button>
      </form>
    </div>

    <div class="card">
      <h2>✉️ Inviter un membre</h2>
      <p class="hint">Générez un lien d'invitation, valable 7 jours, à envoyer par email ou messagerie.</p>
      <form method="post" class="inline-form">
        <?= csrfField() ?>
        <input type="email" name="email" placeholder="Email de la personne (optionnel)">
        <button class="btn btn-primary" name="creer_invitation">Générer un lien</button>
      </form>

      <?php if ($invitations): ?>
        <div class="table-wrap" style="margin-top:16px;">
          <table>
            <thead><tr><th>Destinataire</th><th>Expire le</th><th>Lien</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($invitations as $inv): ?>
              <tr>
                <td><?= e($inv['email'] ?: 'Non spécifié') ?></td>
                <td><?= formaterDate($inv['date_expiration']) ?></td>
                <td><code style="word-break:break-all;font-size:11px;"><?= e(url('/auth/invitation.php?token=' . $inv['token'])) ?></code></td>
                <td>
                  <form method="post" onsubmit="return confirm('Annuler cette invitation ?');">
                    <?= csrfField() ?>
                    <input type="hidden" name="id_invitation" value="<?= (int)$inv['id_invitation'] ?>">
                    <button type="submit" name="annuler_invitation" class="btn btn-secondary">Annuler</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <div class="card">
      <h2>👥 Membres de l'équipe (<?= count($membres) ?>)</h2>
      <?php if (!$membres): ?>
        <div class="empty">Aucun membre pour l'instant. Envoyez une invitation pour commencer.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Membre</th><th>Email</th><th>Depuis</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($membres as $m): ?>
              <tr>
                <td>👤 <?= e($m['prenom'] . ' ' . $m['nom']) ?></td>
                <td><?= e($m['email']) ?></td>
                <td><?= formaterDate($m['date_ajout']) ?></td>
                <td>
                  <form method="post" onsubmit="return confirm('Retirer ce membre de l’équipe et de tous vos projets ?');">
                    <?= csrfField() ?>
                    <input type="hidden" name="id_utilisateur" value="<?= (int)$m['id_utilisateur'] ?>">
                    <button type="submit" name="retirer_membre" class="btn btn-secondary">🗑️ Retirer</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
      <p class="hint">Pour affecter un membre à un projet précis, ouvrez ce projet depuis « Mes projets ».</p>
    </div>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
