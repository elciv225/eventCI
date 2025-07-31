<?php
require_once 'vendor/autoload.php';
require_once 'qrcode.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Configuration SMTP - À personnaliser selon votre fournisseur
 */
class MailConfig {
    const SMTP_HOST = 'smtp.gmail.com';
    const SMTP_PORT = 587;
    const SMTP_USERNAME = 'eventci2025@gmail.com'; // Doit être l'adresse email complète
    const SMTP_PASSWORD = 'cprw cujr qjpm ucwc'; // Mots de passe des applications Google
    const FROM_EMAIL = 'eventci2025@gmail.com'; // Email
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
 * @param string $title Titre de l'email
 * @param string $content Contenu HTML
 * @param string $footerText Texte du footer (optionnel)
 * @return string Template HTML complet
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
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                line-height: 1.6;
                color: #333;
                background-color: #f4f4f4;
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
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
                color: #667eea;
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
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
                background-color: #f8f9ff;
                border-left: 4px solid #667eea;
                padding: 20px;
                margin: 20px 0;
                border-radius: 0 5px 5px 0;
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
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
 * Envoie un email simple avec template
 * @param string $to Email destinataire
 * @param string $subject Sujet de l'email
 * @param string $title Titre affiché dans l'email
 * @param string $content Contenu HTML
 * @param string $replyTo Email de réponse (optionnel)
 * @return bool Succès de l'envoi
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
 * Envoie un email de bienvenue
 * @param string $to Email destinataire
 * @param string $username Nom d'utilisateur
 * @param string $activationLink Lien d'activation (optionnel)
 * @return bool Succès de l'envoi
 */
function sendWelcomeEmail($to, $username, $activationLink = null) {
    $title = "Bienvenue sur l'application";
    $subject = "Bienvenue sur EventCI, " . $username . " !";

    $content = "
        <h2 style='color: var(--text-highlight);'>Bonjour " . htmlspecialchars($username) . " !</h2>
        <p style='color: var(--text-primary);'>Nous sommes ravis de vous accueillir sur EventCI, votre plateforme de billetterie en ligne.</p>

        <div class='info-box' style='background-color: var(--bg-tertiary); border-left: 4px solid var(--text-highlight); padding: 20px; margin: 20px 0; border-radius: 0 5px 5px 0;'>
            <h3 style='color: var(--text-tertiary);'>Prochaines étapes :</h3>
            <p style='color: var(--text-secondary);'>• Complétez votre profil</p>
            <p style='color: var(--text-secondary);'>• Découvrez les événements disponibles</p>
            <p style='color: var(--text-secondary);'>• Achetez vos premiers tickets</p>
        </div>";

    if ($activationLink) {
        $content .= "
        <p style='color: var(--text-primary);'>Pour commencer, veuillez activer votre compte en cliquant sur le bouton ci-dessous :</p>
        <div style='text-align: center;'>
            <a href='" . htmlspecialchars($activationLink) . "' class='btn' style='display: inline-block; background: var(--text-highlight); color: white !important; padding: 15px 30px; text-decoration: none; border-radius: 25px; margin: 20px 0; font-weight: bold; text-align: center;'>Activer mon compte</a>
        </div>";
    }

    $content .= "
        <div class='divider' style='height: 2px; background: var(--text-highlight); margin: 30px 0; border-radius: 1px;'></div>
        <p style='color: var(--text-primary);'>Si vous avez des questions, n'hésitez pas à nous contacter.</p>
        <p style='color: var(--text-primary);'><strong>L'équipe EventCI</strong></p>";

    return sendSimpleEmail($to, $subject, $title, $content);
}

/**
 * Envoie un email de reçu de commande de ticket
 * @param string $to Email destinataire
 * @param string $username Nom d'utilisateur
 * @param array $ticketData Données du ticket acheté
 * @param string $viewTicketUrl URL pour voir le ticket (optionnel)
 * @return bool Succès de l'envoi
 */
function sendTicketReceiptEmail($to, $username, $ticketData, $viewTicketUrl = null) {
    $title = "Reçu de commande de ticket";
    $subject = "Confirmation de votre achat de ticket - EventCI";

    // Formatage de la date d'achat
    $dateAchat = isset($ticketData['DateAchat']) ? new DateTime($ticketData['DateAchat']) : new DateTime();
    $dateAchatFormatted = $dateAchat->format('d/m/Y à H:i');

    // Formatage du prix
    $prix = isset($ticketData['Prix']) ? $ticketData['Prix'] : 0;
    $prixFormatted = number_format($prix, 2, ',', ' ') . ' €';

    $content = "
        <h2 style='color: var(--text-highlight);'>Merci pour votre achat, " . htmlspecialchars($username) . " !</h2>
        <p style='color: var(--text-primary);'>Votre commande a été confirmée et votre ticket est prêt.</p>

        <div class='info-box' style='background-color: var(--bg-tertiary); border-left: 4px solid var(--text-highlight); padding: 20px; margin: 20px 0; border-radius: 0 5px 5px 0;'>
            <h3 style='color: var(--text-tertiary);'>Détails de votre ticket :</h3>
            <p style='color: var(--text-secondary);'><strong>Événement :</strong> " . htmlspecialchars($ticketData['Titre_Evenement'] ?? 'N/A') . "</p>
            <p style='color: var(--text-secondary);'><strong>Type de ticket :</strong> " . htmlspecialchars($ticketData['Titre_Ticket'] ?? 'N/A') . "</p>
            <p style='color: var(--text-secondary);'><strong>Date de l'événement :</strong> " . htmlspecialchars($ticketData['Date_Evenement'] ?? 'N/A') . "</p>
            <p style='color: var(--text-secondary);'><strong>Lieu :</strong> " . htmlspecialchars($ticketData['Lieu'] ?? 'N/A') . "</p>
            <p style='color: var(--text-secondary);'><strong>Prix :</strong> " . $prixFormatted . "</p>
            <p style='color: var(--text-secondary);'><strong>Date d'achat :</strong> " . $dateAchatFormatted . "</p>
            <p style='color: var(--text-secondary);'><strong>Numéro de commande :</strong> " . htmlspecialchars($ticketData['Id_Achat'] ?? 'N/A') . "</p>
        </div>";

    // Ajout du QR Code si disponible
    if (isset($ticketData['QRCode']) && !empty($ticketData['QRCode'])) {
        $content .= "
        <div style='text-align: center; margin: 30px 0;'>
            <h3 style='color: #555;'>Votre QR Code d'accès</h3>
            <p style='color: #666;'>Présentez ce code à l'entrée de l'événement.</p>
            <img src='" . $ticketData['QRCode'] . "' alt='QR Code de votre ticket' style='max-width: 200px; margin-top: 15px;' />
        </div>";
    }

    if ($viewTicketUrl) {
        $content .= "
        <div style='text-align: center;'>
            <a href='" . htmlspecialchars($viewTicketUrl) . "' class='btn' style='display: inline-block; background: var(--text-highlight); color: white !important; padding: 15px 30px; text-decoration: none; border-radius: 25px; margin: 20px 0; font-weight: bold; text-align: center;'>Voir mon ticket</a>
        </div>";
    }

    $content .= "
        <div class='divider' style='height: 2px; background: var(--text-highlight); margin: 30px 0; border-radius: 1px;'></div>
        <p style='color: var(--text-primary);'>Conservez précieusement ce reçu, il pourra vous être demandé lors de l'événement.</p>
        <p style='color: var(--text-primary);'>Si vous avez des questions concernant votre achat, n'hésitez pas à nous contacter.</p>
        <p style='color: var(--text-primary);'><strong>L'équipe EventCI</strong></p>";

    return sendSimpleEmail($to, $subject, $title, $content);
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

    // Email de reçu de ticket
    $ticketData = [
        'Id_Achat' => 12345,
        'Titre_Evenement' => 'Concert de Jazz',
        'Titre_Ticket' => 'Place VIP',
        'Date_Evenement' => '15/12/2023 à 20:00',
        'Lieu' => 'Salle de concert, Paris',
        'Prix' => 45.00,
        'DateAchat' => '2023-11-25 14:30:00'
    ];

    $success = sendTicketReceiptEmail(
        'elielassy06@gmail.com',
        'Jean Dupont',
        $ticketData,
        'https://eventci.com/ticket?id=12345'
    );

    if ($success) {
        echo "Emails envoyés avec succès!\n";
    } else {
        echo "Erreur lors de l'envoi des emails.\n";
    }
}

?>
