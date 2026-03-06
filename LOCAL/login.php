<?php
require __DIR__ . "/config.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    $stmt = $pdo->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $u = $stmt->fetch();

    if ($u && password_verify($password, $u["password_hash"])) {
        $_SESSION["user_id"] = (int)$u["id"];
        $_SESSION["username"] = $u["username"];
        $_SESSION["role"] = $u["role"];
        header("Location: index.php");
        exit;
    } else {
        $error = "Identifiants incorrects.";
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Connexion - Dépenses</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body{font-family:Arial;background:#f2f5fb;margin:0}
    .box{max-width:360px;margin:70px auto;background:#fff;padding:20px;border:1px solid #ddd;border-radius:10px}
    input{width:100%;padding:10px;margin:8px 0;border:1px solid #ccc;border-radius:8px}
    button{width:100%;padding:10px;border:0;border-radius:8px;background:#111;color:#fff;font-weight:700}
    .err{background:#ffe6e6;border:1px solid #ffb3b3;padding:10px;border-radius:8px;margin-bottom:10px}
  </style>
</head>
<body>
  <div class="box">
    <h2>Connexion</h2>
    <?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post">
      <label>Nom d'utilisateur</label>
      <input name="username" required>
      <label>Mot de passe</label>
      <input type="password" name="password" required>
      <button type="submit">Se connecter</button>
    </form>
    <p style="font-size:12px;color:#666;margin-top:10px">Compte test : admin / admin123</p>
  </div>
</body>
</html>
