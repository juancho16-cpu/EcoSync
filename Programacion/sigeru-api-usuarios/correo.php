<?php

require_once "config_correo.php";
require_once "libs/PHPMailer/Exception.php";
require_once "libs/PHPMailer/PHPMailer.php";
require_once "libs/PHPMailer/SMTP.php";

use PHPMailer\PHPMailer\PHPMailer;

function correoConfigurado() {
    global $correoGmail, $claveAppGmail;
    return $correoGmail != "" && $claveAppGmail != "";
}

function enviarCorreo($para, $nombre, $asunto, $mensaje) {
    global $correoGmail, $claveAppGmail;

    if (!correoConfigurado()) {
        return false;
    }

    $mail = new PHPMailer(true);
    try {
        
        $mail->isSMTP();
        $mail->Host = "smtp.gmail.com";
        $mail->Port = 587;
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Username = $correoGmail;
        $mail->Password = $claveAppGmail;
        $mail->CharSet = "UTF-8";

        $mail->setFrom($correoGmail, "SiGeRU - EcoSync");
        $mail->addAddress($para, $nombre);

        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body = plantillaCorreo($asunto, $mensaje);
        $mail->AltBody = strip_tags($mensaje);

        $mail->send();
        return true;
    } catch (Exception $error) {
        
        error_log("No se pudo mandar el correo a $para: " . $mail->ErrorInfo);
        return false;
    }
}

function plantillaCorreo($titulo, $mensaje) {
    return "<div style='font-family: Arial, sans-serif; max-width: 520px; margin: auto; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden'>
                <div style='background: #176b3a; color: #fff; padding: 18px 24px; font-size: 22px; font-weight: bold'>SiGeRU</div>
                <div style='padding: 24px; color: #1f2937; line-height: 1.6'>
                    <h2 style='color: #176b3a; margin-top: 0'>$titulo</h2>
                    $mensaje
                </div>
                <div style='background: #f5f7fa; padding: 12px 24px; color: #6b7280; font-size: 12px'>EcoSync - Sistema de Gestión de Residuos Urbanos</div>
            </div>";
}

function botonCorreo($texto, $enlace) {
    return "<p style='text-align: center; margin: 25px 0'>
                <a href='$enlace' style='background: #176b3a; color: #fff; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: bold'>$texto</a>
            </p>";
}
