<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hostur Jaen — Aviso del Establecimiento</title>
    <meta name="description" content="Aviso interno del establecimiento. Emitido por Hostur Jaen. Pagina independiente de acceso libre.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;900&family=IM+Fell+English:ital@0;1&family=Montserrat:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        :root {
            --hostur-dark:   #4a5e1a;   /* oscuro oliva */
            --hostur-mid:    #6b8221;   /* oliva medio  */
            --hostur-light:  #8faa28;   /* oliva claro  */
            --hostur-accent: #b5c94a;   /* amarillo-lima */
            --ink:           #0e1a06;   /* casi negro verdoso */
            --cream:         #f5f0e8;
            --gold:          #d4a017;
            --gold-light:    #f0c040;
            --red-main:      #c0260a;
            --red-soft:      #e84a2a;
        }

        *, *::before, *::after { box-sizing: border-box; }

        html, body {
            margin: 0; padding: 0;
            min-height: 100vh;
        }

        body {
            background: var(--ink);
            font-family: 'Montserrat', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 1rem;
        }

        /* ===== outer frame ===== */
        .sign-wrapper {
            position: relative;
            padding: 12px;
            background: linear-gradient(145deg, var(--hostur-dark), var(--hostur-mid), var(--hostur-light), var(--hostur-accent), var(--hostur-light), var(--hostur-mid), var(--hostur-dark));
            border-radius: 6px;
            max-width: 720px;
            width: 100%;
            box-shadow:
                0 0 0 3px var(--hostur-dark),
                0 0 35px rgba(143,170,40,.45),
                0 0 90px rgba(143,170,40,.18),
                0 24px 64px rgba(0,0,0,.85);
            animation: glowPulse 4s ease-in-out infinite;
        }

        @keyframes glowPulse {
            0%, 100% { box-shadow: 0 0 0 3px var(--hostur-dark), 0 0 35px rgba(143,170,40,.45), 0 0 90px rgba(143,170,40,.18), 0 24px 64px rgba(0,0,0,.85); }
            50%       { box-shadow: 0 0 0 3px var(--hostur-dark), 0 0 60px rgba(181,201,74,.75), 0 0 130px rgba(143,170,40,.32), 0 24px 64px rgba(0,0,0,.85); }
        }

        /* ===== sign body ===== */
        .sign {
            background: #0c150a;
            border-radius: 4px;
            padding: 44px 44px 52px;
            position: relative;
            overflow: hidden;
        }

        /* subtle linen texture */
        .sign::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                repeating-linear-gradient(0deg,  transparent, transparent 3px, rgba(255,255,255,.008) 3px, rgba(255,255,255,.008) 6px),
                repeating-linear-gradient(90deg, transparent, transparent 3px, rgba(255,255,255,.008) 3px, rgba(255,255,255,.008) 6px);
            pointer-events: none;
            z-index: 0;
        }

        /* ===== decorative corners ===== */
        .corner {
            position: absolute;
            width: 64px;
            height: 64px;
            opacity: .55;
        }
        .corner svg { width: 100%; height: 100%; }
        .corner-tl { top: 14px; left: 14px; }
        .corner-tr { top: 14px; right: 14px; transform: scaleX(-1); }
        .corner-bl { bottom: 14px; left: 14px; transform: scaleY(-1); }
        .corner-br { bottom: 14px; right: 14px; transform: scale(-1); }

        /* ===== floating bg pattern ===== */
        .bg-pattern {
            position: absolute;
            inset: 0;
            pointer-events: none;
            overflow: hidden;
            opacity: .035;
            z-index: 0;
        }
        .bg-leaf {
            position: absolute;
            fill: var(--hostur-accent);
            animation: floatLeaf linear infinite;
        }
        @keyframes floatLeaf {
            from { transform: translateY(115%) rotate(0deg);   opacity: 0; }
            10%  { opacity: 1; }
            90%  { opacity: 1; }
            to   { transform: translateY(-115%) rotate(360deg); opacity: 0; }
        }

        /* ===== content ===== */
        .content { position: relative; z-index: 1; }

        /* --- logo recreado --- */
        .logo-block {
            text-align: center;
            margin-bottom: 4px;
        }
        .logo-hostur {
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
            font-size: clamp(2.4rem, 8vw, 4.2rem);
            letter-spacing: -.02em;
            line-height: 1;
            color: var(--hostur-mid);
            display: inline;
        }
        .logo-jaen {
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
            font-size: clamp(2.4rem, 8vw, 4.2rem);
            letter-spacing: -.02em;
            line-height: 1;
            color: var(--hostur-accent);
            display: inline;
        }
        .logo-assoc {
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            font-size: clamp(.5rem, 1.6vw, .65rem);
            color: rgba(181,201,74,.6);
            letter-spacing: .18em;
            text-transform: uppercase;
            margin-top: 6px;
            line-height: 1.45;
        }

        /* divider */
        .divider {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 20px 0;
        }
        .div-line {
            flex: 1;
            height: 1.5px;
            background: linear-gradient(90deg, transparent, var(--hostur-mid), transparent);
        }
        .div-diamond {
            width: 9px;
            height: 9px;
            background: var(--hostur-accent);
            transform: rotate(45deg);
            box-shadow: 0 0 7px rgba(181,201,74,.7);
            flex-shrink: 0;
        }

        /* icon */
        .icon-circle {
            width: 104px;
            height: 104px;
            border-radius: 50%;
            background: rgba(192,38,10,.14);
            border: 3px solid var(--red-main);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            position: relative;
            box-shadow: 0 0 24px rgba(192,38,10,.28), inset 0 0 18px rgba(192,38,10,.1);
            animation: iconPulse 2.4s ease-in-out infinite;
        }
        @keyframes iconPulse {
            0%, 100% { transform: scale(1);    box-shadow: 0 0 24px rgba(192,38,10,.28), inset 0 0 18px rgba(192,38,10,.1); }
            50%       { transform: scale(1.05); box-shadow: 0 0 42px rgba(192,38,10,.58), inset 0 0 18px rgba(192,38,10,.1); }
        }
        .icon-circle svg { width: 56px; height: 56px; }
        .icon-circle::after {
            content: '';
            position: absolute;
            width: 114%;
            height: 4px;
            background: var(--red-main);
            transform: rotate(-45deg);
            border-radius: 2px;
            box-shadow: 0 0 9px rgba(192,38,10,.6);
        }

        /* notice badge */
        .notice-badge {
            background: linear-gradient(135deg, #6e0a00, #960d00);
            border: 1.5px solid rgba(255,80,60,.45);
            border-radius: 4px;
            padding: 10px 24px;
            text-align: center;
            max-width: 440px;
            margin: 0 auto 24px;
            box-shadow: 0 0 16px rgba(255,68,50,.22);
        }
        .notice-label {
            font-family: 'Montserrat', sans-serif;
            font-size: clamp(.62rem, 1.8vw, .76rem);
            font-weight: 700;
            color: #ffbbb0;
            letter-spacing: .35em;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .notice-label svg { width: 14px; height: 14px; fill: #ffbbb0; flex-shrink: 0; }

        /* main message */
        .main-message {
            font-family: 'Cinzel', serif;
            font-size: clamp(1.15rem, 4.2vw, 2rem);
            font-weight: 900;
            text-align: center;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: .07em;
            line-height: 1.3;
            margin-bottom: 20px;
            text-shadow: 0 2px 10px rgba(0,0,0,.7);
        }
        .main-message em {
            color: var(--red-soft);
            font-style: normal;
            text-shadow: 0 0 18px rgba(232,74,42,.5);
        }

        /* reason box */
        .reason-box {
            background: rgba(107,130,33,.1);
            border: 1px solid rgba(107,130,33,.3);
            border-radius: 4px;
            padding: 20px 26px;
            margin-bottom: 18px;
        }
        .reason-text {
            font-family: 'IM Fell English', serif;
            font-size: clamp(.92rem, 2.9vw, 1.1rem);
            color: #d4e8a0;
            text-align: center;
            line-height: 1.8;
            font-style: italic;
        }
        .reason-text strong {
            color: var(--hostur-accent);
            font-style: normal;
            font-family: 'Montserrat', sans-serif;
            font-size: .93em;
            font-weight: 700;
        }

        /* fine note */
        .fine-note {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            background: rgba(255,50,0,.07);
            border: 1px solid rgba(255,100,50,.18);
            border-radius: 4px;
            padding: 15px 20px;
            margin-bottom: 26px;
        }
        .fine-icon-wrap {
            flex-shrink: 0;
            width: 32px;
            height: 32px;
            display: grid;
            place-items: center;
            margin-top: 2px;
        }
        .fine-icon-wrap svg { width: 22px; height: 22px; fill: #ff9977; }
        .fine-text {
            font-family: 'Montserrat', sans-serif;
            font-size: clamp(.68rem, 2vw, .84rem);
            color: #ffb09a;
            letter-spacing: .03em;
            line-height: 1.55;
        }
        .fine-text strong { font-weight: 700; color: #ffd0c0; }

        /* closing */
        .thank-you {
            font-family: 'IM Fell English', serif;
            font-size: clamp(.95rem, 3vw, 1.14rem);
            color: var(--hostur-accent);
            text-align: center;
            font-style: italic;
            margin-bottom: 4px;
        }
        .management-line {
            font-family: 'Montserrat', sans-serif;
            font-size: clamp(.58rem, 1.6vw, .7rem);
            font-weight: 600;
            color: rgba(143,170,40,.45);
            text-align: center;
            letter-spacing: .3em;
            text-transform: uppercase;
            margin-top: 16px;
        }

        /* responsive padding */
        @media (max-width: 500px) {
            .sign { padding: 32px 22px 40px; }
        }
    </style>
</head>
<body>

<div class="sign-wrapper">
    <div class="sign">

        <!-- floating background pattern -->
        <div class="bg-pattern" aria-hidden="true">
            <svg class="bg-leaf" style="left:8%;  width:52px;height:52px;animation-duration:14s;animation-delay:0s;"  viewBox="0 0 24 24"><path d="M17 8C8 10 5.9 16.17 3.82 21.34L5.71 22l1-2.3A4.49 4.49 0 0 0 8 20C19 20 22 3 22 3S21 8 17 8Z"/></svg>
            <svg class="bg-leaf" style="left:36%; width:44px;height:44px;animation-duration:17s;animation-delay:5s;"  viewBox="0 0 24 24"><path d="M17 8C8 10 5.9 16.17 3.82 21.34L5.71 22l1-2.3A4.49 4.49 0 0 0 8 20C19 20 22 3 22 3S21 8 17 8Z"/></svg>
            <svg class="bg-leaf" style="left:64%; width:50px;height:50px;animation-duration:12s;animation-delay:9s;"  viewBox="0 0 24 24"><path d="M17 8C8 10 5.9 16.17 3.82 21.34L5.71 22l1-2.3A4.49 4.49 0 0 0 8 20C19 20 22 3 22 3S21 8 17 8Z"/></svg>
            <svg class="bg-leaf" style="left:86%; width:40px;height:40px;animation-duration:15s;animation-delay:3s;"  viewBox="0 0 24 24"><path d="M17 8C8 10 5.9 16.17 3.82 21.34L5.71 22l1-2.3A4.49 4.49 0 0 0 8 20C19 20 22 3 22 3S21 8 17 8Z"/></svg>
        </div>

        <!-- decorative corners -->
        <div class="corner corner-tl" aria-hidden="true">
            <svg viewBox="0 0 70 70" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M5 65 Q5 5 65 5" stroke="#8faa28" stroke-width="2" fill="none"/>
                <path d="M13 65 Q13 13 65 13" stroke="#8faa28" stroke-width="1" fill="none" opacity="0.45"/>
                <circle cx="5" cy="5" r="4" fill="#8faa28"/>
                <path d="M22 5 C22 22 5 22 5 38" stroke="#8faa28" stroke-width="1.5" fill="none"/>
            </svg>
        </div>
        <div class="corner corner-tr" aria-hidden="true">
            <svg viewBox="0 0 70 70" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M5 65 Q5 5 65 5" stroke="#8faa28" stroke-width="2" fill="none"/>
                <path d="M13 65 Q13 13 65 13" stroke="#8faa28" stroke-width="1" fill="none" opacity="0.45"/>
                <circle cx="5" cy="5" r="4" fill="#8faa28"/>
                <path d="M22 5 C22 22 5 22 5 38" stroke="#8faa28" stroke-width="1.5" fill="none"/>
            </svg>
        </div>
        <div class="corner corner-bl" aria-hidden="true">
            <svg viewBox="0 0 70 70" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M5 65 Q5 5 65 5" stroke="#8faa28" stroke-width="2" fill="none"/>
                <path d="M13 65 Q13 13 65 13" stroke="#8faa28" stroke-width="1" fill="none" opacity="0.45"/>
                <circle cx="5" cy="5" r="4" fill="#8faa28"/>
                <path d="M22 5 C22 22 5 22 5 38" stroke="#8faa28" stroke-width="1.5" fill="none"/>
            </svg>
        </div>
        <div class="corner corner-br" aria-hidden="true">
            <svg viewBox="0 0 70 70" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M5 65 Q5 5 65 5" stroke="#8faa28" stroke-width="2" fill="none"/>
                <path d="M13 65 Q13 13 65 13" stroke="#8faa28" stroke-width="1" fill="none" opacity="0.45"/>
                <circle cx="5" cy="5" r="4" fill="#8faa28"/>
                <path d="M22 5 C22 22 5 22 5 38" stroke="#8faa28" stroke-width="1.5" fill="none"/>
            </svg>
        </div>

        <div class="content">

            <!-- logo recreado -->
            <div class="logo-block">
                <div>
                    <span class="logo-hostur">Hostur</span><span class="logo-jaen">ja&eacute;n</span>
                </div>
                <div class="logo-assoc">
                    Asociaci&oacute;n Empresarial de Hosteleria<br>y Turismo de la Provincia de Ja&eacute;n
                </div>
            </div>

            <!-- divider -->
            <div class="divider">
                <div class="div-line"></div>
                <div class="div-diamond"></div>
                <div class="div-diamond" style="margin:0 5px;"></div>
                <div class="div-diamond"></div>
                <div class="div-line"></div>
            </div>

            <!-- no-drinks icon -->
            <div class="icon-circle" role="img" aria-label="Prohibido sacar bebidas del local">
                <svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="10" y="18" width="32" height="34" rx="3" fill="#c8960a" opacity="0.8"/>
                    <rect x="42" y="24" width="10" height="16" rx="5" fill="#c8960a" opacity="0.8"/>
                    <rect x="10" y="18" width="32" height="10" rx="3" fill="#f0f0f0" opacity="0.55"/>
                    <line x1="16" y1="36" x2="16" y2="46" stroke="#7a5000" stroke-width="2" opacity="0.55"/>
                    <line x1="22" y1="36" x2="22" y2="46" stroke="#7a5000" stroke-width="2" opacity="0.55"/>
                    <line x1="28" y1="36" x2="28" y2="46" stroke="#7a5000" stroke-width="2" opacity="0.55"/>
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
                <div class="div-diamond"></div>
                <div class="div-line"></div>
            </div>

            <!-- reason -->
            <div class="reason-box">
                <div class="reason-text">
                    Por <strong>Ordenanza Municipal</strong>, el consumo de bebidas alcoh&oacute;licas
                    en la v&iacute;a p&uacute;blica est&aacute; expresamente prohibido en este municipio.<br><br>
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
                    El incumplimiento puede acarrear <strong>sanciones econ&oacute;micas</strong>
                    impuestas por el Ayuntamiento a nuestro establecimiento.
                </div>
            </div>

            <!-- closing -->
            <div class="thank-you">Gracias por vuestra comprensi&oacute;n y colaboraci&oacute;n.</div>

            <!-- bottom divider -->
            <div class="divider" style="margin-top:18px;margin-bottom:10px;">
                <div class="div-line"></div>
                <div class="div-diamond"></div>
                <div class="div-line"></div>
            </div>

            <div class="management-line">La Direcci&oacute;n &nbsp;&mdash;&nbsp; Hostur Ja&eacute;n</div>

        </div><!-- end .content -->
    </div><!-- end .sign -->
</div><!-- end .sign-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
