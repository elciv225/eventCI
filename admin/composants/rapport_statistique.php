<?php
$labelsCategorie = $nbEvenementsParCategorie = [];
$labelsEvenements = $nbVentesParEvenement = [];
$revenusParEvenement = [];
$typesUtilisateur = $totalUtilisateurs = [];

// Événements par catégorie
$res = $conn->query("
    SELECT c.Libelle, COUNT(e.Id_Evenement) AS nb
    FROM categorieevenement c
    LEFT JOIN evenement e ON e.Id_CategorieEvenement = c.Id_CategorieEvenement
    GROUP BY c.Id_CategorieEvenement
");
while ($row = $res->fetch_assoc()) {
    $labelsCategorie[] = $row['Libelle'];
    $nbEvenementsParCategorie[] = $row['nb'];
}

// Tickets vendus par événement
$res = $conn->query("
    SELECT e.Titre, COUNT(*) AS TicketsVendus
    FROM achat a
    JOIN ticketevenement te ON a.Id_TicketEvenement = te.Id_TicketEvenement
    JOIN evenement e ON te.Id_Evenement = e.Id_Evenement
    GROUP BY e.Id_Evenement
");
while ($row = $res->fetch_assoc()) {
    $labelsEvenements[] = $row['Titre'];
    $nbVentesParEvenement[] = $row['TicketsVendus'];
}

// Revenus par événement
$res = $conn->query("
    SELECT e.Titre, SUM(te.Prix) AS revenu
    FROM achat a
    JOIN ticketevenement te ON a.Id_TicketEvenement = te.Id_TicketEvenement
    JOIN evenement e ON te.Id_Evenement = e.Id_Evenement
    GROUP BY e.Id_Evenement
");
while ($row = $res->fetch_assoc()) {
    $revenusParEvenement[] = round($row['revenu'], 2);
}

// Répartition des utilisateurs
$res = $conn->query("
    SELECT Type_utilisateur, COUNT(*) AS nb
    FROM utilisateur
    GROUP BY Type_utilisateur
");
while ($row = $res->fetch_assoc()) {
    $typesUtilisateur[] = $row['Type_utilisateur'];
    $totalUtilisateurs[] = $row['nb'];
}


?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>🎛️ Dashboard Statistique</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
           background-image: url('image/carlos-muza-hpjSkU2UYSU-unsplash.jpg');
            background-size: cover;
            background-position: center;
            color: #333;
        }

        header {
            background-color: #ff2f00ff;
            color: white;
            padding: 20px;
            text-align: center;
            font-size: 26px;
        }

        .tab-container {
            margin: 30px auto;
            width: 90%;
            max-width: 800px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }

        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 2px solid #ff2f00ff;
        }

        .tab {
            flex: 1;
            padding: 2px;
            text-align: center;
            cursor: pointer;
            font-weight: bold;
            background-color: #f1f1f1;
        }

        .tab.active {
            background-color: #ff2f00ff;
            color: white;
        }

        .chart-content {
            display: none;
        }

        .chart-content.active {
            display: block;
        }

        canvas {
            width: 90% !important;
            height: 300px !important;
        }

        .btn-retour {
            display: block;
            margin: 30px auto;
            padding: 12px 24px;
            background-color: #ff2f00ff;
            color: white;
            text-align: center;
            border-radius: 6px;
            text-decoration: none;
            width: 200px;
        }

        .btn-retour:hover {
            background-color: #ff2f00ff;
        }
    </style>
</head>

<body>
    <header>📊 Tableau de Bord Statistique</header>

    <div class="tab-container">
        <div class="tabs">
            <div class="tab active" onclick="switchTab(0)">Événements par Catégorie</div>
            <div class="tab" onclick="switchTab(1)">Tickets Vendus</div>
            <div class="tab" onclick="switchTab(2)">Revenus par Événement</div>
            <div class="tab" onclick="switchTab(3)">Utilisateurs</div>
        </div>

        <div class="chart-content active">
            <canvas id="chartCategorie"></canvas>
        </div>
        <div class="chart-content">
            <canvas id="chartTickets"></canvas>
        </div>
        <div class="chart-content">
            <canvas id="chartRevenus"></canvas>
        </div>
        <div class="chart-content">
            <canvas id="chartUtilisateurs"></canvas>
        </div>
    </div>
    <a href="export_pdf.php" class="btn-retour" target="_blank">📄 Télécharger le rapport PDF</a>

    <a class="btn-retour" href="menu_admin.php">⬅️ Retour au menu</a>

    <script>
        function switchTab(index) {
            const tabs = document.querySelectorAll('.tab');
            const contents = document.querySelectorAll('.chart-content');
            tabs.forEach((tab, i) => tab.classList.toggle('active', i === index));
            contents.forEach((content, i) => content.classList.toggle('active', i === index));
        }

        new Chart(document.getElementById('chartCategorie'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($labelsCategorie) ?>,
                datasets: [{
                    label: 'Nombre d\'événements',
                    data: <?= json_encode($nbEvenementsParCategorie) ?>,
                    backgroundColor: '#ff2f00ff'
                }]
            },
            options: { responsive: true, scales: { y: { beginAtZero: true } } }
        });

        new Chart(document.getElementById('chartTickets'), {
            type: 'pie',
            data: {
                labels: <?= json_encode($labelsEvenements) ?>,
                datasets: [{
                    data: <?= json_encode($nbVentesParEvenement) ?>,
                    backgroundColor: ['#007bff', '#28a745', '#ffc107', '#ff2f00ff', '#17a2b8']
                }]
            }
        });

        new Chart(document.getElementById('chartRevenus'), {
            type: 'line',
            data: {
                labels: <?= json_encode($labelsEvenements) ?>,
                datasets: [{
                    label: 'Revenu (CFA)',
                    data: <?= json_encode($revenusParEvenement) ?>,
                    borderColor: '#ff2f00ff',
                    fill: false
                }]
            },
            options: { responsive: true, scales: { y: { beginAtZero: true } } }
        });

        new Chart(document.getElementById('chartUtilisateurs'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($typesUtilisateur) ?>,
                datasets: [{
                    data: <?= json_encode($totalUtilisateurs) ?>,
                    backgroundColor: ['#17a2b8', '#ffc107', '#6c757d']
                }]
            }
        });
    </script>
</body>

</html>
