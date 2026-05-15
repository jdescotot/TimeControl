<?php
// Cathedral data extracted from the PDF presentation
function extractTimelineYear(string $period): ?int
{
    if (preg_match('/(1[0-9]{3}|20[0-9]{2})/', $period, $match)) {
        return (int) $match[1];
    }

    if (preg_match('/(\d{1,2})(?:st|nd|rd|th)\s*-\s*(\d{1,2})(?:st|nd|rd|th)\s+centur/i', $period, $match)) {
        return (((int) $match[1]) - 1) * 100;
    }

    if (preg_match('/(\d{1,2})(?:st|nd|rd|th)\s+centur/i', $period, $match)) {
        return (((int) $match[1]) - 1) * 100;
    }

    return null;
}

function buildTimelineLayout(array $timeline, int $currentYear): array
{
    $anchors = [];
    foreach ($timeline as $index => $milestone) {
        $year = extractTimelineYear((string) ($milestone['period'] ?? ''));
        if ($year === null) {
            $year = $index === 0 ? $currentYear - 5 : ($anchors[$index - 1] + 5);
        }
        $anchors[] = $year;
    }

    $startYear = !empty($anchors) ? min($anchors) : $currentYear - 10;
    $endYear = !empty($anchors) ? max(max($anchors), $currentYear) : $currentYear;
    $span = max(1, $endYear - $startYear);

    $positions = [];
    foreach ($anchors as $year) {
        $positions[] = (($year - $startYear) / $span) * 100;
    }

    $minGap = 8.0;
    for ($i = 1; $i < count($positions); $i++) {
        if (($positions[$i] - $positions[$i - 1]) < $minGap) {
            $positions[$i] = $positions[$i - 1] + $minGap;
        }
    }

    if (!empty($positions) && end($positions) > 100) {
        $overflow = end($positions) - 100;
        foreach ($positions as $i => $pos) {
            $positions[$i] = max(0, $pos - $overflow);
        }
    }

    $items = [];
    foreach ($timeline as $i => $milestone) {
        $items[] = [
            'position' => round($positions[$i] ?? 0, 2),
            'year' => $anchors[$i] ?? $currentYear,
            'period' => (string) ($milestone['period'] ?? ''),
            'event' => (string) ($milestone['event'] ?? '')
        ];
    }

    return [
        'startYear' => $startYear,
        'currentYear' => $currentYear,
        'items' => $items
    ];
}

$cathedrals = [
    [
        "city" => "Jerez de la Frontera",
        "name" => "SAN SALVADOR CATHEDRAL",
        "region" => "Andalusia, Spain",
        "construction" => "Mainly 18th Century.",
        "styles" => "Late Gothic, Renaissance, and Baroque.",
        "note" => "Built over the ancient Great Mosque, it stands out for its monumental staircase and theatrical Baroque facade.",
        "image" => "https://commons.wikimedia.org/wiki/Special:FilePath/Catedral_de_Jerez_de_la_Frontera_01.jpg?width=600",
        "keywords" => ["Keywords coming soon", "Historical continuity", "Baroque transformation"],
        "timeline" => [
            ["period" => "1264-1265", "event" => "Organization of the collegiate chapter after the Christian conquest of Jerez."],
            ["period" => "1695", "event" => "Start of the new Baroque temple."],
            ["period" => "17th-18th centuries", "event" => "Prolonged construction with Gothic, Baroque, and Neoclassical elements."],
            ["period" => "1778", "event" => "Date associated with the completion of the main structure."],
            ["period" => "March 3, 1980", "event" => "Elevated to cathedral status by Pope John Paul II."],
            ["period" => "June 29, 1980", "event" => "Papal bull promulgated and first bishop installed: Rafael Bellido Caro."]
        ]
    ],
    [
        "city" => "Córdoba",
        "name" => "MOSQUE-CATHEDRAL",
        "region" => "Andalusia, Spain",
        "construction" => "8th-10th C. (Islamic) and 16th C. (Christian).",
        "styles" => "Umayyad, Mudéjar, and Renaissance.",
        "note" => "A masterpiece of historical superposition, inserting Renaissance vaults within an Islamic forest of arches.",
        "image" => "https://commons.wikimedia.org/wiki/Special:FilePath/Gran_Mezquita_de_Córdoba_-_España.jpg?width=600",
        "keywords" => ["Keywords coming soon", "Layered heritage", "Many patrons"],
        "timeline" => [
            ["period" => "786-788", "event" => "Initial construction of the mosque under Abd al-Rahman I."],
            ["period" => "833-855", "event" => "First expansion under Abd al-Rahman II."],
            ["period" => "945-951", "event" => "Courtyard expansion and great minaret under Abd al-Rahman III."],
            ["period" => "962-966", "event" => "Second major expansion under Al-Hakam II."],
            ["period" => "987-988", "event" => "Eastern expansion led by Almanzor."],
            ["period" => "1236", "event" => "Consecrated as a cathedral after the Christian conquest."],
            ["period" => "1523-1607", "event" => "Construction of the Christian cathedral core inside the Islamic structure."],
            ["period" => "1593-1617", "event" => "Bell tower remodeled over the former minaret."]
        ]
    ],
    [
        "city" => "Granada",
        "name" => "CATHEDRAL OF THE INCARNATION",
        "region" => "Andalusia, Spain",
        "construction" => "Started in 1523.",
        "styles" => "Renaissance and Baroque.",
        "note" => "Features Diego de Siloé's revolutionary circular floor plan and Alonso Cano's spectacular tripartite facade.",
        "image" => "https://commons.wikimedia.org/wiki/Special:FilePath/Catedral_de_Granada_frontal_y_torre_vertical.jpg?width=600",
        "keywords" => ["Keywords coming soon", "Renaissance project", "Royal symbolism"],
        "timeline" => [
            ["period" => "1505-1506", "event" => "Planning and first Gothic project by Enrique Egas under Cardinal Cisneros."],
            ["period" => "1519-1521", "event" => "House expropriations and site preparation; Royal Chapel given priority."],
            ["period" => "March 25, 1523", "event" => "Cornerstone laid by Archbishop Antonio de Rojas Manrique."],
            ["period" => "1521-1528", "event" => "Sebastian de Alcantara works as master builder appointed by Egas."],
            ["period" => "1526", "event" => "Charles I stays in Granada and grants major symbolic role to the cathedral."],
            ["period" => "1528", "event" => "Definitive Renaissance project by Diego de Siloe."],
            ["period" => "16th-17th centuries", "event" => "Long development through multiple masters and building phases."]
        ]
    ],
    [
        "city" => "Málaga",
        "name" => "CATHEDRAL OF THE INCARNATION",
        "region" => "Andalusia, Spain",
        "construction" => "16th to 18th Centuries.",
        "styles" => "Renaissance and Classicist Baroque.",
        "note" => "Popularly known as \"La Manquita\" (The One-Armed Lady) due to its unfinished south tower.",
        "image" => "https://commons.wikimedia.org/wiki/Special:FilePath/Catedral_de_Málaga.jpg?width=600",
        "keywords" => ["Keywords coming soon", "Unfinished tower", "Urban icon"],
        "timeline" => [
            ["period" => "Timeline pending", "event" => "Detailed milestones will be added soon."]
        ]
    ],
    [
        "city" => "Jaén",
        "name" => "CATHEDRAL OF THE ASSUMPTION",
        "region" => "Andalusia, Spain",
        "construction" => "16th to 17th Centuries.",
        "styles" => "Pure Renaissance and Baroque.",
        "note" => "Andrés de Vandelvira's masterpiece and an architectural model for many Latin American cathedrals.",
        "image" => "https://commons.wikimedia.org/wiki/Special:FilePath/Catedral_de_Jaén_-_Vista_General.jpg?width=600",
        "keywords" => ["Keywords coming soon", "Vandelvira", "Renaissance model"],
        "timeline" => [
            ["period" => "1246-1249", "event" => "After the Christian conquest, the former mosque becomes a church and episcopal see."],
            ["period" => "14th-15th centuries", "event" => "Previous medieval and Gothic phases."],
            ["period" => "1525", "event" => "Inspection warns of structural risk and pushes plans for a new building."],
            ["period" => "1534 / 1540 / 1551", "event" => "References to the beginning of the Renaissance phase linked to Andres de Vandelvira."],
            ["period" => "1634-1660", "event" => "Works resume under Juan de Aranda Salazar; transept and dome are completed."],
            ["period" => "1660", "event" => "Consecration of the temple."],
            ["period" => "1667-1688", "event" => "Main facade by Eufrasio Lopez de Rojas; sculptural and decorative contributions continue."],
            ["period" => "1764-1801", "event" => "Construction of the Sagrario phase, closing the monumental complex."]
        ]
    ],
    [
        "city" => "Baeza (Jaén)",
        "name" => "CATHEDRAL OF THE NATIVITY",
        "region" => "Jaen, Andalusia, Spain",
        "construction" => "16th Century (over an older temple).",
        "styles" => "Gothic, Plateresque, and Renaissance.",
        "note" => "A sober and elegant space where Vandelvira masterfully adapted Gothic pillars to support Renaissance vaults.",
        "image" => "https://commons.wikimedia.org/wiki/Special:FilePath/Catedral_de_Baeza.jpg?width=600",
        "keywords" => ["Keywords coming soon", "UNESCO setting", "Structural adaptation"],
        "timeline" => [
            ["period" => "Timeline pending", "event" => "Detailed milestones will be added soon."]
        ]
    ],
    [
        "city" => "Castellón (Valencia Region)",
        "name" => "CO-CATHEDRAL OF SANTA MARÍA",
        "region" => "Valencian Community, Spain",
        "construction" => "15th Century and 20th Century.",
        "styles" => "Neo-Gothic (Valencian Gothic style).",
        "note" => "A sober contemporary reconstruction accompanied by the historic freestanding bell tower, \"El Fadrí\".",
        "image" => "https://commons.wikimedia.org/wiki/Special:FilePath/Concatedral_y_Fadrí_de_Castellón.jpg?width=600",
        "keywords" => ["Keywords coming soon", "Reconstruction", "El Fadri"],
        "timeline" => [
            ["period" => "Timeline pending", "event" => "Detailed milestones will be added soon."]
        ]
    ],
    [
        "city" => "San Pedro Sula (Honduras)",
        "name" => "CATHEDRAL OF SAINT PETER",
        "region" => "Honduras",
        "construction" => "Started in 1949.",
        "styles" => "Mission Revival / Neo-colonial.",
        "note" => "A robust reinforced concrete structure with tall stained glass windows, designed for the tropical climate.",
        "image" => "https://commons.wikimedia.org/wiki/Special:FilePath/San_Pedro_Sula_cathedral.jpg?width=600",
        "keywords" => ["Keywords coming soon", "Tropical climate", "Reinforced concrete"],
        "timeline" => [
            ["period" => "Timeline pending", "event" => "Detailed milestones will be added soon."]
        ]
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cathedral Masterpieces</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .hero-section {
            background: linear-gradient(rgba(33, 37, 41, 0.8), rgba(33, 37, 41, 0.8)), url('https://picsum.photos/seed/architecture/1920/1080') no-repeat center center;
            background-size: cover;
            color: white;
            padding: 100px 0;
            text-shadow: 1px 1px 4px rgba(0,0,0,0.5);
        }
        .card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
            border: none;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }
        .card-img-top {
            object-fit: cover;
            height: 250px;
        }
        .badge-style {
            background-color: #6c757d;
            font-weight: 500;
        }
        .city-tag {
            color: #0d6efd;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 1px;
        }
        .footer-section {
            background-color: #212529;
            color: #adb5bd;
            padding: 40px 0;
        }
        .timeline-wrap {
            position: relative;
            min-height: 460px;
            padding: 1.25rem 0 1.25rem 9.5rem;
        }
        .timeline-axis {
            position: absolute;
            top: 1.25rem;
            bottom: 1.25rem;
            left: 6.25rem;
            width: 4px;
            border-radius: 999px;
            background: linear-gradient(180deg, #d00000 0%, #ba181b 45%, #9d0208 100%);
            box-shadow: 0 0 14px rgba(208, 0, 0, 0.25);
        }
        .timeline-label {
            position: absolute;
            left: 0;
            width: 5.4rem;
            text-align: right;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: .03em;
            text-transform: uppercase;
            color: #6c757d;
        }
        .timeline-label.top {
            top: .9rem;
        }
        .timeline-label.bottom {
            bottom: .9rem;
        }
        .timeline-event {
            position: absolute;
            left: 0;
            right: 0;
            transform: translateY(-50%);
        }
        .timeline-dot {
            position: absolute;
            left: 6.25rem;
            top: 50%;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: #d00000;
            border: 3px solid #fff;
            transform: translate(-50%, -50%);
            box-shadow: 0 0 0 4px rgba(208, 0, 0, 0.18);
        }
        .timeline-year {
            position: absolute;
            left: 0;
            top: 50%;
            width: 5.4rem;
            transform: translateY(-50%);
            text-align: right;
            color: #9d0208;
            font-size: 1rem;
            font-weight: 800;
            line-height: 1;
        }
        .timeline-card {
            margin-left: 1.35rem;
            background: #fff;
            border: 1px solid #f2c9cb;
            border-left: 4px solid #ba181b;
            border-radius: 10px;
            padding: .55rem .75rem;
            box-shadow: 0 3px 10px rgba(0,0,0,0.06);
        }
        .timeline-period {
            font-size: .8rem;
            color: #6c757d;
        }
        .timeline-event-text {
            font-size: .93rem;
            color: #212529;
        }
        @media (max-width: 767.98px) {
            .timeline-wrap {
                padding-left: 8.4rem;
                min-height: 500px;
            }
            .timeline-axis,
            .timeline-dot {
                left: 5.35rem;
            }
            .timeline-label,
            .timeline-year {
                width: 4.7rem;
            }
        }
    </style>
</head>
<body>

    <!-- Hero Header -->
    <header class="hero-section text-center mb-5">
        <div class="container">
            <h1 class="display-3 fw-bold mb-3">Cathedral Masterpieces</h1>
            <p class="lead fs-4 mx-auto" style="max-width: 800px;">
                A photographic gallery and architectural study: from the landmarks of Andalusia to influences in the Valencian Community and Honduras.
            </p>
        </div>
    </header>

    <!-- Main Gallery -->
    <main class="container mb-5">
        <div class="row g-4">
            <?php foreach ($cathedrals as $index => $cathedral): ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card h-100">
                        <img src="<?= htmlspecialchars($cathedral['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($cathedral['name']) ?>">
                        <div class="card-body d-flex flex-column">
                            <p class="city-tag mb-1"><i class="bi bi-geo-alt-fill"></i> <?= htmlspecialchars($cathedral['city']) ?></p>
                            <h4 class="card-title fw-bold mb-3"><?= htmlspecialchars($cathedral['name']) ?></h4>
                            
                            <p class="card-text mb-2">
                                <strong><i class="bi bi-hammer"></i> Construction:</strong> <?= htmlspecialchars($cathedral['construction']) ?>
                            </p>
                            
                            <p class="card-text mb-3">
                                <strong>Styles:</strong><br>
                                <?php 
                                    $styles = explode(',', $cathedral['styles']);
                                    foreach ($styles as $style) {
                                        // Clean up "and" and whitespace
                                        $style = trim(str_replace('and ', '', $style));
                                        echo '<span class="badge badge-style me-1 mb-1">' . htmlspecialchars($style) . '</span>';
                                    }
                                ?>
                            </p>
                            
                            <div class="mt-auto pt-3 border-top">
                                <p class="card-text text-muted small">
                                    <em><?= htmlspecialchars($cathedral['note']) ?></em>
                                </p>
                                <button type="button" class="btn btn-outline-primary btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#cathedralModal<?= $index ?>">
                                    View Keywords & Timeline
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <?php foreach ($cathedrals as $index => $cathedral): ?>
        <?php $timelineLayout = buildTimelineLayout($cathedral['timeline'], (int) date('Y')); ?>
        <div class="modal fade" id="cathedralModal<?= $index ?>" tabindex="-1" aria-labelledby="cathedralModalLabel<?= $index ?>" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="cathedralModalLabel<?= $index ?>"><?= htmlspecialchars($cathedral['name']) ?></h5>
                            <small class="text-muted"><?= htmlspecialchars($cathedral['city']) ?> | <?= htmlspecialchars($cathedral['region']) ?></small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <h6 class="fw-bold mb-3">Keywords</h6>
                        <div class="mb-4">
                            <?php foreach ($cathedral['keywords'] as $keyword): ?>
                                <span class="badge text-bg-secondary me-1 mb-1"><?= htmlspecialchars($keyword) ?></span>
                            <?php endforeach; ?>
                        </div>

                        <h6 class="fw-bold mb-3">Timeline</h6>
                        <div class="timeline-wrap">
                            <div class="timeline-axis"></div>
                            <div class="timeline-label top">Start <?= htmlspecialchars((string) $timelineLayout['startYear']) ?></div>
                            <div class="timeline-label bottom">Today <?= htmlspecialchars((string) $timelineLayout['currentYear']) ?></div>

                            <?php foreach ($timelineLayout['items'] as $milestone): ?>
                                <div class="timeline-event" style="top: <?= htmlspecialchars((string) $milestone['position']) ?>%;">
                                    <div class="timeline-year"><?= htmlspecialchars((string) $milestone['year']) ?></div>
                                    <span class="timeline-dot"></span>
                                    <div class="timeline-card">
                                        <div class="timeline-period mb-1"><?= htmlspecialchars($milestone['period']) ?></div>
                                        <div class="timeline-event-text"><?= htmlspecialchars($milestone['event']) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Footer -->
    <footer class="footer-section text-center">
        <div class="container">
            <h5 class="text-white mb-3">End of the Tour</h5>
            <p class="mb-0">Thank you for joining us on this journey through the history of art and architecture.</p>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Bootstrap Icons (Optional but used for UI enhancement) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</body>
</html>