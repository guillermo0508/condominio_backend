<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background: #f9f9f9;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 5px 5px 0 0;
            text-align: center;
        }
        .content {
            background: white;
            padding: 30px;
            border-radius: 0 0 5px 5px;
        }
        .code-box {
            background: #f0f4ff;
            border: 2px solid #667eea;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        .code {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
            letter-spacing: 8px;
            font-family: 'Courier New', monospace;
        }
        .expire-notice {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 10px;
            margin: 20px 0;
            border-radius: 4px;
            font-size: 14px;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #666;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .button {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Verificación de Email</h1>
        </div>
        <div class="content">
            <p>Hola <strong>{{ $userName }}</strong>,</p>

            <p>Hemos recibido tu registro en nuestra plataforma. Para activar tu cuenta, por favor utiliza el siguiente código de verificación:</p>

            <div class="code-box">
                <p style="margin: 0; color: #666; font-size: 14px;">Tu código de verificación</p>
                <div class="code">{{ $code }}</div>
            </div>

            <p>Ingresa este código en la pantalla de verificación de tu navegador, o haz clic en el siguiente botón para ir directamente:</p>

            <div style="text-align: center;">
                <a href="{{ config('app.frontend_url', 'http://localhost:5173') }}/verify-email/{{ urlencode($verification->email) }}" class="button">Verificar Email</a>
            </div>

            <div class="expire-notice">
                ⏰ <strong>Este código expira en:</strong> {{ $expiresAt }}
            </div>

            <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
                <p style="margin: 0; color: #666; font-size: 14px;">
                    <strong>Seguridad:</strong> Si no solicitaste este código, ignora este email. Tu cuenta estará segura.
                </p>
            </div>

            <p style="margin-bottom: 30px;">
                ¿Tienes problemas? Contacta con nuestro equipo de soporte.
            </p>
        </div>
        <div class="footer">
            <p>© {{ date('Y') }} Condominio - Sistema de Gestión. Todos los derechos reservados.</p>
            <p>Este es un email automático, por favor no respondas a este mensaje.</p>
        </div>
    </div>
</body>
</html>
