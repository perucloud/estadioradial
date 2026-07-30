<?php

namespace Tests\Feature;

use Symfony\Component\Console\Command\Command;
use Tests\TestCase;

class DatabaseMigrationCommandTest extends TestCase
{
    public function test_migration_command_requires_an_explicit_mode(): void
    {
        $this->artisan('db:migrate-sqlite-to-mysql')
            ->expectsOutput('Debes indicar exactamente una opción: --dry-run o --execute.')
            ->assertExitCode(Command::INVALID);
    }

    public function test_migration_command_rejects_conflicting_modes(): void
    {
        $this->artisan('db:migrate-sqlite-to-mysql', [
            '--dry-run' => true,
            '--execute' => true,
        ])
            ->expectsOutput('Debes indicar exactamente una opción: --dry-run o --execute.')
            ->assertExitCode(Command::INVALID);
    }
}
