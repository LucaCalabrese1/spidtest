<?php

namespace App\Helpers;

use App\Cms;
use App\Contact;
use App\Organization;
use App\Accreditation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class MailHelper
{
    /**
     * Send an e-mail reminder to the user.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return bool
     */
    public static function sendEmailConferme($subscription, $filename)
    {
        $userId = auth()->user()->id;
        $contact = contact($userId);
        $contact['attachment'] = $filename;
        $companyLogo = (New Organization)->getLogo();
        $accreditations = new Accreditation;
        $accreditations = accreditations($subscription->productid);

        $accreditations = !empty($accreditations['accreditationName']) ? $accreditations['accreditationName'] : [];

        $subscription->start_date_formatted = DataHelper::dateFormat($subscription->s4_start_date);

        Mail::send('emails.conferme', ['contact' => $contact, 'subscription' => $subscription, 'companyLogo' => $companyLogo, 'accreditations' => $accreditations], function ($m) use ($contact) {
            $m->from(getGlobalEmail(), bySlug('office_name'));
            $m->attach($contact['attachment']);
            $m->to($contact['email'], $contact['lastName'] . ' ' . $contact['firstName'])->subject("Richiesta d’iscrizione evento formativo");
        });

        return true;
    }
}
