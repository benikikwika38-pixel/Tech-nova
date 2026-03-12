<?php
session_start();
$bdd = new PDO('mysql:host=localhost;dbname=technova;charset=utf8', 'root', ''); // modifie selon ton serveur

// ----------------- INSCRIPTION -----------------
if(isset($_POST['ins_nom'], $_POST['ins_email'], $_POST['ins_mdp'])){
    $nom = $_POST['ins_nom'];
    $email = $_POST['ins_email'];
    $mdp = password_hash($_POST['ins_mdp'], PASSWORD_DEFAULT);
    $req = $bdd->prepare("INSERT INTO utilisateurs (nom, email, mot_de_passe) VALUES (?, ?, ?)");
    $req->execute([$nom, $email, $mdp]);
    echo "<p style='color:green;text-align:center;'>Inscription réussie ! Connectez-vous.</p>";
}

// ----------------- CONNEXION -----------------
if(isset($_POST['login_email'], $_POST['login_mdp'])){
    $email = $_POST['login_email'];
    $mdp = $_POST['login_mdp'];
    $req = $bdd->prepare("SELECT * FROM utilisateurs WHERE email=?");
    $req->execute([$email]);
    $user = $req->fetch(PDO::FETCH_ASSOC);
    if($user && password_verify($mdp, $user['mot_de_passe'])){
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_nom'] = $user['nom'];
    } else {
        echo "<p style='color:red;text-align:center;'>Email ou mot de passe incorrect</p>";
    }
}

// ----------------- PUBLIER -----------------
if(isset($_POST['contenu']) && isset($_SESSION['user_id'])){
    $req = $bdd->prepare("INSERT INTO publications (utilisateur_id, contenu) VALUES (?, ?)");
    $req->execute([$_SESSION['user_id'], $_POST['contenu']]);
}

// ----------------- LIKES -----------------
if(isset($_GET['like']) && isset($_SESSION['user_id'])){
    $pub_id = intval($_GET['like']);
    $user_id = $_SESSION['user_id'];
    $check = $bdd->prepare("SELECT * FROM likes WHERE publication_id=? AND utilisateur_id=?");
    $check->execute([$pub_id, $user_id]);
    if($check->rowCount() == 0){
        $req = $bdd->prepare("INSERT INTO likes (publication_id, utilisateur_id) VALUES (?, ?)");
        $req->execute([$pub_id, $user_id]);
    }
}

// ----------------- COMMENTAIRES -----------------
if(isset($_POST['commentaire'], $_POST['pub_id']) && isset($_SESSION['user_id'])){
    $req = $bdd->prepare("INSERT INTO commentaires (publication_id, utilisateur_id, contenu) VALUES (?, ?, ?)");
    $req->execute([$_POST['pub_id'], $_SESSION['user_id'], $_POST['commentaire']]);
}

// ----------------- RÉCUPÉRER LES PUBLICATIONS -----------------
$pubs = $bdd->query("SELECT p.*, u.nom FROM publications p JOIN utilisateurs u ON p.utilisateur_id=u.id ORDER BY p.date_post DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Fil d'actualité - TechNova Network</title>
<style>
body{font-family:Arial;background:#f4f6f9;margin:0;padding:0;}
header{background:#2980b9;color:white;padding:20px;text-align:center;font-size:28px;}
.container{display:flex;max-width:1200px;margin:20px auto;gap:20px;}
.left,.right{width:25%;background:white;padding:20px;border-radius:10px;box-shadow:0 0 10px rgba(0,0,0,0.1);}
.center{width:50%;}
.post{background:white;padding:15px;margin-bottom:20px;border-radius:10px;box-shadow:0 0 5px rgba(0,0,0,0.05);}
.post h3{margin:0 0 10px 0;color:#2980b9;}
.post p{margin:5px 0;font-size:16px;}
.likes,.comments{font-size:14px;color:#555;margin-top:5px;}
button,input,textarea{background:#2980b9;color:white;border:none;border-radius:5px;padding:5px 10px;cursor:pointer;margin-top:5px;}
button:hover,input:hover,textarea:hover{background:#1f618d;}
textarea{width:100%;border-radius:5px;margin-top:10px;}
form{margin-bottom:20px;}
</style>
</head>
<body>

<header>TechNova Network - Apprendre, partager et évoluer</header>

<div class="container">

<!-- Colonne gauche : profil et top étudiants -->
<div class="left">
<?php if(isset($_SESSION['user_id'])): ?>
<h2>Profil</h2>
<p><strong><?= htmlspecialchars($_SESSION['user_nom']) ?></strong></p>
<hr>
<h2>Top étudiants</h2>
<ol>
<?php
$top = $bdd->query("SELECT nom, COUNT(*) as total FROM publications GROUP BY utilisateur_id ORDER BY total DESC LIMIT 5");
foreach($top as $t){
    echo "<li>".htmlspecialchars($t['nom'])." - ".$t['total']." posts</li>";
}
?>
</ol>
<a href="deconnexion.php"><button>Déconnexion</button></a>
<?php else: ?>
<h2>Connexion</h2>
<form method="POST">
<input type="email" name="login_email" placeholder="Email" required><br>
<input type="password" name="login_mdp" placeholder="Mot de passe" required><br>
<button type="submit">Se connecter</button>
</form>

<h2>Inscription</h2>
<form method="POST">
<input type="text" name="ins_nom" placeholder="Nom complet" required><br>
<input type="email" name="ins_email" placeholder="Email" required><br>
<input type="password" name="ins_mdp" placeholder="Mot de passe" required><br>
<button type="submit">S’inscrire</button>
</form>
<?php endif; ?>
</div>

<!-- Colonne centrale : fil d'actualité -->
<div class="center">
<h2>Fil d'actualité</h2>

<?php if(isset($_SESSION['user_id'])): ?>
<form method="POST">
<textarea name="contenu" placeholder="Publier quelque chose..." required></textarea>
<button type="submit">Publier</button>
</form>
<?php else: ?>
<p style="color:red;">Connectez-vous pour publier, liker ou commenter</p>
<?php endif; ?>

<?php foreach($pubs as $p): ?>
<div class="post">
<h3><?= htmlspecialchars($p['nom']) ?></h3>
<p><?= htmlspecialchars($p['contenu']) ?></p>
<?php
$likes = $bdd->prepare("SELECT COUNT(*) FROM likes WHERE publication_id=?");
$likes->execute([$p['id']]);
$likesCount = $likes->fetchColumn();

$comments = $bdd->prepare("SELECT COUNT(*) FROM commentaires WHERE publication_id=?");
$comments->execute([$p['id']]);
$commentsCount = $comments->fetchColumn();
?>
<div class="likes">❤️ <?= $likesCount ?> likes</div>
<div class="comments">💬 <?= $commentsCount ?> commentaires</div>

<?php if(isset($_SESSION['user_id'])): ?>
<a href="?like=<?= $p['id'] ?>"><button>Like</button></a>

<form method="POST">
<input type="hidden" name="pub_id" value="<?= $p['id'] ?>">
<textarea name="commentaire" placeholder="Ajouter un commentaire..." required></textarea>
<button type="submit">Commenter</button>
</form>
<?php else: ?>
<p style="color:red;">Connectez-vous pour interagir avec ce post</p>
<?php endif; ?>
</div>
<?php endforeach; ?>
</div>

<!-- Colonne droite : formations -->
<div class="right">
<h2>Formations TechNova</h2>
<ul>
<li>Initiation à l’informatique (Gratuit)</li>
<li>Word (Gratuit)</li>
<li>Excel (5$)</li>
<li>Bases de données (5$)</li>
<li>Programmation (10$)</li>
</ul>
</div>

</div>

</body>
</html>
