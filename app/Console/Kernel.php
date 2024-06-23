<?php /** @noinspection ALL */

namespace App\Console;

use App\Console\Commands\BackupDailyCommand;
use App\Console\Commands\QRCommand;
use App\Console\Commands\ReserveNotificationCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     * @var array
     */
    protected $commands = [
        BackupDailyCommand::class,
        ReserveNotificationCommand::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule( Schedule $schedule )
    {
        $schedule->command( 'backup:bot' )->daily()->at( '00:00' );

        $schedule->command( 'reserve:notification' )->daily()->at( '00:00' );
        $schedule->command( 'reserve:notification' )->daily()->at( '12:00' );
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load( __DIR__ . '/Commands' );

        require base_path( 'routes/console.php' );
    }
}
