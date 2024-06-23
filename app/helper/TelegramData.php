<?php

namespace App\helper;

/**
 * @property object $message
 * @property string $music_id
 * @property integer $chat_id
 * @property integer $from_id
 * @property string $text
 * @property integer $message_id
 * @property string $first_name
 * @property string $last_name
 * @property string $username
 * @property string $caption
 * @property object $contact
 * @property $reply
 * @property integer $reply_id
 * @property $forward
 * @property integer $forward_id
 * @property string $sticker_id
 * @property string $video_id
 * @property string $file_id
 * @property string $voice_id
 * @property string $photo0_id
 * @property string $photo1_id
 * @property string $photo2_id
 * @property string $txt
 * @property string $type
 * @property string $urlLink
 * @property object $callback_query
 * @property object $chat_join_request
 * @property object $chat_member
 * @property object $invite_link
 * @property string $Data
 * @property string $dataid
 * @property integer $chatid
 * @property integer $fromid
 * @property string $tccall
 * @property integer $messageid
 * @property object $channel_post
 * @property object $inline_query
 * @property string $query
 * @property string $status
 * @property array $text_data
 * @property string[] $query_data
 */
class TelegramData
{
    public function __set( $name, $value )
    {
        $this->{$name} = $value;
    }

    /**
     * @var object
     */
    protected object $update;

    /**
     * @var string
     */
    public $type = 'index';

    /**
     * @param object $update
     */
    public function __construct( object $update )
    {
        $this->update = $update;
        $this->config();
        file_put_contents( storage_path( '/app/public/data.json' ), json_encode( $update ) );
    }

    /**
     * @return \App\helper\TelegramData
     */
    public static function getUpdate()
    {
        return new TelegramData( json_decode( file_get_contents( 'php://input' ) ) );
    }

    /**
     * @return void
     */
    private function config()
    {

        if ( isset( $this->update->message ) )
        {

            $this->message    = $this->update->message;
            $this->chat_id    = $this->message->chat->id ?? '';
            $this->text       = tr_num( trim( $this->message->text ?? '' ) );
            $this->message_id = $this->message->message_id ?? '';
            $this->from_id    = $this->message->from->id ?? '';
            $this->first_name = $this->message->from->first_name ?? '';
            $this->last_name  = $this->message->from->last_name ?? '';
            $this->username   = $this->message->from->username ?? '';
            $this->caption    = $this->message->caption ?? '';
            $this->contact    = $this->message->contact ?? '';
            $this->reply      = $this->message->reply_to_message->forward_from->id ?? '';
            $this->reply_id   = $this->message->reply_to_message->from->id ?? '';
            $this->forward    = $this->message->forward_from ?? '';
            $this->forward_id = $this->message->forward_from->id ?? '';
            $this->sticker_id = $this->message->sticker->file_id ?? '';
            $this->video_id   = $this->message->video->file_id ?? '';
            $this->voice_id   = $this->message->voice->file_id ?? '';
            $this->file_id    = $this->message->document->file_id ?? '';
            $this->music_id   = $this->message->audio->file_id ?? '';
            $this->photo0_id  = $this->message->photo[ 0 ]->file_id ?? '';
            $this->photo1_id  = $this->message->photo[ 1 ]->file_id ?? '';
            $this->photo2_id  = $this->message->photo[ 2 ]->file_id ?? '';
            $this->txt        = $this->message->chat->text ?? '';
            $this->type       = $this->message->chat->type ?? 'index';
            $this->urlLink    = $this->message->entities ?? '';

        }

        if ( isset( $this->update->callback_query ) )
        {

            $this->callback_query = $this->update->callback_query;
            $this->Data           = $this->callback_query->data ?? '';
            $this->dataid         = $this->callback_query->id ?? '';
            $this->chatid         = $this->callback_query->message->chat->id ?? '';
            $this->fromid         = $this->callback_query->from->id ?? '';
            $this->tccall         = $this->callback_query->message->chat->type ?? '';
            $this->messageid      = $this->callback_query->message->message_id ?? '';
            $this->type           = $this->callback_query->message->chat->type ?? 'index';
            $this->first_name     = $this->callback_query->from->first_name ?? '';

        }

        if ( isset( $this->update->channel_post ) )
        {

            $this->channel_post = $this->update->channel_post;
            $this->type         = $this->channel_post->chat->type ?? 'index';
            $this->chat_id      = $this->channel_post->chat->id ?? '';
            $this->text         = $this->channel_post->text ?? '';

        }

        if ( isset( $this->update->edited_message ) )
        {

            $this->message    = $this->update->edited_message;
            $this->chat_id    = $this->message->chat->id ?? '';
            $this->text       = $this->message->text ?? '';
            $this->message_id = $this->message->message_id ?? '';
            $this->from_id    = $this->message->from->id ?? '';
            $this->first_name = $this->message->from->first_name ?? '';
            $this->last_name  = $this->message->from->last_name ?? '';
            $this->username   = $this->message->from->username ?? '';

        }

        if ( isset( $this->update->inline_query ) )
        {

            $this->inline_query = $this->update->inline_query;
            $this->query        = $this->inline_query->query ?? '';
            $this->chatid       = $this->inline_query->from->id ?? '';

        }

        if ( isset( $this->update->chat_join_request ) )
        {

            $this->chat_join_request = $this->update->chat_join_request;
            $this->chat_id           = $this->chat_join_request->chat->id ?? '';
            $this->type              = $this->chat_join_request->chat->type ?? 'index';
            $this->text              = $this->chat_join_request->text ?? '';
            $this->message_id        = $this->chat_join_request->message_id ?? '';
            $this->from_id           = $this->chat_join_request->from->id ?? '';
            $this->first_name        = $this->chat_join_request->from->first_name ?? '';
            $this->last_name         = $this->chat_join_request->from->last_name ?? '';
            $this->username          = $this->chat_join_request->from->username ?? '';
            $this->invite_link       = $this->chat_join_request->invite_link ?? '';

        }

        if ( isset( $this->update->chat_member ) )
        {

            $this->chat_member = $this->update->chat_member;
            $this->chat_id     = $this->chat_member->chat->id ?? '';
            $this->type        = $this->chat_member->chat->type ?? 'index';
            $this->text        = $this->chat_member->text ?? '';
            $this->message_id  = $this->chat_member->message_id ?? '';
            $this->from_id     = $this->chat_member->from->id ?? '';
            $this->first_name  = $this->chat_member->from->first_name ?? '';
            $this->last_name   = $this->chat_member->from->last_name ?? '';
            $this->username    = $this->chat_member->from->username ?? '';
            $this->status      = $this->chat_member->new_chat_member->status ?? '';

        }

        if ( isset( $this->callback_query ) )
        {

            $this->query_data = explode( '-', $this->Data );

        }

        if ( isset( $this->text ) && is_string( $this->text ) )
        {

            $this->text_data = explode( ' ', $this->text );

        }

    }

}
