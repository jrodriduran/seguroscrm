<?php

return [
    'warning' => [
        'title' => 'Aviso de tu cuenta',
        'default' => 'Hay un pago pendiente. Ponte en contacto con soporte para evitar interrupciones.',
    ],

    'read-only' => [
        'title' => 'Cuenta en modo solo lectura',
        'default' => 'Puedes consultar y exportar tu información, pero no crear ni modificar nada hasta regularizar la cuenta.',
        'blocked' => 'La cuenta está en modo solo lectura: no se guardó ningún cambio. Contacta a soporte.',
    ],

    'suspended' => [
        'title' => 'Cuenta suspendida',
        'default' => 'El acceso está suspendido temporalmente. Ponte en contacto con soporte para reactivarlo.',
        'data-safe' => 'Tu información está a salvo y no se ha borrado nada.',
        'logout' => 'Cerrar sesión',
    ],

    'support' => [
        'banner' => 'Sesión del soporte de la plataforma: queda registrada en el historial de accesos de la agencia.',
        'expired' => 'El enlace de soporte venció o ya se usó.',
    ],
];
