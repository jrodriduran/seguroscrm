<?php

return [
    'acl' => [
        'financials' => 'Información financiera (ingresos, comisiones)',
        'policies' => 'Pólizas',
        'commissions' => 'Comisiones',
        'hierarchy' => 'Jerarquía y overrides',
        'analytics' => 'Analítica y valoración',
        'edit' => 'Editar',
    ],

    'menu' => [
        'security' => 'Seguridad',
        'security-info' => 'Registro de accesos, verificación en dos pasos y protección del inicio de sesión.',
        'access-logs' => 'Registro de accesos',
        'access-logs-info' => 'Quién inició sesión, cuándo y desde qué dirección IP.',
        'two-factor' => 'Verificación en dos pasos',
        'two-factor-info' => 'Mira quién tiene el 2FA activo y resetéalo si alguien pierde su teléfono.',
    ],

    'access-logs' => [
        'title' => 'Registro de accesos',

        'datagrid' => [
            'date' => 'Fecha',
            'user' => 'Usuario',
            'email' => 'Email',
            'event' => 'Evento',
            'ip' => 'Dirección IP',
            'device' => 'Dispositivo',
        ],

        'events' => [
            'login' => 'Inició sesión',
            'logout' => 'Cerró sesión',
            'failed' => 'Intento fallido',
            'blocked_ip' => 'IP bloqueada',
            'mfa_failed' => 'Código 2FA incorrecto',
            'mfa_enabled' => '2FA activado',
            'mfa_disabled' => '2FA desactivado',
            'mfa_reset' => '2FA reseteado por admin',
            'support_login' => 'Ingreso del soporte de la plataforma',
            'password_reset' => 'Contraseña restablecida por la plataforma',
        ],
    ],

    'ip-restriction' => [
        'denied' => 'No se permite el acceso desde tu red. Contacta con tu administrador.',
    ],

    'two-factor' => [
        'challenge' => [
            'title' => 'Verificación en dos pasos',
            'info' => 'Escribe el código de 6 dígitos de tu app autenticadora.',
            'code' => 'Código de verificación',
            'recovery-hint' => '¿Perdiste el teléfono? Escribe uno de tus códigos de recuperación.',
            'trust' => 'Confiar en este navegador durante :days días',
            'sign-out' => 'Cerrar sesión',
            'verify' => 'Verificar',
            'invalid' => 'El código no es válido. Revisa la app e inténtalo de nuevo.',
            'throttled' => 'Demasiados intentos. Vuelve a intentarlo en :seconds segundos.',
            'required' => 'Se requiere la verificación en dos pasos.',
        ],

        'setup' => [
            'title' => 'Seguridad de la cuenta',
            'heading' => 'Verificación en dos pasos',
            'info' => 'Protege tu cuenta pidiendo, además de la contraseña, un código de tu teléfono.',
            'status-on' => 'Activada',
            'status-off' => 'Desactivada',
            'required-notice' => 'Tu organización exige la verificación en dos pasos. Actívala para seguir usando el CRM.',
            'recovery-title' => 'Guarda tus códigos de recuperación',
            'recovery-info' => 'Cada código sirve una sola vez si pierdes el acceso a tu teléfono. Guárdalos en un lugar seguro: no se volverán a mostrar.',
            'copy' => 'Copiar códigos',
            'step-app' => 'Instala una app autenticadora en tu teléfono: Google Authenticator, Microsoft Authenticator o Authy.',
            'step-scan' => 'Escanea este código QR con la app o escribe la clave a mano.',
            'manual-key' => 'Clave de configuración',
            'step-confirm' => 'Escribe el código de 6 dígitos que muestra la app para terminar.',
            'enable' => 'Activar verificación en dos pasos',
            'enabled-success' => 'La verificación en dos pasos está activada.',
            'recovery-codes' => 'Códigos de recuperación',
            'remaining' => 'Te quedan :count códigos de recuperación sin usar.',
            'current-code' => 'Código actual',
            'regenerate' => 'Generar códigos nuevos',
            'disable-title' => 'Desactivar',
            'disable-info' => 'Tu cuenta quedará protegida solo con la contraseña.',
            'disable' => 'Desactivar verificación en dos pasos',
            'disabled-success' => 'La verificación en dos pasos está desactivada.',
            'cannot-disable' => 'Tu organización exige la verificación en dos pasos, así que no se puede desactivar.',
            'devices' => 'Navegadores de confianza',
            'forget-devices' => 'Olvidar todos',
            'devices-forgotten' => 'Navegadores de confianza eliminados. La próxima vez pedirán el código.',
            'last-used' => 'último uso :date',
            'no-devices' => 'No hay navegadores de confianza. Marca "Confiar en este navegador" al escribir el código para no pedirlo durante un tiempo.',
        ],

        'users' => [
            'datagrid' => [
                'name' => 'Nombre',
                'email' => 'Email',
                'role' => 'Rol',
                'status' => 'Dos pasos',
                'devices' => 'Navegadores de confianza',
            ],

            'enabled-since' => 'Activa desde :date',
            'disabled' => 'Desactivada',
            'reset' => 'Resetear verificación en dos pasos',
            'reset-success' => 'Se reseteó la verificación en dos pasos de :name.',
            'my-account' => 'Mi verificación en dos pasos',
        ],
    ],

    'configuration' => [
        'title' => 'Seguridad',
        'info' => 'Protección del inicio de sesión de tu equipo: IPs permitidas y verificación en dos pasos.',

        'ip-restriction' => [
            'title' => 'Restricción por dirección IP',
            'info' => 'Permite el panel solo desde direcciones IP concretas, como tu oficina.',
            'enabled' => 'Activar restricción por IP',
            'allowed-ips' => 'Direcciones IP permitidas',
            'allowed-ips-info' => 'Sepáralas con comas. Se aceptan IPs sueltas (203.0.113.10) o rangos (192.168.1.0/24).',
            'exempt-admins' => 'Los administradores pueden entrar desde cualquier lugar',
            'exempt-admins-info' => 'Recomendado: evita que todos queden bloqueados si la lista está mal.',
        ],

        'two-factor' => [
            'title' => 'Verificación en dos pasos',
            'info' => 'Pide un código de una app autenticadora después de la contraseña.',
            'required-for' => 'Exigir verificación en dos pasos a',
            'required-none' => 'Nadie (opcional para cada usuario)',
            'required-admins' => 'Administradores',
            'required-all' => 'Todos',
            'trust-days' => 'Días que un navegador de confianza no pide el código',
            'trust-days-info' => '0 desactiva los navegadores de confianza.',
        ],
    ],
];
