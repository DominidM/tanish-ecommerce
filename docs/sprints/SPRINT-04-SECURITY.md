# SPRINT 04 — Seguridad y Continuidad

## Nombre del Sprint
Sprint 04: Seguridad y Continuidad

## Objetivo
Implementar MFA para administradores, automatizar backups, y documentar el estado de HTTPS.

## Justificación Académica
- OE5: "Aplicar controles básicos de seguridad (HTTPS, autenticación multifactor para administradores y respaldo periódico)"
- Tabla 2: "N.° de controles de seguridad implementados (HTTPS, MFA, backups)"
- Abstract: "controles básicos de seguridad (HTTPS, autenticación multifactor y mínimo privilegio)"

## Secciones del Documento Relacionadas
- 3.4.2 OE5: seguridad
- 5.13: Plan de seguridad
- Tabla 2: Indicadores de seguridad

## Estado Inicial
- **Nonces:** IMPLEMENTADO (3 formularios)
- **Capabilities:** IMPLEMENTADO (manage_tanish_inventory)
- **Sanitización:** IMPLEMENTADO (absint, sanitize_textarea_field, etc.)
- **Escaping:** IMPLEMENTADO (esc_html, esc_url, etc.)
- **MySQL privado:** IMPLEMENTADO (sin port mapping)
- **.env ignorado:** IMPLEMENTADO
- **Prepared statements:** IMPLEMENTADO
- **HTTPS:** PREPARADO (docker-compose.prod.yml + Caddyfile), no activo
- **MFA:** NO IMPLEMENTADO
- **Backups:** PARTIAL (script manual, sin cron)

## Historias de Usuario

### HU-19: Como administrador, quiero autenticación multifactor para proteger mi cuenta
**Criterio:** Plugin MFA instalado y configurado para rol administrator.

### HU-20: Como administrador, quiero backups automáticos de la base de datos
**Criterio:** Backup diario automático de MySQL.

### HU-21: Como administrador, quiero saber el estado de HTTPS
**Criterio:** Documentación clara de qué está preparado y qué falta.

## Requisitos Funcionales
1. Plugin MFA instalado y activo
2. MFA configurado para al menos el usuario administrator
3. Backup automático de MySQL (script + cron o alternativa)
4. Documentación de estado HTTPS

## Requisitos No Funcionales
- No desarrollar MFA propio
- Usar plugin WordPress reconocido y mantenido
- Backup no debe interferir con operación
- HTTPS: documentar solo lo que realmente existe

## Tareas Técnicas

### T-28: Instalar plugin MFA
**Plugin candidato:** WP 2FA, Two Factor Authentication, o similar
**Acción:** `docker exec tanish-wordpress wp plugin install <plugin> --activate --allow-root`
**Configurar:** MFA para rol administrator
**Verificar:** Login requiere segundo factor

### T-29: Configurar MFA para administrator
**Acción:** Configurar método TOTP o email para usuario tanish_admin
**Verificar:** Login exitoso con MFA

### T-30: Crear script de backup automatizado
**Archivo:** `scripts/backup-auto.sh` (nuevo)
**Acción:** Script que ejecuta mysqldump + gzip con timestamp
**Automatización:** Si el host permite cron, agregar entrada; si no, documentar instrucción manual

### T-31: Documentar estado HTTPS
**Acción:** Crear sección en documentación que explique:
- Local: HTTP en localhost:8080
- Producción preparada: docker-compose.prod.yml + Caddyfile + Cloudflare
- Producción desplegada: NO (requiere VPS + dominio)
**NO afirmar:** HTTPS activo si no lo está

### T-32: Validar restore de backup
**Acción:** Verificar que `scripts/restore.sh` funciona con un dump de prueba
**NO hacer:** Restauración destructiva en entorno principal
**Resultado:** Documentar que el procedimiento de restore es válido

### T-33: Documentar evidencia
**Acción:** Guardar outputs de cada verificación
**Archivos:** `docs/evidencias/sprint-04/`

## Criterios de Aceptación
- [ ] Plugin MFA instalado y activo
- [ ] MFA configurado para administrator
- [ ] Login con MFA funciona
- [ ] Script backup automatizado creado
- [ ] Instrucción de cron documentada
- [ ] Estado HTTPS documentado correctamente
- [ ] Restore verificado (sintaxis/procedimiento)
- [ ] Evidencia documentada

## Pruebas
1. Login normal → funciona
2. Login con MFA → requiere segundo factor
3. Ejecutar backup automático → archivo generado
4. Verificar contenido del dump → BD completa
5. Verificar estado HTTPS → documentación correcta
6. Verificar restore → procedimiento válido

## Evidencias Requeridas
- Plugin MFA instalado (wp plugin list)
- Configuración MFA (captura)
- Login con MFA exitoso (output)
- Archivo de backup generado
- Contenido de documentación HTTPS
- Output de verificación de restore

## Riesgos
- R-10: Plugin MFA incompatible → probar alternativas
- R-11: Cron no disponible en host → documentar instrucción manual
- R-12: Backup muy grande → comprimir y rotar

## Dependencias
Ninguna — independiente.

## Definition of Done
- [ ] MFA instalado y funcionando
- [ ] Backup automatizado creado
- [ ] HTTPS documentado correctamente
- [ ] Restore verificado
- [ ] Evidencia documentada

## Estado Final
**STATUS: PLANNED**

## Archivos Modificados (planificados)
- `scripts/backup-auto.sh` (nuevo)
- `docs/evidencias/sprint-04/` (nuevo directorio)

## Resultado Esperado
MFA operativo + backups automatizados + documentación HTTPS clara.

## Brechas Restantes
- **BLOCKED:** HTTPS requiere VPS + dominio desplegado
- **PARTIAL:** Cron puede no estar disponible en host local
- MFA funcional pero sin prueba con múltiples usuarios
