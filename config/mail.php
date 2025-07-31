<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/qrcode.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Configuration SMTP - À personnaliser selon votre fournisseur
 */
class MailConfig {
    const SMTP_HOST = 'smtp.gmail.com';
    const SMTP_PORT = 587;
    const SMTP_USERNAME = 'eventci2025@gmail.com';
    const SMTP_PASSWORD = 'cprw cujr qjpm ucwc';
    const FROM_EMAIL = 'eventci2025@gmail.com';
    const FROM_NAME = 'EventCI';
    const CHARSET = 'UTF-8';
}

/**
 * Initialise et configure PHPMailer
 * @return PHPMailer
 * @throws Exception
 */
function initMailer() {
    $mail = new PHPMailer(true);

    try {
        // Configuration serveur SMTP
        $mail->isSMTP();
        $mail->Host = MailConfig::SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = MailConfig::SMTP_USERNAME;
        $mail->Password = MailConfig::SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = MailConfig::SMTP_PORT;

        // Configuration générale
        $mail->setFrom(MailConfig::FROM_EMAIL, MailConfig::FROM_NAME);
        $mail->CharSet = MailConfig::CHARSET;
        $mail->isHTML(true);

        return $mail;
    } catch (Exception $e) {
        throw new Exception("Erreur d'initialisation du mailer: " . $e->getMessage());
    }
}

/**
 * Template HTML de base avec CSS intégré
 */
function getEmailTemplate($title, $content, $footerText = '') {
    $defaultFooter = $footerText ?: 'Cet email a été envoyé automatiquement, merci de ne pas répondre.';

    return "
    <!DOCTYPE html>
    <html lang='fr'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>{$title}</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                line-height: 1.6;
                color: #333;
                background-color: #f8f8f8;
            }

            .email-container {
                max-width: 600px;
                margin: 20px auto;
                background-color: #ffffff;
                border-radius: 10px;
                overflow: hidden;
                box-shadow: 0 0 20px rgba(0,0,0,0.1);
            }

            .header {
                background: linear-gradient(135deg, #d1410c 0%, #ff7043 100%);
                color: white;
                padding: 30px;
                text-align: center;
            }

            .header h1 {
                font-size: 28px;
                margin-bottom: 10px;
                font-weight: 300;
            }

            .content {
                padding: 40px 30px;
            }

            .content h2 {
                color: #d1410c;
                margin-bottom: 20px;
                font-size: 24px;
            }

            .content h3 {
                color: #555;
                margin: 25px 0 15px 0;
                font-size: 18px;
            }

            .content p {
                margin-bottom: 15px;
                color: #666;
            }

            .btn {
                display: inline-block;
                background: linear-gradient(135deg, #d1410c 0%, #ff7043 100%);
                color: white !important;
                padding: 15px 30px;
                text-decoration: none;
                border-radius: 25px;
                margin: 20px 0;
                font-weight: bold;
                text-align: center;
                transition: transform 0.2s ease;
            }

            .btn:hover {
                transform: translateY(-2px);
            }

            .info-box {
                background-color: #f2f2f2;
                border-left: 4px solid #d1410c;
                padding: 20px;
                margin: 20px 0;
                border-radius: 0 5px 5px 0;
            }

            .qr-code-container {
                text-align: center;
                margin: 30px 0;
                padding: 20px;
                background-color: #f8f9fa;
                border-radius: 10px;
                border: 2px dashed #d1410c;
            }

            .qr-code-container img {
                max-width: 200px;
                height: auto;
                border: 3px solid #fff;
                border-radius: 10px;
                box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            }

            .footer {
                background-color: #f8f9fa;
                padding: 20px;
                text-align: center;
                border-top: 1px solid #e9ecef;
                color: #6c757d;
                font-size: 12px;
            }

            .divider {
                height: 2px;
                background: linear-gradient(135deg, #d1410c 0%, #ff7043 100%);
                margin: 30px 0;
                border-radius: 1px;
            }

            @media only screen and (max-width: 600px) {
                .email-container {
                    margin: 10px;
                    border-radius: 0;
                }

                .content, .header {
                    padding: 20px;
                }

                .header h1 {
                    font-size: 24px;
                }
            }
        </style>
    </head>
    <body>
        <div class='email-container'>
            <div class='header'>
                <h1>{$title}</h1>
            </div>
            <div class='content'>
                {$content}
            </div>
            <div class='footer'>
                {$defaultFooter}
            </div>
        </div>
    </body>
    </html>";
}

/**
 * Génère un QR code et le retourne en base64
 * @param string $data Données à encoder
 * @return string QR code en base64 ou chaîne vide si erreur
 */
function generateQRCodeBase64($data) {
    try {
        // Utilise la fonction de votre fichier qrcode.php
        if (function_exists('generateQRCode')) {
            // Génère le QR code et le sauvegarde temporairement
            $tempFile = sys_get_temp_dir() . '/qr_' . uniqid() . '.png';
            $qrResult = generateQRCode($data, $tempFile);

            if ($qrResult && file_exists($tempFile)) {
                // Lit le fichier et le convertit en base64
                $imageData = file_get_contents($tempFile);
                $base64 = base64_encode($imageData);

                // Supprime le fichier temporaire
                unlink($tempFile);

                return 'data:image/png;base64,' . $base64;
            }
        }

        return '';
    } catch (Exception $e) {
        error_log("Erreur génération QR code: " . $e->getMessage());
        return '';
    }
}

/**
 * Envoie un email simple avec template
 */
function sendSimpleEmail($to, $subject, $title, $content, $replyTo = null) {
    try {
        $mail = initMailer();

        $mail->addAddress($to);
        if ($replyTo) {
            $mail->addReplyTo($replyTo);
        }

        $mail->Subject = $subject;
        $mail->Body = getEmailTemplate($title, $content);

        return $mail->send();
    } catch (Exception $e) {
        error_log("Erreur envoi email: " . $e->getMessage());
        return false;
    }
}

/**
 * Envoie un email avec pièce jointe (QR code)
 * @param string $to Email destinataire
 * @param string $subject Sujet
 * @param string $title Titre
 * @param string $content Contenu HTML
 * @param string $qrCodePath Chemin vers le fichier QR code
 * @param string $replyTo Email de réponse (optionnel)
 * @return bool Succès de l'envoi
 */
function sendEmailWithAttachment($to, $subject, $title, $content, $qrCodePath = null, $replyTo = null) {
    try {
        $mail = initMailer();

        $mail->addAddress($to);
        if ($replyTo) {
            $mail->addReplyTo($replyTo);
        }

        // Ajouter la pièce jointe QR code si fournie
        if ($qrCodePath && file_exists($qrCodePath)) {
            $mail->addAttachment($qrCodePath, 'qr-code-ticket.png');
        }

        $mail->Subject = $subject;
        $mail->Body = getEmailTemplate($title, $content);

        return $mail->send();
    } catch (Exception $e) {
        error_log("Erreur envoi email avec pièce jointe: " . $e->getMessage());
        return false;
    }
}

/**
 * Envoie un email de bienvenue
 */
function sendWelcomeEmail($to, $username, $activationLink = null) {
    $title = "Bienvenue sur l'application";
    $subject = "Bienvenue sur EventCI, " . $username . " !";

    $content = "
        <h2 style='color: #d1410c;'>Bonjour " . htmlspecialchars($username) . " !</h2>
        <p style='color: #333;'>Nous sommes ravis de vous accueillir sur EventCI, votre plateforme de billetterie en ligne.</p>

        <div class='info-box'>
            <h3 style='color: #555;'>Prochaines étapes :</h3>
            <p style='color: #666;'>• Complétez votre profil</p>
            <p style='color: #666;'>• Découvrez les événements disponibles</p>
            <p style='color: #666;'>• Achetez vos premiers tickets</p>
        </div>";

    if ($activationLink) {
        $content .= "
        <p style='color: #333;'>Pour commencer, veuillez activer votre compte en cliquant sur le bouton ci-dessous :</p>
        <div style='text-align: center;'>
            <a href='" . htmlspecialchars($activationLink) . "' class='btn'>Activer mon compte</a>
        </div>";
    }

    $content .= "
        <div class='divider'></div>
        <p style='color: #333;'>Si vous avez des questions, n'hésitez pas à nous contacter.</p>
        <p style='color: #333;'><strong>L'équipe EventCI</strong></p>";

    return sendSimpleEmail($to, $subject, $title, $content);
}

/**
 * SOLUTION 1: Envoie un email de reçu avec QR code intégré en base64
 */
function sendTicketReceiptEmailWithQR(string $to, string $username, array $ticketData, string $viewTicketUrl = ''): bool
{
    $title = "Reçu de commande de ticket";
    $subject = "Confirmation de votre achat de ticket - EventCI";

    // Formatage de la date d'achat
    $dateAchat = isset($ticketData['DateAchat']) ? new DateTime($ticketData['DateAchat']) :
        (isset($ticketData['DatePaiement']) ? new DateTime($ticketData['DatePaiement']) : new DateTime());
    $dateAchatFormatted = $dateAchat->format('d/m/Y à H:i');

    // Formatage du prix
    $prix = isset($ticketData['Prix']) ? $ticketData['Prix'] : 0;
    $prixFormatted = number_format($prix, 0, '', ' ') . ' FCFA';

    $content = "
        <h2 style='color: #d1410c;'>Merci pour votre achat, " . htmlspecialchars($username) . " !</h2>
        <p style='color: #333;'>Votre commande a été confirmée et votre ticket est prêt.</p>

        <div class='info-box'>
            <h3 style='color: #555;'>Détails de votre ticket :</h3>
            <p style='color: #666;'><strong>Événement :</strong> " . htmlspecialchars($ticketData['Titre_Evenement'] ?? 'N/A') . "</p>
            <p style='color: #666;'><strong>Type de ticket :</strong> " . htmlspecialchars($ticketData['Titre_Ticket'] ?? 'N/A') . "</p>
            <p style='color: #666;'><strong>Date de l'événement :</strong> " . htmlspecialchars($ticketData['Date_Evenement'] ?? 'N/A') . "</p>
            <p style='color: #666;'><strong>Lieu :</strong> " . htmlspecialchars($ticketData['Lieu'] ?? 'N/A') . "</p>
            <p style='color: #666;'><strong>Prix :</strong> " . $prixFormatted . "</p>
            <p style='color: #666;'><strong>Date d'achat :</strong> " . $dateAchatFormatted . "</p>
            <p style='color: #666;'><strong>Numéro de commande :</strong> " . htmlspecialchars($ticketData['Id_Achat'] ?? 'N/A') . "</p>
        </div>";

    // Générer le QR code en base64 si des données sont disponibles
    $qrData = $ticketData['QRData'] ?? $ticketData['Id_Achat'] ?? '';
    if (!empty($qrData)) {
        $qrCodeBase64 = generateQRCodeBase64($qrData);
        if (!empty($qrCodeBase64)) {
            $content .= "
            <div class='qr-code-container'>
                <h3 style='color: #555; margin-bottom: 15px;'>Votre QR Code :</h3>
                <img src='" . $qrCodeBase64 . "' alt='QR Code' style='max-width: 200px; margin: 10px auto;'>
                <p style='color: #666; margin-top: 15px;'>Présentez ce QR code à l'entrée de l'événement pour valider votre ticket.</p>
            </div>";
        }
    }

    if ($viewTicketUrl) {
        $content .= "
        <div style='text-align: center;'>
            <a href='" . htmlspecialchars($viewTicketUrl) . "' class='btn'>Voir mon ticket</a>
        </div>";
    }

    $content .= "
        <div class='divider'></div>
        <p style='color: #333;'>Conservez précieusement ce reçu, il pourra vous être demandé lors de l'événement.</p>
        <p style='color: #333;'>Si vous avez des questions concernant votre achat, n'hésitez pas à nous contacter.</p>
        <p style='color: #333;'><strong>L'équipe EventCI</strong></p>";

    return sendSimpleEmail($to, $subject, $title, $content);
}

/**
 * SOLUTION 2: Envoie un email de reçu avec QR code en pièce jointe
 */
function sendTicketReceiptEmailWithAttachment(string $to, string $username, array $ticketData, string $viewTicketUrl = ''): bool
{
    $title = "Reçu de commande de ticket";
    $subject = "Confirmation de votre achat de ticket - EventCI";

    // Formatage de la date d'achat
    $dateAchat = isset($ticketData['DateAchat']) ? new DateTime($ticketData['DateAchat']) :
        (isset($ticketData['DatePaiement']) ? new DateTime($ticketData['DatePaiement']) : new DateTime());
    $dateAchatFormatted = $dateAchat->format('d/m/Y à H:i');

    // Formatage du prix
    $prix = isset($ticketData['Prix']) ? $ticketData['Prix'] : 0;
    $prixFormatted = number_format($prix, 0, '', ' ') . ' FCFA';

    $content = "
        <h2 style='color: #d1410c;'>Merci pour votre achat, " . htmlspecialchars($username) . " !</h2>
        <p style='color: #333;'>Votre commande a été confirmée et votre ticket est prêt.</p>

        <div class='info-box'>
            <h3 style='color: #555;'>Détails de votre ticket :</h3>
            <p style='color: #666;'><strong>Événement :</strong> " . htmlspecialchars($ticketData['Titre_Evenement'] ?? 'N/A') . "</p>
            <p style='color: #666;'><strong>Type de ticket :</strong> " . htmlspecialchars($ticketData['Titre_Ticket'] ?? 'N/A') . "</p>
            <p style='color: #666;'><strong>Date de l'événement :</strong> " . htmlspecialchars($ticketData['Date_Evenement'] ?? 'N/A') . "</p>
            <p style='color: #666;'><strong>Lieu :</strong> " . htmlspecialchars($ticketData['Lieu'] ?? 'N/A') . "</p>
            <p style='color: #666;'><strong>Prix :</strong> " . $prixFormatted . "</p>
            <p style='color: #666;'><strong>Date d'achat :</strong> " . $dateAchatFormatted . "</p>
            <p style='color: #666;'><strong>Numéro de commande :</strong> " . htmlspecialchars($ticketData['Id_Achat'] ?? 'N/A') . "</p>
        </div>";

    // Générer le QR code dans un fichier temporaire
    $qrCodePath = null;
    $qrData = $ticketData['QRData'] ?? $ticketData['Id_Achat'] ?? '';
    if (!empty($qrData) && function_exists('generateQRCode')) {
        $qrCodePath = sys_get_temp_dir() . '/qr_ticket_' . $ticketData['Id_Achat'] . '.png';
        generateQRCode($qrData, $qrCodePath);

        $content .= "
        <div style='text-align: center; margin: 30px 0;'>
            <div style='background-color: #f8f9fa; padding: 20px; border-radius: 10px; border: 2px dashed #d1410c;'>
                <h3 style='color: #555; margin-bottom: 15px;'>Votre QR Code :</h3>
                <p style='color: #666;'>Le QR code de votre ticket est joint à cet email en pièce jointe.</p>
                <p style='color: #666;'>Téléchargez-le et présentez-le à l'entrée de l'événement pour valider votre ticket.</p>
            </div>
        </div>";
    }

    if ($viewTicketUrl) {
        $content .= "
        <div style='text-align: center;'>
            <a href='" . htmlspecialchars($viewTicketUrl) . "' class='btn'>Voir mon ticket</a>
        </div>";
    }

    $content .= "
        <div class='divider'></div>
        <p style='color: #333;'>Conservez précieusement ce reçu, il pourra vous être demandé lors de l'événement.</p>
        <p style='color: #333;'>Si vous avez des questions concernant votre achat, n'hésitez pas à nous contacter.</p>
        <p style='color: #333;'><strong>L'équipe EventCI</strong></p>";

    // Envoyer l'email avec ou sans pièce jointe
    $result = sendEmailWithAttachment($to, $subject, $title, $content, $qrCodePath);

    // Nettoyer le fichier temporaire
    if ($qrCodePath && file_exists($qrCodePath)) {
        unlink($qrCodePath);
    }

    return $result;
}

/**
 * Fonction originale modifiée (pour compatibilité)
 */
function sendTicketReceiptEmail(string $to, string $username, array $ticketData, string $viewTicketUrl = ''): bool
{
    // Utilise la nouvelle version avec QR code intégré
    return sendTicketReceiptEmailWithQR($to, $username, $ticketData, $viewTicketUrl);
}

/**
 * Exemple d'utilisation
 */
function exempleUtilisation() {
    // Email de bienvenue
    $success = sendWelcomeEmail(
        'elielassy06@gmail.com',
        'Jean Dupont',
        'https://eventci.com/activation?token=123456'
    );

    // Email de reçu de ticket avec QR code
    $ticketData = [
        'Id_Achat' => 12345,
        'Titre_Evenement' => 'Concert de Jazz',
        'Titre_Ticket' => 'Place VIP',
        'Date_Evenement' => '15/12/2023 à 20:00',
        'Lieu' => 'Salle de concert, Paris',
        'Prix' => 45.00,
        'DateAchat' => '2023-11-25 14:30:00',
        'QRData' => 'TICKET-12345-CONCERT-JAZZ-VIP' // Données pour le QR code
    ];

    // SOLUTION 1: QR code intégré dans l'email
    $success1 = sendTicketReceiptEmailWithQR(
        'elielassy06@gmail.com',
        'Jean Dupont',
        $ticketData,
        'https://eventci.com/ticket?id=12345'
    );

    // SOLUTION 2: QR code en pièce jointe
    $success2 = sendTicketReceiptEmailWithAttachment(
        'elielassy06@gmail.com',
        'Jean Dupont',
        $ticketData,
        'https://eventci.com/ticket?id=12345'
    );

    if ($success1 && $success2) {
        echo "Emails envoyés avec succès!\n";
    } else {
        echo "Erreur lors de l'envoi des emails.\n";
    }
}

?>