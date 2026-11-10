#!/bin/bash
# ============================================================
# Script de Mantención - PAC Dilocar
# Uso: bash mantencion.sh [on|off]
# ============================================================

APP_DIR="/var/www/pacdilocar"
SECRET="pacdilocar2026"

case "$1" in
  on)
    cd "$APP_DIR"
    php artisan down --secret="$SECRET" --retry=1200
    echo ""
    echo "✅ Modo mantención ACTIVADO"
    echo "   Usuarios ven la página de mantención."
    echo "   Tú puedes acceder en: http://pacdilocar.carabineros.cl/$SECRET"
    echo ""
    ;;
  off)
    cd "$APP_DIR"
    php artisan up
    echo ""
    echo "✅ Sistema RESTAURADO - usuarios pueden acceder normalmente"
    echo ""
    ;;
  *)
    echo "Uso: bash mantencion.sh on | off"
    ;;
esac
