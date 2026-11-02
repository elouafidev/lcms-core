<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Confirmation d'inscription</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f8fafc; color: #1e293b; margin: 0; padding: 24px; }
        .box { max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 32px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .btn { display: inline-block; background: #2563eb; color: #ffffff !important; padding: 12px 28px; border-radius: 8px; text-decoration: none; font-weight: 600; margin: 24px 0; }
        .footer { font-size: 12px; color: #94a3b8; margin-top: 24px; border-top: 1px solid #f1f5f9; padding-top: 16px; }
    </style>
</head>
<body>
    <div class="box">
        <h2 style="color: #0f172a; margin-top: 0;">Confirmez votre adresse e-mail</h2>
        <p>Bonjour,</p>
        <p>Nous avons bien reçu votre demande d'inscription à notre lettre d'information.</p>
        <p>Afin de valider définitivement votre inscription conformément à notre politique de confidentialité et au RGPD (Double Opt-In), veuillez cliquer sur le bouton ci-dessous :</p>
        <p style="text-align: center;">
            <a href="{{ $confirmationUrl }}" class="btn">Confirmer mon inscription</a>
        </p>
        <p style="font-size: 13px; color: #64748b;">
            Si le bouton ne fonctionne pas, vous pouvez copier et coller ce lien dans votre navigateur :<br>
            <a href="{{ $confirmationUrl }}" style="color: #2563eb; word-break: break-all;">{{ $confirmationUrl }}</a>
        </p>
        <div class="footer">
            Si vous n'êtes pas à l'origine de cette demande, vous pouvez ignorer cet e-mail. Aucune inscription ne sera validée sans cette confirmation.
        </div>
    </div>
</body>
</html>
