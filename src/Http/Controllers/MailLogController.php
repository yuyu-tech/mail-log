<?php

namespace Yuyu\MailLog\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Yuyu\MailLog\Models\EmailLog;
use Yuyu\MailLog\Models\EmailVisitorHistory;

class MailLogController extends Controller
{
    /**
     * Log Mail Visitor History
     * 
     * @param string $id
     * @param Request $request
     * @return Illuminate\Support\Facades\Response
     */
    public function logMailVisitHistory(string $id, Request $request)
    {
        $id = Crypt::decryptString($id);
        $emailLog = EmailLog::find($id);

        if($emailLog) {
            /**
             * Update status to confirmed
             */
            $emailLog->status = config('mailLog.status.confirmed');
            $emailLog->save();

            /**
             * Log Visitor's IP
             */
            $visit = new EmailVisitorHistory; 
            $visit->client_ip = $request->ip();
            $emailLog->visitors()->save($visit);
        }

        $file = __DIR__.'/../../resources/no_img.jpg';

        return response(file_get_contents($file))
            ->header('content-type', mime_content_type($file))
            ->header('content-length', filesize($file))
            ->header('Cache-Control', 'max-age=8640000');
    }
}
