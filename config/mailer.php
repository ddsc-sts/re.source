<?php


function enviarEmailCodigo(string $para, string $nomeDestinatario, string $codigo): bool {

    $host          = (string) env('MAIL_HOST', 'smtp.gmail.com');
    $port          = (int) env('MAIL_PORT', 587);
    $user          = (string) env('MAIL_USERNAME', '');
    $pass          = (string) env('MAIL_PASSWORD', '');
    $remetente     = (string) env('MAIL_FROM_ADDRESS', $user);
    $nomeRemetente = (string) env('MAIL_FROM_NAME', 'Re.Source');
    $verifyTls     = (bool) env('MAIL_VERIFY_TLS', true);
    $assunto       = 'Seu código de verificação — Re.Source';

    if ($user === '' || $pass === '' || $remetente === '') {
        error_log('SMTP nao configurado: preencha as variaveis MAIL_* no arquivo .env.');
        return false;
    }

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

    $tmp = tmpfile();
    if ($tmp === false) {
        error_log('enviarEmailCodigo: falha ao criar arquivo temporario (tmpfile).');
        return false;
    }
    fwrite($tmp, $mensagem);
    fseek($tmp, 0);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => "smtp://{$host}:{$port}",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USE_SSL        => CURLUSESSL_ALL,
        CURLOPT_USERNAME       => $user,
        CURLOPT_PASSWORD       => $pass,
        CURLOPT_MAIL_FROM      => "<{$remetente}>",
        CURLOPT_MAIL_RCPT      => ["<{$para}>"],
        CURLOPT_READDATA       => $tmp,
        CURLOPT_UPLOAD         => true,
        CURLOPT_VERBOSE        => false,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => $verifyTls,
        CURLOPT_SSL_VERIFYHOST => $verifyTls ? 2 : 0,
    ]);

    $ok    = curl_exec($ch);
    $errno = curl_errno($ch);
    $erro  = curl_error($ch);
    $info  = curl_getinfo($ch);
    curl_close($ch);
    fclose($tmp);

    if ($errno !== 0 || $ok === false) {
        error_log(sprintf(
            'enviarEmailCodigo: falha ao enviar para %s | curl_errno=%d | curl_error=%s | http/smtp_code=%s',
            $para,
            $errno,
            $erro,
            $info['http_code'] ?? 'n/a'
        ));
        return false;
    }

    return true;
}

function enviarEmailRecuperacao(string $para, string $nomeDestinatario, string $link): bool {

    $host          = (string) env('MAIL_HOST', 'smtp.gmail.com');
    $port          = (int) env('MAIL_PORT', 587);
    $user          = (string) env('MAIL_USERNAME', '');
    $pass          = (string) env('MAIL_PASSWORD', '');
    $remetente     = (string) env('MAIL_FROM_ADDRESS', $user);
    $nomeRemetente = (string) env('MAIL_FROM_NAME', 'Re.Source');
    $verifyTls     = (bool) env('MAIL_VERIFY_TLS', true);
    $assunto       = 'Redefinição de senha — Re.Source';

    if ($user === '' || $pass === '' || $remetente === '') {
        error_log('SMTP nao configurado: preencha as variaveis MAIL_* no arquivo .env.');
        return false;
    }

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
              Recebemos uma solicitação para redefinir a senha da sua conta Re.Source.<br>
              Clique no botão abaixo — o link expira em <strong>1 hora</strong>.
            </p>
            <div style="text-align:center;margin:0 0 28px;">
              <a href="{$link}"
                 style="display:inline-block;background:#157347;color:#fff;text-decoration:none;
                        padding:14px 36px;border-radius:8px;font-size:15px;font-weight:600;">
                Redefinir minha senha
              </a>
            </div>
            <p style="margin:0 0 12px;color:#888;font-size:12px;line-height:1.6;">
              Ou copie e cole este link no navegador:
            </p>
            <p style="margin:0 0 24px;word-break:break-all;">
              <a href="{$link}" style="color:#157347;font-size:12px;">{$link}</a>
            </p>
            <p style="margin:0;color:#888;font-size:12px;line-height:1.6;">
              Se você não solicitou a redefinição de senha, ignore este e-mail.<br>
              Sua senha <strong>não será alterada</strong> sem que você acesse o link acima.
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
    $corpo .= "Olá, {$nomeDestinatario}!\n\nAcesse o link para redefinir sua senha:\n{$link}\n\nExpira em 1 hora.\r\n";
    $corpo .= "--{$boundary}\r\n";
    $corpo .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
    $corpo .= $html . "\r\n";
    $corpo .= "--{$boundary}--\r\n";

    $mensagem = $cabecalhos . "\r\n" . $corpo;

    $tmp = tmpfile();
    if ($tmp === false) {
        error_log('enviarEmailRecuperacao: falha ao criar arquivo temporario (tmpfile).');
        return false;
    }
    fwrite($tmp, $mensagem);
    fseek($tmp, 0);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => "smtp://{$host}:{$port}",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USE_SSL        => CURLUSESSL_ALL,
        CURLOPT_USERNAME       => $user,
        CURLOPT_PASSWORD       => $pass,
        CURLOPT_MAIL_FROM      => "<{$remetente}>",
        CURLOPT_MAIL_RCPT      => ["<{$para}>"],
        CURLOPT_READDATA       => $tmp,
        CURLOPT_UPLOAD         => true,
        CURLOPT_VERBOSE        => false,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => $verifyTls,
        CURLOPT_SSL_VERIFYHOST => $verifyTls ? 2 : 0,
    ]);

    $ok    = curl_exec($ch);
    $errno = curl_errno($ch);
    $erro  = curl_error($ch);
    $info  = curl_getinfo($ch);
    curl_close($ch);
    fclose($tmp);

    if ($errno !== 0 || $ok === false) {
        error_log(sprintf(
            'enviarEmailRecuperacao: falha ao enviar para %s | curl_errno=%d | curl_error=%s | http/smtp_code=%s',
            $para,
            $errno,
            $erro,
            $info['http_code'] ?? 'n/a'
        ));
        return false;
    }

    return true;
}

/** Envia alertas transacionais do marketplace sem credenciais fixas no codigo. */
function enviarEmailFluxo(
    string $para,
    string $nomeDestinatario,
    string $assunto,
    string $titulo,
    string $texto,
    ?string $link = null
): bool {
    $host = (string) env('MAIL_HOST', 'smtp.gmail.com');
    $port = (int) env('MAIL_PORT', 587);
    $user = (string) env('MAIL_USERNAME', '');
    $pass = (string) env('MAIL_PASSWORD', '');
    $remetente = (string) env('MAIL_FROM_ADDRESS', $user);
    $nomeRemetente = (string) env('MAIL_FROM_NAME', 'Re.Source');
    $verifyTls = (bool) env('MAIL_VERIFY_TLS', true);

    if ($para === '' || $user === '' || $pass === '' || $remetente === '') {
        error_log('enviarEmailFluxo: destinatario vazio ou SMTP nao configurado (MAIL_*).');
        return false;
    }

    $safeName = htmlspecialchars($nomeDestinatario, ENT_QUOTES, 'UTF-8');
    $safeTitle = htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8');
    $safeText = nl2br(htmlspecialchars($texto, ENT_QUOTES, 'UTF-8'));
    $safeLink = $link ? htmlspecialchars($link, ENT_QUOTES, 'UTF-8') : null;
    $button = $safeLink
        ? '<p style="margin:28px 0 0"><a href="' . $safeLink . '" style="display:inline-block;padding:12px 24px;border-radius:8px;background:#157347;color:#fff;text-decoration:none;font-weight:700">Abrir na Re.Source</a></p>'
        : '';

    $html = '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"></head>'
        . '<body style="margin:0;padding:36px;background:#f2f7f4;font-family:Arial,sans-serif;color:#263238">'
        . '<div style="max-width:560px;margin:auto;background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 8px 30px rgba(0,0,0,.08)">'
        . '<div style="padding:24px 32px;background:#157347;color:#fff;font-size:22px;font-weight:800">Re.Source</div>'
        . '<div style="padding:32px"><p style="margin:0 0 10px">Olá, ' . $safeName . '!</p>'
        . '<h1 style="margin:0 0 14px;font-size:22px">' . $safeTitle . '</h1>'
        . '<p style="margin:0;color:#56645d;line-height:1.6">' . $safeText . '</p>' . $button . '</div></div></body></html>';

    $boundary = md5(uniqid('', true));
    $encodedSubject = '=?UTF-8?B?' . base64_encode($assunto) . '?=';
    $encodedSender = '=?UTF-8?B?' . base64_encode($nomeRemetente) . '?=';
    $headers = "From: {$encodedSender} <{$remetente}>\r\n"
        . "To: <{$para}>\r\nSubject: {$encodedSubject}\r\nMIME-Version: 1.0\r\n"
        . "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\nDate: " . date('r') . "\r\n";
    $body = "--{$boundary}\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n{$texto}\r\n"
        . "--{$boundary}\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n{$html}\r\n--{$boundary}--\r\n";

    $tmp = tmpfile();
    if ($tmp === false) {
        error_log('enviarEmailFluxo: falha ao criar arquivo temporario (tmpfile).');
        return false;
    }
    fwrite($tmp, $headers . "\r\n" . $body);
    fseek($tmp, 0);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => "smtp://{$host}:{$port}", CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USE_SSL => CURLUSESSL_ALL, CURLOPT_USERNAME => $user,
        CURLOPT_PASSWORD => $pass, CURLOPT_MAIL_FROM => "<{$remetente}>",
        CURLOPT_MAIL_RCPT => ["<{$para}>"], CURLOPT_READDATA => $tmp,
        CURLOPT_UPLOAD => true, CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => $verifyTls,
        CURLOPT_SSL_VERIFYHOST => $verifyTls ? 2 : 0,
    ]);
    $result = curl_exec($ch);
    $errno  = curl_errno($ch);
    $erro   = curl_error($ch);
    $info   = curl_getinfo($ch);
    curl_close($ch);
    fclose($tmp);

    if ($errno !== 0 || $result === false) {
        error_log(sprintf(
            'enviarEmailFluxo: falha ao enviar para %s | curl_errno=%d | curl_error=%s | http/smtp_code=%s',
            $para,
            $errno,
            $erro,
            $info['http_code'] ?? 'n/a'
        ));
        return false;
    }

    return true;
}