<?php

// BackEnd/config/mailer.php — Gmail SMTP via cURL (sem Composer)

function enviarEmailCodigo(string $para, string $nomeDestinatario, string $codigo): bool {

    $host          = 'smtp.gmail.com';
    $port          = 587;
    $user          = 're.source.com.br@gmail.com';
    $pass          = 'rwpdcazjahiafozj';
    $remetente     = 're.source.com.br@gmail.com';
    $nomeRemetente = 'Re.Source';
    $assunto       = 'Seu código de verificação — Re.Source';

    $html = <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:'Segoe UI',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:40px 0;">
    <tr><td align="center">
      <table width="520" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,.08);">
        <tr>
          <td style="background:#157347;padding:32px 40px;">
            <p style="margin:0;color:#fff;font-size:22px;font-weight:700;">Re.Source</p>
            <p style="margin:4px 0 0;color:#a8d5b8;font-size:13px;">Plataforma de gestão de resíduos</p>
          </td>
        </tr>
        <tr>
          <td style="padding:40px;">
            <p style="margin:0 0 8px;color:#111;font-size:18px;font-weight:600;">Olá, {$nomeDestinatario}!</p>
            <p style="margin:0 0 28px;color:#555;font-size:14px;line-height:1.6;">
              Use o código abaixo para confirmar seu cadastro. Ele expira em <strong>1 hora</strong>.
            </p>
            <div style="text-align:center;margin:0 0 28px;">
              <div style="display:inline-block;background:#f0fff4;border:2px dashed #157347;border-radius:10px;padding:20px 40px;">
                <span style="font-size:36px;font-weight:800;letter-spacing:12px;color:#157347;font-family:monospace;">{$codigo}</span>
              </div>
            </div>
            <p style="margin:0;color:#888;font-size:12px;line-height:1.6;">
              Se você não solicitou este cadastro, ignore este e-mail.
            </p>
          </td>
        </tr>
        <tr>
          <td style="background:#f8f8f8;padding:20px 40px;border-top:1px solid #eee;">
            <p style="margin:0;color:#aaa;font-size:11px;">© 2026 Re.Source · Todos os direitos reservados</p>
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;

    $boundary           = md5(uniqid());
    $assuntoCodificado  = '=?UTF-8?B?' . base64_encode($assunto) . '?=';
    $nomeCodificado     = '=?UTF-8?B?' . base64_encode($nomeRemetente) . '?=';
    $nomeDestCodificado = '=?UTF-8?B?' . base64_encode($nomeDestinatario) . '?=';

    $cabecalhos  = "From: {$nomeCodificado} <{$remetente}>\r\n";
    $cabecalhos .= "To: {$nomeDestCodificado} <{$para}>\r\n";
    $cabecalhos .= "Subject: {$assuntoCodificado}\r\n";
    $cabecalhos .= "MIME-Version: 1.0\r\n";
    $cabecalhos .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
    $cabecalhos .= "Date: " . date('r') . "\r\n";

    $corpo  = "--{$boundary}\r\n";
    $corpo .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
    $corpo .= "Seu código de verificação Re.Source: {$codigo}\nExpira em 1 hora.\r\n";
    $corpo .= "--{$boundary}\r\n";
    $corpo .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
    $corpo .= $html . "\r\n";
    $corpo .= "--{$boundary}--\r\n";

    $mensagem = $cabecalhos . "\r\n" . $corpo;

    // Arquivo temporário com a mensagem (cURL SMTP precisa de stream)
    $tmp = tmpfile();
    fwrite($tmp, $mensagem);
    fseek($tmp, 0);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => "smtp://smtp.gmail.com:587",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USE_SSL        => CURLUSESSL_ALL,
        CURLOPT_USERNAME       => $user,
        CURLOPT_PASSWORD       => $pass,
        CURLOPT_MAIL_FROM      => "<{$remetente}>",
        CURLOPT_MAIL_RCPT      => ["<{$para}>"],
        CURLOPT_READDATA       => $tmp,
        CURLOPT_UPLOAD         => true,
        CURLOPT_VERBOSE        => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);

    $ok    = curl_exec($ch);
    $errno = curl_errno($ch);
    curl_close($ch);
    fclose($tmp);

    return $ok !== false && $errno === 0;
}