<?php

namespace Database\Seeders;

use App\Helpers\Constants;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $permissionAll = Permission::all();

        $arrayData = [
            [
                'id' => Constants::ROLE_SUPERADMIN_UUID,
                'name' => 'Super Administrador',
                'description' => 'Super Administrador',
                'viewable' => 0,
                'company_id' => null,
            ],
            [
                'id' => 'a08fb77e-e692-49cc-bb0a-389937936c4f',
                'name' => 'Radicador (Thirds)',
                'description' => 'Radicador (Thirds)',
                'viewable' => 1,
                'company_id' => Constants::COMPANY_UUID,
            ],
        ];

        // Inicializar la barra de progreso
        $this->command->info('Starting Seed Data ...');
        $bar = $this->command->getOutput()->createProgressBar(count($arrayData));

        foreach ($arrayData as $key => $value) {
            $model = new Role;
            $model->id = $value['id'];
            $model->name = $value['name'];
            $model->guard_name = 'api';
            $model->description = $value['description'];
            $model->viewable = $value['viewable'];
            $model->company_id = $value['company_id'];
            $model->save();

            // Asignar todos los permisos al primer rol
            if ($key == 0) {
                $model->givePermissionTo($permissionAll);
            }
        }

        $bar->finish(); // Finalizar la barra

    }
}
