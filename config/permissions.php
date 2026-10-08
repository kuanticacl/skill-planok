<?php

/*
|--------------------------------------------------------------------------
| Catálogo de permisos del CRM
|--------------------------------------------------------------------------
|
| Cada grupo contiene permisos con la forma "grupo.accion". Para sumar un
| permiso nuevo basta agregarlo aquí: aparece solo en la matriz de roles y
| queda disponible como Gate ("can:clave"), en las policies y en el front
| mediante can('clave').
|
| El rol con slug "admin" siempre tiene todos los permisos.
|
*/

return [
    'admin_role' => 'admin',

    'groups' => [
        'dashboard' => [
            'label' => 'Dashboard y Kanban',
            'icon' => 'layout-dashboard',
            'permissions' => [
                'dashboard.view' => 'Ver dashboard y tablero',
            ],
        ],
        'leads' => [
            'label' => 'Leads',
            'icon' => 'users',
            'permissions' => [
                'leads.view' => 'Ver leads asignados a él/ella',
                'leads.view_all' => 'Ver todos los leads',
                'leads.create' => 'Crear leads',
                'leads.update' => 'Editar leads',
                'leads.move' => 'Mover entre etapas',
                'leads.assign' => 'Asignar a otros usuarios',
                'leads.delete' => 'Eliminar leads',
                'leads.notes' => 'Agregar notas y seguimientos',
            ],
        ],
        'clients' => [
            'label' => 'Clientes',
            'icon' => 'building-2',
            'permissions' => [
                'clients.view' => 'Ver clientes',
                'clients.create' => 'Crear clientes',
                'clients.update' => 'Editar clientes',
                'clients.delete' => 'Eliminar clientes',
            ],
        ],
        'users' => [
            'label' => 'Usuarios',
            'icon' => 'user-cog',
            'permissions' => [
                'users.view' => 'Ver usuarios',
                'users.create' => 'Crear usuarios',
                'users.update' => 'Editar usuarios',
                'users.delete' => 'Eliminar usuarios',
            ],
        ],
        'roles' => [
            'label' => 'Roles y permisos',
            'icon' => 'shield-check',
            'permissions' => [
                'roles.view' => 'Ver roles',
                'roles.create' => 'Crear roles',
                'roles.update' => 'Editar roles y permisos',
                'roles.delete' => 'Eliminar roles',
            ],
        ],
        'email' => [
            'label' => 'Email marketing',
            'icon' => 'mail',
            'permissions' => [
                'templates.view' => 'Ver plantillas de email',
                'templates.manage' => 'Crear y editar plantillas',
                'campaigns.view' => 'Ver boletines y su rendimiento',
                'campaigns.create' => 'Crear y programar boletines',
                'campaigns.send' => 'Enviar boletines',
                'lists.manage' => 'Gestionar audiencias (listas)',
                'automations.manage' => 'Gestionar automatizaciones',
                'email_logs.view' => 'Ver historial de mensajes',
                'email_settings.manage' => 'Configurar Resend, API keys y bajas',
            ],
        ],
        'settings' => [
            'label' => 'Configuración del CRM',
            'icon' => 'settings-2',
            'permissions' => [
                'sources.manage' => 'Gestionar orígenes y API',
                'stages.manage' => 'Gestionar etapas del Kanban',
                'fields.manage' => 'Gestionar campos personalizados',
            ],
        ],
    ],

    /*
    | Roles creados por el seeder. "admin" es de sistema (no editable ni
    | eliminable). Los demás se pueden modificar libremente desde el CRM.
    */
    'default_roles' => [
        'admin' => [
            'name' => 'Administrador',
            'description' => 'Acceso total al CRM.',
            'is_system' => true,
            'permissions' => ['*'],
        ],
        'comercial' => [
            'name' => 'Ejecutivo comercial',
            'description' => 'Gestiona sus leads asignados y consulta clientes.',
            'is_system' => false,
            'permissions' => [
                'dashboard.view',
                'leads.view', 'leads.create', 'leads.update', 'leads.move', 'leads.notes',
                'clients.view',
            ],
        ],
        'supervisor' => [
            'name' => 'Supervisor',
            'description' => 'Ve y asigna todos los leads; administra clientes.',
            'is_system' => false,
            'permissions' => [
                'dashboard.view',
                'leads.view', 'leads.view_all', 'leads.create', 'leads.update', 'leads.move', 'leads.assign', 'leads.notes',
                'clients.view', 'clients.create', 'clients.update',
                'users.view',
                'templates.view', 'campaigns.view', 'email_logs.view',
            ],
        ],
        'lectura' => [
            'name' => 'Solo lectura',
            'description' => 'Consulta información sin modificarla.',
            'is_system' => false,
            'permissions' => ['dashboard.view', 'leads.view', 'leads.view_all', 'clients.view'],
        ],
    ],
];
