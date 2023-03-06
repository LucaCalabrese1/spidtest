<?php

use App\Attachment;
use App\Helpers\DataHelper;
use Illuminate\Support\Facades\DB;

if (!function_exists('getAccreditationByCourseId')) {
    function getAccreditationByCourseId($courseId)
    {
        $accreditations = DB::table('vtiger_products_accreditation')
            ->select('vtiger_products_accreditation.*')
            ->where([
                ['vtiger_products_accreditation.productid', '=', $courseId],
            ])->get();
        return $accreditations;
    }

}
if (!function_exists('accreditationsRaw')) {
    function accreditationsRaw($courseId)
    {
        $accreditationIds = getAccreditationByCourseId($courseId);
        $accreditationsRaw = [];
        foreach ($accreditationIds as $key => $id) {
            $accreditationsRaw = [
                'accreditationName' => getAccreditationName($id->s4_accreditation_id),
                'typologyCourseName' => getTypologyCourseName($id->s4_type_id),
                'typologyAccreditationName' => getTypologyAccreditationName($id->s4_formative_type_id),
                'methodologyCourseName' => getMethodologyAccreditationName($id->s4_method_id),
                'targetAccreditationName' => getTargetAccreditationName($id->s4_target_id)
            ];

        }
        return $accreditationsRaw;
    }

}
if (!function_exists('accreditations')) {
    function accreditations($courseId)
    {
        $accreditationsRaw = accreditationsRaw($courseId);
        $accreditation = [];
        foreach ($accreditationsRaw as $label => $accreditations) {
            foreach ($accreditations as $key => $accreditationValue) {
                $accreditation[$label] = $accreditationValue;
            }
        }
        return $accreditation;
    }

}
if (!function_exists('getAccreditationName')) {
    function getAccreditationName($accreditationId)
    {
        $accreditationName = DB::table('vtiger_accreditamenti')
            ->join('vtiger_crmentity', 'vtiger_accreditamenti.riconoscimentieaccreditamentiid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_accreditamenti.re_re as accreditamento')
            ->where([
                ['vtiger_accreditamenti.riconoscimentieaccreditamentiid', '=', $accreditationId],
            ])->get();

        return $accreditationName;
    }

}
if (!function_exists('getTypologyCourseName')) {
    function getTypologyCourseName($typologyId)
    {
        $typologyCourseName = DB::table('vtiger_tipologieeaccreditamenti')
            ->join('vtiger_crmentity', 'vtiger_tipologieeaccreditamenti.tipologieeaccreditamentiid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_tipologieeaccreditamenti.tipologieeaccreditamenti_tks_t as tipologiaCorso')
            ->where([
                ['vtiger_tipologieeaccreditamenti.tipologieeaccreditamentiid', '=', $typologyId],
            ])->get();

        return $typologyCourseName;
    }

}
if (!function_exists('getTypologyAccreditationName')) {
    function getTypologyAccreditationName($typologyAccreditationId)
    {
        $typologyAccreditationName = DB::table('vtiger_tipoformaaccre')
            ->join('vtiger_crmentity', 'vtiger_tipoformaaccre.tipoformaaccreid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_tipoformaaccre.tipoformaaccre_tks_tipologiafo as tipologiaAccreditamento')
            ->where([
                ['vtiger_tipoformaaccre.tipoformaaccreid', '=', $typologyAccreditationId],
            ])->get();

        return $typologyAccreditationName;
    }

}
if (!function_exists('getMethodologyAccreditationName')) {
    function getMethodologyAccreditationName($methodologyAccreditationId)
    {
        $methodologyAccreditationName = DB::table('vtiger_metodologieeaccreditamenti')
            ->join('vtiger_crmentity', 'vtiger_metodologieeaccreditamenti.metodologieeaccreditamentiid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_metodologieeaccreditamenti.ma_nome as metodologia')
            ->where([
                ['vtiger_metodologieeaccreditamenti.metodologieeaccreditamentiid', '=', $methodologyAccreditationId],
            ])->get();

        return $methodologyAccreditationName;
    }

}
if (!function_exists('getTargetAccreditationName')) {
    function getTargetAccreditationName($targetAccreditationId)
    {
        $targetAccreditationName = DB::table('vtiger_obiettivieaccredita')
            ->join('vtiger_crmentity', 'vtiger_obiettivieaccredita.obiettivieaccreditaid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_obiettivieaccredita.obiettivieaccredita_tks_obiett as obiettivo')
            ->where([
                ['vtiger_obiettivieaccredita.obiettivieaccreditaid', '=', $targetAccreditationId],
            ])->get();

        return $targetAccreditationName;
    }

}
if (!function_exists('LeggiAttivitaFormativeRaws')) {
    function LeggiAttivitaFormativeRaws($idContatto)
    {
        $attivita = DB::table('vtiger_attivitaformative')
            ->join('vtiger_crmentity', 'vtiger_attivitaformative.attivitaformativeid', '=', 'vtiger_crmentity.crmid')
            ->join('vtiger_afbase', 'vtiger_attivitaformative.afbaseid', '=', 'vtiger_afbase.afbaseid')
            ->leftjoin('vtiger_products', 'vtiger_attivitaformative.productid', '=', 'vtiger_products.productid')
            ->select('vtiger_attivitaformative.*', 'vtiger_products.*', 'vtiger_afbase.*', 'vtiger_crmentity.description')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_attivitaformative.contactid', '=', $idContatto],
                ['vtiger_attivitaformative.productid', '=', null],
            ])
            ->orderby('data_scadenza', 'desc')
            ->get();

        return $attivita;
    }

}
if (!function_exists('LeggiAttivitaFormativeRaws_2')) {
    function LeggiAttivitaFormativeRaws_2($idContatto)
    {
        $attivita = DB::table('vtiger_attivitaformative')
            ->join('vtiger_crmentity', 'vtiger_attivitaformative.attivitaformativeid', '=', 'vtiger_crmentity.crmid')
            ->join('vtiger_afbase', 'vtiger_attivitaformative.afbaseid', '=', 'vtiger_afbase.afbaseid')
            ->leftjoin('vtiger_products', 'vtiger_attivitaformative.productid', '=', 'vtiger_products.productid')
            ->select('vtiger_attivitaformative.*', 'vtiger_products.*', 'vtiger_afbase.*', 'vtiger_crmentity.description')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_attivitaformative.contactid', '=', $idContatto],
                ['vtiger_attivitaformative.productid', '=', null],
            ])
            ->orderby('data_scadenza', 'desc')
            ->take(50)->get();

        return $attivita;
    }

}
//Letturà Attivita non più in corso
if (!function_exists('LeggiAttivitaFormativeConcluseRaw')) {
    function LeggiAttivitaFormativeConcluseRaw($idContatto)
    {
        $attivita = DB::table('vtiger_attivitaformative')
            ->join('vtiger_crmentity', 'vtiger_attivitaformative.attivitaformativeid', '=', 'vtiger_crmentity.crmid')
            ->join('vtiger_afbase', 'vtiger_attivitaformative.afbaseid', '=', 'vtiger_afbase.afbaseid')
            ->leftjoin('vtiger_products', 'vtiger_attivitaformative.productid', '=', 'vtiger_products.productid')
            ->select('vtiger_attivitaformative.*', 'vtiger_products.*', 'vtiger_afbase.*', 'vtiger_crmentity.description')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_attivitaformative.contactid', '=', $idContatto],
                ['vtiger_attivitaformative.productid', '<>', null],
            ])
            ->orderby('data_scadenza', 'desc')
            ->get();

        return $attivita;
    }

}
if (!function_exists('LeggiAttivitaFormative')) {
    function LeggiAttivitaFormative($idContatto)
    {
        $attivitaRaw = LeggiAttivitaFormativeRaws($idContatto);
        $attivita = [];
        $attachmentsFile = [];
        foreach ($attivitaRaw as $key => $attivitaSingola) {

            $origin = new DateTime();
            $target = new DateTime($attivitaSingola->data_scadenza);
            $interval = $origin->diff($target);

            $days = $interval->format('%R%a');
            $countAttachments = (new App\Attachment)->attachmentsAndNotesForActivity($idContatto, $attivitaSingola->attivitaformativeid);
            foreach ($countAttachments as $as) {
                $attachmentsFile[] = $as;
            }
            $attachS = new Attachment;
            $attachS = attachmentsAndNotesForCourse($idContatto, $attivitaSingola->attivitaformativeid);
            $attivita[$attivitaSingola->attivitaformativeid] = [
                'attivitaformativeid' => $attivitaSingola->attivitaformativeid,
                'nome' => $attivitaSingola->nome,
                'datainizio' => DataHelper::dateFormat($attivitaSingola->datainizio),
                'data_scadenza' => DataHelper::dateFormat($attivitaSingola->data_scadenza),
                'productname' => $attivitaSingola->productname,
                'description' => $attivitaSingola->description,
                'countAttachments' => count($countAttachments),
                'attachS' => $attachS->toArray(),
                'pathAttachment' => collect($attachmentsFile),
                'days' => $days,

            ];
        }

        return $attivita;
    }

}
if (!function_exists('LeggiAttivitaFormativeConcluse')) {
    function LeggiAttivitaFormativeConcluse($idContatto)
    {
        $attivitaRaw = LeggiAttivitaFormativeConcluseRaw($idContatto);
        $attivita = [];
        $attachmentsFile = [];
        foreach ($attivitaRaw as $key => $attivitaSingola) {
            $countAttachments = (new App\Attachment)->attachmentsAndNotesForActivity($idContatto, $attivitaSingola->attivitaformativeid);
            foreach ($countAttachments as $as) {
                $attachmentsFile[] = $as;
            }
            $attachS = new Attachment;
            $attachS = attachmentsAndNotesForCourse($idContatto, $attivitaSingola->attivitaformativeid);

            $attivita[$attivitaSingola->attivitaformativeid] = [
                'attivitaformativeid' => $attivitaSingola->attivitaformativeid,
                'nome' => $attivitaSingola->nome,
                'datainizio' => DataHelper::dateFormat($attivitaSingola->datainizio),
                'data_scadenza' => DataHelper::dateFormat($attivitaSingola->data_scadenza),
                'productname' => $attivitaSingola->productname,
                'description' => $attivitaSingola->description,
                'countAttachments' => count($countAttachments),
                'pathAttachment' => collect($attachmentsFile),
                'attachS' => $attachS->toArray()

            ];
        }

        return $attivita;
    }


}
if (!function_exists('fileInfoAttivita')) {
    function fileInfoAttivita($AttivitaId)
    {
        $attachments = DB::table('vtiger_notes')
            ->join('vtiger_senotesrel', 'vtiger_senotesrel.notesid', '=', 'vtiger_notes.notesid')
            ->join('vtiger_crmentity', 'vtiger_crmentity.crmid', '=', 'vtiger_senotesrel.crmid')
            ->join('vtiger_crmentity as crm', 'crm.crmid', '=', 'vtiger_notes.notesid')
            ->leftJoin('vtiger_seattachmentsrel', 'vtiger_seattachmentsrel.crmid', '=', 'vtiger_notes.notesid')
            ->leftJoin('vtiger_attachments', 'vtiger_seattachmentsrel.attachmentsid', '=', 'vtiger_attachments.attachmentsid')
            ->select('vtiger_notes.title', 'vtiger_attachments.attachmentsid', 'filename', 'path', 'vtiger_notes.notesid')
            ->where([
                ['vtiger_notes.s4_refdocumento', '=', $AttivitaId],
                ['vtiger_notes.filestatus', '=', 1],
                ['vtiger_crmentity.deleted', '=', '0'],
                ['crm.deleted', '=', '0'],
            ])
            ->get();

        return $attachments;
    }

}
if (!function_exists('LeggiAttivitaFormativaById')) {
    function LeggiAttivitaFormativaById($idAttivita)
    {
        $attivita = DB::table('vtiger_attivitaformative')
            ->join('vtiger_crmentity', 'vtiger_attivitaformative.attivitaformativeid', '=', 'vtiger_crmentity.crmid')
            ->join('vtiger_afbase', 'vtiger_attivitaformative.afbaseid', '=', 'vtiger_afbase.afbaseid')
            ->leftjoin('vtiger_products', 'vtiger_attivitaformative.productid', '=', 'vtiger_products.productid')
            ->leftjoin('vtiger_account', 'vtiger_attivitaformative.account_id', '=', 'vtiger_account.accountid')
            ->select('vtiger_attivitaformative.*', 'vtiger_products.*', 'vtiger_afbase.*', 'vtiger_account.accountname','vtiger_crmentity.description')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_attivitaformative.attivitaformativeid', '=', $idAttivita],
            ])
            ->first();
        return $attivita;
    }

}

if (!function_exists('insertAttivitaCfData')) {
    function insertAttivitaCfData($uniqueId)
    {

        DB::table('vtiger_attivitaformativecf')->insert(
            [
                'attivitaformativeid' => $uniqueId
            ]
        );
    }
}
if (!function_exists('insertAttivitaData')) {
    function insertAttivitaData($uniqueId,$request,$contactid){
        DB::table('vtiger_attivitaformative')->insert(
            [
                'attivitaformativeid' => $uniqueId,
                'datainizio' => $request->attivita_acquisizione,
                'data_scadenza' => $request->attivita_end_date,
                's4_data_acquisizione' => $request->attivita_acquisizione,
                'afbaseid' => $request->attivita_formativa,
                'contactid' => $contactid
            ]
        );
    }

}
