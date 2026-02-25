#!/bin/bash
# =============================================================
# init-certbot.sh
# Ejecutar UNA sola vez en el servidor, ANTES de deploy-server.sh
# cuando aún no existen certificados Let's Encrypt.
#
# Requisitos previos:
#   - Puertos 80 y 443 abiertos en el firewall
#   - DNS de ambos dominios apuntando a este servidor
#   - .env cargado (o pasar las variables manualmente)
# =============================================================

set -e

COMPOSE_FILE="docker-compose.server.yml"
ENV_FILE=".env.server"

echo ""
echo "=========================================="
echo " 🔐 Init Certbot — Let's Encrypt"
echo "=========================================="
echo ""

# Cargar variables
if [ ! -f "$ENV_FILE" ]; then
  echo "❌ No se encontró $ENV_FILE. Copia .env.server y ajusta los valores."
  exit 1
fi
# Cargar variables del .env (ignorando variables readonly del sistema como UID, HOSTNAME, etc.)
set +e
while IFS='=' read -r key value; do
  [[ -z "$key" || "$key" =~ ^# ]] && continue
  export "$key=$value" 2>/dev/null
done < <(grep -v '^#' "$ENV_FILE" | grep -v '^$')
set -e

echo "📋 Dominios a certificar:"
echo "   - ${NGINX_FRONT_SERVER_NAME}"
echo "   - ${NGINX_API_SERVER_NAME}"
echo ""

# -------------------------------------------------------------
# PASO 0: Limpieza
# -------------------------------------------------------------
echo "▶ [0/4] Limpiando contenedores temporales previos..."
docker stop certbot_init_nginx 2>/dev/null || true
docker rm certbot_init_nginx 2>/dev/null || true

# -------------------------------------------------------------
# PASO 1: Levantar solo Nginx con una config HTTP temporal
# para que Certbot pueda completar el challenge webroot
# -------------------------------------------------------------
echo "▶ [1/4] Levantando Nginx temporalmente para el challenge HTTP..."
echo "   (Asegúrate de que nada esté usando el puerto 80 en el host)"

# Config HTTP temporal (sin SSL, solo el bloque del challenge)
cat > /tmp/nginx_certbot_init.conf << EOF
server {
    listen 80;
    server_name ${NGINX_FRONT_SERVER_NAME} ${NGINX_API_SERVER_NAME};

    location /.well-known/acme-challenge/ {
        root /var/www/certbot;
    }

    location / {
        return 200 'Certbot init en progreso...';
        add_header Content-Type text/plain;
    }
}
EOF

docker run -d --rm \
  --name certbot_init_nginx \
  -p 80:80 \
  -v certbot_www:/var/www/certbot \
  -v /tmp/nginx_certbot_init.conf:/etc/nginx/conf.d/default.conf:ro \
  nginx:1.28

echo "   ✅ Nginx temporal levantado."

# -------------------------------------------------------------
# PASO 2: Obtener certificados con Certbot
# -------------------------------------------------------------
echo ""
echo "▶ [2/4] Obteniendo certificados con Certbot..."

docker run --rm \
  -v certbot_certs:/etc/letsencrypt \
  -v certbot_www:/var/www/certbot \
  certbot/certbot:latest certonly \
    --webroot \
    --webroot-path=/var/www/certbot \
    --email admin@desarrollo.com.co \
    --agree-tos \
    --no-eff-email \
    --non-interactive \
    --expand \
    -d "${NGINX_FRONT_SERVER_NAME}" \
    -d "${NGINX_API_SERVER_NAME}"

echo "   ✅ Certificados obtenidos."

# -------------------------------------------------------------
# PASO 3: Detener Nginx temporal
# -------------------------------------------------------------
echo ""
echo "▶ [3/4] Deteniendo Nginx temporal..."
docker stop certbot_init_nginx || true
echo "   ✅ Listo."

# -------------------------------------------------------------
# PASO 4: Indicar que se puede continuar con deploy-server.sh
# -------------------------------------------------------------
echo ""
echo "▶ [4/4] Todo listo."
echo ""
echo "=========================================="
echo " ✅ Certificados generados correctamente."
echo "    Ahora ejecuta: bash deploy-server.sh"
echo "=========================================="
echo ""
