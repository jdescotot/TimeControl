<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Turismo de Andalucia - Industria Eficiente</title>
    <meta name="description" content="Pagina independiente de Turismo de Andalucia para promover eficiencia energetica, gestion hidrica y economia circular.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --brand-orange: #f58220;
            --brand-green: #5b8f2f;
            --brand-cyan: #0984a3;
            --brand-ink: #0d1b2a;
            --brand-cream: #f8f4eb;
            --brand-sand: #efe8db;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: "Outfit", sans-serif;
            color: var(--brand-ink);
            background:
                radial-gradient(circle at 10% 5%, rgba(245, 130, 32, 0.12), transparent 35%),
                radial-gradient(circle at 90% 20%, rgba(9, 132, 163, 0.14), transparent 40%),
                linear-gradient(180deg, #fff, var(--brand-cream));
        }

        .font-sora {
            font-family: "Sora", sans-serif;
        }

        .hero {
            min-height: 88vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before,
        .hero::after {
            content: "";
            position: absolute;
            border-radius: 999px;
            filter: blur(2px);
            z-index: 0;
        }

        .hero::before {
            width: 360px;
            height: 360px;
            top: -120px;
            right: -120px;
            background: linear-gradient(135deg, var(--brand-orange), #ffb86b);
            opacity: 0.25;
        }

        .hero::after {
            width: 280px;
            height: 280px;
            left: -90px;
            bottom: -90px;
            background: linear-gradient(135deg, var(--brand-cyan), #7ed6ea);
            opacity: 0.25;
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .hero-badge {
            background-color: rgba(9, 132, 163, 0.12);
            border: 1px solid rgba(9, 132, 163, 0.25);
            border-radius: 999px;
            letter-spacing: 0.08em;
        }

        .hero-title {
            font-size: clamp(2rem, 6vw, 4.2rem);
            line-height: 1.05;
            letter-spacing: -0.02em;
        }

        .hero-highlight {
            color: var(--brand-orange);
        }

        .hero-panel {
            border-radius: 1.5rem;
            background: #fff;
            border: 1px solid rgba(13, 27, 42, 0.08);
            box-shadow: 0 18px 50px rgba(13, 27, 42, 0.08);
        }

        .stat-pill {
            border-radius: 999px;
            padding: 0.6rem 1rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--brand-sand);
            border: 1px solid rgba(13, 27, 42, 0.1);
        }

        .section-block {
            padding: 5rem 0;
        }

        .kpi-card {
            border: none;
            border-radius: 1.25rem;
            box-shadow: 0 14px 28px rgba(13, 27, 42, 0.08);
            background: #fff;
            transition: transform 0.25s ease;
        }

        .kpi-card:hover {
            transform: translateY(-6px);
        }

        .kpi-icon {
            width: 3rem;
            height: 3rem;
            border-radius: 0.85rem;
            display: grid;
            place-items: center;
            color: #fff;
            font-size: 1.3rem;
        }

        .bg-energy {
            background: linear-gradient(135deg, #f0a23d, var(--brand-orange));
        }

        .bg-water {
            background: linear-gradient(135deg, #2fbdd8, var(--brand-cyan));
        }

        .bg-circular {
            background: linear-gradient(135deg, #8fbe5e, var(--brand-green));
        }

        .timeline {
            border-left: 4px solid rgba(13, 27, 42, 0.15);
            margin-left: 0.8rem;
            padding-left: 1.4rem;
        }

        .timeline-step {
            position: relative;
            padding-bottom: 1.5rem;
        }

        .timeline-step::before {
            content: "";
            position: absolute;
            width: 0.9rem;
            height: 0.9rem;
            left: -1.86rem;
            top: 0.25rem;
            border-radius: 50%;
            background: var(--brand-orange);
            border: 2px solid #fff;
            box-shadow: 0 0 0 2px rgba(245, 130, 32, 0.4);
        }

        .audience-tag {
            display: inline-block;
            border-radius: 999px;
            background: #fff;
            border: 1px solid rgba(13, 27, 42, 0.12);
            padding: 0.5rem 0.9rem;
            margin: 0.25rem;
            font-weight: 500;
        }

        .cta-section {
            background: linear-gradient(120deg, var(--brand-ink), #163b5d);
            color: #fff;
            border-radius: 1.5rem;
            box-shadow: 0 20px 40px rgba(9, 20, 32, 0.3);
        }

        .footer-note {
            color: rgba(13, 27, 42, 0.7);
            font-size: 0.95rem;
        }

        [data-reveal] {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.65s ease, transform 0.65s ease;
        }

        [data-reveal].is-visible {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>
<body>
    <header class="hero">
        <div class="container hero-content py-5">
            <div class="row g-4 align-items-center">
                <div class="col-lg-7" data-reveal>
                    <span class="hero-badge px-3 py-2 fw-semibold small text-uppercase">Proyecto de asesoramiento y formacion</span>
                    <h1 class="hero-title font-sora fw-800 mt-3 mb-3">
                        Turismo de Andalucia,<br>
                        una industria <span class="hero-highlight">eficiente</span>
                    </h1>
                    <p class="lead mb-4">Impulsamos la sostenibilidad en establecimientos turisticos andaluces con diagnostico, plan de mejora y formacion especializada.</p>
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        <span class="stat-pill"><i class="bi bi-lightning-charge-fill text-warning"></i> Eficiencia energetica</span>
                        <span class="stat-pill"><i class="bi bi-droplet-half text-info"></i> Gestion hidrica</span>
                        <span class="stat-pill"><i class="bi bi-recycle text-success"></i> Economia circular</span>
                    </div>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="#solicitud" class="btn btn-warning btn-lg px-4 fw-semibold">Solicitar asesoramiento</a>
                        <a href="https://www.andaluciaturismoeficiente.es" target="_blank" rel="noopener noreferrer" class="btn btn-outline-dark btn-lg px-4">Ver sitio oficial</a>
                    </div>
                </div>
                <div class="col-lg-5" data-reveal>
                    <div class="hero-panel p-4 p-md-5">
                        <h2 class="h4 mb-3 font-sora">Que incluye el programa</h2>
                        <ul class="list-unstyled mb-0">
                            <li class="d-flex gap-2 mb-3"><i class="bi bi-check2-circle text-success"></i> Visita tecnica y cuestionario de sostenibilidad.</li>
                            <li class="d-flex gap-2 mb-3"><i class="bi bi-check2-circle text-success"></i> Informe con diagnostico y recomendaciones accionables.</li>
                            <li class="d-flex gap-2 mb-3"><i class="bi bi-check2-circle text-success"></i> Materiales formativos para equipos operativos y direccion.</li>
                            <li class="d-flex gap-2"><i class="bi bi-check2-circle text-success"></i> Acompanamiento para la adopcion de medidas.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main>
        <section class="section-block">
            <div class="container">
                <div class="row g-4" data-reveal>
                    <div class="col-md-4">
                        <article class="card kpi-card h-100 p-3">
                            <div class="kpi-icon bg-energy mb-3"><i class="bi bi-lightning-fill"></i></div>
                            <h3 class="h5 font-sora">Reducir consumo energetico</h3>
                            <p class="mb-0">Mejoras en climatizacion, iluminacion y operativa para bajar costes y emisiones.</p>
                        </article>
                    </div>
                    <div class="col-md-4">
                        <article class="card kpi-card h-100 p-3">
                            <div class="kpi-icon bg-water mb-3"><i class="bi bi-water"></i></div>
                            <h3 class="h5 font-sora">Optimizar uso del agua</h3>
                            <p class="mb-0">Practicas de ahorro hidrico y control de consumos en puntos criticos del servicio.</p>
                        </article>
                    </div>
                    <div class="col-md-4">
                        <article class="card kpi-card h-100 p-3">
                            <div class="kpi-icon bg-circular mb-3"><i class="bi bi-arrow-repeat"></i></div>
                            <h3 class="h5 font-sora">Potenciar economia circular</h3>
                            <p class="mb-0">Gestion de recursos y residuos para generar valor sostenible en el negocio.</p>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <section class="section-block pt-0">
            <div class="container">
                <div class="row g-5 align-items-start">
                    <div class="col-lg-6" data-reveal>
                        <h2 class="font-sora fw-bold mb-3">A quien va dirigido</h2>
                        <p class="mb-3">Responsables y trabajadores de establecimientos turisticos inscritos en el Registro de Turismo de Andalucia (RTA).</p>
                        <div>
                            <span class="audience-tag">Hoteles</span>
                            <span class="audience-tag">Hostales</span>
                            <span class="audience-tag">Campings</span>
                            <span class="audience-tag">Viviendas de uso turistico</span>
                            <span class="audience-tag">Alojamientos rurales</span>
                            <span class="audience-tag">Restauracion</span>
                        </div>
                    </div>
                    <div class="col-lg-6" data-reveal>
                        <h2 class="font-sora fw-bold mb-3">Como funciona</h2>
                        <div class="timeline">
                            <div class="timeline-step">
                                <h3 class="h6 mb-1">1. Solicitud de asesoramiento</h3>
                                <p class="mb-0">Envias tu interes mediante el formulario de esta pagina.</p>
                            </div>
                            <div class="timeline-step">
                                <h3 class="h6 mb-1">2. Visita tecnica</h3>
                                <p class="mb-0">Se realiza un cuestionario para evaluar el estado de sostenibilidad.</p>
                            </div>
                            <div class="timeline-step">
                                <h3 class="h6 mb-1">3. Informe de resultados</h3>
                                <p class="mb-0">Recibes diagnostico, recomendaciones y materiales formativos.</p>
                            </div>
                            <div class="timeline-step pb-0">
                                <h3 class="h6 mb-1">4. Formacion y adherencia</h3>
                                <p class="mb-0">Capacitacion presencial y online para implantar mejoras sostenibles.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section-block pt-0" id="solicitud">
            <div class="container" data-reveal>
                <div class="cta-section p-4 p-md-5">
                    <div class="row g-4 align-items-center">
                        <div class="col-lg-8">
                            <h2 class="font-sora fw-bold mb-2">Solicitud de asesoramiento</h2>
                            <p class="mb-0">Esta pagina es independiente y de acceso libre. No requiere usuario ni contrasena para consultarla o usar su llamada a la accion.</p>
                        </div>
                        <div class="col-lg-4 text-lg-end">
                            <button type="button" class="btn btn-warning btn-lg px-4 fw-semibold" data-bs-toggle="modal" data-bs-target="#infoModal">
                                Abrir formulario
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="py-4">
        <div class="container text-center footer-note">
            Turismo de Andalucia - Industria eficiente. Single page informativa e independiente.
        </div>
    </footer>

    <div class="modal fade" id="infoModal" tabindex="-1" aria-labelledby="infoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header">
                    <h5 class="modal-title font-sora" id="infoModalLabel">Formulario de solicitud</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    Puedes conectar este boton al formulario web final cuando lo tengas disponible.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            var revealItems = document.querySelectorAll("[data-reveal]");
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add("is-visible");
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.15 });

            revealItems.forEach(function (item) {
                observer.observe(item);
            });
        });
    </script>
</body>
</html>

