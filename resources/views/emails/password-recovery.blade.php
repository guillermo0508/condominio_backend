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
            background: linear-gradient(135deg, #f56565 0%, #c53030 100%);
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
            background: #fff5f5;
            border: 2px solid #f56565;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        .code {
            font-size: 32px;
            font-weight: bold;
            color: #c53030;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔑 Recuperación de Contraseña</h1>
        </div>
        <div class="content">
            <p>Hola <strong>{{ $userName }}</strong>,</p>

            <p>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta. Por favor, utiliza el siguiente código de seguridad:</p>

            <div class="code-box">
                <p style="margin: 0; color: #666; font-size: 14px;">Tu código de recuperación</p>
                <div class="code">{{ $code }}</div>
            </div>

            <p>Ingresa este código en la pantalla de recuperación de contraseña de tu navegador para establecer una nueva.</p>

            <div class="expire-notice">
                ⏰ <strong>Este código expira en:</strong> {{ $expiresAt }}
            </div>

            <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
                <p style="margin: 0; color: #666; font-size: 14px;">
                    <strong>Seguridad:</strong> Si no solicitaste un cambio de contraseña, puedes ignorar este email de forma segura. Tu contraseña no cambiará.
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
