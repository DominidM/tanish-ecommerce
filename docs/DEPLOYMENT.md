# TANISH - Guía de despliegue en VPS

## 1. Arquitectura recomendada

La configuración más simple y estable para un MVP 24/7 es:

Internet
  ↓
Cloudflare DNS + proxy HTTPS
  ↓
VPS Ubuntu 24.04 LTS
  ↓
Docker Compose
  ├── MySQL 8 (persistente)
  ├── WordPress (persistente)
  └── Caddy (reverse proxy + TLS)

Este flujo evita depender de un Quick Tunnel, de un host local o de una PC personal. La computadora del desarrollador solo sirve para preparar el repositorio y para ejecutar despliegues desde Git, no como origen público.

### Recomendación final sobre Cloudflare

Se recomienda mantener Cloudflare como capa de DNS y protección, pero no como tunnel temporal. La ruta pública debe ser:

Usuario -> Cloudflare -> VPS public IP -> Caddy -> WordPress

No se necesita un Worker público para la ruta principal salvo que se quiera una capa adicional de lógica o un fallback funcional. Para este MVP, el Worker actual no aporta valor suficiente frente a exponer directamente el dominio y el reverse proxy.

## 2. Requisitos mínimos del VPS

- Ubuntu Server 24.04 LTS
- 2 vCPU
- 2 GB RAM mínimo recomendado
- 20 GB de disco mínimo
- IP pública fija o estática
- Docker Engine
- Docker Compose v2
- Firewall con puertos 80 y 443 abiertos
- Dominio o subdominio apuntando a la IP del VPS

## 3. Variables de entorno

Crear un archivo local .env.production en el VPS, nunca versionado. Base del archivo:

```bash
cp .env.production.example .env.production
```

Variables claves:

```env
MYSQL_DATABASE=tanish_wordpress
MYSQL_USER=tanish_wp
MYSQL_PASSWORD=CHANGE_ME
MYSQL_ROOT_PASSWORD=CHANGE_ME
WORDPRESS_TABLE_PREFIX=wp_
WORDPRESS_DEBUG=0
WORDPRESS_URL=https://tanish.example.com
CADDY_DOMAIN=tanish.example.com
CADDY_EMAIL=admin@example.com
```

## 4. Primer despliegue

### En el VPS

```bash
sudo apt update
sudo apt install -y docker.io docker-compose-plugin git curl
sudo systemctl enable docker
sudo systemctl start docker
sudo usermod -aG docker $USER
```

Luego clone el repositorio y se configuran los archivos:

```bash
git clone https://github.com/DominidM/tanish-ecommerce.git
cd tanish-ecommerce
cp .env.production.example .env.production
# editar valores reales
chmod +x scripts/deploy-production.sh scripts/backup-production.sh
./scripts/deploy-production.sh
```

## 5. Cloudflare DNS

Configurar en Cloudflare:

- Tipo A
- Nombre: tanish
- Valor: IP_PUBLICA_DEL_VPS
- Proxy: Proxied (si se desea protección y HTTPS gestionado por Cloudflare)

Ejemplo:

```text
tanish.example.com -> A -> IP_PUBLICA_DEL_VPS
```

Esto permite que el tráfico llegue a la IP del VPS y luego a Caddy. Cloudflare puede seguir terminando TLS para el dominio. No se necesita Quick Tunnel ni `*.trycloudflare.com`.

## 6. HTTPS

La opción recomendada es que Cloudflare termine HTTPS y también sirva la capa pública. Caddy, en el VPS, puede opcionalmente manejar TLS directo si hay un certificado legítimo, pero para MVP la forma más simple es:

- Cloudflare = acceso público con HTTPS
- VPS = HTTP interno en la red Docker
- Caddy = reverse proxy HTTP con redirección esencial si se desea servir TLS local

La decisión exacta depende del tipo de proxy que uses en Cloudflare (DNS-only vs proxied). Para la mayoría de casos, proxy con Cloudflare es suficiente.

## 7. Persistencia

Las persistencias clave son:

- MySQL -> volumen `tanish_mysql_data_prod`
- WordPress -> volumen `tanish_wordpress_data_prod`
- Caddy config -> volumen `tanish_caddy_config`
- Caddy certs -> volumen `tanish_caddy_data`

Esto mantiene los datos aunque se reinicien contenedores o el servidor.

## 8. MySQL production

- Base de datos en contenedor `mysql:8.0`
- Puerto 3306 solo en red Docker interna
- No exponer a Internet
- Volumen persistente montado en `/var/lib/mysql`
- `restart: unless-stopped`

## 9. wp-content y custom code

Para evitar perder `plugins/tanish-inventory` y `themes/tanish-storefront`, se montan como directorios del repositorio dentro del contenedor WordPress:

```yaml
- ./plugins/tanish-inventory:/var/www/html/wp-content/plugins/tanish-inventory:ro
- ./themes/tanish-storefront:/var/www/html/wp-content/themes/tanish-storefront:ro
```

Esto mantiene el código custom versionado junto con el despliegue, sin depender de un Jenkins o build complejo.

## 10. Healthchecks

La composición incluye:

- healthcheck para MySQL
- healthcheck para WordPress
- `depends_on` con `condition: service_healthy`

Esto ayuda a que WordPress espere a MySQL antes de arrancar.

## 11. Reinicio automático

Todos los servicios llevan:

```yaml
restart: unless-stopped
```

Con esto, tras un reinicio del VPS, Docker reanuda los contenedores y TANISH vuelve a estar disponible sin intervención manual.

## 12. Backups

Uso recomendado:

```bash
./scripts/backup-production.sh
```

Se generan backups con fecha de:

- base de datos MySQL
- `wp-content/uploads`
- archivos de configuración y custom code

No se usa ninguna contraseña fija en el script; se leerán desde `.env.production`.

## 13. Deployment

```bash
./scripts/deploy-production.sh
```

Efectúa:

- validación de Compose
- `git pull --ff-only`
- `docker compose up -d --remove-orphans`
- comprobación de salud básica

Nunca se usa `docker compose down -v` ni se destruyen volúmenes.

## 14. Rollback básico

Si hay un problema:

```bash
git log --oneline -5
# revisar el último commit funcional

git checkout <commit-estable>
./scripts/deploy-production.sh
```

En caso de fallo de base de datos o contenido:

```bash
./scripts/backup-production.sh
# restaurar carpeta o DB según sea necesario
```

## 15. Migración de datos

Antes de mover a producción, conservar la base actual local y los uploads.

1. Exportar MySQL local:
   ```bash
   docker exec tanish-mysql mysqldump -u root -p"$MYSQL_ROOT_PASSWORD" tanish_wordpress > tanish_local_backup.sql
   ```
2. Exportar contenido:
   ```bash
   tar -czf tanish_wp_uploads.tar.gz ./wp-content/uploads
   ```
3. Copiar archivos al VPS
4. Importar la BD al MySQL del VPS
5. Restaurar uploads en el volumen WordPress del VPS
6. Verificar URLs, WooCommerce, inventario y páginas

No se ejecuta migración destructiva si el VPS aún no está listo.

## 16. Troubleshooting

### WordPress no responde

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml logs -f wordpress
```

### MySQL no arranca

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml logs -f mysql
```

### Problemas con DNS/Cloudflare

- Revisar A record apuntando a la IP pública
- Verificar que el dominio resuelve correctamente
- Confirmar que no queda un tunnel viejo en el dominio

### Error de permisos

- Revisar propiedad del volumen `wordpress_data`
- USar `wp-content` en volumen persistente
- No poner permisos globales 777

## 17. Resumen de la ruta pública final

Usuario
  ↓
Cloudflare DNS
  ↓
IP pública del VPS
  ↓
Caddy
  ↓
WordPress + WooCommerce + TANISH Inventory

Mientras la PC local esté apagada, el sitio seguiría disponible siempre que el VPS esté encendido y Cloudflare siga apuntando al mismo host.
