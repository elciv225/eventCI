<?php
$recherche = $_GET['recherche'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$start = ($page - 1) * $limit;

if ($recherche) {
    $stmt = $conn->prepare("
        SELECT * 
        FROM ticketevenement 
        WHERE Titre LIKE ? 
        ORDER BY Id_TicketEvenement DESC 
        LIMIT ?, ?
    ");
    $searchTerm = '%' . $recherche . '%';
    $stmt->bind_param("sii", $searchTerm, $start, $limit);
} else {
    $stmt = $conn->prepare("
        SELECT * 
        FROM ticketevenement 
        ORDER BY Id_TicketEvenement DESC 
        LIMIT ?, ?
    ");
    $stmt->bind_param("ii", $start, $limit);
}

$stmt->execute();
$tickets = $stmt->get_result();

$countQuery = $conn->query("SELECT COUNT(*) AS total FROM ticketevenement");
$totalRows = $countQuery->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Tickets Événement</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&family=Inter:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <style>
        /* Custom CSS for specific elements not easily achievable with pure Tailwind or for consistency */
        body {
            font-family: 'Inter', sans-serif;
        }

        h2 {
            font-family: 'Montserrat', sans-serif;
        }

        /* Custom background pattern */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('image/andy-li-A_dJOYpxEVU-unsplash.jpg');
            opacity: 1;
            z-index: -1;
        }

        /* Custom underline for H2 */
        h2::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: 0;
            transform: translateX(-50%);
            width: 70px;
            height: 3px;
            /* primary-blue */
            border-radius: 2px;
        }

        /* Specific hover effects for buttons not covered by simple Tailwind classes */
        .btn-primary:hover {
            background: linear-gradient(135deg, #2563EB 0%, #1D4ED8 100%);
        }

        .btn-back:hover {
            background: linear-gradient(135deg, #4B5563 0%, #2D3748 100%);
        }

        .btn-edit:hover {
            background-color: #2563EB;
            /* Darker blue */
        }

        .btn-delete:hover {
            background-color: #DC2626;
            /* Darker red */
        }

        .pagination-link.active {
            background: linear-gradient(135deg, #1D4ED8 0%, #1E40AF 100%);
            /* Darker blue for active */
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>

<body
    class="bg-gradient-to-br from-blue-100 via-blue-100 to-indigo-200 min-h-screen p-5 flex flex-col items-center overflow-x-hidden relative">

    <h2
        class="font-montserrat text-center text-gray-800 mb-8 text-3xl font-extrabold relative pb-4 flex items-center justify-center gap-2 uppercase tracking-wide text-shadow-sm">
        <i data-lucide="ticket" class="w-6 h-6 text-blue-500 stroke-[2.5]"></i> Gestion des Tickets Événement
    </h2>

    <div class="back text-center mb-6">
        <a href="menu_admin.php"
            class="bg-gradient-to-br from-gray-600 to-gray-700 text-white no-underline py-2.5 px-5 rounded-full font-semibold transition-all duration-300 shadow-md inline-flex items-center gap-2 btn-back">
            <i data-lucide="arrow-left" class="w-5 h-5"></i> Retour au menu admin
        </a>
    </div>

    <div
        class="search text-center mb-8 flex justify-center items-center w-full max-w-md bg-blue-50 p-4 rounded-md shadow-inner border border-gray-300">
        <form method="GET" action="" class="flex gap-2 w-full">
            <input type="text" name="recherche" placeholder="🔍 Rechercher un titre"
                value="<?= htmlspecialchars($recherche ?? ''); ?>"
                class="flex-grow py-2.5 px-4 border border-gray-300 rounded-md outline-none text-base bg-white text-gray-800 transition-all duration-300 focus:border-blue-500 focus:shadow-outline-blue">
            <button type="submit"
                class="py-2.5 px-4 bg-gradient-to-br from-blue-500 to-blue-600 text-white border-none rounded-md cursor-pointer text-base font-semibold transition-all duration-300 shadow-md flex items-center gap-1 btn-primary">
                <i data-lucide="search" class="w-5 h-5"></i> Rechercher
            </button>
        </form>
    </div>

    <div class="ticket-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 w-full max-w-6xl mb-8">
        <?php if (isset($tickets) && $tickets->num_rows > 0): ?>
            <?php while ($ticket = $tickets->fetch_assoc()): ?>
                <div
                    class="ticket-card bg-white rounded-lg shadow-md p-4 flex flex-col justify-between transition-all duration-300 border border-gray-200 hover:translate-y-[-3px] hover:shadow-lg">
                    <h3 class="font-montserrat text-blue-500 mb-2 text-lg font-bold"><?= htmlspecialchars($ticket['Titre']) ?>
                    </h3>
                    <p class="my-1 text-gray-700 text-sm"><strong>Description :</strong>
                        <?= htmlspecialchars($ticket['Description']) ?></p>
                    <p class="my-1 text-gray-700 text-sm"><strong>Prix :</strong>
                        <?= number_format($ticket['Prix'], 2, ',', ' ') ?> CFA</p>
                    <p class="my-1 text-gray-700 text-sm"><strong>Disponible :</strong> <?= $ticket['NombreDisponible'] ?></p>
                    <p class="my-1 text-gray-700 text-sm"><strong>ID Événement :</strong> <?= $ticket['Id_Evenement'] ?></p>
                    <div class="actions mt-4 flex justify-center gap-2">
                        <a href="modifier_ticket_evenement.php?id=<?= $ticket['Id_TicketEvenement'] ?>"
                            class="py-1.5 px-3 rounded-full font-semibold text-sm text-white inline-flex items-center gap-1 transition-all duration-300 shadow-sm bg-blue-500 btn-edit hover:shadow-md">
                            <i data-lucide="edit" class="w-4 h-4"></i> Modifier
                        </a>
                        <a href="supprimer_ticket_evenement.php?id=<?= $ticket['Id_TicketEvenement'] ?>"
                            class="py-1.5 px-3 rounded-full font-semibold text-sm text-white inline-flex items-center gap-1 transition-all duration-300 shadow-sm bg-red-500 btn-delete hover:shadow-md"
                            onclick="return confirm('Confirmer la suppression ?')">
                            <i data-lucide="trash-2" class="w-4 h-4"></i> Supprimer
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p
                class="no-users-message animate_animated animate_fadeIn col-span-full text-center p-6 bg-red-50 border border-red-300 rounded-md text-red-800 font-semibold mt-6 flex items-center justify-center gap-3 shadow-sm">
                <i data-lucide="info" class="w-6 h-6 text-red-500"></i> Aucun ticket trouvé pour votre recherche.
            </p>
        <?php endif; ?>
    </div>

    <div class="pagination text-center mt-6 flex justify-center gap-1.5 flex-wrap">
        <?php if (isset($totalPages) && $totalPages > 1): ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&recherche=<?= urlencode($recherche ?? '') ?>"
                    class="py-2 px-3 bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-md no-underline font-semibold transition-all duration-300 shadow-sm hover:translate-y-[-1px] hover:shadow-md pagination-link <?= (isset($page) && $i === $page) ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        <?php endif; ?>
    </div>

    <script>
        // Initialize Lucide icons after the DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();
        });
    </script>
</body>

</html>