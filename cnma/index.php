<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CNMA — Plateforme de gestion des sinistres automobiles</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --green:#0d7b1c;--green-d:#0a5f15;--green-l:#e8f5e9;
  --blue:#1a237e;--blue-l:#e8eaf6;
  --amber:#d97706;--amber-l:#fffbeb;
  --gray-50:#f9fafb;--gray-100:#f3f4f6;--gray-200:#e5e7eb;
  --gray-500:#6b7280;--gray-700:#374151;--gray-900:#111827;
  --radius:12px;--shadow:0 4px 16px rgba(0,0,0,.08);
}
html{scroll-behavior:smooth}
body{font-family:'Inter',sans-serif;color:var(--gray-900);background:#fff;font-size:15px;line-height:1.6}

/* ===== TOPBAR ===== */
.topbar{
  position:sticky;top:0;z-index:100;
  background:rgba(255,255,255,.96);backdrop-filter:blur(8px);
  border-bottom:1px solid var(--gray-200);
  height:68px;display:flex;align-items:center;
  padding:0 6%;gap:0;
}
.logo{display:flex;align-items:center;gap:10px;text-decoration:none}
.logo img{height:38px}
.logo-text{font-size:17px;font-weight:700;color:var(--green)}
.logo-sub{font-size:11px;color:var(--gray-500);font-weight:400;display:block;margin-top:-2px}
.nav-links{display:flex;gap:32px;margin-left:auto;margin-right:32px}
.nav-links a{text-decoration:none;color:var(--gray-700);font-size:14px;font-weight:500;transition:.15s}
.nav-links a:hover{color:var(--green)}
.btn-connect{
  background:var(--green);color:#fff;padding:9px 22px;
  border-radius:8px;text-decoration:none;font-size:14px;font-weight:600;
  transition:.15s;white-space:nowrap;
}
.btn-connect:hover{background:var(--green-d)}

/* ===== HERO ===== */
.hero{
  background:linear-gradient(135deg,#0a3d12 0%,#145218 40%,#1b5e20 100%);
  padding:88px 6% 80px;display:flex;align-items:center;gap:60px;
  min-height:520px;position:relative;overflow:hidden;
}
.hero::before{
  content:'';position:absolute;inset:0;
  background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}
.hero-content{flex:1;position:relative;z-index:1}
.hero-badge{
  display:inline-flex;align-items:center;gap:6px;
  background:rgba(255,255,255,.12);color:rgba(255,255,255,.9);
  padding:5px 14px;border-radius:999px;font-size:12px;font-weight:500;
  margin-bottom:24px;border:1px solid rgba(255,255,255,.15);
}
.hero h1{font-size:clamp(26px,3.5vw,42px);font-weight:700;color:#fff;line-height:1.2;margin-bottom:16px}
.hero p{font-size:16px;color:rgba(255,255,255,.78);max-width:520px;margin-bottom:36px;line-height:1.7}
.hero-btns{display:flex;gap:12px;flex-wrap:wrap}
.hero-card{
  background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15);
  border-radius:var(--radius);padding:28px 26px;min-width:280px;
  position:relative;z-index:1;flex-shrink:0;
}
.hero-card h4{color:rgba(255,255,255,.5);font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:1px;margin-bottom:14px}
.hero-stat{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid rgba(255,255,255,.08)}
.hero-stat:last-child{border-bottom:none}
.hero-stat .val{font-size:22px;font-weight:700;color:#fff;font-variant-numeric:tabular-nums;min-width:50px}
.hero-stat .lbl{font-size:13px;color:rgba(255,255,255,.65)}
.hero-stat .ic{width:34px;height:34px;border-radius:8px;background:rgba(255,255,255,.1);display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.7);font-size:14px;flex-shrink:0}

/* ===== ACCENT BUTTONS ===== */
.btn-white{background:#fff;color:var(--green);padding:11px 24px;border-radius:8px;font-size:14px;font-weight:600;text-decoration:none;transition:.15s}
.btn-white:hover{background:var(--green-l)}
.btn-outline-w{border:1.5px solid rgba(255,255,255,.5);color:#fff;padding:11px 24px;border-radius:8px;font-size:14px;font-weight:500;text-decoration:none;transition:.15s}
.btn-outline-w:hover{background:rgba(255,255,255,.1)}

/* ===== SECTIONS ===== */
section{padding:72px 6%}
.section-label{display:flex;align-items:center;gap:8px;font-size:12px;font-weight:600;color:var(--green);text-transform:uppercase;letter-spacing:1.2px;margin-bottom:12px}
.section-label::before{content:'';width:20px;height:2px;background:var(--green)}
h2.section-title{font-size:clamp(22px,2.5vw,34px);font-weight:700;color:var(--gray-900);margin-bottom:10px;line-height:1.3}
.section-sub{font-size:15px;color:var(--gray-500);max-width:560px;margin-bottom:48px}

/* ===== ESPACES (3 rôles) ===== */
.espaces-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}
.espace-card{
  border:1.5px solid var(--gray-200);border-radius:var(--radius);
  padding:28px;transition:all .2s;position:relative;overflow:hidden;
  text-decoration:none;display:block;background:#fff;
}
.espace-card::after{
  content:'';position:absolute;top:0;left:0;width:100%;height:4px;
  border-radius:var(--radius) var(--radius) 0 0;
}
.espace-card.assure::after{background:var(--blue)}
.espace-card.crma::after{background:var(--green)}
.espace-card.cnma::after{background:var(--amber)}
.espace-card:hover{box-shadow:var(--shadow);transform:translateY(-3px);border-color:transparent}
.espace-icon{width:52px;height:52px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;margin-bottom:18px}
.espace-card.assure .espace-icon{background:var(--blue-l);color:var(--blue)}
.espace-card.crma   .espace-icon{background:var(--green-l);color:var(--green)}
.espace-card.cnma   .espace-icon{background:var(--amber-l);color:var(--amber)}
.espace-card h3{font-size:17px;font-weight:700;margin-bottom:8px;color:var(--gray-900)}
.espace-card .role-label{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.8px;margin-bottom:16px}
.espace-card.assure .role-label{color:var(--blue)}
.espace-card.crma   .role-label{color:var(--green)}
.espace-card.cnma   .role-label{color:var(--amber)}
.espace-card p{font-size:13.5px;color:var(--gray-500);line-height:1.6;margin-bottom:20px}
.feature-list{list-style:none;display:flex;flex-direction:column;gap:7px;margin-bottom:22px}
.feature-list li{display:flex;align-items:flex-start;gap:8px;font-size:13px;color:var(--gray-700)}
.feature-list li i{font-size:11px;margin-top:3px;flex-shrink:0}
.espace-card.assure .feature-list li i{color:var(--blue)}
.espace-card.crma   .feature-list li i{color:var(--green)}
.espace-card.cnma   .feature-list li i{color:var(--amber)}
.espace-link{font-size:13px;font-weight:600;display:flex;align-items:center;gap:5px;transition:.15s}
.espace-card.assure .espace-link{color:var(--blue)}
.espace-card.crma   .espace-link{color:var(--green)}
.espace-card.cnma   .espace-link{color:var(--amber)}

/* ===== WORKFLOW ===== */
.workflow-bg{background:var(--gray-50)}
.workflow-steps{display:grid;grid-template-columns:repeat(5,1fr);gap:0;position:relative}
.workflow-steps::before{
  content:'';position:absolute;top:32px;left:10%;right:10%;
  height:2px;background:linear-gradient(90deg,var(--green),var(--blue));
  z-index:0;
}
.wf-step{display:flex;flex-direction:column;align-items:center;text-align:center;padding:0 12px;position:relative;z-index:1}
.wf-num{
  width:64px;height:64px;border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  font-size:20px;margin-bottom:16px;border:4px solid #fff;
  box-shadow:0 0 0 2px var(--green);
}
.wf-step:nth-child(1) .wf-num,.wf-step:nth-child(2) .wf-num{background:var(--green-l);color:var(--green);box-shadow:0 0 0 2px var(--green)}
.wf-step:nth-child(3) .wf-num{background:var(--amber-l);color:var(--amber);box-shadow:0 0 0 2px var(--amber)}
.wf-step:nth-child(4) .wf-num,.wf-step:nth-child(5) .wf-num{background:var(--blue-l);color:var(--blue);box-shadow:0 0 0 2px var(--blue)}
.wf-step h4{font-size:13.5px;font-weight:600;color:var(--gray-900);margin-bottom:6px}
.wf-step p{font-size:12px;color:var(--gray-500);line-height:1.5}
.wf-actor{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;margin-bottom:10px;padding:2px 10px;border-radius:999px}
.wf-step:nth-child(1) .wf-actor,.wf-step:nth-child(2) .wf-actor{background:var(--green-l);color:var(--green)}
.wf-step:nth-child(3) .wf-actor{background:var(--amber-l);color:var(--amber)}
.wf-step:nth-child(4) .wf-actor,.wf-step:nth-child(5) .wf-actor{background:var(--blue-l);color:var(--blue)}

/* ===== VALEUR AJOUTÉE ===== */
.valeur-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px}
.valeur-card{background:#fff;border:1px solid var(--gray-200);border-radius:var(--radius);padding:24px}
.valeur-card i{font-size:24px;color:var(--green);margin-bottom:14px}
.valeur-card h4{font-size:15px;font-weight:600;margin-bottom:8px;color:var(--gray-900)}
.valeur-card p{font-size:13px;color:var(--gray-500);line-height:1.6}

/* ===== AGENCES (CARTE) ===== */
.agences-bg{background:#fff}
.agences-layout{display:grid;grid-template-columns:320px 1fr;gap:28px;align-items:start}
.agences-list{display:flex;flex-direction:column;gap:8px;max-height:480px;overflow-y:auto;padding-right:4px}
.agences-list::-webkit-scrollbar{width:4px}
.agences-list::-webkit-scrollbar-thumb{background:var(--gray-200);border-radius:4px}
.agence-item{
  padding:14px 16px;border-radius:var(--radius);border:1.5px solid var(--gray-200);
  cursor:pointer;transition:all .15s;display:flex;align-items:center;gap:12px;
}
.agence-item:hover,.agence-item.active{border-color:var(--green);background:var(--green-l)}
.agence-item .ai-icon{width:36px;height:36px;border-radius:8px;background:var(--green-l);color:var(--green);display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0}
.agence-item.active .ai-icon{background:var(--green);color:#fff}
.agence-item .ai-name{font-size:13.5px;font-weight:600;color:var(--gray-800)}
.agence-item .ai-wil{font-size:12px;color:var(--gray-500)}
.map-container{border-radius:var(--radius);overflow:hidden;border:1px solid var(--gray-200);height:480px}
.map-container iframe{width:100%;height:100%;border:none}
#agence-info{background:var(--green-l);border:1.5px solid var(--green);border-radius:var(--radius);padding:14px 16px;margin-top:12px;display:none}
#agence-info h5{font-size:13.5px;font-weight:700;color:var(--green-d);margin-bottom:6px}
#agence-info p{font-size:12.5px;color:var(--gray-700)}

/* ===== CONTACT ===== */
.contact-bg{background:var(--gray-50)}
.contact-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}
.contact-card{background:#fff;border:1px solid var(--gray-200);border-radius:var(--radius);padding:24px;text-align:center}
.contact-card i{font-size:26px;color:var(--green);margin-bottom:12px}
.contact-card h4{font-size:14px;font-weight:600;margin-bottom:6px}
.contact-card p{font-size:13.5px;color:var(--gray-500)}

/* ===== FOOTER ===== */
footer{background:var(--gray-900);color:rgba(255,255,255,.6);padding:28px 6%;text-align:center;font-size:13.5px}
footer strong{color:#fff}

/* ===== RESPONSIVE ===== */
@media(max-width:1000px){
  .espaces-grid{grid-template-columns:1fr}
  .workflow-steps{grid-template-columns:1fr;gap:20px}
  .workflow-steps::before{display:none}
  .valeur-grid{grid-template-columns:1fr 1fr}
  .agences-layout{grid-template-columns:1fr}
  .contact-grid{grid-template-columns:1fr}
  .hero{flex-direction:column;gap:36px}
  .hero-card{min-width:unset;width:100%}
  .nav-links{display:none}
}
</style>
</head>
<body>

<!-- ===== TOPBAR ===== -->
<div class="topbar">
    <a href="#" class="logo">
        <img src="images/logo.webp" alt="CNMA">
        <div>
            <div class="logo-text">CNMA</div>
            <span class="logo-sub">Gestion des sinistres</span>
        </div>
    </a>
    <nav class="nav-links">
        <a href="#espaces">Espaces</a>
        <a href="#workflow">Fonctionnement</a>
        <a href="#agences">Agences</a>
        <a href="#contact">Contact</a>
    </nav>
    <a href="pages/login.php" class="btn-connect"><i class="fa fa-sign-in-alt" style="margin-right:6px"></i>Se connecter</a>
</div>

<!-- ===== HERO ===== -->
<section class="hero">
    <div class="hero-content">
        <div class="hero-badge">
            <i class="fa fa-shield-halved" style="font-size:11px"></i>
            Assurance Automobile · CNMA Algérie
        </div>
        <h1>Plateforme centralisée<br>de gestion des sinistres</h1>
        <p>
            Un système décisionnel complet pour le traitement, le suivi
            et la validation des dossiers sinistres automobiles — de la CRMA à la CNMA.
        </p>
        <div class="hero-btns">
            <a href="pages/login.php" class="btn-white"><i class="fa fa-sign-in-alt" style="margin-right:6px"></i>Accéder à mon espace</a>
            <a href="#workflow" class="btn-outline-w">Comment ça fonctionne <i class="fa fa-arrow-down" style="margin-left:6px"></i></a>
        </div>
    </div>
    <div class="hero-card">
        <h4>Vue d'ensemble du système</h4>
        <div class="hero-stat">
            <div class="ic"><i class="fa fa-folder-open"></i></div>
            <div class="val">3</div>
            <div class="lbl">Espaces utilisateurs distincts</div>
        </div>
        <div class="hero-stat">
            <div class="ic"><i class="fa fa-building"></i></div>
            <div class="val">4</div>
            <div class="lbl">Agences CRMA connectées</div>
        </div>
        <div class="hero-stat">
            <div class="ic"><i class="fa fa-check-double"></i></div>
            <div class="val">2</div>
            <div class="lbl">Niveaux de validation</div>
        </div>
        <div class="hero-stat">
            <div class="ic"><i class="fa fa-clock"></i></div>
            <div class="val">100%</div>
            <div class="lbl">Traçabilité des actions</div>
        </div>
    </div>
</section>

<!-- ===== ESPACES ===== -->
<section id="espaces">
    <div class="section-label"><i class="fa fa-users"></i> Trois espaces dédiés</div>
    <h2 class="section-title">Chaque acteur a son rôle dans le processus</h2>
    <p class="section-sub">La plateforme distingue clairement les responsabilités de chaque intervenant, de la consultation à la validation finale.</p>

    <div class="espaces-grid">

        <!-- ASSURÉ -->
        <div class="espace-card assure">
            <div class="espace-icon"><i class="fa fa-user"></i></div>
            <div class="role-label">Espace Assuré</div>
            <h3>Suivi de mon dossier</h3>
            <p>L'assuré dispose d'un accès personnalisé pour consulter en temps réel l'état de ses dossiers et ses remboursements.</p>
            <ul class="feature-list">
                <li><i class="fa fa-check-circle"></i>Consulter ses contrats d'assurance</li>
                <li><i class="fa fa-check-circle"></i>Suivre l'état d'avancement des dossiers</li>
                <li><i class="fa fa-check-circle"></i>Consulter le statut des remboursements</li>
                <li><i class="fa fa-check-circle"></i>Télécharger les documents du dossier</li>
                <li><i class="fa fa-check-circle"></i>Recevoir les notifications CRMA/CNMA</li>
            </ul>
            <a href="pages/login.php" class="espace-link">Accéder à l'espace <i class="fa fa-arrow-right"></i></a>
        </div>

        <!-- CRMA -->
        <div class="espace-card crma">
            <div class="espace-icon"><i class="fa fa-building"></i></div>
            <div class="role-label">Espace CRMA — Gestionnaire</div>
            <h3>Gestion opérationnelle</h3>
            <p>L'agent CRMA est le gestionnaire opérationnel du sinistre : il crée les dossiers, affecte les experts, gère les réserves et les règlements.</p>
            <ul class="feature-list">
                <li><i class="fa fa-check-circle"></i>Créer et instruire les dossiers sinistres</li>
                <li><i class="fa fa-check-circle"></i>Affecter un expert et saisir l'expertise</li>
                <li><i class="fa fa-check-circle"></i>Gérer les réserves par garantie</li>
                <li><i class="fa fa-check-circle"></i>Transmettre les dossiers à la CNMA</li>
                <li><i class="fa fa-check-circle"></i>Effectuer les règlements après validation</li>
            </ul>
            <a href="pages/login.php" class="espace-link">Accéder à l'espace <i class="fa fa-arrow-right"></i></a>
        </div>

        <!-- CNMA -->
        <div class="espace-card cnma">
            <div class="espace-icon"><i class="fa fa-gavel"></i></div>
            <div class="role-label">Espace CNMA — Décisionnel</div>
            <h3>Validation &amp; supervision</h3>
            <p>La CNMA est le niveau décisionnel central : elle valide ou refuse les dossiers transmis, contrôle les montants et supervise l'ensemble du processus.</p>
            <ul class="feature-list">
                <li><i class="fa fa-check-circle"></i>Valider ou refuser les dossiers transmis</li>
                <li><i class="fa fa-check-circle"></i>Demander des compléments au CRMA</li>
                <li><i class="fa fa-check-circle"></i>Superviser les réserves et règlements</li>
                <li><i class="fa fa-check-circle"></i>Accéder aux statistiques globales</li>
                <li><i class="fa fa-check-circle"></i>Gérer les comptes utilisateurs</li>
            </ul>
            <a href="pages/login.php" class="espace-link">Accéder à l'espace <i class="fa fa-arrow-right"></i></a>
        </div>

    </div>
</section>

<!-- ===== WORKFLOW ===== -->
<section id="workflow" class="workflow-bg">
    <div class="section-label"><i class="fa fa-arrows-turn-right"></i> Circuit de traitement</div>
    <h2 class="section-title">Comment un dossier sinistre est traité</h2>
    <p class="section-sub" style="margin-bottom:52px">Du fait déclaré à la clôture du dossier, chaque étape est tracée, validée et notifiée aux parties concernées.</p>

    <div class="workflow-steps">
        <div class="wf-step">
            <span class="wf-actor">CRMA</span>
            <div class="wf-num"><i class="fa fa-folder-plus"></i></div>
            <h4>Ouverture du dossier</h4>
            <p>L'agent CRMA ouvre le dossier, identifie les parties, affecte un expert.</p>
        </div>
        <div class="wf-step">
            <span class="wf-actor">CRMA</span>
            <div class="wf-num"><i class="fa fa-magnifying-glass"></i></div>
            <h4>Expertise &amp; réserve</h4>
            <p>L'expert évalue les dommages, une réserve financière est provisionnée.</p>
        </div>
        <div class="wf-step">
            <span class="wf-actor">Automatique</span>
            <div class="wf-num"><i class="fa fa-paper-plane"></i></div>
            <h4>Transmission CNMA</h4>
            <p>Si la réserve dépasse le seuil, le dossier est transmis automatiquement à la CNMA.</p>
        </div>
        <div class="wf-step">
            <span class="wf-actor">CNMA</span>
            <div class="wf-num"><i class="fa fa-gavel"></i></div>
            <h4>Décision CNMA</h4>
            <p>La CNMA valide, refuse, ou demande un complément de dossier au CRMA.</p>
        </div>
        <div class="wf-step">
            <span class="wf-actor">CRMA</span>
            <div class="wf-num"><i class="fa fa-money-bill-wave"></i></div>
            <h4>Règlement</h4>
            <p>Après validation, le CRMA effectue le règlement et notifie l'assuré.</p>
        </div>
    </div>
</section>

<!-- ===== VALEUR AJOUTÉE ===== -->
<section>
    <div class="section-label"><i class="fa fa-star"></i> Pourquoi cette plateforme</div>
    <h2 class="section-title">Un outil au service de la performance</h2>
    <p class="section-sub">La centralisation des dossiers réduit les délais, améliore la traçabilité et renforce le contrôle décisionnel.</p>

    <div class="valeur-grid">
        <div class="valeur-card">
            <i class="fa fa-clock"></i>
            <h4>Réduction des délais</h4>
            <p>Les workflows automatisés et les seuils de validation accélèrent le traitement des dossiers.</p>
        </div>
        <div class="valeur-card">
            <i class="fa fa-eye"></i>
            <h4>Suivi en temps réel</h4>
            <p>Chaque action est tracée dans l'historique. L'assuré consulte l'état de son dossier à tout moment.</p>
        </div>
        <div class="valeur-card">
            <i class="fa fa-shield-halved"></i>
            <h4>Contrôle décisionnel</h4>
            <p>La CNMA supervise et valide les dossiers dépassant le seuil, garantissant un double niveau de contrôle.</p>
        </div>
        <div class="valeur-card">
            <i class="fa fa-database"></i>
            <h4>Centralisation</h4>
            <p>Contrats, véhicules, expertises, réserves et règlements sont gérés dans un référentiel unique.</p>
        </div>
    </div>
</section>

<!-- ===== AGENCES ===== -->
<section id="agences" class="agences-bg">
    <div class="section-label"><i class="fa fa-location-dot"></i> Réseau d'agences</div>
    <h2 class="section-title">Agences CRMA partenaires</h2>
    <p class="section-sub">Cliquez sur une agence pour localiser son adresse sur la carte.</p>

    <div class="agences-layout">
        <div>
            <div class="agences-list" id="agences-list">
                <!-- Agences générées -->
            </div>
            <div id="agence-info">
                <h5 id="ai-nom"></h5>
                <p id="ai-detail"></p>
            </div>
        </div>
        <div class="map-container">
            <iframe id="map-frame"
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3197.0!2d3.0588897!3d36.7537626!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x128faf3c0753cc8d%3A0x5eac5fb5a94cf38f!2sAlger%20Centre!5e0!3m2!1sfr!2sdz!4v1712345678"
                allowfullscreen loading="lazy"
                referrerpolicy="no-referrer-when-downgrade">
            </iframe>
        </div>
    </div>
</section>

<!-- ===== CONTACT ===== -->
<section id="contact" class="contact-bg">
    <div class="section-label"><i class="fa fa-envelope"></i> Contact</div>
    <h2 class="section-title">Nous contacter</h2>
    <p class="section-sub">Pour toute question relative à vos dossiers, rapprochez-vous de votre agence CRMA.</p>

    <div class="contact-grid">
        <div class="contact-card">
            <i class="fa fa-phone"></i>
            <h4>Téléphone</h4>
            <p>021 74 50 21</p>
        </div>
        <div class="contact-card">
            <i class="fa fa-envelope"></i>
            <h4>Email</h4>
            <p>contact@cnma.dz</p>
        </div>
        <div class="contact-card">
            <i class="fa fa-map-marker-alt"></i>
            <h4>Siège social</h4>
            <p>Direction CNMA, Alger</p>
        </div>
    </div>
</section>

<!-- ===== FOOTER ===== -->
<footer>
    <p>© 2026 <strong>CNMA</strong> — Plateforme de gestion des sinistres automobiles · Tous droits réservés</p>
</footer>

<script>
const agences = [
    {
        nom:"CRMA Alger",
        wilaya:"Wilaya d'Alger",
        adresse:"Alger Centre, Alger",
        tel:"021 74 50 21",
        email:"alger@crma.dz",
        embed:"https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3197.0!2d3.0588897!3d36.7537626!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x128faf3c0753cc8d%3A0x5eac5fb5a94cf38f!2sAlger%20Centre!5e0!3m2!1sfr!2sdz!4v1"
    },
    {
        nom:"CRMA Oran",
        wilaya:"Wilaya d'Oran",
        adresse:"Oran, Algérie",
        tel:"041 33 20 00",
        email:"oran@crma.dz",
        embed:"https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3321.0!2d-0.6350897!3d35.6970626!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0xd75f37948e3b0af%3A0x7a3c56d9e4e41929!2sOran!5e0!3m2!1sfr!2sdz!4v1"
    },
    {
        nom:"CRMA Constantine",
        wilaya:"Wilaya de Constantine",
        adresse:"Constantine, Algérie",
        tel:"031 68 10 00",
        email:"constantine@crma.dz",
        embed:"https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3260.0!2d6.6147397!3d36.3650226!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x12f314f0cd18ea23%3A0x5b3d87c6bdc4f218!2sConstantine!5e0!3m2!1sfr!2sdz!4v1"
    },
    {
        nom:"CRMA Ouargla",
        wilaya:"Wilaya de Ouargla",
        adresse:"Ouargla, Algérie",
        tel:"029 70 20 00",
        email:"ouargla@crma.dz",
        embed:"https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3500.0!2d5.3247597!3d31.9490026!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x12f34b87b5f3b5d5%3A0x7c24a5e4b5f7a3c8!2sOuargla!5e0!3m2!1sfr!2sdz!4v1"
    }
];

const list = document.getElementById('agences-list');
const frame= document.getElementById('map-frame');
const infoBox = document.getElementById('agence-info');
const aiNom   = document.getElementById('ai-nom');
const aiDet   = document.getElementById('ai-detail');

agences.forEach((ag,i)=>{
    const div = document.createElement('div');
    div.className = 'agence-item' + (i===0?' active':'');
    div.innerHTML = `
        <div class="ai-icon"><i class="fa fa-building"></i></div>
        <div><div class="ai-name">${ag.nom}</div><div class="ai-wil">${ag.wilaya}</div></div>
    `;
    div.addEventListener('click',()=>{
        document.querySelectorAll('.agence-item').forEach(e=>e.classList.remove('active'));
        div.classList.add('active');
        frame.src = ag.embed;
        aiNom.textContent = ag.nom;
        aiDet.innerHTML   = `📍 ${ag.adresse}<br>📞 ${ag.tel}<br>✉ ${ag.email}`;
        infoBox.style.display='block';
    });
    list.appendChild(div);
});

// Afficher info de la première agence
aiNom.textContent = agences[0].nom;
aiDet.innerHTML   = `📍 ${agences[0].adresse}<br>📞 ${agences[0].tel}<br>✉ ${agences[0].email}`;
infoBox.style.display='block';
</script>
</body>
</html>