# TANISH E-Commerce Platform

Plataforma E-Business desarrollada para **TANISH S.A.C.**, orientada a comercio electrónico y gestión interna de inventario.

El sistema integra una tienda virtual basada en WordPress + WooCommerce con un módulo propio de control de inventario para alinear las ventas online con el stock físico y los procesos internos de la empresa.

> Repositorio oficial: https://github.com/DominidM/tanish-ecommerce.git

---

## Descripción

El sistema permitirá:

- **Catálogo de productos** con categorías, variaciones, precios y stock
- **Comercio electrónico** completo (tienda pública)
- **Carrito de compras** y **checkout** integrado
- **Gestión de pedidos** (estados, detalle, cliente, comprobantes)
- **Control de stock** nativo de WooCommerce
- **Entradas de inventario** (compras, devoluciones, producción)
- **Salidas de inventario** (ventas, mermas, transferencias)
- **Ajustes de inventario** (conteos físicos, correcciones)
- **Historial / Kardex** lógico de todos los movimientos
- **Analítica web** para medición de tráfico y conversión

**WooCommerce será el núcleo comercial.**

El plugin propio:

**`tanish-inventory`**

será responsable de ampliar la gestión de inventario por encima de las capacidades estándar de WooCommerce, implementando:

- Kardex lógico y trazabilidad completa
- Tipos de movimiento auditables
- Stock vinculado bidireccionalmente con productos de WooCommerce
- Usuarios y roles autorizados para modificar inventario
- Reportes y conciliación

### Alcance inicial

La primera versión **NO** tendrá integración automática con **SUNAT**. La emisión electrónica de boletas/facturas seguirá siendo manejada externamente por el sistema contable actual de TANISH S.A.C.

La integración con SUNAT (facturación electrónica) se evaluará como segunda versión.

---

## Tecnologías

### Infraestructura

- **Ubuntu 24.04 LTS (Noble Numbat)**
- **Docker Engine** (29.x)
- **Docker Compose** (v2.40+)

### Backend / CMS

- **WordPress** (imagen oficial `wordpress:latest`)
- **PHP 8.3 + Apache** (incluido en imagen WordPress)

### E-Commerce

- **WooCommerce** (instalación posterior vía wp-admin)

### Base de datos

- **MySQL 8.0** (imagen oficial `mysql:8.0`)
  - Database: `tanish_wordpress`
  - User: `tanish_wp`
  - Credenciales gestionadas por `.env`

### Control de versiones

- **Git** (2.43+)
- **GitHub** — https://github.com/DominidM/tanish-ecommerce

### Analítica futura

- **Google Analytics 4 (GA4)**
- **Google Search Console**

### Desarrollo propio

- **Plugin WordPress: `tanish-inventory`** — `plugins/tanish-inventory/`
  - Entradas / Salidas / Ajustes
  - Historial / Kardex
  - Trazabilidad
  - Roles y permisos

---

## Arquitectura

```
Cliente
   |
   v
WordPress  (PHP + Apache)  — :8080
   |
WooCommerce
   |
   +----------------+
   |                |
Pedidos         Productos
                    |
                    v
                 Stock (WooCommerce)
                    |
              tanish-inventory
                    |
        +-----------+-----------+
        |           |           |
     Entradas    Salidas     Ajustes
                    |
                    v
              Historial/Kardex
                    |
                    v
                  MySQL  (tanish_wordpress)
```

**Despliegue local con Docker:**

```
Ubuntu 24.04
   └── Docker
        ├── WordPress (PHP + Apache) ── tanish-wordpress :8080 -> 80
        │      └── volumen: tanish_wordpress_data:/var/www/html
        └── MySQL 8.0 ── tanish-mysql :3306 (interno)
               └── volumen: tanish_mysql_data:/var/lib/mysql
               └── red: tanish-network (bridge)
```

- WordPress se conecta a MySQL mediante la red interna `tanish-network` usando `WORDPRESS_DB_HOST=mysql:3306`.
- MySQL **no expone** puerto al host (solo interno) por seguridad.
- Volúmenes persistentes `tanish_mysql_data` y `tanish_wordpress_data` conservan datos entre reinicios.

---

## Estructura del Proyecto

```
tanish-ecommerce/
├── docker-compose.yml          # Orquestación WordPress + MySQL
├── .env                        # Variables reales locales (NO se sube a Git)
├── .env.example                # Plantilla de variables (SÍ se sube)
├── .gitignore
├── README.md
│
├── plugins/
│   └── tanish-inventory/       # Plugin propio ( scaffold futuro )
│       └── .gitkeep
│
├── docs/
│   ├── arquitectura/           # Diagramas, decisiones técnicas
│   ├── requisitos/             # Historias de usuario, RF/RNF
│   ├── pruebas/                # Planes y casos de prueba
│   └── evidencias/             # Capturas, demos
│
└── scripts/                    # Utilidades, backups, helpers
```

> **Nota:** WordPress core **no se sube** al repositorio. Se ejecuta desde la imagen oficial `wordpress:latest` y persiste en el volumen Docker `tanish_wordpress_data`.

---

## Requisitos

| Herramienta | Versión mínima | Verificación |
|---|---|---|
| Ubuntu | 24.04 LTS | `lsb_release -a` |
| Docker Engine | 29.x | `docker --version` |
| Docker Compose | v2.40+ | `docker compose version` |
| Git | 2.43+ | `git --version` |

No es necesario instalar Apache, PHP ni MySQL en el host — todo corre en Docker.

---

## Instalación

### 1. Clonar repositorio

```bash
git clone https://github.com/DominidM/tanish-ecommerce.git
cd tanish-ecommerce
```

### 2. Configurar variables de entorno

```bash
cp .env.example .env
# Editar .env y reemplazar CHANGE_ME por contraseñas seguras
nano .env
```

**.env.example:**

```ini
MYSQL_DATABASE=tanish_wordpress
MYSQL_USER=tanish_wp
MYSQL_PASSWORD=CHANGE_ME
MYSQL_ROOT_PASSWORD=CHANGE_ME

WORDPRESS_DB_HOST=mysql:3306
WORDPRESS_DB_NAME=tanish_wordpress
WORDPRESS_DB_USER=tanish_wp
WORDPRESS_DB_PASSWORD=CHANGE_ME
```

> `.env` está en `.gitignore` y nunca debe subirse al repositorio.

### 3. Validar configuración

```bash
docker compose config
```

Si no hay errores, continuar.

### 4. Levantar servicios

```bash
docker compose up -d
docker compose ps
docker compose logs --tail=50
```

Esperar ~30s a que MySQL pase el `healthcheck` (`healthy`) y WordPress copie los archivos.

---

## Acceso

- **Instalador WordPress:** http://localhost:8080
  - Redirige automáticamente a `http://localhost:8080/wp-admin/install.php`
- **Administración (post-instalación):** http://localhost:8080/wp-admin

**Primer uso:**

1. Abrir http://localhost:8080
2. Seleccionar idioma
3. Crear usuario administrador, contraseña y email
4. Instalar WooCommerce desde `Plugins > Añadir nuevo` (paso posterior)

---

## Comandos Útiles

### Iniciar servicios

```bash
docker compose up -d
```

### Detener servicios (conserva datos)

```bash
docker compose down
```

### Ver estado

```bash
docker compose ps
```

### Ver logs

```bash
docker compose logs -f
docker compose logs --tail=50
docker compose logs wordpress
docker compose logs mysql
```

### Reiniciar

```bash
docker compose restart
```

### Entrar a contenedores

```bash
docker exec -it tanish-wordpress bash
docker exec -it tanish-mysql bash
docker exec -it tanish-mysql mysql -u tanish_wp -p
```

### Validar configuración

```bash
docker compose config
```

---

## Importante sobre los Datos

### `docker compose down`

Detiene y **elimina** los contenedores y la red, **pero conserva** los volúmenes persistentes:

- `tanish_mysql_data`
- `tanish_wordpress_data`

> **Los datos de WordPress y la base de datos se conservan.** Puedes hacer `up -d` nuevamente y todo seguirá ahí.

### `docker compose down -v` — ¡PELIGRO!

El flag `-v` **ELIMINA también los volúmenes persistentes**.

```bash
docker compose down -v  # BORRA base de datos y archivos de WordPress
```

- Borra `tanish_mysql_data` → pierdes toda la BD (`tanish_wordpress`)
- Borra `tanish_wordpress_data` → pierdes `wp-config`, plugins, uploads, temas

> **NO utilizar `docker compose down -v` salvo que realmente quieras eliminar la base de datos y comenzar desde cero.**

**Backup recomendado antes de cualquier `down -v`:**

```bash
docker exec tanish-mysql mysqldump -u root -p"$MYSQL_ROOT_PASSWORD" tanish_wordpress > backup_$(date +%F).sql
```

---

## Roadmap

- [ ] Entorno Docker (WordPress + MySQL)
- [ ] Instalación WordPress
- [ ] Instalación WooCommerce
- [ ] Configuración catálogo (categorías, atributos, productos)
- [ ] Carrito y checkout
- [ ] Gestión de pedidos
- [ ] Control de stock WooCommerce
- [ ] Plugin `tanish-inventory` — scaffold y estructura
- [ ] Entradas de inventario
- [ ] Salidas de inventario
- [ ] Ajustes de inventario
- [ ] Kardex / historial de movimientos
- [ ] Trazabilidad completa
- [ ] Roles y permisos (quién puede mover stock)
- [ ] Google Analytics 4
- [ ] Google Search Console
- [ ] Pruebas (unitarias, integración, E2E)
- [ ] Despliegue (staging / producción)

---

## Alcance Actual

### La versión inicial SÍ incluye:

- E-commerce completo (catálogo, carrito, checkout, pedidos)
- Control de stock (WooCommerce + plugin propio)
- Gestión de inventario (entradas, salidas, ajustes, kardex)
- Usuarios y roles para inventario
- Analítica web (GA4 / Search Console)

### La versión inicial NO incluye:

- Integración automática con SUNAT
- ERP contable integrado
- Facturación electrónica propia (boletas/facturas)
- Reemplazo del sistema contable existente de TANISH S.A.C.

La emisión de comprobantes electrónicos seguirá haciéndose con la herramienta externa actual. Estas funcionalidades podrán evaluarse en futuras versiones del sistema.

---

## Contribución

```bash
git status
git add <archivos>
git commit -m "feat: descripción clara"
# git push  # solo con autorización
```

- No hacer `git push --force`
- No sobrescribir historia
- `.env` nunca se commitea (verificar con `git status`)

---

## Licencia

Proyecto privado de **TANISH S.A.C.** — Todos los derechos reservados.

---

## Autor

**Juan Dominid Muñoz Eslava** — [@DominidM](https://github.com/DominidM) — `dominidzero@gmail.com`

Desarrollado para **TANISH S.A.C.** — 2026
