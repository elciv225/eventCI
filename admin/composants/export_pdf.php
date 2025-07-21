<?php
require('fpdf186/fpdf.php');
session_start();
require_once 'base.php';

// 🛡️ Sécurité : accès réservé à l'admin fixe
if (!isset($_SESSION['is_admin_fixed']) || $_SESSION['is_admin_fixed'] !== true) {
    header("Location: connexion.php");
    exit("Accès refusé.");
}

// --- 🔍 Récupération des statistiques globales ---
function getValeur($conn, $sql, $champ) {
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        return $row[$champ] ?? 0;
    }
    return 0;
}

$totalUsers = getValeur($conn, "SELECT COUNT(Id_Utilisateur) AS total_users FROM utilisateur", "total_users");
$totalEvents = getValeur($conn, "SELECT COUNT(Id_Evenement) AS total_events FROM evenement", "total_events");
$totalTickets = getValeur($conn, "SELECT COUNT(Id_Achat) AS total_tickets FROM achat", "total_tickets");
$totalRevenue = getValeur($conn, "
    SELECT SUM(te.Prix) AS total_revenue
    FROM achat a
    JOIN ticketevenement te ON a.Id_TicketEvenement = te.Id_TicketEvenement", "total_revenue");

// --- 📊 Récupération des données détaillées ---
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
    ORDER BY TicketsVendus DESC
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
    ORDER BY revenu DESC
");
$labelsRevenusEvenements = [];
while ($row = $res->fetch_assoc()) {
    $labelsRevenusEvenements[] = $row['Titre'];
    $revenusParEvenement[] = round($row['revenu'], 2);
}

// Répartition des utilisateurs
$res = $conn->query("
    SELECT Type_utilisateur, COUNT(*) AS nb
    FROM utilisateur
    GROUP BY Type_utilisateur
    ORDER BY nb DESC
");
while ($row = $res->fetch_assoc()) {
    $typesUtilisateur[] = $row['Type_utilisateur'];
    $totalUtilisateurs[] = $row['nb'];
}

$conn->close();

// --- 📄 Classe PDF personnalisée avec méthodes de graphiques ---
class PDF extends FPDF
{
    // Méthode pour créer un graphique en barres simple
    function BarChart($w, $h, $data, $format, $color = array(100, 100, 255))
    {
        $this->SetFillColor($color[0], $color[1], $color[2]);
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(0);
        
        $max = max($data);
        if($max == 0) $max = 1;
        
        $w_bar = $w / count($data);
        $x = $this->GetX();
        $y = $this->GetY();
        
        foreach($data as $i => $val) {
            $height = ($val / $max) * $h;
            $this->Rect($x + $i * $w_bar, $y + $h - $height, $w_bar - 2, $height, 'F');
            
            // Valeur au-dessus de la barre
            $this->SetXY($x + $i * $w_bar, $y + $h - $height - 5);
            $this->Cell($w_bar, 5, sprintf($format, $val), 0, 0, 'C');
        }
        
        $this->SetY($y + $h + 5);
    }
    
    // Méthode pour créer un graphique en secteurs simple (pie chart textuel)
    function PieChartText($data, $labels)
    {
        $total = array_sum($data);
        if($total == 0) return;
        
        $this->SetFont('Arial', '', 10);
        foreach($data as $i => $val) {
            $percentage = round(($val / $total) * 100, 1);
            $blocks = str_repeat('█', min(round($percentage / 2), 50));
            $this->Cell(0, 6, $labels[$i] . ': ' . $val . ' (' . $percentage . '%) ' . $blocks, 0, 1);
        }
        $this->Ln(5);
    }
    
    // Graphique linéaire simple (représentation textuelle)
    function LineChartText($data, $labels, $format)
    {
        $this->SetFont('Arial', '', 10);
        $max = max($data);
        if($max == 0) $max = 1;
        
        foreach($data as $i => $val) {
            $blocks = str_repeat('▓', min(round(($val / $max) * 30), 30));
            $this->Cell(0, 6, substr($labels[$i], 0, 25) . ': ' . sprintf($format, $val) . ' ' . $blocks, 0, 1);
        }
        $this->Ln(5);
    }
}

// 📄 Création du PDF avec graphiques
$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, '📊 Rapport Statistique Complet avec Graphiques', 0, 1, 'C');
$pdf->Ln(10);

// 📌 Statistiques globales
$pdf->SetFont('Arial', 'BU', 14);
$pdf->Cell(0, 10, 'Statistiques Générales', 0, 1, 'L');
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 8, "👥 Utilisateurs enregistrés : " . number_format($totalUsers, 0, ',', ' '), 0, 1);
$pdf->Cell(0, 8, "📅 Événements publiés : " . number_format($totalEvents, 0, ',', ' '), 0, 1);
$pdf->Cell(0, 8, "🎟️ Tickets vendus : " . number_format($totalTickets, 0, ',', ' '), 0, 1);
$pdf->Cell(0, 8, "💰 Chiffre d'affaires : " . number_format($totalRevenue, 2, ',', ' ') . " CFA", 0, 1);
$pdf->Ln(10);

// 📊 Graphique : Événements par catégorie
if (!empty($labelsCategorie)) {
    $pdf->SetFont('Arial', 'BU', 14);
    $pdf->Cell(0, 10, 'Graphique : Événements par Catégorie', 0, 1, 'L');
    $pdf->Ln(5);
    
    // Graphique en barres
    $pdf->BarChart(170, 50, $nbEvenementsParCategorie, '%.0f', array(0, 123, 255));
    
    // Labels des catégories
    $pdf->SetFont('Arial', '', 9);
    $w_label = 170 / count($labelsCategorie);
    $x_start = $pdf->GetX();
    foreach($labelsCategorie as $i => $label) {
        $pdf->SetXY($x_start + $i * $w_label, $pdf->GetY());
        $pdf->Cell($w_label, 5, substr($label, 0, 12), 0, 0, 'C');
    }
    $pdf->Ln(15);
}

// 🎟️ Graphique : Tickets vendus (Pie Chart textuel)
if (!empty($labelsEvenements)) {
    $pdf->SetFont('Arial', 'BU', 14);
    $pdf->Cell(0, 10, 'Graphique : Répartition des Tickets Vendus', 0, 1, 'L');
    $pdf->Ln(5);
    $pdf->PieChartText($nbVentesParEvenement, $labelsEvenements);
}

// 💰 Graphique : Revenus par événement (Line Chart textuel)
if (!empty($labelsRevenusEvenements)) {
    $pdf->SetFont('Arial', 'BU', 14);
    $pdf->Cell(0, 10, 'Graphique : Revenus par Événement', 0, 1, 'L');
    $pdf->Ln(5);
    $pdf->LineChartText($revenusParEvenement, $labelsRevenusEvenements, '%.2f CFA');
}

// 👥 Graphique : Répartition des utilisateurs
if (!empty($typesUtilisateur)) {
    $pdf->SetFont('Arial', 'BU', 14);
    $pdf->Cell(0, 10, 'Graphique : Répartition des Utilisateurs', 0, 1, 'L');
    $pdf->Ln(5);
    $pdf->PieChartText($totalUtilisateurs, $typesUtilisateur);
}

// --- Section détaillée (votre code original) ---
$pdf->AddPage();
$pdf->SetFont('Arial', 'BU', 14);
$pdf->Cell(0, 10, 'Détails par Section', 0, 1, 'L');

// 📚 Répartition des événements par catégorie (détail)
if (!empty($labelsCategorie)) {
    $pdf->SetFont('Arial', 'BU', 12);
    $pdf->Cell(0, 8, 'Événements par Catégorie (Détail)', 0, 1, 'L');
    $pdf->SetFont('Arial', '', 10);
    foreach ($labelsCategorie as $index => $cat) {
        $total = $nbEvenementsParCategorie[$index];
        $barre = str_repeat('█', min(round($total / max($nbEvenementsParCategorie) * 30), 30));
        $pdf->Cell(0, 6, "$cat : $total $barre", 0, 1);
    }
    $pdf->Ln(8);
}

// 🎟️ Tickets vendus par événement (détail)
if (!empty($labelsEvenements)) {
    $pdf->SetFont('Arial', 'BU', 12);
    $pdf->Cell(0, 8, 'Tickets Vendus par Événement (Détail)', 0, 1, 'L');
    $pdf->SetFont('Arial', '', 10);
    foreach ($labelsEvenements as $index => $event) {
        $tickets = $nbVentesParEvenement[$index];
        $pdf->Cell(0, 6, substr($event, 0, 40) . " : " . number_format($tickets, 0, ',', ' ') . " tickets", 0, 1);
    }
    $pdf->Ln(8);
}

// 💰 Revenus par Événement (détail)
if (!empty($labelsRevenusEvenements)) {
    $pdf->SetFont('Arial', 'BU', 12);
    $pdf->Cell(0, 8, 'Revenus par Événement (Détail)', 0, 1, 'L');
    $pdf->SetFont('Arial', '', 10);
    foreach ($labelsRevenusEvenements as $index => $event) {
        $revenue = $revenusParEvenement[$index];
        $pdf->Cell(0, 6, substr($event, 0, 40) . " : " . number_format($revenue, 2, ',', ' ') . " CFA", 0, 1);
    }
    $pdf->Ln(8);
}

// 👥 Répartition des Utilisateurs (détail)
if (!empty($typesUtilisateur)) {
    $pdf->SetFont('Arial', 'BU', 12);
    $pdf->Cell(0, 8, 'Répartition des Utilisateurs (Détail)', 0, 1, 'L');
    $pdf->SetFont('Arial', '', 10);
    foreach ($typesUtilisateur as $index => $type) {
        $count = $totalUtilisateurs[$index];
        $pdf->Cell(0, 6, "$type : " . number_format($count, 0, ',', ' ') . " utilisateurs", 0, 1);
    }
}

// Pied de page avec date de génération
$pdf->SetY(-30);
$pdf->SetFont('Arial', 'I', 8);
$pdf->Cell(0, 10, 'Rapport généré le ' . date('d/m/Y à H:i'), 0, 0, 'C');

$pdf->Output('rapport_statistique_avec_graphiques.pdf', 'D');
?>