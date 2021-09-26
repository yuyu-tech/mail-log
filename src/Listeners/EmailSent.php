<?php

namespace Yuyu\MailLog\Listeners;

use DB;
use Illuminate\Mail\Events\MessageSent;
use Exception;
use Yuyu\MailLog\Models\EmailLog;

class EmailSent
{
    /**
     * Handle the event.
     *
     * @param MessageSent $event
     */
    public function handle(MessageSent $event)
    {
        $message = $event->message;
        try{
            $messageUniqueId = substr($message->getHeaders()->get('Message-Unique-Id')->getFieldBody(), 1, -1);

            $emailLog = EmailLog::where('message_unique_id', $messageUniqueId)->first();
            
            /**
             * Throw Exception: Log entry not found
             */
            if(!$emailLog) {
                return;    
            }

            /**
             * Update status to sent
             */
            $emailLog->status = config('mailLog.status.sent');
            $emailLog->error = null;
            $emailLog->save();
        }
        catch(Exception $e) {
            /**
             * Throw Exception: Message unique id not found
             */
        }
    }
}