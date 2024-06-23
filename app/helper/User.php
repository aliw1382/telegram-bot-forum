<?php

namespace App\helper;

use App\Exceptions\ExceptionWarning;
use App\Models\Admin;
use App\Models\Event;
use App\Models\Message;
use App\Models\ParticipantEvents;
use App\Models\Subscription;
use App\Models\User as ModelUser;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\HigherOrderBuilderProxy;
use Illuminate\Support\Facades\Storage;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * @property int $id
 * @property string $name
 * @property string $status
 * @property array $data
 * @property string $phone
 * @property string $reserve_status
 * @property int $step
 * @property int|null $student_id;
 * @property string $created_at
 */
class User
{

    /**
     * @var bool
     */
    private bool $is_new_member = false;

    /**
     * @param string $user_id
     * @param bool $check_exist
     */
    public function __construct( protected string $user_id, bool $check_exist = true )
    {
        if ( $check_exist ) $this->userExists();
    }


    /**
     * @param string $role
     * @return bool
     * @throws \App\Exceptions\ExceptionWarning
     */
    public function toAdmin( string $role = 'administrator' ) : bool
    {
        if ( $this->isAdmin() ) throw new ExceptionWarning( 'این فرد قبلا ادمین می باشد.' );

        $result = Admin::on()->create( [
            'user_id' => $this->user_id,
            'status'  => 'no',
            'role'    => $role
        ] );

        return isset( $result->id );
    }

    /**
     * @return bool
     * @throws \App\Exceptions\ExceptionWarning
     */
    public function removeAdmin() : bool
    {
        if ( ! $this->isAdmin() ) throw new ExceptionWarning( 'این فرد ادمین نمی باشد.' );

        return Admin::on()->where( 'user_id', $this->user_id )->delete();
    }

    /**
     * @param $name
     * @return mixed|void
     */
    public function __get( $name )
    {
        if ( isset( $this->user()->{$name} ) ) return $this->user()->{$name};
    }

    /**
     * @param string $name
     * @return mixed|void
     */
    public function __isset( string $name )
    {
        if ( isset( $this->user()->{$name} ) ) return $this->user()->{$name};
    }

    /**
     * @param string $user_id
     * @return bool
     */
    public static function isUserExists( string $user_id ) : bool
    {
        return ModelUser::on()->where( 'user_id', $user_id )->exists();
    }

    /**
     * @return void
     */
    private function userExists()
    {
        global $new_members;

        if ( ! $this->exists() )
        {

            ModelUser::on()->create( [
                'user_id' => $this->user_id
            ] );
            $new_members[] = $this->user_id;

        }

        $this->is_new_member = in_array( $this->user_id, ( $new_members ?? [] ) );
    }

    /**
     * @return Builder|\LaravelIdea\Helper\App\Models\_IH_User_QB
     */
    private function __model()
    {
        return ModelUser::on()->where( 'user_id', $this->user_id );
    }

    /**
     * @return bool
     */
    private function exists() : bool
    {
        return $this->__model()->exists();
    }

    /**
     * @return \App\Models\User
     */
    public function user() : mixed
    {
        return $this->__model()->first();
    }

    /**
     * @return bool
     */
    public function isIsNewMember() : bool
    {
        return $this->is_new_member;
    }

    /**
     * @return string
     */
    public function getUserId() : string
    {
        return $this->user_id;
    }

    /**
     * @param string|null $status
     * @return $this
     */
    public function setStatus( ?string $status ) : User
    {
        $user         = $this->user();
        $user->status = $status;
        $this->status = $status;
        $user->save();
        return $this;
    }

    /**
     * @return $this
     */
    public function clearStatus() : User
    {
        return $this->setStatus( null );
    }

    /**
     * @param array|string|null $data
     * @return $this
     */
    public function setData( array | string | null $data ) : User
    {
        $user       = $this->user();
        $user->data = $data;
        $this->data = $data;
        $user->save();
        return $this;
    }

    /**
     * @return $this
     */
    public function clearData() : User
    {
        return $this->setData( null );
    }

    /**
     * @param null|int $step
     * @return $this
     */
    public function setStep( null | int $step ) : User
    {
        $user       = $this->user();
        $user->step = $step;
        $this->step = $step;
        $user->save();
        return $this;
    }

    /**
     * @return $this
     */
    public function clearStep() : User
    {
        return $this->setStep( null );
    }

    /**
     * @param string|null $name
     * @return $this
     */
    public function setName( ?string $name ) : User
    {
        $user       = $this->user();
        $user->name = $name;
        $this->name = $name;
        $user->save();
        return $this;
    }

    /**
     * @return $this
     */
    public function clearName() : User
    {
        return $this->setName( null );
    }

    /**
     * @return int
     */
    public function getWallet() : int
    {
        return $this->user()->wallet->balance;
    }

    /**
     * @param int $amount
     * @param string $description
     * @param array $more
     * @return $this
     */
    public function addWallet( int $amount, string $description = '', array $more = [] ) : User
    {
        $this->user()->wallet->deposit( $amount, array_merge( [ 'description' => $description ], $more ) );
        return $this;
    }

    /**
     * @param int $amount
     * @param string $description
     * @param array $more
     * @return $this
     */
    public function demoteWallet( int $amount, string $description = '', array $more = [] ) : User
    {
        $this->user()->wallet->withdraw( $amount, array_merge( [ 'description' => $description ], $more ) );
        return $this;
    }

    /**
     * @param int $amount
     * @param string $description
     * @param array $more
     * @return $this
     */
    public function forceDemoteWallet( int $amount, string $description = '', array $more = [] ) : User
    {
        $this->user()->wallet->forceWithdraw( $amount, array_merge( [ 'description' => $description ], $more ) );
        return $this;
    }

    /**
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function encode( string $key, string $value ) : User
    {
        if ( property_exists( $this, $key ) )
        {
            $user         = $this->user();
            $user->{$key} = string_encode( $value );
            $user->save();
        }
        $this->{$key} = string_encode( $value );
        return $this;
    }

    /**
     * @param string $key
     * @param string|null $default
     * @return string|null
     */
    public function decode( string $key, string $default = null ) : ?string
    {
        if ( isset( $this->{$key} ) )
        {

            return string_decode( $this->{$key} ) ?? $default;

        }
        return null;
    }

    private ?string $keyboard = null;

    /**
     * @param string $keyboard
     * @return $this
     */
    public function setKeyboard( string $keyboard ) : User
    {
        $this->keyboard = $keyboard;
        return $this;
    }

    /**
     * @param string $text
     * @return $this
     * @throws Exception
     */
    public function SendMessage( string $text ) : User
    {
        tel()->sendMessage( $this->user_id, $text, $this->keyboard );
        return $this;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function update( array $data ) : User
    {
        $this->__model()->update( $data );
        return $this;
    }

    /**
     * @param string $text
     * @return $this
     * @throws Exception
     */
    public function SendMessageHtml( string $text ) : User
    {
        tel()->sendMessage( $this->user_id, $text, $this->keyboard, 'html' );
        return $this;
    }

    /**
     * @param string $text
     * @return $this
     * @throws Exception
     */
    public function SendMessageWithoutMedia( string $text ) : User
    {
        tel()->supperSendMessage( [
            'chat_id'                  => $this->user_id,
            'text'                     => $text,
            'parse_mode'               => 'html',
            'disable_web_page_preview' => true,
            'reply_markup'             => $this->keyboard
        ] );
        return $this;
    }

    /**
     * @return bool
     */
    public function isAdmin() : bool
    {
        return (bool) Admin::on()->where( 'user_id', $this->user_id )->exists();
    }

    /**
     * @return bool
     */
    public function isPanelAdmin() : bool
    {
        return (bool) Admin::on()->where( 'user_id', $this->user_id )->where( 'status', 'yes' )->exists();
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function loginPanel() : User
    {
        if ( ! $this->isAdmin() ) throw new Exception( 'کاربر ' . $this->user_id . ' ادمین نمیباشید.' );
        Admin::on()->where( 'user_id', $this->user_id )->update( [
            'status' => 'yes'
        ] );
        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function logoutPanel() : User
    {
        if ( ! $this->isAdmin() ) throw new Exception( 'کاربر ' . $this->user_id . ' ادمین نمیباشید.' );
        Admin::on()->where( 'user_id', $this->user_id )->update( [
            'status' => 'no'
        ] );
        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    public function togglePanel() : User
    {
        if ( ! $this->isAdmin() ) throw new Exception( 'کاربر ' . $this->user_id . ' ادمین نمیباشید.' );
        if ( $this->isPanelAdmin() ) $this->logoutPanel();
        else $this->loginPanel();
        return $this;
    }

    /**
     * @param string|null $text
     * @return string
     */
    public function mention( string $text = null ) : string
    {
        return "<a href='tg://user?id=" . $this->user_id . "'>" . ( $text ?? $this->user_id ) . "</a>";
    }

    /**
     * @return string
     */
    public function code() : string
    {
        return "<code>" . $this->user_id . "</code>";
    }

    /**
     * @return $this
     */
    public function reset() : static
    {
        $user         = $this->user();
        $user->status = null;
        $user->data   = null;
        $user->step   = null;
        $user->save();
        return $this;
    }

    /**
     * @return HigherOrderBuilderProxy|mixed|string
     * @throws ExceptionWarning
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function getRole() : mixed
    {
        if ( ! $this->isAdmin() ) throw new ExceptionWarning( 'این کاربر ادمین نمی باشد.' );
        if ( ! cache()->has( 'role-' . $this->getUserId() ) ) cache()->put( 'role-' . $this->user_id, Admin::on()->where( 'user_id', $this->user_id )->first()->role );
        return cache()->get( 'role-' . $this->getUserId() );
    }

    /**
     * @param string $role
     * @return bool
     * @throws ContainerExceptionInterface
     * @throws ExceptionWarning
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     */
    public function haveRole( string $role ) : bool
    {
        return $this->getRole() == $role;
    }

    /**
     * @param Event $event
     * @param string $type
     * @param array|null $data
     * @return bool
     */
    public function registerEvent( Event $event, string $type, array $data = null ) : bool
    {

        if ( ! $this->isRegisteredEvent( $event ) )
        {

            ParticipantEvents::create( [

                'payment_type' => $type,
                'user_id'      => $this->getUserId(),
                'event_id'     => $event->id,
                'student_id'   => $this->student_id,
                'data'         => $data

            ] );

            return true;

        }

        return false;

    }

    /**
     * @return bool
     */
    public function isLoginUser()
    {
        return is_numeric( $this->student_id );
    }

    /**
     * @param Event $event
     * @return bool
     */
    public function isRegisteredEvent( Event $event ) : bool
    {
        return ParticipantEvents::where( 'event_id', $event->id )->where( function ( Builder $query ) {
            $query->where( 'user_id', $this->user_id );
            if ( $this->isLoginUser() ) $query->orWhere( 'student_id', $this->student_id );
        } )->exists();
    }

    /**
     * @param int $timeRef
     * @param string $name
     * @return bool
     */
    public function spam( int $timeRef = 60, string $name = 'time' ) : bool
    {

        $user_id = $this->user_id;

        Storage::makeDirectory( '/telegram/users/' . $user_id );
        $path = '/telegram/users/' . $user_id . '/' . $name . '.txt';

        if ( ! file_exists( $path ) || ( Storage::get( $path ) - time() ) <= 0 )
        {

            Storage::put( $path, time() + $timeRef );
            return true;

        }

        return false;

    }

    /**
     * @param string $user_id
     * @return bool
     */
    public function is( string $user_id ) : bool
    {
        return $this->user_id == $user_id;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function isOnChannel() : bool
    {
//        return true;
        $response = telegram()->getChatMember( env( 'CHANNEL_ID' ), $this->user_id );
        if ( isset( $response[ 'result' ] ) && isset( $response[ 'result' ][ 'status' ] ) && $response[ 'result' ][ 'status' ] != 'left' && $response[ 'result' ][ 'status' ] != 'ban' ) return true;
        return false;
    }

    /**
     * @return int
     */
    public function subscription() : int
    {
        return Subscription::where( 'user_id', $this->user_id )->first()->day ?? 0;
    }

    /**
     * @param int $day
     * @return $this
     */
    public function addSubscription( int $day ) : static
    {
        $subscription          = new Subscription();
        $subscription->user_id = $this->user_id;
        $subscription->day     = $this->subscription() + $day;
        $subscription->save();
        return $this;
    }

}
