<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Third;
use App\Models\User;
use App\Models\Role;
use App\Helpers\Constants;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class MigrateThirds extends Command
{
    protected $signature = 'migrate:thirds';
    protected $description = 'Migra terceros a usuarios de forma masiva';

    public function handle()
    {
        $this->info('Iniciando migración de registros...');

        // Usamos chunk para manejar la memoria eficientemente
        Third::chunk(200, function ($thirds) {
            DB::transaction(function () use ($thirds) {
                foreach ($thirds as $third) {

                    // updateOrCreate evita duplicados y errores
                    $user = User::updateOrCreate(
                        ['email' => $third->nit], 
                        [
                            'name'     => $third->name,
                            'surname'  => '',
                            'password' => $third->nit,
                            'role_id'  => 'a08fb77e-e692-49cc-bb0a-389937936c4f',
                            'third_id' => $third->id,
                            'company_id' => Constants::COMPANY_UUID,
                        ]
                    );

                    $role = Role::find($user['role_id']);
                    if ($role) {
                        $user->syncRoles($role);
                    }
                }
            });
            $this->comment('Procesados 200 registros más...');
        });

        $this->info('¡Migración completada exitosamente!');
    }
}