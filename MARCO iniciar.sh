#!/bin/bash
echo "🚀 Iniciando servidores de GestiON..."

# Navegar a la carpeta del proyecto
cd "/home/marco/Documentos/Clases 2026/Git/GestiON_2025-26/gestion"

# Esta línea asegura que al presionar Ctrl+C se apaguen ambos servidores
trap 'kill $(jobs -p)' EXIT

# Levantar PHP (Backend) en segundo plano
php artisan serve &

# Levantar Vite (Frontend) en segundo plano
npm run dev &

# Mantener la ventana abierta mostrando los registros
wait
