<?php
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Configuration SMTP - À personnaliser selon votre fournisseur
 */
class MailConfig {
    const SMTP_HOST = 'smtp.gmail.com';
    const SMTP_PORT = 587;
    const SMTP_USERNAME = 'votre-email@gmail.com';
    const SMTP_PASSWORD = 'votre-mot-de-passe-app';
    const FROM_EMAIL = 'votre-email@gmail.com';
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
    $title = "Bienvenue !";
    $subject = "Bienvenue sur notre plateforme, " . $username . " !";

    $content = "
        <h2>Bonjour " . htmlspecialchars($username) . " !</h2>
        <p>Nous sommes ravis de vous accueillir sur notre plateforme.</p>
        
        <div class='info-box'>
            <h3>Prochaines étapes :</h3>
            <p>• Complétez votre profil</p>
            <p>• Explorez nos fonctionnalités</p>
            <p>• Rejoignez notre communauté</p>
        </div>";

    if ($activationLink) {
        $content .= "
        <p>Pour commencer, veuillez activer votre compte en cliquant sur le bouton ci-dessous :</p>
        <div style='text-align: center;'>
            <a href='" . htmlspecialchars($activationLink) . "' class='btn'>Activer mon compte</a>
        </div>";
    }

    $content .= "
        <div class='divider'></div>
        <p>Si vous avez des questions, n'hésitez pas à nous contacter.</p>
        <p><strong>L'équipe</strong></p>";

    return sendSimpleEmail($to, $subject, $title, $content);
}

/**
 * Envoie un email de notification
 * @param string $to Email destinataire
 * @param string $notificationTitle Titre de la notification
 * @param string $message Message de la notification
 * @param string $actionUrl URL d'action (optionnel)
 * @param string $actionText Texte du bouton d'action (optionnel)
 * @return bool Succès de l'envoi
 */
function sendNotificationEmail($to, $notificationTitle, $message, $actionUrl = null, $actionText = 'Voir détails') {
    $title = "Notification";
    $subject = $notificationTitle;

    $content = "
        <h2>" . htmlspecialchars($notificationTitle) . "</h2>
        <p>" . nl2br(htmlspecialchars($message)) . "</p>";

    if ($actionUrl && $actionText) {
        $content .= "
        <div style='text-align: center; margin: 30px 0;'>
            <a href='" . htmlspecialchars($actionUrl) . "' class='btn'>" . htmlspecialchars($actionText) . "</a>
        </div>";
    }

    return sendSimpleEmail($to, $subject, $title, $content);
}

/**
 * Envoie un email de réinitialisation de mot de passe
 * @param string $to Email destinataire
 * @param string $username Nom d'utilisateur
 * @param string $resetLink Lien de réinitialisation
 * @param int $expiryMinutes Durée de validité en minutes
 * @return bool Succès de l'envoi
 */
function sendPasswordResetEmail($to, $username, $resetLink, $expiryMinutes = 30) {
    $title = "Réinitialisation de mot de passe";
    $subject = "Réinitialisation de votre mot de passe";

    $content = "
        <h2>Réinitialisation de mot de passe</h2>
        <p>Bonjour " . htmlspecialchars($username) . ",</p>
        <p>Vous avez demandé la réinitialisation de votre mot de passe.</p>
        
        <div class='info-box'>
            <p><strong>Important :</strong> Ce lien est valide pendant {$expiryMinutes} minutes seulement.</p>
        </div>
        
        <div style='text-align: center; margin: 30px 0;'>
            <a href='" . htmlspecialchars($resetLink) . "' class='btn'>Réinitialiser mon mot de passe</a>
        </div>
        
        <p>Si vous n'avez pas demandé cette réinitialisation, ignorez simplement cet email.</p>
        <p>Pour votre sécurité, ne partagez jamais ce lien avec personne.</p>";

    return sendSimpleEmail($to, $subject, $title, $content);
}

/**
 * Envoie un email avec plusieurs destinataires
 * @param array $recipients Liste des emails destinataires
 * @param string $subject Sujet
 * @param string $title Titre
 * @param string $content Contenu HTML
 * @param bool $useBcc Utiliser BCC pour masquer les destinataires
 * @return array Résultats de l'envoi [succès => int, échecs => array]
 */
function sendBulkEmail($recipients, $subject, $title, $content, $useBcc = true) {
    $results = ['success' => 0, 'failures' => []];

    try {
        $mail = initMailer();
        $mail->Subject = $subject;
        $mail->Body = getEmailTemplate($title, $content);

        foreach ($recipients as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $results['failures'][] = $email . ' (email invalide)';
                continue;
            }

            try {
                if ($useBcc) {
                    $mail->addBCC($email);
                } else {
                    $mail->addAddress($email);
                }
            } catch (Exception $e) {
                $results['failures'][] = $email . ' (' . $e->getMessage() . ')';
            }
        }

        if ($mail->send()) {
            $results['success'] = count($recipients) - count($results['failures']);
        } else {
            foreach ($recipients as $email) {
                if (!in_array($email, array_map(function($f) { return explode(' ', $f)[0]; }, $results['failures']))) {
                    $results['failures'][] = $email . ' (erreur d\'envoi)';
                }
            }
        }

    } catch (Exception $e) {
        error_log("Erreur envoi bulk: " . $e->getMessage());
        foreach ($recipients as $email) {
            $results['failures'][] = $email . ' (erreur système)';
        }
    }

    return $results;
}

/**
 * Exemple d'utilisation
 */
function exempleUtilisation() {
    // Email simple
    $success = sendSimpleEmail(
        'destinataire@example.com',
        'Test Email',
        'Email de test',
        '<p>Ceci est un test de notre système d\'email.</p>'
    );

    // Email de bienvenue
    $success = sendWelcomeEmail(
        'nouvel.utilisateur@example.com',
        'Jean Dupont',
        'https://monsite.com/activation?token=123456'
    );

    // Email de notification
    $success = sendNotificationEmail(
        'user@example.com',
        'Nouvelle commande reçue',
        'Vous avez reçu une nouvelle commande #12345',
        'https://monsite.com/commandes/12345',
        'Voir la commande'
    );

    // Email de réinitialisation
    $success = sendPasswordResetEmail(
        'user@example.com',
        'Jean Dupont',
        'https://monsite.com/reset?token=abcdef',
        60
    );

    // Envoi en masse
    $recipients = ['user1@example.com', 'user2@example.com', 'user3@example.com'];
    $results = sendBulkEmail(
        $recipients,
        'Newsletter mensuelle',
        'Notre newsletter',
        '<h2>Actualités du mois</h2><p>Voici les dernières nouvelles...</p>'
    );

    echo "Envois réussis: " . $results['success'] . "\n";
    echo "Échecs: " . count($results['failures']) . "\n";
}

// Décommenter pour tester
// exempleUtilisation();
?>