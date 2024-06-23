<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    const LIST_TICKETS = [
        '📀 رویداد ها',
        '🎓 دوره های آموزشی',
        '📝 نظرات و انتقادات',
        '🔅 سایر موارد',
        '📋 درخواست عضویت در انجمن',
        '🤝 همکاری با انجمن',
        '👤 ثبت شماره دانشجویی',
        '🏆 مسابقات',
        '📬 ارتباط با صنعت',
        '👨‍💻 ارتباط با دبیر',
    ];

    const STATUS_OPEN = 'open';

    const STATUS_CLOSE = 'close';

    /**
     * @param int $user_id
     * @return bool
     */
    public static function ticketActive( int $user_id ) : bool
    {
        return self::on()->where( 'user_id', $user_id )->where( 'status', self::STATUS_OPEN )->exists();
    }

    /**
     * @param int $user_id
     * @return mixed
     */
    public static function getActiveTicket( int $user_id ) : mixed
    {
        return self::on()->where( 'user_id', $user_id )->where( 'status', self::STATUS_OPEN )->first();
    }

    /**
     * @param int $user_id
     * @return mixed
     */
    public static function getTickets( int $user_id ) : mixed
    {
        return self::on()->where( 'user_id', $user_id )->limit( 10 )->orderBy( 'id', 'DESC' )->get();
    }

}
