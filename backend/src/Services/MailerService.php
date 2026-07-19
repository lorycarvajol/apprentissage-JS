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
