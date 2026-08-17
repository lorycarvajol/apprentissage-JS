<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class MailerService
{
    private static function send(string $toEmail, string $toName, string $subject, string $htmlBody): bool
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = $_ENV['MAIL_HOST'] ?? '';
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['MAIL_USERNAME'] ?? '';
            $mail->Password = $_ENV['MAIL_PASSWORD'] ?? '';
            $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'] ?? PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = (int)($_ENV['MAIL_PORT'] ?? 587);
            $mail->CharSet = 'UTF-8';

            // Encodage de transfert déclaré explicitement plutôt que laissé au
            // `8bit` par défaut de PHPMailer. C'est un durcissement, pas la
            // correction d'une panne : `8bit` suppose un chemin SMTP acceptant
            // 8BITMIME de bout en bout, et un relais qui reconvertit le message
            // pour un transport 7 bits doit alors échapper lui-même les `=` du
            // corps. En quoted-printable explicite, PHPMailer les écrit en `=3D`
            // et l'en-tête annonce l'encodage réellement appliqué : le contenu et
            // son étiquette ne peuvent plus diverger.
            //
            // Contexte, parce qu'il a coûté cher le 17/08/2026 : les liens
            // `?token=…` semblaient arriver amputés de leur `=`. C'était un
            // artefact de l'outil qui servait à lire la boîte de réception, lequel
            // appliquait un décodage quoted-printable de trop. Les mails reçus par
            // le vrai client étaient intacts, vérification de compte et
            // réinitialisation de mot de passe comprises. Leçon : un corps de mail
            // lu à travers une API n'est pas une preuve de ce qui a été livré —
            // seul le client de messagerie du destinataire fait foi.
            $mail->Encoding = PHPMailer::ENCODING_QUOTED_PRINTABLE;

            $mail->setFrom(
                $_ENV['MAIL_FROM_ADDRESS'] ?? 'no-reply@example.com',
                $_ENV['MAIL_FROM_NAME'] ?? 'Plateforme Apprentissage JavaScript'
            );
            $mail->addAddress($toEmail, $toName);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;

            $mail->send();
            return true;
        } catch (PHPMailerException $e) {
            error_log("Mailer error: " . $mail->ErrorInfo);
            return false;
        }
    }

    public static function sendVerificationEmail(string $toEmail, string $toName, string $verificationUrl): bool
    {
        $safeName = htmlspecialchars($toName, ENT_QUOTES, 'UTF-8');
        $safeUrl = htmlspecialchars($verificationUrl, ENT_QUOTES, 'UTF-8');

        $body = "
            <p>Bonjour {$safeName},</p>
            <p>Merci de votre inscription sur la plateforme d'apprentissage JavaScript. Cliquez sur le lien ci-dessous pour activer votre compte :</p>
            <p><a href=\"{$safeUrl}\">Vérifier mon adresse email</a></p>
            <p>Si vous n'êtes pas à l'origine de cette inscription, vous pouvez ignorer cet email.</p>
        ";

        return self::send($toEmail, $toName, 'Vérifiez votre adresse email', $body);
    }

    public static function sendPasswordResetEmail(string $toEmail, string $toName, string $resetUrl): bool
    {
        $safeName = htmlspecialchars($toName, ENT_QUOTES, 'UTF-8');
        $safeUrl = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');

        $body = "
            <p>Bonjour {$safeName},</p>
            <p>Vous avez demandé la réinitialisation de votre mot de passe. Ce lien est valable 1 heure :</p>
            <p><a href=\"{$safeUrl}\">Réinitialiser mon mot de passe</a></p>
            <p>Si vous n'êtes pas à l'origine de cette demande, vous pouvez ignorer cet email.</p>
        ";

        return self::send($toEmail, $toName, 'Réinitialisation de votre mot de passe', $body);
    }
}
