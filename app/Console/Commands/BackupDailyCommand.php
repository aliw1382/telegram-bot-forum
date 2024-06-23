<?php

namespace App\Console\Commands;

use App\helper\Str;
use App\helper\TelegramBot;
use App\helper\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BackupDailyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:bot';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Backup Source And Send To Telegram';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     * @throws \Exception
     */
    public function handle() : int
    {

        $user     = new User( env( 'ADMIN_LOG' ) );
        $telegram = telegram();

        $msg = $telegram->sendMessage( $user->getUserId(), Str::codeB( '♻️ Starting backup...' ) )[ 'result' ][ 'message_id' ];

        \Artisan::call( 'backup:run --disable-notifications' );

        sleep( 1 );
        $telegram->editMessageText( $user->getUserId(), $msg, Str::codeB( '✅ Successfully Create BackUp.' ) );

        sleep( 1 );
        $telegram->editMessageText( $user->getUserId(), $msg, Str::codeB( '📤 Uploading BackUp ....' ) );
        $telegram->sendChatAction( $user->getUserId(), TelegramBot::ActionUploadDocument );

        $files = Storage::files( env( 'APP_NAME' ) );
        foreach ( $files as $file )
        {

            $telegram->sendDocument( $user->getUserId(), url()->to( 'storage/app/' . $file ), 'Your BackUp: ' . jdate()->format( 'Y/m/d H:i:s' ) );

        }

        \Artisan::call( 'backup:clean --disable-notifications' );
        Storage::deleteDirectory( env( 'APP_NAME' ) );

        $telegram->editMessageText( $user->getUserId(), $msg, Str::codeB( '✅ Backup completed!' ) );

        $this->alert( 'Backup completed' );

        return 1;

    }
}
