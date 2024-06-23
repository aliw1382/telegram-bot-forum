<?php

namespace App\Http\Controllers;

use App\Helper\TelegramData;
use App\Models\Post;

class ChannelPostController extends TelegramData
{


    public function channel()
    {

        if ( ! empty( $this->text ) || ! empty( $this->channel_post->caption ) )
        {

            $content = $this->text ?? $this->channel_post->caption;

            if ( ( str_contains( $content, '#معرفی_سایت' ) || str_contains( $content, '#مطالب_مفید' ) ) && $this->chat_id == env( 'CHANNEL_ID' ) )
            {

                Post::on()->create( [

                    'chat_id'    => $this->chat_id,
                    'message_id' => $this->message_id

                ] );

            }

        }

    }

}
