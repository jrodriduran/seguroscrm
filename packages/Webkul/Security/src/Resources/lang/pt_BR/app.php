<?php

return [
    'acl' => [
        'financials' => 'Informação financeira (receitas, comissões)',
        'policies' => 'Apólices',
        'commissions' => 'Comissões',
        'hierarchy' => 'Hierarquia e overrides',
        'analytics' => 'Análises e avaliação',
        'edit' => 'Editar',
    ],

    'menu' => [
        'security' => 'Segurança',
        'security-info' => 'Registro de acessos, verificação em duas etapas e proteção do login.',
        'access-logs' => 'Registro de acessos',
        'access-logs-info' => 'Quem entrou, quando e de qual endereço IP.',
        'two-factor' => 'Verificação em duas etapas',
        'two-factor-info' => 'Veja quem tem 2FA ativo e redefina-o se alguém perder o telefone.',
    ],

    'access-logs' => [
        'title' => 'Registro de acessos',

        'datagrid' => [
            'date' => 'Data',
            'user' => 'Usuário',
            'email' => 'Email',
            'event' => 'Evento',
            'ip' => 'Endereço IP',
            'device' => 'Dispositivo',
        ],

        'events' => [
            'login' => 'Entrou',
            'logout' => 'Saiu',
            'failed' => 'Tentativa falha',
            'blocked_ip' => 'IP bloqueado',
            'mfa_failed' => 'Código 2FA incorreto',
            'mfa_enabled' => '2FA ativado',
            'mfa_disabled' => '2FA desativado',
            'mfa_reset' => '2FA redefinido pelo admin',
        ],
    ],

    'ip-restriction' => [
        'denied' => 'O acesso a partir da sua rede não é permitido. Contate o administrador.',
    ],

    'two-factor' => [
        'challenge' => [
            'title' => 'Verificação em duas etapas',
            'info' => 'Digite o código de 6 dígitos do seu app autenticador.',
            'code' => 'Código de verificação',
            'recovery-hint' => 'Perdeu o telefone? Digite um dos seus códigos de recuperação.',
            'trust' => 'Confiar neste navegador por :days dias',
            'sign-out' => 'Sair',
            'verify' => 'Verificar',
            'invalid' => 'Código inválido. Confira o app e tente novamente.',
            'throttled' => 'Muitas tentativas. Tente novamente em :seconds segundos.',
            'required' => 'Verificação em duas etapas necessária.',
        ],

        'setup' => [
            'title' => 'Segurança da conta',
            'heading' => 'Verificação em duas etapas',
            'info' => 'Proteja sua conta com um código do seu telefone além da senha.',
            'status-on' => 'Ativada',
            'status-off' => 'Desativada',
            'required-notice' => 'Sua organização exige a verificação em duas etapas. Ative-a para continuar usando o CRM.',
            'recovery-title' => 'Guarde seus códigos de recuperação',
            'recovery-info' => 'Cada código funciona uma vez se você perder o acesso ao telefone. Guarde-os em local seguro: não serão mostrados novamente.',
            'copy' => 'Copiar códigos',
            'step-app' => 'Instale um app autenticador no telefone: Google Authenticator, Microsoft Authenticator ou Authy.',
            'step-scan' => 'Escaneie este QR code com o app ou digite a chave manualmente.',
            'manual-key' => 'Chave de configuração',
            'step-confirm' => 'Digite o código de 6 dígitos mostrado no app para concluir.',
            'enable' => 'Ativar verificação em duas etapas',
            'enabled-success' => 'A verificação em duas etapas está ativada.',
            'recovery-codes' => 'Códigos de recuperação',
            'remaining' => 'Restam :count códigos de recuperação não usados.',
            'current-code' => 'Código atual',
            'regenerate' => 'Gerar novos códigos',
            'disable-title' => 'Desativar',
            'disable-info' => 'Sua conta ficará protegida apenas pela senha.',
            'disable' => 'Desativar verificação em duas etapas',
            'disabled-success' => 'A verificação em duas etapas está desativada.',
            'cannot-disable' => 'Sua organização exige a verificação em duas etapas; ela não pode ser desativada.',
            'devices' => 'Navegadores confiáveis',
            'forget-devices' => 'Esquecer todos',
            'devices-forgotten' => 'Navegadores confiáveis removidos. Na próxima vez pedirão o código.',
            'last-used' => 'último uso :date',
            'no-devices' => 'Nenhum navegador confiável. Marque "Confiar neste navegador" ao digitar o código para não pedi-lo por um tempo.',
        ],

        'users' => [
            'datagrid' => [
                'name' => 'Nome',
                'email' => 'Email',
                'role' => 'Função',
                'status' => 'Duas etapas',
                'devices' => 'Navegadores confiáveis',
            ],

            'enabled-since' => 'Ativa desde :date',
            'disabled' => 'Desativada',
            'reset' => 'Redefinir verificação em duas etapas',
            'reset-success' => 'A verificação em duas etapas de :name foi redefinida.',
            'my-account' => 'Minha verificação em duas etapas',
        ],
    ],

    'configuration' => [
        'title' => 'Segurança',
        'info' => 'Proteção do login da sua equipe: IPs permitidos e verificação em duas etapas.',

        'ip-restriction' => [
            'title' => 'Restrição por endereço IP',
            'info' => 'Permita o painel apenas a partir de endereços IP específicos, como o seu escritório.',
            'enabled' => 'Ativar restrição por IP',
            'allowed-ips' => 'Endereços IP permitidos',
            'allowed-ips-info' => 'Separe com vírgulas. IPs individuais (203.0.113.10) ou faixas (192.168.1.0/24).',
            'exempt-admins' => 'Administradores podem entrar de qualquer lugar',
            'exempt-admins-info' => 'Recomendado: evita bloquear todos se a lista estiver errada.',
        ],

        'two-factor' => [
            'title' => 'Verificação em duas etapas',
            'info' => 'Peça um código de um app autenticador depois da senha.',
            'required-for' => 'Exigir verificação em duas etapas para',
            'required-none' => 'Ninguém (opcional para cada usuário)',
            'required-admins' => 'Administradores',
            'required-all' => 'Todos',
            'trust-days' => 'Dias em que um navegador confiável não pede o código',
            'trust-days-info' => '0 desativa os navegadores confiáveis.',
        ],
    ],
];
