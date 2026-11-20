<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Crea (o actualiza) el admin real y la vendedora Inés González para
 * producción. NUNCA se llama automáticamente desde DatabaseSeeder — se
 * corre a propósito, una sola vez, con:
 *
 *   php artisan db:seed --class=ProductionUserSeeder --force
 *
 * Ninguna contraseña se escribe en este archivo. Se piden con input oculto
 * (Command::secret()); si se deja vacía, se genera una aleatoria segura y
 * se imprime UNA sola vez en la consola para guardarla en un gestor de
 * contraseñas y rotarla en el primer login. No se persiste en ningún log.
 */
class ProductionUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminEmail = $this->askEmail('Correo del admin real de Concreplus');
        $adminName = $this->askName('Nombre del admin');

        $this->seedUser(email: $adminEmail, name: $adminName, role: 'admin');

        $this->seedUser(email: 'IGonzalez@concreplus.com.mx', name: 'Inés González', role: 'vendedora');
    }

    /**
     * Pide un correo por consola hasta recibir uno no vacío y con formato
     * válido (filter_var + FILTER_VALIDATE_EMAIL).
     */
    private function askEmail(string $prompt): string
    {
        while (true) {
            $email = trim((string) $this->command->ask($prompt));

            if ($email === '') {
                $this->command->error('El correo no puede quedar vacío.');

                continue;
            }

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->command->error("\"{$email}\" no tiene formato de correo válido. Intenta de nuevo.");

                continue;
            }

            return $email;
        }
    }

    /**
     * Pide un nombre por consola hasta recibir uno no vacío.
     */
    private function askName(string $prompt): string
    {
        while (true) {
            $name = trim((string) $this->command->ask($prompt));

            if ($name === '') {
                $this->command->error('El nombre no puede quedar vacío.');

                continue;
            }

            return $name;
        }
    }

    /**
     * Si el usuario no existe, lo crea con la contraseña indicada (pedida
     * por secret() o generada al azar). Si el usuario ya existe, NO
     * modifica su contraseña ni su nombre — por seguridad, este seeder
     * nunca cambia una contraseña existente. En ambos casos, solo se
     * asegura de que quede activo y sincroniza el rol indicado.
     */
    private function seedUser(string $email, string $name, string $role): void
    {
        $password = $this->command->secret("Password para {$email} (déjalo vacío para generar uno aleatorio)");

        if (blank($password)) {
            $password = Str::password(16);
            $this->command->warn("Password generado para {$email}: {$password}");
            $this->command->warn('Guárdalo ahora en un gestor de contraseñas — no se vuelve a mostrar ni se guarda en ningún log.');
        }

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $user->is_active = true;
        $user->save();

        $user->syncRoles([$role]);

        $this->command->info("Listo: usuario creado o existente validado: {$user->email} ({$role})");
    }
}
