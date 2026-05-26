<?php
$tiene_registro_hoy = (bool)($tiene_registro_hoy ?? false);
$jornada_abierta = (bool)($jornada_abierta ?? false);
$tiene_solicitud_pendiente = (bool)($tiene_solicitud_pendiente ?? false);
$bloqueo_salida_pendiente = (bool)($bloqueo_salida_pendiente ?? false);
$estado_solicitud_pendiente = $estado_solicitud_pendiente ?? null;
$registro_hoy = (isset($registro_hoy) && is_array($registro_hoy)) ? $registro_hoy : null;
?>
<div class="card marcacion-card">
    <div class="card-header">
        <h2>Marcación de Hoy</h2>
        <div class="date-badge"><?php echo date('d/m/Y'); ?></div>
    </div>
    <div class="card-body">
        <?php if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'solicitud_ok'): ?>
            <div class="status-message success" style="margin-bottom: 15px;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <span>Solicitud enviada correctamente.</span>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'respuesta_ok'): ?>
            <div class="status-message success" style="margin-bottom: 15px;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <span>Respuesta enviada correctamente.</span>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'salida_anterior_entrada'): ?>
            <div class="status-message warning" style="margin-bottom: 15px;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <span>La hora de salida no puede ser anterior o igual a la hora de entrada.</span>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['error']) && $_GET['error'] === 'salida_futuro'): ?>
            <div class="status-message warning" style="margin-bottom: 15px;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <span>La hora de salida no puede ser en el futuro.</span>
            </div>
        <?php endif; ?>

        <?php if (!$tiene_registro_hoy && !$jornada_abierta): ?>
            <div class="status-message info">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="16" x2="12" y2="12"></line>
                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                </svg>
                <span>Aún no has marcado entrada hoy</span>
            </div>
            <form action="marcar.php" method="POST" class="marcacion-form">
                <input type="hidden" name="accion" value="entrada">
                <button type="submit" class="btn btn-primary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                        <polyline points="10 17 15 12 10 7"></polyline>
                        <line x1="15" y1="12" x2="3" y2="12"></line>
                    </svg>
                    Marcar Entrada
                </button>
            </form>
        <?php elseif ($jornada_abierta): ?>
            <?php if ($tiene_solicitud_pendiente): ?>
                <div class="status-message info">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                    <span>
                        Tienes una solicitud en revisión. Puedes marcar una nueva entrada mientras se procesa.
                    </span>
                </div>
                <div class="jornada-info" style="margin-bottom: 12px;">
                    <div class="info-item">
                        <span class="label">Entrada pendiente:</span>
                        <span class="value"><?php echo $registro_hoy && $registro_hoy['entrada'] ? date('H:i', strtotime($registro_hoy['entrada'])) : '—'; ?></span>
                    </div>
                    <?php if ($estado_solicitud_pendiente): ?>
                    <div class="info-item">
                        <span class="label">Estado:</span>
                        <span class="value"><?php echo htmlspecialchars($estado_solicitud_pendiente); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                <form action="marcar.php" method="POST" class="marcacion-form">
                    <input type="hidden" name="accion" value="entrada">
                    <button type="submit" class="btn btn-primary">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                            <polyline points="10 17 15 12 10 7"></polyline>
                            <line x1="15" y1="12" x2="3" y2="12"></line>
                        </svg>
                        Marcar Nueva Entrada
                    </button>
                </form>
            <?php elseif ($bloqueo_salida_pendiente): ?>
                <?php
                $entrada_dt_form = new DateTime($registro_hoy['entrada']);
                $siguiente_dt_form = clone $entrada_dt_form;
                $siguiente_dt_form->modify('+1 day');
                ?>
                <div class="status-message warning" style="margin-bottom: 15px;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <span>Tienes una jornada anterior sin cerrar. Entrada: <strong><?php echo htmlspecialchars($entrada_dt_form->format('d/m/Y H:i')); ?></strong>. Indica la hora de salida para continuar.</span>
                </div>
                <form action="marcar.php" method="POST" style="display:flex; flex-direction:column; gap:16px; margin-top:8px;">
                    <input type="hidden" name="accion" value="cerrar_y_entrar">
                    <input type="hidden" name="marcacion_id_anterior" value="<?php echo (int)($registro_hoy['id'] ?? 0); ?>">
                    <div>
                        <label style="font-weight:600; display:block; margin-bottom:6px;">Hora de salida</label>
                        <input type="time" name="hora_salida" required style="padding:8px 12px; border:1px solid #cbd5e0; border-radius:8px; font-size:1rem; width:100%; max-width:200px;">
                    </div>
                    <div>
                        <label style="font-weight:600; display:block; margin-bottom:8px;">¿Qué día fue la salida?</label>
                        <label style="display:flex; align-items:center; gap:8px; margin-bottom:8px; cursor:pointer;">
                            <input type="radio" name="dia_salida" value="mismo" checked>
                            <?php echo htmlspecialchars($entrada_dt_form->format('d/m/Y') . ' — día de entrada'); ?>
                        </label>
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                            <input type="radio" name="dia_salida" value="siguiente">
                            <?php echo htmlspecialchars($siguiente_dt_form->format('d/m/Y') . ' — día siguiente'); ?>
                        </label>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                                <polyline points="10 17 15 12 10 7"></polyline>
                                <line x1="15" y1="12" x2="3" y2="12"></line>
                            </svg>
                            Confirmar salida y marcar nueva entrada
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div class="status-message warning">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <span>Jornada en curso - Entrada marcada: <?php echo $registro_hoy && $registro_hoy['entrada'] ? date('H:i:s', strtotime($registro_hoy['entrada'])) : '—'; ?></span>
                </div>
                <form action="marcar.php" method="POST" class="marcacion-form">
                    <input type="hidden" name="accion" value="salida">
                    <button type="submit" class="btn btn-secondary">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                        Marcar Salida
                    </button>
                </form>
            <?php endif; ?>
        <?php else: ?>
            <div class="status-message success">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <span>Última jornada completada</span>
            </div>
            <div class="jornada-info">
                <div class="info-item">
                    <span class="label">Entrada:</span>
                    <span class="value"><?php echo $registro_hoy && $registro_hoy['entrada'] ? date('H:i:s', strtotime($registro_hoy['entrada'])) : '—'; ?></span>
                </div>
                <div class="info-item">
                    <span class="label">Salida:</span>
                    <span class="value"><?php echo $registro_hoy && $registro_hoy['salida'] ? date('H:i:s', strtotime($registro_hoy['salida'])) : '—'; ?></span>
                </div>
            </div>
            <form action="marcar.php" method="POST" class="marcacion-form" style="margin-top: 12px;">
                <input type="hidden" name="accion" value="entrada">
                <button type="submit" class="btn btn-primary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                        <polyline points="10 17 15 12 10 7"></polyline>
                        <line x1="15" y1="12" x2="3" y2="12"></line>
                    </svg>
                    Marcar Nueva Entrada
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>
