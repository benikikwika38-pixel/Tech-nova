<?php
session_start();
$bdd = new PDO('mysql:host=localhost;dbname=technova;charset=utf8','root',''); // Modifie selon ton serveur

// Test connexion (à remplacer par vrai login)
if(!isset($_SESSION['user_id'])){
    $_SESSION['user_id'] = 1; // Pour test
    $_SESSION['user_nom'] = "Jean Dupont";
}

// Ajouter un post
if(isset($_POST['contenu']) && $_SESSION['user_id']){
    $req = $bdd->prepare("INSERT INTO posts (utilisateur_id, contenu) VALUES (?, ?)");
    $req->execute([$_SESSION['user_id'], $_POST['contenu']]);
}

// Ajouter un like
if(isset($_GET['like']) && $_SESSION['user_id']){
    $post_id = intval($_GET['like']);
    $user_id = $_SESSION['user_id'];
    $check = $bdd->prepare("SELECT * FROM likes WHERE post_id=? AND utilisateur_id=?");
    $check->execute([$post_id,$user_id]);
    if($check->rowCount() == 0){
        $req = $bdd->prepare("INSERT INTO likes (post_id, utilisateur_id) VALUES (?, ?)");
        $req->execute([$post_id, $user_id]);
        // Notification simple
        $post_owner = $bdd->query("SELECT utilisateur_id FROM posts WHERE id=$post_id")->fetchColumn();
        if($post_owner != $user_id){
            $bdd->prepare("INSERT INTO notifications (utilisateur_id, message) VALUES (?, ?)")->execute([$post_owner, "💖 Nouveau like sur votre post !"]);
        }
    }
}

// Ajouter un commentaire
if(isset($_POST['commentaire']) && isset($_POST['post_id']) && $_SESSION['user_id']){
    $req = $bdd->prepare("INSERT INTO commentaires (post_id, utilisateur_id, contenu) VALUES (?, ?, ?)");
    $req->execute([$_POST['post_id'], $_SESSION['user_id'], $_POST['commentaire']]);
    // Notification simple
    $post_owner = $bdd->query("SELECT utilisateur_id FROM posts WHERE id=".$_POST['post_id'])->fetchColumn();
    if($post_owner != $_SESSION['user_id']){
        $bdd->prepare("INSERT INTO notifications (utilisateur_id, message) VALUES (?, ?)")->execute([$post_owner, "💬 Nouveau commentaire sur votre post !"]);
    }
}

// Récupérer posts pour affichage
$posts = $bdd->query("SELECT p.*, u.nom FROM posts p JOIN utilisateurs u ON p.utilisateur_id=u.id ORDER BY date_post DESC")->fetchAll(PDO::FETCH_ASSOC);

// Récupérer notifications de l’utilisateur
$notifs = [];
if(isset($_SESSION['user_id'])){
    $stmt = $bdd->prepare("SELECT * FROM notifications WHERE utilisateur_id=? AND lu=0 ORDER BY date_notif DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Marquer comme lu
    $bdd->prepare("UPDATE notifications SET lu=1 WHERE utilisateur_id=?")->execute([$_SESSION['user_id']]);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mini TikTok Textuel - TechNova</title>
<style>
body{font-family:Arial;background:#f4f6f9;margin:0;padding:0;}
header{background:#2980b9;color:white;padding:20px;text-align:center;font-size:24px;}
.container{max-width:700px;margin:20px auto;padding:0 10px;}
.post{background:white;padding:15px;margin-bottom:20px;border-radius:10px;box-shadow:0 0 5px rgba(0,0,0,0.1);}
.post h3{margin:0 0 10px;color:#2980b9;}
.post p{font-size:16px;margin:5px 0;}
.likes,.comments{font-size:14px;color:#555;margin-top:5px;}
button,textarea{background:#2980b9;color:white;border:none;border-radius:5px;padding:5px 10px;cursor:pointer;margin-top:5px;}
button:hover,textarea:hover{background:#1f618d;}
textarea{width:100%;border-radius:5px;margin-top:10px;}
form{margin-top:10px;}
.notification{position:fixed;top:10px;right:10px;background:#27ae60;color:white;padding:10px 15px;border-radius:5px;box-shadow:0 0 5px rgba(0,0,0,0.2);margin-bottom:5px;}
.top-etudiants{background:white;padding:10px;margin-bottom:20px;border-radius:10px;box-shadow:0 0 5px rgba(0,0,0,0.1);}
</style>
</head>
<body>

<header>Mini TikTok Textuel - TechNova</header>

<div class="container">

<!-- Formulaire pour poster -->
<form method="POST">
<textarea name="contenu" placeholder="Écrire quelque chose..." required></textarea>
<button type="submit">Publier</button>
</form>

<!-- Notifications -->
<?php foreach($notifs as $n): ?>
<div class="notification"><?= htmlspecialchars($n['message']) ?></div>
<?php endforeach; ?>

<!-- Top étudiants -->
<div class="top-etudiants">
<h3>Top étudiants</h3>
<ol>
<?php
$top = $bdd->query("SELECT u.nom, COUNT(p.id) as total_posts FROM utilisateurs u LEFT JOIN posts p ON u.id=p.utilisateur_id GROUP BY u.id ORDER BY total_posts DESC LIMIT 5");
foreach($top as $t){
    echo "<li>".htmlspecialchars($t['nom'])." - ".$t['total_posts']." posts</li>";
}
?>
</ol>
</div>

<!-- Affichage des posts -->
<?php foreach($posts as $p): ?>
<div class="post">
<h3><?= htmlspecialchars($p['nom']) ?></h3>
<p><?= htmlspecialchars($p['contenu']) ?></p>

<?php
// Compter likes et commentaires
$likes = $bdd->prepare("SELECT COUNT(*) FROM likes WHERE post_id=?");
$likes->execute([$p['id']]);
$likesCount = $likes->fetchColumn();

$comments = $bdd->prepare("SELECT COUNT(*) FROM commentaires WHERE post_id=?");
$comments->execute([$p['id']]);
$commentsCount = $comments->fetchColumn();
?>
<div class="likes">❤️ <?= $likesCount ?> likes</div>
<div class="comments">💬 <?= $commentsCount ?> commentaires</div>

<a href="?like=<?= $p['id'] ?>"><button>Like</button></a>

<form method="POST">
<input type="hidden" name="post_id" value="<?= $p['id'] ?>">
<textarea name="commentaire" placeholder="Ajouter un commentaire..." required></textarea>
<button type="submit">Commenter</button>
</form>

</div>
<?php endforeach; ?>

</div>

</body>
    </html>
