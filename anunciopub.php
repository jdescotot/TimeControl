<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hostur Jaen — Aviso del Establecimiento</title>
    <meta name="description" content="Aviso interno del establecimiento. Emitido por Hostur Jaen. Pagina independiente de acceso libre.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        :root {
            --red-main:      #c0260a;
            --black:         #111111;
            --white:         #ffffff;
        }

        *, *::before, *::after { box-sizing: border-box; }

        html, body {
            margin: 0; padding: 0;
            min-height: 100vh;
        }

        body {
            background: #ffffff;
            font-family: 'Montserrat', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 1rem;
        }

        /* ===== outer frame ===== */
        .sign-wrapper {
            position: relative;
            padding: 10px;
            background: #ffffff;
            border-radius: 0;
            max-width: 720px;
            width: 100%;
            border: 12px solid var(--red-main);
            box-shadow: none;
        }

        /* ===== sign body ===== */
        .sign {
            background: #ffffff;
            border-radius: 0;
            padding: 30px 34px 30px;
            position: relative;
            overflow: hidden;
            border: 0;
        }

        /* ===== content ===== */
        .content { position: relative; z-index: 1; }

        .brand-header {
            border: 0;
            background: transparent;
            padding: 0 0 .35rem;
            border-radius: 0;
            margin-bottom: 1.2rem;
            text-align: center;
        }

        .brand-logo {
            width: min(460px, 100%);
            height: auto;
            display: block;
            margin: 0 auto;
        }

        /* divider */
        .divider {
            margin: 16px 0;
        }
        .div-line {
            width: 100%;
            height: 3px;
            background: #111111;
        }

        /* icon */
        .icon-circle {
            width: 104px;
            height: 104px;
            border-radius: 50%;
            background: #ffffff;
            border: 6px solid var(--red-main);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 18px;
            position: relative;
            box-shadow: none;
        }
        .icon-circle svg {
            width: 56px;
            height: 56px;
            transform: translateY(-5px);
        }
        .icon-circle::after {
            content: '';
            position: absolute;
            width: 114%;
            height: 6px;
            background: var(--red-main);
            transform: rotate(-45deg);
            border-radius: 2px;
            box-shadow: none;
        }

        /* notice badge */
        .notice-badge {
            background: var(--red-main);
            border: 2px solid var(--red-main);
            border-radius: 0;
            padding: 12px 18px;
            text-align: center;
            max-width: 520px;
            margin: 0 auto 18px;
            box-shadow: none;
        }
        .notice-label {
            font-family: 'Montserrat', sans-serif;
            font-size: clamp(.95rem, 2.2vw, 1.15rem);
            font-weight: 800;
            color: #ffffff;
            letter-spacing: .08em;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .notice-label svg { width: 17px; height: 17px; fill: #ffffff; flex-shrink: 0; }

        /* main message */
        .main-message {
            font-family: 'Montserrat', sans-serif;
            font-size: clamp(1.5rem, 4.8vw, 2.35rem);
            font-weight: 800;
            text-align: center;
            color: #111111;
            text-transform: uppercase;
            letter-spacing: .04em;
            line-height: 1.35;
            margin-bottom: 16px;
            text-shadow: none;
        }

        .main-message::after {
            content: '';
            display: block;
            width: min(160px, 40%);
            height: 4px;
            border-radius: 999px;
            background: #111111;
            margin: 12px auto 0;
        }
        .main-message em {
            color: var(--red-main);
            font-style: normal;
            text-shadow: none;
        }

        /* reason box */
        .reason-box {
            background: #ffffff;
            border: 2px solid #111111;
            border-radius: 0;
            padding: 18px 20px;
            margin-bottom: 14px;
        }
        .reason-text {
            font-family: 'Montserrat', sans-serif;
            font-size: clamp(1.05rem, 2.9vw, 1.2rem);
            font-weight: 600;
            color: #111111;
            text-align: center;
            line-height: 1.65;
            font-style: normal;
        }
        .reason-text strong {
            color: #111111;
            font-style: normal;
            font-family: 'Montserrat', sans-serif;
            font-size: 1em;
            font-weight: 800;
        }

        /* fine note */
        .fine-note {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            background: #ffffff;
            border: 2px solid #111111;
            border-radius: 0;
            padding: 13px 16px;
            margin-bottom: 18px;
        }
        .fine-icon-wrap {
            flex-shrink: 0;
            width: 32px;
            height: 32px;
            display: grid;
            place-items: center;
            margin-top: 2px;
        }
        .fine-icon-wrap svg { width: 22px; height: 22px; fill: #111111; }
        .fine-text {
            font-family: 'Montserrat', sans-serif;
            font-size: clamp(.95rem, 2.5vw, 1.08rem);
            color: #111111;
            letter-spacing: .02em;
            line-height: 1.5;
        }
        .fine-text strong { font-weight: 800; color: #111111; }

        /* closing */
        .thank-you {
            font-family: 'Montserrat', sans-serif;
            font-size: clamp(1rem, 2.8vw, 1.18rem);
            font-weight: 600;
            color: #111111;
            text-align: center;
            font-style: normal;
            margin-bottom: 2px;
        }
        .management-line {
            font-family: 'Montserrat', sans-serif;
            font-size: clamp(.82rem, 2vw, .95rem);
            font-weight: 700;
            color: #111111;
            text-align: center;
            letter-spacing: .08em;
            text-transform: uppercase;
            margin-top: 12px;
        }

        /* responsive padding */
        @media (max-width: 500px) {
            .sign { padding: 24px 18px 26px; }
            .brand-header { padding: 0 0 .35rem; }
            .main-message { font-size: clamp(1.25rem, 6vw, 1.6rem); }
            .notice-label { font-size: clamp(.86rem, 4vw, 1rem); }
        }
    </style>
</head>
<body>

<div class="sign-wrapper">
    <div class="sign">

        <div class="content">

            <div class="brand-header">
                <img src="imagenes/logohosturjaen600x.png" alt="Hostur Jaen" class="brand-logo">
            </div>

            <!-- divider -->
            <div class="divider">
                <div class="div-line"></div>
            </div>

            <!-- no-drinks icon -->
            <div class="icon-circle" role="img" aria-label="Prohibido sacar bebidas del local">
                <svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="10" y="12" width="32" height="38" rx="3" fill="#c8960a" opacity="0.85"/>
                    <rect x="42" y="18" width="10" height="18" rx="5" fill="#c8960a" opacity="0.85"/>
                    <rect x="10" y="12" width="32" height="10" rx="3" fill="#f0f0f0" opacity="0.6"/>
                    <line x1="16" y1="30" x2="16" y2="42" stroke="#7a5000" stroke-width="2" opacity="0.6"/>
                    <line x1="22" y1="30" x2="22" y2="42" stroke="#7a5000" stroke-width="2" opacity="0.6"/>
                    <line x1="28" y1="30" x2="28" y2="42" stroke="#7a5000" stroke-width="2" opacity="0.6"/>
                </svg>
            </div>

            <!-- notice badge -->
            <div class="notice-badge">
                <div class="notice-label">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 2 L22 20 H2 Z M11 9v5h2V9Zm0 7v2h2v-2Z"/></svg>
                    Aviso Importante
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 2 L22 20 H2 Z M11 9v5h2V9Zm0 7v2h2v-2Z"/></svg>
                </div>
            </div>

            <!-- main message -->
            <div class="main-message">
                Queda <em>PROHIBIDO</em> sacar<br>bebidas fuera del local
            </div>

            <!-- divider -->
            <div class="divider">
                <div class="div-line"></div>
            </div>

            <!-- reason -->
            <div class="reason-box">
                <div class="reason-text">
                    <strong>La ordenanza municipal proh&iacute;be expresamente el consumo de bebidas en la v&iacute;a p&uacute;blica.</strong><br><br>
                    Les rogamos disfruten de sus consumiciones <strong>dentro del establecimiento</strong>.
                </div>
            </div>

            <!-- fine note -->
            <div class="fine-note">
                <div class="fine-icon-wrap" aria-hidden="true">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 1a11 11 0 1 0 11 11A11 11 0 0 0 12 1Zm1 16h-2v-2h2Zm0-4h-2V7h2Z"/>
                    </svg>
                </div>
                <div class="fine-text">
                    El incumplimiento acarrea <strong>importantes sanciones econ&oacute;micas</strong>
                    impuestas por el Ayuntamiento a nuestro establecimiento.
                </div>
            </div>

            <!-- closing -->
            <div class="thank-you">Gracias por vuestra comprensi&oacute;n y colaboraci&oacute;n.</div>

            <!-- bottom divider -->
            <div class="divider" style="margin-top:18px;margin-bottom:10px;">
                <div class="div-line"></div>
            </div>

            <div class="management-line">La Direcci&oacute;n &nbsp;&mdash;&nbsp; Hosturja&eacute;n</div>

        </div><!-- end .content -->
    </div><!-- end .sign -->
</div><!-- end .sign-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
