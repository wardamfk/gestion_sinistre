
<!DOCTYPE html>
<html>
<head>
    <title>CNMA - Gestion des sinistres</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/style2.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>
<body>

<!-- HEADER -->
<!-- HEADER -->
<div class="topbar">
    <div class="logo">
        <img src="images/logo.webp" height="40">
        <span>CNMA</span>
    </div>

    <div class="menu">
        <a href="#accueil">Accueil</a>
        <a href="#services">Services</a>
        <a href="#contact">Contact</a>
        <a href="pages/login.php" class="btn-login">Se connecter</a>
    </div>
</div>

<!-- HERO -->
<section class="hero" id="accueil">
    <div class="hero-content">
        <h1>Plateforme de gestion des sinistres automobiles</h1>
        <p>
           Un espace en ligne pour suivre et gérer vos sinistres automobiles.
        </p>

        <div class="hero-buttons">
    <a href="pages/login.php?role=assure" class="btn btn-assure">
        <i class="fa fa-user"></i> Espace Assuré
    </a>

    <a href="pages/login.php?role=crma" class="btn btn-crma">
        <i class="fa fa-building"></i> Espace CRMA
    </a>

    <a href="pages/login.php?role=cnma" class="btn btn-cnma">
        <i class="fa fa-briefcase"></i> Espace CNMA
    </a>

   

    
</div>

        <div class="scroll-down">
            <a href="#services"><i class="fa fa-angle-down"></i></a>
        </div>
    </div>
</section>

<!-- SERVICES -->
<section class="services" id="services">
    <h2>Nos Services</h2>

    <div class="cards-container">
        <div class="card">
            <i class="fa fa-car"></i>
            <h3>Déclaration de sinistre</h3>
            <p>Déclarez votre sinistre en ligne rapidement.</p>
        </div>

        <div class="card">
            <i class="fa fa-folder"></i>
            <h3>Suivi des dossiers</h3>
            <p>Suivez l'état d’avancement de votre dossier.</p>
        </div>

        <div class="card">
            <i class="fa fa-file"></i>
            <h3>Gestion des contrats</h3>
            <p>Consultez et gérez vos contrats.</p>
        </div>

        <div class="card">
            <i class="fa fa-money-bill"></i>
            <h3>Paiement</h3>
            <p>Suivez vos remboursements.</p>
        </div>
    </div>
</section>
<section class="stats">
    <h2>TEST</h2>
</section>
<!-- LOCALISATION AGENCE -->
<div class="map-section" id="contact">
    <h2>Trouver une agence CNMA</h2>
    <p>Localisez l'agence CNMA la plus proche de vous.</p>

    <iframe 
        src="https://www.google.com/maps?q=CNMA+Alger&output=embed"
        width="100%" 
        height="400" 
        style="border:0;">
    </iframe>
</div>

<!-- CONTACT -->
<div class="contact-section">
    <h2>Contact</h2>
    <div class="contact-container">
        <div class="contact-box">
            <i class="fa fa-phone"></i>
            <p>021 74 50 21</p>
        </div>

        <div class="contact-box">
            <i class="fa fa-envelope"></i>
            <p>contact@cnma.dz</p>
        </div>

        <div class="contact-box">
            <i class="fa fa-map-marker-alt"></i>
            <p>Alger, Algérie</p>
        </div>
    </div>
</div>
<!-- FOOTER -->
<section class="footer" id="contact">
    <p>© 2026 CNMA - Gestion des sinistres automobiles</p>
</section>

</body>
</html>