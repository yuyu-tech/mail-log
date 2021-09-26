<?php

namespace Yuyu\MailLog\Listeners;

use DB;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Yuyu\MailLog\Models\EmailLog;
use Yuyu\MailLog\Models\AllowedList;
use Yuyu\MailLog\Models\BlockedList;
use Illuminate\Support\Facades\Mail;

class EmailSending
{
    /**
     * Allowed List
     * 
     * @var array
     */
    protected $allowedList;

    /**
     * Address Modified
     */
    protected $addressModified = false;

    /**
     * Blocked List
     * 
     * @var array
     */
    protected $blockedList;

    /**
     * Handle the event.
     *
     * @param MessageSending $event
     */
    public function handle(MessageSending $event)
    {
        // Fetch mail filter list
        $this->allowedList = AllowedList::select('type', 'address')->get();
        $this->blockedList = BlockedList::select('type', 'address')->get();
        
        $message = $event->message;
        $messageUniqueId = $message->getId();
        
        // Set message unique id that we can use for unique reference in sent method
        $message->getHeaders()->addIdHeader('Message-Unique-Id', $messageUniqueId);

        /**
         * Log mail data
         */
        $emailLog = new EmailLog;
        $emailLog->message_unique_id = $messageUniqueId;
        $emailLog->mailable = empty($event->data["mailable"]) ? null : $event->data["mailable"];
        $emailLog->reference_model_id = empty($event->data["referenceModelId"]) ? null : $event->data["referenceModelId"];
        $emailLog->data = json_encode($event->data);
        $emailLog->subject = $message->getSubject();
        $emailLog->body = $message->getBody();
        $emailLog->from = array_keys($message->getFrom())[0];
        $emailLog->from_name = array_values($message->getFrom())[0];
        $emailLog->reply_to = is_array($message->getReplyTo()) ? array_keys($message->getReplyTo())[0] : null;
        $emailLog->reply_to_name = is_array($message->getReplyTo()) ? array_values($message->getReplyTo())[0] : null;
        $emailLog->to = $message->getTo() ? array_keys($message->getTo()) : null;
        $emailLog->cc = $message->getCc() ?  array_keys($message->getCc()) : null;
        $emailLog->bcc = $message->getBcc() ?  json_encode(array_keys($message->getBcc())) : null;
        $emailLog->date = $message->getDate();
        $emailLog->content_type = $message->getContentType();
        $emailLog->headers = (string)$message->getHeaders();
        $emailLog->status = config('mailLog.status.pending');
        $emailLog->save();
        
        /**
         * Filter TO addresses
         */
        $emailLog->filtered_to = $this->filterEmails($emailLog->to);
        if(!count($emailLog->filtered_to['valid'])) {
            return $this->logError($emailLog, 'NO_TO_ADDRESS');
        }
        elseif(config('mailLog.send_if_all_valid', false) && count($emailLog->filtered_to['valid']) !== count($emailLog->to)) {
            return $this->logError($emailLog, 'TO_ADDRESS_MODIFIED');
        }
        $message->setTo($emailLog->filtered_to['valid']);

        /**
         * Filter CC addresses
         */
        $emailLog->filtered_cc = !empty($emailLog->cc) ? $this->filterEmails($emailLog->cc) : null;
        if(!empty($emailLog->cc) && config('mailLog.send_if_all_valid', false) && count($emailLog->filtered_cc['valid']) !== count($emailLog->cc)) {
            return $this->logError($emailLog, 'CC_ADDRESS_MODIFIED');
        }
        $message->setCc(!empty($emailLog->filtered_cc['valid']) ? $emailLog->filtered_cc['valid'] : []);

        /**
         * Filter BCC addresses
         */
        $emailLog->filtered_bcc = !empty($emailLog->bcc) ? $this->filterEmails($emailLog->bcc) : null;
        if(!empty($emailLog->bcc) && config('mailLog.send_if_all_valid', false) && count($emailLog->filtered_bcc['valid']) !== count($emailLog->bcc)) {
            return $this->logError($emailLog, 'BCC_ADDRESS_MODIFIED');
        }
        $message->setBcc(!empty($emailLog->filtered_bcc['valid']) ? $emailLog->filtered_bcc['valid'] : []);

        $emailLog->is_address_modified = $this->addressModified;
        $emailLog->save();

        /**
         * Append visit logging url to body if mail content type is html
         */
        if($message->getContentType() === 'text/html') {
            $logId = Crypt::encryptString($emailLog->id);
            $body = $message->getBody();
            $imgUrl = route('log-mail-visit-history', ["id" => $logId]);
            $body .= "<img src='{$imgUrl}' style='display:none' />";
            $message->setBody($body);
        }
    }

    /**
     * Log Error
     * 
     * @param EmailLog $emailLog
     * @param string @errorCode
     * @return boolean
     */
    protected function logError(EmailLog $emailLog, string $errorCode) {
        $emailLog->error = config("mailLog.errors.{$errorCode}", "Error occurred while sending an email. Error Code: {$errorCode}");
        $emailLog->is_address_modified = $this->addressModified;
        $emailLog->status = config('mailLog.status.error');
        $emailLog->save();
        return false;
    }

    /**
     * Filter Emails
     * 
     * @param array $emails
     * @return array
     */
    protected function filterEmails(array $emails) {
        $validatedEmails = [
            'valid' => [],
            'invalid' => []
        ];

        foreach ($emails as $email){
            if(empty($email)) {
                continue;
            }

            /**
             * Is email valid? check
             */
            $validator = Validator::make(['email' => $email], [
                'email' => 'email'
            ]);

            if($validator->fails()) {
                $validatedEmails['invalid']['invalid_format'] = $email;
                $this->addressModified = true;
            }
            else {
                $validatedEmails['filtered_emails'][] = $email;
            }
        }

        if(count($validatedEmails['invalid']) && config('mailLog.send_if_all_valid', false)) {
            return $validatedEmails;
        }

        /**
         * Filter email based on blacklisted or whitelisted domain & email address 
         */
        foreach($validatedEmails['filtered_emails'] as $email) {
            $isAllowed = config('mailLog.allow_if_not_in_filter_list', true);
            $domain = explode('@', $email)[1];

            if(
                $this->blockedList
                    ->where('type', config('mailLog.address.type.email', 1))
                    ->where('address', $email)
                    ->count()
            ) {
                $validatedEmails['invalid']['blocked_addresses'][] = $email;
                $this->addressModified = true;
            }
            else if(
                $this->allowedList
                    ->where('type', config('mailLog.address.type.email', 1))
                    ->where('address', $email)
                    ->count()
            ) {
                $validatedEmails['valid'][] = $email;
            }
            else if(
                $this->blockedList
                    ->where('type', config('mailLog.address.type.domain', 2))
                    ->where('address', $domain)
                    ->count()
            ) {
                $validatedEmails['invalid']['blocked_domain'][] = $email;
                $this->addressModified = true;
            }
            else if(
                $this->allowedList
                    ->where('type', config('mailLog.address.type.domain', 2))
                    ->where('address', $domain)
                    ->count()
            ) {
                $validatedEmails['valid'][] = $email;
            }
            else if(config('mailLog.allow_if_not_in_filter_list', true)) {
                $validatedEmails['valid'][] = $email;
            }
            else {
                $validatedEmails['invalid']['no_filter_applied'][] = $email;
                $this->addressModified = true;
            }
        }

        return [
            'valid' => $validatedEmails['valid'],
            'invalid' => $validatedEmails['invalid']
        ];
    }
}