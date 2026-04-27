---
name: control-salud-despliegue
description: >-
  Despliegue de Control Salud Web (PHP + MySQL) con Docker y Nginx en subcarpeta
  configurable o subdominio; alinear base_path con la URL real. Usar cuando el
  usuario pregunte por producción, servidor, Docker, Nginx, subcarpeta, o despliegue.
---

# Despliegue Control Salud Web

## Subcarpeta: misma ruta en tres sitios

Elegí **una** ruta pública (ej. `/clinica`, `/control-salud`, etc.) y usala **igual** en:

1. **`web/config/config.local.php`:** `'base_path' => '/TU_RUTA'` (sin barra final; vacío `''` si la app está en la raíz del host).
2. **Nginx:** `location /TU_RUTA/ { ... }` y redirect opcional `location = /TU_RUTA` → `/TU_RUTA/`.
3. **URLs que compartís:** `https://dominio/TU_RUTA/`.

Si pegaste mal una ruta, corregí **solo** esos tres para que coincidan; no hace falta que el nombre de la carpeta en disco sea igual.

## Qué subir

- `web/`, `docker/`, `docker-compose.yml`, `sql/schema_mysql.sql` y migraciones pendientes.

## BD (PHP en contenedor vs host)

- Dentro del mismo compose que MySQL: `host` = nombre del servicio MySQL, `port` = `3306`.
- PHP en host contra puerto publicado: `127.0.0.1` + puerto mapeado.

## No romper otros sitios

- Proyecto en carpeta nueva; compose aparte; web en `127.0.0.1:PUERTO:80`.
- En Nginx: **agregar** solo el `location` de `/TU_RUTA/`; `nginx -t` antes de recargar.

## Nginx (plantilla; reemplazar `TU_RUTA` y puerto)

```nginx
location = /TU_RUTA {
    return 301 /TU_RUTA/;
}

location /TU_RUTA/ {
    proxy_pass http://127.0.0.1:PUERTO/;
    proxy_http_version 1.1;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}
```

Detalle: `proxy_pass` con URL que termina en `/` quita el prefijo `/TU_RUTA/` al reenviar al backend.

## Más detalle en el repo

Ver `README_DESPLIEGUE.md`.
