<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear permisos si no existen, ahora con su grupo
        $permissions = [
            // Dashboard y General
            ['name' => 'ver_dashboard', 'description' => 'Permite al usuario ver la página principal del panel de control.', 'group' => 'General'],

            // Administración de Usuarios y Roles
            ['name' => 'gestionar_usuarios', 'description' => 'Permite crear, editar, eliminar y ver usuarios del sistema.', 'group' => 'Administración de Usuarios y Roles'],
            ['name' => 'gestionar_roles', 'description' => 'Permite crear, editar, eliminar y ver roles del sistema.', 'group' => 'Administración de Usuarios y Roles'],
            ['name' => 'gestionar_permisos', 'description' => 'Permite asignar y revocar permisos a los roles.', 'group' => 'Administración de Usuarios y Roles'],

            // Contratos
            ['name' => 'crear_contratos', 'description' => 'Permite crear nuevos contratos de alquiler o venta.', 'group' => 'Contratos'],
            ['name' => 'ver_contratos', 'description' => 'Permite ver los detalles de los contratos existentes.', 'group' => 'Contratos'],
            ['name' => 'editar_contratos', 'description' => 'Permite modificar los detalles de los contratos.', 'group' => 'Contratos'],
            ['name' => 'eliminar_contratos', 'description' => 'Permite eliminar contratos del sistema.', 'group' => 'Contratos'],

            // Pagos y Cobranzas
            ['name' => 'registrar_pagos', 'description' => 'Permite registrar pagos de alquileres o ventas.', 'group' => 'Pagos y Cobranzas'],
            ['name' => 'ver_pagos', 'description' => 'Permite ver el historial de pagos.', 'group' => 'Pagos y Cobranzas'],
            ['name' => 'generar_informes_cobro', 'description' => 'Permite generar informes sobre el estado de los cobros.', 'group' => 'Pagos y Cobranzas'],
            ['name' => 'generar_liquidaciones', 'description' => 'Permite generar liquidaciones de alquileres para propietarios.', 'group' => 'Pagos y Cobranzas'],
            ['name' => 'ver_liquidaciones', 'description' => 'Permite ver el historial de liquidaciones generadas.', 'group' => 'Pagos y Cobranzas'],
            ['name' => 'lqi.post_issue_adjustments', 'description' => 'Permite emitir ND/NC posteriores a la liquidación del inquilino.', 'group' => 'Pagos y Cobranzas'],

            // Recepción y Agenda
            ['name' => 'registrar_visitas', 'description' => 'Permite registrar nuevas visitas de clientes a propiedades.', 'group' => 'Recepción y Agenda'],
            ['name' => 'ver_agenda', 'description' => 'Permite ver la agenda de citas y eventos.', 'group' => 'Recepción y Agenda'],

            // Mantenimiento y Órdenes de Trabajo
            ['name' => 'crear_ordenes_trabajo', 'description' => 'Permite crear nuevas órdenes de trabajo de mantenimiento.', 'group' => 'Mantenimiento'],
            ['name' => 'ver_ordenes_trabajo', 'description' => 'Permite ver el listado y detalles de las órdenes de trabajo.', 'group' => 'Mantenimiento'],
            ['name' => 'editar_ordenes_trabajo', 'description' => 'Permite modificar los detalles de las órdenes de trabajo.', 'group' => 'Mantenimiento'],
            ['name' => 'cambiar_estado_mantenimiento', 'description' => 'Permite actualizar el estado de las órdenes de trabajo.', 'group' => 'Mantenimiento'],

            // Propiedades y Ventas
            ['name' => 'crear_propiedades', 'description' => 'Permite registrar nuevas propiedades en el sistema.', 'group' => 'Propiedades y Ventas'],
            ['name' => 'ver_propiedades', 'description' => 'Permite ver el catálogo de propiedades.', 'group' => 'Propiedades y Ventas'],
            ['name' => 'editar_propiedades', 'description' => 'Permite modificar los detalles de las propiedades.', 'group' => 'Propiedades y Ventas'],
            ['name' => 'eliminar_propiedades', 'description' => 'Permite eliminar propiedades del sistema.', 'group' => 'Propiedades y Ventas'],
            ['name' => 'gestionar_clientes_venta', 'description' => 'Permite gestionar la información de los clientes interesados en comprar.', 'group' => 'Propiedades y Ventas'],
            ['name' => 'crear_ofertas', 'description' => 'Permite crear nuevas ofertas de compra o alquiler.', 'group' => 'Propiedades y Ventas'],

            // Gerencia e Informes
            ['name' => 'ver_informes_gerenciales', 'description' => 'Permite acceder a informes de rendimiento general de la inmobiliaria.', 'group' => 'Gerencia e Informes'],
            ['name' => 'aprobar_acciones', 'description' => 'Permite aprobar ciertas acciones que requieren autorización gerencial.', 'group' => 'Gerencia e Informes'],
        ];

        // Asegúrate de que la columna 'group' se guarde
        foreach ($permissions as $permissionData) {
            Permission::firstOrCreate(
                ['name' => $permissionData['name']],
                [
                    'description' => $permissionData['description'],
                    'group' => $permissionData['group'] // Añade la columna group aquí
                ]
            );
        }

        // Crear roles si no existen
        $adminRole = Role::firstOrCreate(['name' => 'administrador'], ['description' => 'Rol con acceso total al sistema.']);
        if ($adminRole->wasRecentlyCreated || !$adminRole->permissions()->count()) {
            $adminRole->givePermissionTo(Permission::all());
        }

        $gestorContratosRole = Role::firstOrCreate(['name' => 'gestor_de_contratos'], ['description' => 'Rol encargado de la gestión de contratos de alquiler y venta.']);
        if ($gestorContratosRole->wasRecentlyCreated || !$gestorContratosRole->permissions()->count()) {
            $gestorContratosRole->givePermissionTo([
                'crear_contratos',
                'ver_contratos',
                'editar_contratos',
                'eliminar_contratos',
                'ver_dashboard',
            ]);
        }

        $responsableCobranzasRole = Role::firstOrCreate(['name' => 'responsable_de_cobranzas'], ['description' => 'Rol encargado del seguimiento y registro de los cobros de alquileres.']);
        if ($responsableCobranzasRole->wasRecentlyCreated || !$responsableCobranzasRole->permissions()->count()) {
            $responsableCobranzasRole->givePermissionTo([
                'registrar_pagos',
                'ver_pagos',
                'generar_informes_cobro',
                'generar_liquidaciones', // Añadido
                'ver_liquidaciones', // Añadido
                'ver_dashboard',
            ]);
        }

        $recepcionistaRole = Role::firstOrCreate(['name' => 'recepcionista'], ['description' => 'Rol con permisos para la gestión de visitas y la agenda.']);
        if ($recepcionistaRole->wasRecentlyCreated || !$recepcionistaRole->permissions()->count()) {
            $recepcionistaRole->givePermissionTo([
                'registrar_visitas',
                'ver_agenda',
                'ver_dashboard',
            ]);
        }

        $tecnicoMantenimientoRole = Role::firstOrCreate(['name' => 'tecnico_de_mantenimiento'], ['description' => 'Rol encargado de la gestión de las órdenes de trabajo de mantenimiento.']);
        if ($tecnicoMantenimientoRole->wasRecentlyCreated || !$tecnicoMantenimientoRole->permissions()->count()) {
            $tecnicoMantenimientoRole->givePermissionTo([
                'crear_ordenes_trabajo',
                'ver_ordenes_trabajo',
                'editar_ordenes_trabajo',
                'cambiar_estado_mantenimiento',
                'ver_dashboard',
            ]);
        }

        $agenteVentasRole = Role::firstOrCreate(['name' => 'agente_de_ventas'], ['description' => 'Rol encargado de la gestión de propiedades en venta y clientes interesados.']);
        if ($agenteVentasRole->wasRecentlyCreated || !$agenteVentasRole->permissions()->count()) {
            $agenteVentasRole->givePermissionTo([
                'crear_propiedades',
                'ver_propiedades',
                'editar_propiedades',
                'eliminar_propiedades',
                'gestionar_clientes_venta',
                'crear_ofertas',
                'ver_dashboard',
            ]);
        }

        $gerenteRole = Role::firstOrCreate(['name' => 'gerente'], ['description' => 'Rol con acceso a informes generales y capacidad de aprobación.']);
        if ($gerenteRole->wasRecentlyCreated || !$gerenteRole->permissions()->count()) {
            $gerenteRole->givePermissionTo([
                'ver_dashboard',
                'ver_informes_gerenciales',
                'aprobar_acciones',
                'ver_contratos',
                'ver_pagos',
                'ver_ordenes_trabajo',
                'ver_propiedades',
                'gestionar_usuarios', // Añadido el permiso de gestión de usuarios para el gerente
                'gestionar_roles', // Opcional: si el gerente también puede gestionar roles
                'ver_liquidaciones', // Opcional: si el gerente debe ver liquidaciones
            ]);
        }

        $liquidadorRole = Role::firstOrCreate(['name' => 'liquidador'], ['description' => 'Rol encargado de generar y gestionar las liquidaciones de alquileres.']);
        if ($liquidadorRole->wasRecentlyCreated || !$liquidadorRole->permissions()->count()) {
            $liquidadorRole->givePermissionTo([
                'generar_liquidaciones',
                'ver_liquidaciones',
                'lqi.post_issue_adjustments',
                'ver_dashboard',
                'ver_pagos', // Para ver los pagos relacionados con liquidaciones
                'ver_contratos', // Para ver contratos relacionados
            ]);
        }
    }
}
