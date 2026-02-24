#!/bin/bash
# =============================================================
# deploy-server.sh
# Deploy completo en el servidor de producción.
#
# Uso normal (primera vez, después de init-certbot.sh):
#   bash deploy-server.sh
#
# Para forzar rebuild de imágenes:
#   bash deploy-server.sh --build
#
# Para renovar certificados SSL:
#   bash deploy-server.sh --renew-ssl
# =============================================================

set -e

COMPOSE_FILE="docker-compose.server.yml"
ENV_FILE=".env.server"
BUILD_FLAG=""
RENEW_SSL=false

# Parsear argumentos
for arg in "$@"; do
  case $arg in
    --build) BUILD_FLAG="--build" ;;
    --renew-ssl) RENEW_SSL=true ;;
  esac
done

echo ""
echo "=========================================="
echo " 🚀 Deploy en SERVIDOR (producción)"
echo "=========================================="
echo ""

# Verificar que existe el .env de servidor
if [ ! -f "$ENV_FILE" ]; then
  echo "❌ No se encontró $ENV_FILE"
  echo "   Copia .env.server, ajusta los valores y vuelve a intentarlo."
  exit 1
fi

# Cargar el .env de servidor como archivo activo
cp "$ENV_FILE" .env
echo "✅ Usando configuración: $ENV_FILE"

# Verificar que existen los certificados (si no, sugerir init-certbot.sh)
export $(grep -v '^#' "$ENV_FILE" | xargs)
CERT_FILE_CHECK="/etc/letsencrypt/live/${NGINX_FRONT_SERVER_NAME}/fullchain.pem"

CERT_VOLUME=$(docker volume ls -q | grep -w "dssalud_certbot_certs" || true)
if [ -z "$CERT_VOLUME" ]; then
  echo ""
  echo "⚠️  No se encontró el volumen de certificados 'dssalud_certbot_certs'."
  echo "   Si es la primera vez, ejecuta primero:"
  echo "     bash docker/certbot/init-certbot.sh"
  echo ""
  read -p "¿Continuar de todos modos? (s/N): " CONTINUE
  if [[ ! "$CONTINUE" =~ ^[sS]$ ]]; then
    exit 1
  fi
fi

# Renovar certificados si se pidió
if [ "$RENEW_SSL" = true ]; then
  echo ""
  echo "▶ Renovando certificados SSL..."
  docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" run --rm certbot renew
  echo "   ✅ Certificados renovados."
fi

echo ""
echo "▶ Deteniendo contenedores anteriores..."
docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" down

echo ""
echo "▶ Levantando todos los servicios${BUILD_FLAG:+ (con rebuild)}..."
docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" up -d $BUILD_FLAG

echo ""
echo "▶ Esperando a que el build de Vue finalice..."
# Esperar a que el contenedor vue termine (sale cuando el build está listo)
docker wait "${COMPOSE_PROJECT_NAME}_vue" 2>/dev/null || true

echo ""
echo "▶ Estado de los contenedores:"
docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE" ps

echo ""
echo "=========================================="
echo " ✅ Producción lista:"
echo "    🌐 Front: https://${NGINX_FRONT_SERVER_NAME}"
echo "    🔌 API:   https://${NGINX_API_SERVER_NAME}"
echo "=========================================="
echo ""
echo "📌 Para renovar SSL en el futuro:"
echo "   bash deploy-server.sh --renew-ssl"
echo ""
echo "📌 Para renovación automática (crontab):"
echo "   0 3 * * * cd $(pwd) && bash deploy-server.sh --renew-ssl >> /var/log/certbot-renew.log 2>&1"
echo ""
