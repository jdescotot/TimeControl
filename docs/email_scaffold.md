# Email scaffold - Instrucciones rápidas

1. Copia `mail_config.php.example` a `mail_config.php` y completa las credenciales (DB y SMTP). Nunca subas `mail_config.php` al repositorio.
2. Ejecuta el SQL `sql/create_email_queue.sql` en la base de datos indicada (p. ej. `dbs14946632`).
3. En el servidor, asegura que `mail_uploads/` exista y tenga permisos de escritura por el proceso web.
4. Para encolar mensajes utiliza la UI `enviar_correo.php`.
5. Para procesar la cola ejecuta desde la línea de comandos: `php worker_send.php`.

**Notas de seguridad:**
- Guarda `mail_config.php` fuera del webroot si es posible o con permisos restringidos.
- No incluyas nunca contraseñas en la UI ni en variables accesibles por el cliente (JS/HTML).

