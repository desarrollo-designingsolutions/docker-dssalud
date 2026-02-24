# ============================================================
# Vue 3 — Producción (build estático)
# El artefacto /app/dist se copia al volumen compartido
# que Nginx usa como root del front.
# ============================================================

FROM node:24-alpine AS builder

WORKDIR /app

# Copiar package files para aprovechar caché de capas
COPY frontend/package*.json ./

# Instalar dependencias
RUN npm install --legacy-peer-deps
RUN npm install --save-dev laravel-echo pusher-js --legacy-peer-deps
RUN npm install @tinymce/tinymce-vue --legacy-peer-deps

# Copiar el código fuente completo
COPY frontend/ ./

# Construir para producción
RUN npm run build

# ============================================================
# Stage 2: imagen ligera solo con el dist generado
# Nginx del host leerá este volumen; esta imagen solo sirve
# para producir y preservar el artefacto dist/.
# ============================================================
FROM alpine:3.20

WORKDIR /dist

# Copiar el build
COPY --from=builder /app/dist ./

# El entrypoint mantiene el contenedor vivo (no es necesario si
# se usa un bind-mount o named volume; aquí es solo por si acaso)
CMD ["sh", "-c", "echo 'Vue build listo en /dist' && tail -f /dev/null"]
