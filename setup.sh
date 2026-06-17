#!/bin/bash

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}🚀 SETUP CONDOMINIO - Sistema de Gestión${NC}\n"

if [ ! -f "composer.json" ]; then
    echo -e "${RED}❌ Error: Este script debe ejecutarse en el directorio condominiobackend${NC}"
    exit 1
fi

echo -e "${YELLOW}1️⃣  Configurando APP_KEY...${NC}"
if grep -q "APP_KEY=$" .env; then
    php artisan key:generate
    echo -e "${GREEN}✅ APP_KEY generada${NC}\n"
else
    echo -e "${GREEN}✅ APP_KEY ya existe${NC}\n"
fi

echo -e "${YELLOW}2️⃣  Ejecutando migraciones...${NC}"
php artisan migrate --force
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Migraciones completadas${NC}\n"
else
    echo -e "${RED}❌ Error en migraciones${NC}"
    exit 1
fi

echo -e "${YELLOW}3️⃣  Limpiando caché...${NC}"
php artisan cache:clear
php artisan config:clear
php artisan route:clear
echo -e "${GREEN}✅ Caché limpiada${NC}\n"

echo -e "${YELLOW}4️⃣  Creando usuario administrador...${NC}"
php artisan tinker <<'EOF'
use App\Models\User;
use Illuminate\Support\Facades\Hash;

$admin = User::where('email', 'admin@example.com')->first();

if (!$admin) {
    User::create([
        'name' => 'Admin Master',
        'email' => 'admin@example.com',
        'password' => Hash::make('Admin123!'),
        'role' => 'admin',
        'is_admin' => true,
        'status' => 'active',
        'email_verified_at' => now(),
    ]);
    echo "✅ Usuario admin creado\n";
} else {
    echo "ℹ️ Usuario admin ya existe\n";
}
exit
EOF

echo -e "${GREEN}✅ Administrador configurado${NC}\n"

echo -e "${BLUE}════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}✅ SETUP COMPLETADO CON ÉXITO!${NC}"
echo -e "${BLUE}════════════════════════════════════════════════════${NC}\n"

echo -e "${YELLOW}📋 Próximos pasos:${NC}"
echo -e "  1. ${GREEN}Iniciar servidor Laravel:${NC}"
echo "     php artisan serve"
echo ""
echo -e "  2. ${GREEN}En otra terminal, iniciar Frontend:${NC}"
echo "     cd ../condominiofrontend"
echo "     npm run dev"
echo ""
echo -e "  3. ${GREEN}Abrir en navegador:${NC}"
echo "     http://localhost:5173"
echo ""
echo -e "${YELLOW}🔐 Credenciales Admin:${NC}"
echo "   Email: admin@example.com"
echo "   Password: Admin123!"
echo ""
echo -e "${YELLOW}📧 Email para pruebas:${NC}"
echo "   Se enviarán desde: noreply@condominio.com"
echo "   Usando: guillermo.amescua.23s@utzmg.edu.mx"
echo ""
echo -e "${BLUE}════════════════════════════════════════════════════${NC}\n"
