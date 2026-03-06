<?php
require __DIR__ . "/config.php";
require __DIR__ . "/auth.php";

// Sécurité : admin seulement
if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    die("Accès refusé.");
}

$msg = "";
$err = "";

/**
 * Helpers
 */
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function valid_username(string $u): bool { return (bool)preg_match('/^[a-zA-Z0-9_.-]{3,30}$/', $u); }

// ----- Action : changer son propre mot de passe -----
if (isset($_POST['action']) && $_POST['action'] === 'change_my_password') {
    $old = $_POST['old_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $new2 = $_POST['new_password2'] ?? '';

    if (strlen($new) < 6) {
        $err = "Le nouveau mot de passe doit faire au moins 6 caractères.";
    } elseif ($new !== $new2) {
        $err = "La confirmation ne correspond pas.";
    } else {
        $st = $pdo->prepare("SELECT password_hash FROM users WHERE id = ? LIMIT 1");
        $st->execute([$_SESSION['user_id']]);
        $u = $st->fetch();

        if (!$u || !password_verify($old, $u['password_hash'])) {
            $err = "Ancien mot de passe incorrect.";
        } else {
            $hash = password_hash($new, PASSWORD_BCRYPT);
            $up = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ? LIMIT 1");
            $up->execute([$hash, $_SESSION['user_id']]);
            $msg = "✅ Mot de passe mis à jour.";
        }
    }
}

// ----- Action : créer un utilisateur -----
if (isset($_POST['action']) && $_POST['action'] === 'create_user') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';

    if (!in_array($role, ['admin','user'], true)) $role = 'user';

    if (!valid_username($username)) {
        $err = "Nom d'utilisateur invalide (3-30 caractères, lettres/chiffres/._-).";
    } elseif (strlen($password) < 6) {
        $err = "Mot de passe trop court (min 6).";
    } else {
        $st = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
        $st->execute([$username]);
        if ($st->fetch()) {
            $err = "Ce nom d'utilisateur existe déjà.";
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $ins = $pdo->prepare("INSERT INTO users (username, password_hash, role) VALUES (?,?,?)");
            $ins->execute([$username, $hash, $role]);
            $msg = "✅ Utilisateur créé : {$username} ({$role})";
        }
    }
}

// ----- Action : changer rôle utilisateur -----
if (isset($_POST['action']) && $_POST['action'] === 'set_role') {
    $uid = (int)($_POST['uid'] ?? 0);
    $role = $_POST['role'] ?? 'user';
    if (!in_array($role, ['admin','user'], true)) $role = 'user';

    if ($uid <= 0) {
        $err = "Utilisateur invalide.";
    } elseif ($uid === (int)$_SESSION['user_id']) {
        $err = "Tu ne peux pas changer ton propre rôle ici.";
    } else {
        $up = $pdo->prepare("UPDATE users SET role = ? WHERE id = ? LIMIT 1");
        $up->execute([$role, $uid]);
        $msg = "✅ Rôle mis à jour.";
    }
}

// ----- Action : reset mot de passe utilisateur -----
if (isset($_POST['action']) && $_POST['action'] === 'reset_password') {
    $uid = (int)($_POST['uid'] ?? 0);
    $new = $_POST['new_password'] ?? '';

    if ($uid <= 0) {
        $err = "Utilisateur invalide.";
    } elseif (strlen($new) < 6) {
        $err = "Mot de passe trop court (min 6).";
    } else {
        $hash = password_hash($new, PASSWORD_BCRYPT);
        $up = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ? LIMIT 1");
        $up->execute([$hash, $uid]);
        $msg = "✅ Mot de passe réinitialisé.";
    }
}

// Charger la liste des utilisateurs
$users = $pdo->query("SELECT id, username, role, created_at FROM users ORDER BY id DESC")->fetchAll();
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Admin - Comptabilité</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="assets/style.css">
  <style>
    .grid2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
    @media (max-width: 900px){.grid2{grid-template-columns:1fr}}
    .mini{font-size:12px;color:#666}
    .ok{background:#ecfff1;border:1px solid #bff2cb;padding:10px;border-radius:10px;margin-bottom:12px}
    .bad{background:#fff2f2;border:1px solid #ffd0d0;padding:10px;border-radius:10px;margin-bottom:12px}
    .row{display:flex;gap:10px;flex-wrap:wrap}
    .row > *{flex:1;min-width:180px}
    .table-wrap{overflow:auto}
    table{width:100%;border-collapse:collapse}
    th,td{border:1px solid #333;padding:8px 10px;font-size:13px}
    th{background:#f2f2f2;text-transform:uppercase;font-size:12px}
    .right{text-align:right}
  </style>
</head>
<body>
<div class="container">

  <div class="topbar">
    <div>
      <div class="hello"><strong>Administration</strong></div>
      <div class="mini">Connecté : <?= h($_SESSION['username'] ?? '') ?> (<?= h($_SESSION['role'] ?? '') ?>)</div>
    </div>
    <div class="top-actions">
      <a class="btn outline" href="index.php">Retour</a>
      <a class="btn danger" href="logout.php">Déconnexion</a>
    </div>
  </div>

  <?php if ($msg): ?><div class="ok"><?= h($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="bad"><?= h($err) ?></div><?php endif; ?>

  <div class="grid2">

    <!-- Changer mon mot de passe -->
    <div class="card">
      <h2>Changer mon mot de passe</h2>
      <form method="post" class="form">
        <input type="hidden" name="action" value="change_my_password">

        <label>Ancien mot de passe</label>
        <input type="password" name="old_password" required>

        <label>Nouveau mot de passe</label>
        <input type="password" name="new_password" required>

        <label>Confirmer</label>
        <input type="password" name="new_password2" required>

        <button class="btn" type="submit">Mettre à jour</button>
      </form>
      <div class="mini">Conseil : minimum 6 caractères.</div>
    </div>

    <!-- Créer un utilisateur -->
    <div class="card">
      <h2>Créer un utilisateur</h2>
      <form method="post" class="form">
        <input type="hidden" name="action" value="create_user">

        <label>Nom d'utilisateur</label>
        <input type="text" name="username" placeholder="ex: caisse1" required>

        <label>Mot de passe</label>
        <input type="password" name="password" required>

        <label>Rôle</label>
        <select name="role" style="width:100%;padding:10px;border:1px solid #cfd5e3;border-radius:10px">
          <option value="user">user</option>
          <option value="admin">admin</option>
        </select>

        <button class="btn" type="submit">Créer</button>
      </form>
      <div class="mini">Nom autorisé : lettres/chiffres + . _ - (3 à 30 caractères)</div>
    </div>

  </div>

  <!-- Liste utilisateurs -->
  <div class="card" style="margin-top:16px;">
    <h2>Utilisateurs</h2>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Rôle</th>
            <th>Créé le</th>
            <th style="width:320px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
            <tr>
              <td><?= (int)$u['id'] ?></td>
              <td><?= h($u['username']) ?></td>
              <td><?= h($u['role']) ?></td>
              <td><?= h($u['created_at']) ?></td>
              <td>
                <div class="row">

                  <!-- Changer rôle -->
                  <form method="post" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                    <input type="hidden" name="action" value="set_role">
                    <input type="hidden" name="uid" value="<?= (int)$u['id'] ?>">
                    <select name="role" style="padding:8px;border:1px solid #cfd5e3;border-radius:10px;">
                      <option value="user" <?= $u['role']==='user'?'selected':'' ?>>user</option>
                      <option value="admin" <?= $u['role']==='admin'?'selected':'' ?>>admin</option>
                    </select>
                    <button class="btn outline" type="submit" <?= ((int)$u['id']===(int)$_SESSION['user_id'])?'disabled':''; ?>>Rôle</button>
                  </form>

                  <!-- Reset mot de passe -->
                  <form method="post" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="uid" value="<?= (int)$u['id'] ?>">
                    <input type="password" name="new_password" placeholder="Nouveau mdp" style="padding:8px;border:1px solid #cfd5e3;border-radius:10px;min-width:140px" required>
                    <button class="btn outline" type="submit">Reset</button>
                  </form>

                </div>
                <?php if ((int)$u['id']===(int)$_SESSION['user_id']): ?>
                  <div class="mini">* Ton compte (certaines actions désactivées)</div>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  </div>

</div>
</body>
</html>
