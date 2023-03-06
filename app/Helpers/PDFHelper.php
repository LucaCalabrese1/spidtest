<?php

namespace App\Helpers;

use App\Vtiger\CrmEntity;
use App\Accreditation;
use App\Organization;
use App\Course;
use Illuminate\Support\Facades\File;
use niklasravnsborg\LaravelPdf\Facades\Pdf;

class PDFHelper
{
    public static function generateConferme($subId)
    {
        $subscription = subDetail($subId) ?? '';
        $companyLogo = (New Organization)->getLogo();
        $accreditations = new Accreditation;
        $accreditations = accreditations($subscription->productid);

        $courses = new Course;
        $courses = courseRawById($subscription->productid);

        $accreditations = !empty($accreditations['accreditationName']) ? $accreditations['accreditationName'] : [];
        $code = $subscription->s4_barcode;
        $subscription->start_date_formatted = DataHelper::dateFormat($subscription->s4_start_date);

        // here
        $attachmentId =  CrmEntity::attachments($subId, $subscription->productid, $subscription->contact_id);

        $pdf = PDF::loadView('pdfs.conferme', compact('subscription', 'companyLogo', 'accreditations', 'code', 'courses'));

        $path = storage_path('app')."\{$subscription->productid}";
        $sailforPath = config('sailfor.portal_storage_path')."/{$subscription->productid}";

        $filename = $path."/Iscri_{$subId}.pdf";
        $sailforFilename = $sailforPath."/{$attachmentId}_Iscri_{$subId}.pdf";

        if(!is_dir($path)) {
            File::makeDirectory($path, 0777, true, true);
        }
        if(!is_dir($sailforPath)) {
            File::makeDirectory($sailforPath, 0777, true, true);
        }

        if(!File::exists($filename) && !File::exists($sailforFilename)) {
            $pdf->save($sailforFilename);
            $pdf->save($filename);
            MailHelper::sendEmailConferme($subscription, $filename);
        }

        return true;
    }
}
