<?php

use App\Accreditation;
use App\Assignment;
use App\Attachment;
use App\Category;
use App\Course;
use App\Helpers\DataHelper;
use App\Location;
use App\Subscription;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

if (!function_exists('attachments')) {
    function attachments($entityId = null)
    {
        $attachments = DB::table('vtiger_attachments')
            ->join('vtiger_seattachmentsrel', 'vtiger_seattachmentsrel.attachmentsid', '=', 'vtiger_attachments.attachmentsid')
            ->join('vtiger_crmentity', 'vtiger_seattachmentsrel.crmid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_attachments.*')
            ->where([
                ['vtiger_seattachmentsrel.crmid', '=', $entityId],
                ['vtiger_crmentity.deleted', '=', '0'],
            ])->get();
//dd($attachments);
        return $attachments;

    }

}
if (!function_exists('getAttachemtImageById')) {
    function getAttachemtImageById($entityId)
    {
        $attachments = attachments($entityId);
        $sailforUrl = config('sailfor.url');
        $imgNotFound = config('sailfor.image_not_found');
        $imgUrl = '';

        if (count($attachments) == 1) {
            foreach ($attachments as $attachment) {
                $imgUrl .= $sailforUrl . $attachment->path . $attachment->attachmentsid . '_' . $attachment->name;
            }
        } else {
            $imgUrl .= $imgNotFound;
        }
        return $imgUrl;
    }

}
if (!function_exists('attachmentsAndNotes')) {
    function attachmentsAndNotes($contactId)
    {
        $attachments = DB::table('vtiger_notes')
            ->join('vtiger_senotesrel', 'vtiger_senotesrel.notesid', '=', 'vtiger_notes.notesid')
            ->leftJoin('vtiger_notescf', 'vtiger_notescf.notesid', '=', 'vtiger_notes.notesid')
            ->join('vtiger_crmentity', 'vtiger_crmentity.crmid', '=', 'vtiger_notes.notesid')
            ->join('vtiger_crmentity as crm2', 'crm2.crmid', '=', 'vtiger_senotesrel.crmid')
            ->leftJoin('vtiger_groups', 'vtiger_groups.groupid', '=', 'vtiger_crmentity.smownerid')
            ->leftJoin('vtiger_seattachmentsrel', 'vtiger_seattachmentsrel.crmid', '=', 'vtiger_notes.notesid')
            ->leftJoin('vtiger_attachments', 'vtiger_seattachmentsrel.attachmentsid', '=', 'vtiger_attachments.attachmentsid')
            ->leftJoin('vtiger_users', 'vtiger_crmentity.smownerid', '=', 'vtiger_users.id')
            ->select(DB::raw('CASE WHEN LENGTH(`vtiger_notes`.`title`)>16
                THEN CONCAT(SUBSTRING(`vtiger_notes`.`title`, 1, 16),"...")
                ELSE `vtiger_notes`.`title` END as titlemax16xchr'),
                'vtiger_crmentity.crmid',
                'vtiger_notes.title',
                'vtiger_notes.note_no',
                'vtiger_notes.folderid',
                'vtiger_crmentity.modifiedtime',
                'vtiger_notes.filelocationtype',
                'vtiger_notes.filestatus',
                'vtiger_attachments.attachmentsid',
                'filename',
                'path',
                'vtiger_notes.notesid',
                'vtiger_notes.s4_id_evento',
                'vtiger_notes.s4_tipo_documento')
            ->where([
                ['crm2.crmid', '=', $contactId],
                ['vtiger_notes.filestatus', '=', 1],
                ['vtiger_attachments.attachmentsid', '<>', null],
                ['vtiger_notes.s4_tipo_documento', '=', 'Materiale Didattico'],
                ['vtiger_crmentity.deleted', '=', '0'],
            ])->get();
        return $attachments;
    }

}
if (!function_exists('contactAttachments')) {
    function contactAttachments($contactId)
    {
        $attachments = DB::table('vtiger_notes')
            ->join('vtiger_senotesrel', 'vtiger_senotesrel.notesid', '=', 'vtiger_notes.notesid')
            ->leftJoin('vtiger_notescf', 'vtiger_notescf.notesid', '=', 'vtiger_notes.notesid')
            ->join('vtiger_crmentity', 'vtiger_crmentity.crmid', '=', 'vtiger_notes.notesid')
            ->join('vtiger_crmentity as crm2', 'crm2.crmid', '=', 'vtiger_senotesrel.crmid')
            ->leftJoin('vtiger_groups', 'vtiger_groups.groupid', '=', 'vtiger_crmentity.smownerid')
            ->leftJoin('vtiger_seattachmentsrel', 'vtiger_seattachmentsrel.crmid', '=', 'vtiger_notes.notesid')
            ->leftJoin('vtiger_attachments', 'vtiger_seattachmentsrel.attachmentsid', '=', 'vtiger_attachments.attachmentsid')
            ->leftJoin('vtiger_users', 'vtiger_crmentity.smownerid', '=', 'vtiger_users.id')
            ->select('vtiger_crmentity.crmid',
                'vtiger_crmentity.modifiedtime',
                'vtiger_attachments.attachmentsid',
                'filename',
                'path',
                'vtiger_notes.*')
            ->where([
                ['crm2.crmid', '=', $contactId],
                //['vtiger_attachments.attachmentsid', '<>', null],
                ['vtiger_notes.filestatus', '=', 1],
                ['vtiger_crmentity.deleted', '=', '0']
                // ['vtiger_notes.s4_id_evento', '=', '0']  // prendo solo gli allegati senza un idCorso
                // ['vtiger_notes.s4_tipo_documento', '=', 'Materiale Didattico'],  //prendo tutti i tipi di allegato
            ])->orderBy('vtiger_notes.s4_datadocumento', 'DESC')
            ->get();

        return $attachments;
    }

}
if (!function_exists('curriculumAttachments')) {
    function curriculumAttachments($contactId)
    {
        $attachments = DB::table('vtiger_notes')
            ->join('vtiger_senotesrel', 'vtiger_senotesrel.notesid', '=', 'vtiger_notes.notesid')
            ->leftJoin('vtiger_notescf', 'vtiger_notescf.notesid', '=', 'vtiger_notes.notesid')
            ->join('vtiger_crmentity', 'vtiger_crmentity.crmid', '=', 'vtiger_notes.notesid')
            ->join('vtiger_crmentity as crm2', 'crm2.crmid', '=', 'vtiger_senotesrel.crmid')
            ->leftJoin('vtiger_groups', 'vtiger_groups.groupid', '=', 'vtiger_crmentity.smownerid')
            ->leftJoin('vtiger_seattachmentsrel', 'vtiger_seattachmentsrel.crmid', '=', 'vtiger_notes.notesid')
            ->leftJoin('vtiger_attachments', 'vtiger_seattachmentsrel.attachmentsid', '=', 'vtiger_attachments.attachmentsid')
            ->leftJoin('vtiger_users', 'vtiger_crmentity.smownerid', '=', 'vtiger_users.id')
            ->select('vtiger_crmentity.crmid',
                'vtiger_notes.title',
                'vtiger_notes.note_no',
                'vtiger_notes.folderid',
                'vtiger_crmentity.modifiedtime',
                'vtiger_notes.filelocationtype',
                'vtiger_notes.filestatus',
                'vtiger_attachments.attachmentsid',
                'filename',
                'path',
                'vtiger_notes.notesid',
                'vtiger_notes.s4_id_evento',
                'vtiger_notes.s4_datadocumento',
                'vtiger_notes.s4_tipo_documento')
            ->where([
                ['crm2.crmid', '=', $contactId],
                ['vtiger_notes.filestatus', '=', 1],
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_notes.filename', 'LIKE', 'Curriculum_formativo%']
            ])->orderBy('vtiger_crmentity.modifiedtime', 'DESC')
            ->first();

        return $attachments;
    }

}
if (!function_exists('attachmentsAndNotesForCourse')) {
    function attachmentsAndNotesForCourse($contactId, $courseId)
    {
        $attachments = DB::table('vtiger_notes')
            ->join('vtiger_senotesrel', 'vtiger_senotesrel.notesid', '=', 'vtiger_notes.notesid')
            ->leftJoin('vtiger_notescf', 'vtiger_notescf.notesid', '=', 'vtiger_notes.notesid')
            ->join('vtiger_crmentity', 'vtiger_crmentity.crmid', '=', 'vtiger_notes.notesid')
            ->join('vtiger_crmentity as crm2', 'crm2.crmid', '=', 'vtiger_senotesrel.crmid')
            ->leftJoin('vtiger_groups', 'vtiger_groups.groupid', '=', 'vtiger_crmentity.smownerid')
            ->leftJoin('vtiger_seattachmentsrel', 'vtiger_seattachmentsrel.crmid', '=', 'vtiger_notes.notesid')
            ->leftJoin('vtiger_attachments', 'vtiger_seattachmentsrel.attachmentsid', '=', 'vtiger_attachments.attachmentsid')
            ->leftJoin('vtiger_users', 'vtiger_crmentity.smownerid', '=', 'vtiger_users.id')
            ->select(DB::raw('CASE WHEN LENGTH(`vtiger_notes`.`title`)>16
                THEN CONCAT(SUBSTRING(`vtiger_notes`.`title`, 1, 16),"...")
                ELSE `vtiger_notes`.`title` END as titlemax16xchr'),
                'vtiger_crmentity.crmid',
                'vtiger_notes.title',
                'vtiger_notes.note_no',
                'vtiger_notes.folderid',
                'vtiger_crmentity.modifiedtime',
                'vtiger_notes.filelocationtype',
                'vtiger_notes.filestatus',
                'vtiger_attachments.attachmentsid',
                'filename',
                'path',
                'vtiger_notes.notesid',
                'vtiger_notes.s4_id_evento', // id del corso
                'vtiger_notes.s4_tipo_documento')
            ->where([
                ['crm2.crmid', '=', $contactId],
                ['vtiger_notes.filestatus', '=', 1],
                //['vtiger_attachments.attachmentsid', '<>', ''],
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_notes.s4_id_evento', '=', $courseId]
            ])
            ->orderBy('vtiger_notes.title', 'asc')
            ->get();
        return $attachments;
    }

}
if (!function_exists('attachmentsAndNotesForActivity')) {
    function attachmentsAndNotesForActivity($contactId, $AttivitaId)
    {
        $attachments = DB::table('vtiger_notes')
            ->join('vtiger_senotesrel', 'vtiger_senotesrel.notesid', '=', 'vtiger_notes.notesid')
            ->leftJoin('vtiger_notescf', 'vtiger_notescf.notesid', '=', 'vtiger_notes.notesid')
            ->join('vtiger_crmentity', 'vtiger_crmentity.crmid', '=', 'vtiger_notes.notesid')
            ->join('vtiger_crmentity as crm2', 'crm2.crmid', '=', 'vtiger_senotesrel.crmid')
            ->leftJoin('vtiger_groups', 'vtiger_groups.groupid', '=', 'vtiger_crmentity.smownerid')
            ->leftJoin('vtiger_seattachmentsrel', 'vtiger_seattachmentsrel.crmid', '=', 'vtiger_notes.notesid')
            ->leftJoin('vtiger_attachments', 'vtiger_seattachmentsrel.attachmentsid', '=', 'vtiger_attachments.attachmentsid')
            ->leftJoin('vtiger_users', 'vtiger_crmentity.smownerid', '=', 'vtiger_users.id')
            ->select(DB::raw('CASE WHEN LENGTH(`vtiger_notes`.`title`)>16
                THEN CONCAT(SUBSTRING(`vtiger_notes`.`title`, 1, 16),"...")
                ELSE `vtiger_notes`.`title` END as titlemax16xchr'),
                'vtiger_crmentity.crmid',
                'vtiger_notes.title',
                'vtiger_notes.note_no',
                'vtiger_notes.folderid',
                'vtiger_crmentity.modifiedtime',
                'vtiger_notes.filelocationtype',
                'vtiger_notes.filestatus',
                'vtiger_attachments.attachmentsid',
                'filename',
                'path',
                'vtiger_notes.notesid',
                'vtiger_notes.s4_id_evento', // id del corso
                'vtiger_notes.s4_tipo_documento')
            ->where([
                ['crm2.crmid', '=', $contactId],
                ['vtiger_notes.filestatus', '=', 1],
                //['vtiger_attachments.attachmentsid', '<>', ''],
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_notes.s4_refdocumento', '=', $AttivitaId]
            ])
            ->orderBy('vtiger_notes.title', 'asc')
            ->get();
        return $attachments;
    }

}
if (!function_exists('attachmentsAndNotesForMilestone')) {
    function attachmentsAndNotesForMilestone($contactId, $milestone_no)
    {
        $attachments = DB::table('vtiger_notes')
            ->join('vtiger_senotesrel', 'vtiger_senotesrel.notesid', '=', 'vtiger_notes.notesid')
            ->leftJoin('vtiger_notescf', 'vtiger_notescf.notesid', '=', 'vtiger_notes.notesid')
            ->join('vtiger_crmentity', 'vtiger_crmentity.crmid', '=', 'vtiger_notes.notesid')
            ->join('vtiger_crmentity as crm2', 'crm2.crmid', '=', 'vtiger_senotesrel.crmid')
            ->leftJoin('vtiger_groups', 'vtiger_groups.groupid', '=', 'vtiger_crmentity.smownerid')
            ->leftJoin('vtiger_seattachmentsrel', 'vtiger_seattachmentsrel.crmid', '=', 'vtiger_notes.notesid')
            ->leftJoin('vtiger_attachments', 'vtiger_seattachmentsrel.attachmentsid', '=', 'vtiger_attachments.attachmentsid')
            ->leftJoin('vtiger_users', 'vtiger_crmentity.smownerid', '=', 'vtiger_users.id')
            ->select(DB::raw('CASE WHEN LENGTH(`vtiger_notes`.`title`)>16
                THEN CONCAT(SUBSTRING(`vtiger_notes`.`title`, 1, 16),"...")
                ELSE `vtiger_notes`.`title` END as titlemax16xchr'),
                'vtiger_crmentity.crmid',
                'vtiger_notes.title',
                'vtiger_notes.note_no',
                'vtiger_notes.folderid',
                'vtiger_crmentity.modifiedtime',
                'vtiger_notes.filelocationtype',
                'vtiger_notes.filestatus',
                'vtiger_attachments.attachmentsid',
                'filename',
                'path',
                'vtiger_notes.notesid',
                'vtiger_notes.s4_id_evento', // id del corso
                'vtiger_notes.s4_tipo_documento')
            ->where([
                ['crm2.crmid', '=', $contactId],
                ['vtiger_notes.filestatus', '=', 1],
                //['vtiger_attachments.attachmentsid', '<>', ''],
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_notes.s4_refdocumento', '=', $milestone_no]
            ])
            ->orderBy('vtiger_notes.title', 'asc')
            ->get();

        return $attachments;
    }

}
if (!function_exists('assignmentsByContactIdHistory')) {

    function assignmentsByContactIdHistory($contactId, $year = null, $text = null)
    {
        if (isset($year) || isset($text)) {
            $assignmentsRaw = assignmentsRawHistory($contactId, $year, $text);
        } else {
            $assignmentsRaw = assignmentsRawHistory($contactId);

        }

        $ass = [];
        $historyCredits = 0;
        $TotalCredit = 0;
        $historyCreditsForYear = [];
        $ore = '';
        foreach ($assignmentsRaw as $key => $assRaw) {
            //corso
            $course = new Course;
            $course = courseByIdAll($assRaw->productid);
            $countAttachments = [];
            if ($course) {
                //crediti Totali x anno
                $years = new Assignment;
                $years = getYears($contactId, $assRaw->productid)->toArray();
                //giorni e ore incarico
                $totalDaysHours = getTotalDaysHours($contactId, $assRaw->productid);
                $ore = $totalDaysHours['timeCount'];
                // SE le ore sono diverse da 0 allora aggiungo i crediti alla finestra dei crediti
                if ($ore <> '0:0') {
                    if (!empty($years)) {
                        foreach ($years as $y) {
//                            dd((array)$y);
                            $year = (array)$y;
                            $yearsArray = substr($year['programma_data'], 0, 4);

                        }

                        // crediti Totali con Anno come parametro
                        $TotalCreditArray1 = new Assignment;
                        $TotalCreditArray1 = getTotalCreditForYear($contactId, $assRaw->productid, $yearsArray);
                        $TotalCreditForYear = $TotalCreditArray1['credits'];
                        $historyCreditsForYear[$yearsArray][] = $TotalCreditForYear;
                    }
                }
                //totale crediti corso singolo
                $TotalCredit1 = new Assignment;
                $TotalCredit1 = getTotalCredit($contactId, $assRaw->productid);
                $TotalCredit = $TotalCredit1['credits'];
                $historyCredits += $TotalCredit;

                //materia corso
                $materia = '';
                $getMateria = getMateria($assRaw->productid);
                $materia = empty($getMateria) ? '' : (array)($getMateria[0]);
                $courseName = $course['shortdescription'] ?? $course['title']. ($course['edition'] == 0 ? "" : " - " . $course['edition']);

                //prima prendevo la chiave che corrispondeva al nome dell array
                //if(count(array_keys($course['location'])) > 0)
                //  $location = array_keys($course['location'])[0];
                //ora estraggo la location dalla query getLocationByIdRaw
                $location = '';
                $courseForLocation = new Course;
                $courseForLocation = courseRawByIdAll($assRaw->productid);
                $locationComplete = (new Location)->getLocationByIdRaw($courseForLocation[0]->s4_location); // to get the location
                if (count($locationComplete) > 0) {
                    if ($locationComplete[0]->s4address) {
                        $location = $locationComplete[0]->s4address;
                    } else {
                        $location = $locationComplete[0]->locationsala_tks_nomesala;
                    }
                }

                //metodologia
                $accreditations = new Accreditation;
                $accreditations = accreditations($assRaw->productid);
                foreach ($accreditations as $key2 => $value) {
                    $stdArray[$key2] = (array)$value;
                }
                $metodologia = ($stdArray["methodologyCourseName"]["metodologia"]);
                // conto tutti gli allegati(di qualsiasi tipo materiale didattico, file digitale, ecc) del corso specifico, del utente in questione
                $countAttachments = new Attachment;
                $countAttachments = attachmentsAndNotesForCourse($contactId, $assRaw->productid);
                $attachS = new Attachment;
                $attachS = attachmentsAndNotesForCourse($contactId, $assRaw->productid);
                $ass[$assRaw->productid] = [
                    'courseId' => $assRaw->productid,
                    'courseName' => $courseName,
                    'status' => $assRaw->assetstatus,
                    's4_year' => $assRaw->s4_year,
                    'credits' => DataHelper::numberFormat($TotalCredit),
                    'isEcm' => $TotalCredit1['isEcm'],
                    'countAttachments' => count($countAttachments),
                    'attachS' => $attachS->toArray(),
                    'metodologia' => $metodologia,
                    'courseEnd' => DataHelper::dateFormat($assRaw->s4_end_date),
                    'courseStar' => $course['start'],
                    'coursehoursAmount' => $course['hoursAmount'],
                    'location' => ($location == '') ? '' : $location,
                    'role' => $assRaw->s4_ruolo,
                    'historyCredits' => $historyCreditsForYear,
                    'orePresenza' => ($totalDaysHours['timeCount'] == '0:0' || $totalDaysHours['timeCount'] == '0:00') ? '' : $totalDaysHours['timeCount'],
                    'giorniPresenza' => ($totalDaysHours['daysCount'] == '0') ? '' : $totalDaysHours['daysCount'],
                    'materia' => ($materia == '') ? '' : $materia['s4_descrizione_materia'],
                    'subtitle' => $course['subtitle'],

                ];
                //se Ore presenza = 0 non faccio vedere l iscrizione
                if ($ass[$assRaw->productid]['orePresenza'] == '') {
                    unset($ass[$assRaw->productid]);
                }
            }
        }
        return $ass;
    }

}
if (!function_exists('assignmentsRawHistory')) {
    function assignmentsRawHistory($contactId, $year = null, $text = null)
    {
        if (isset($text) && isset($year)) {
            $mySubs = DB::table('vtiger_assets')
                ->join('vtiger_crmentity', 'vtiger_assets.assetsid', '=', 'vtiger_crmentity.crmid')
                ->join('vtiger_products', 'vtiger_products.productid', '=', 'vtiger_assets.product')
                ->join('vtiger_contactdetails', 'vtiger_contactdetails.contactid', '=', 'vtiger_assets.contact')
                ->select('vtiger_products.*', 'vtiger_assets.assetstatus', 'vtiger_assets.s4_ruolo')
                ->where([
                    ['vtiger_crmentity.deleted', '=', 0],
                    ['vtiger_products.discontinued', '=', '0'],
                    ['vtiger_assets.visualizza_in_cf', '=', '1'],
                    ['vtiger_assets.contact', '=', $contactId],
                    ['vtiger_products.s4_year', '=', $year],
                    ['vtiger_products.fulldescription', 'LIKE', '%' . $text . '%'],
                    ['vtiger_products.s4_end_date', '<', Carbon::now()->toDateString()],
                ])
                ->orderBy('vtiger_products.s4_year', 'desc')
                ->get();
        } elseif (isset($text) && !isset($year)) {
            $mySubs = DB::table('vtiger_assets')
                ->join('vtiger_crmentity', 'vtiger_assets.assetsid', '=', 'vtiger_crmentity.crmid')
                ->join('vtiger_products', 'vtiger_products.productid', '=', 'vtiger_assets.product')
                ->join('vtiger_contactdetails', 'vtiger_contactdetails.contactid', '=', 'vtiger_assets.contact')
                ->select('vtiger_products.*', 'vtiger_assets.assetstatus', 'vtiger_assets.s4_ruolo')
                ->where([
                    ['vtiger_crmentity.deleted', '=', 0],
                    ['vtiger_products.discontinued', '=', '0'],
                    ['vtiger_assets.visualizza_in_cf', '=', '1'],
                    ['vtiger_assets.contact', '=', $contactId],
                    ['vtiger_products.fulldescription', 'LIKE', '%' . $text . '%'],
                    ['vtiger_products.s4_end_date', '<', Carbon::now()->toDateString()],
                ])
                ->orderBy('vtiger_products.s4_year', 'desc')
                ->get();
        } elseif (!isset($text) && isset($year)) {
            $mySubs = DB::table('vtiger_assets')
                ->join('vtiger_crmentity', 'vtiger_assets.assetsid', '=', 'vtiger_crmentity.crmid')
                ->join('vtiger_products', 'vtiger_products.productid', '=', 'vtiger_assets.product')
                ->join('vtiger_contactdetails', 'vtiger_contactdetails.contactid', '=', 'vtiger_assets.contact')
                ->select('vtiger_products.*', 'vtiger_assets.assetstatus', 'vtiger_assets.s4_ruolo')
                ->where([
                    ['vtiger_crmentity.deleted', '=', 0],
                    ['vtiger_products.discontinued', '=', '0'],
                    ['vtiger_assets.visualizza_in_cf', '=', '1'],
                    ['vtiger_assets.contact', '=', $contactId],
                    ['vtiger_products.s4_year', '=', $year],
                    ['vtiger_products.s4_end_date', '<', Carbon::now()->toDateString()],
                ])
                ->orderBy('vtiger_products.s4_year', 'desc')
                ->get();
        } elseif (!isset($text) && !isset($year)) {
            $mySubs = DB::table('vtiger_assets')
                ->join('vtiger_crmentity', 'vtiger_assets.assetsid', '=', 'vtiger_crmentity.crmid')
                ->join('vtiger_products', 'vtiger_products.productid', '=', 'vtiger_assets.product')
                ->join('vtiger_contactdetails', 'vtiger_contactdetails.contactid', '=', 'vtiger_assets.contact')
                ->select('vtiger_products.*', 'vtiger_assets.assetstatus', 'vtiger_assets.s4_ruolo')
                ->where([
                    ['vtiger_crmentity.deleted', '=', 0],
                    ['vtiger_products.discontinued', '=', '0'],
                    ['vtiger_assets.visualizza_in_cf', '=', '1'],
                    ['vtiger_assets.contact', '=', $contactId],
                    ['vtiger_products.s4_end_date', '<', Carbon::now()->toDateString()],
                ])
                ->orderBy('vtiger_products.s4_start_date', 'desc')
                ->get();
        }

        return $mySubs;
    }

}
if (!function_exists('assignmentsRawHistoryCF')) {
    function assignmentsRawHistoryCF($contactId, $courseId)
    {

            $mySubs = DB::table('vtiger_assets')
                ->join('vtiger_crmentity', 'vtiger_assets.assetsid', '=', 'vtiger_crmentity.crmid')
                ->join('vtiger_products', 'vtiger_products.productid', '=', 'vtiger_assets.product')
                ->join('vtiger_contactdetails', 'vtiger_contactdetails.contactid', '=', 'vtiger_assets.contact')
                ->select('vtiger_products.*', 'vtiger_assets.*')
                ->where([
                    ['vtiger_crmentity.deleted', '=', 0],
                    ['vtiger_products.productid', '=', $courseId],
                    ['vtiger_assets.contact', '=', $contactId],
                ])
                ->orderBy('vtiger_products.s4_year', 'desc')
                ->first();


        return $mySubs;
    }

}
if (!function_exists('getYears')) {
    function getYears($contactId, $courseId)
    {
        $mySubs = DB::table('vtiger_programma')
            ->join('vtiger_crmentity', 'vtiger_programma.programmaid', '=', 'vtiger_crmentity.crmid')
            ->join('vtiger_crmentityrel', function ($join) {
                $join->on('vtiger_crmentityrel.relcrmid', '=', 'vtiger_crmentity.crmid');
                $join->orOn('vtiger_crmentityrel.crmid', '=', 'vtiger_crmentity.crmid');
            })
            ->leftjoin('vtiger_programmacf', 'vtiger_programmacf.programmaid', '=', 'vtiger_programma.programmaid')
            ->leftjoin('vtiger_users', 'vtiger_users.id', '=', 'vtiger_crmentity.smownerid')
            ->leftjoin('vtiger_groups', 'vtiger_groups.groupid', '=', 'vtiger_crmentity.smownerid')
            ->select('vtiger_programma.programma_data')
            ->where([
                ['vtiger_crmentity.deleted', '=', 0],
                ['vtiger_crmentityrel.crmid', '=', $courseId],
                ['vtiger_programma.programma_relatore', '=', $contactId],
            ])
            ->distinct('vtiger_programma.programma_data')
            ->get();
        return $mySubs;
    }

}
if (!function_exists('getTotalDaysHours')) {

    function getTotalDaysHours($contactId, $courseId)
    {

        $contactDays = 0;
        $HoursMinutes = 0;

        //Ore e minuti da Interventi Incarichi
        $incarichi = DB::table('vtiger_programma')
            ->join('vtiger_crmentity', 'vtiger_programma.programmaid', '=', 'vtiger_crmentity.crmid')
            ->join('vtiger_crmentityrel', function ($join) {
                $join->on('vtiger_crmentityrel.relcrmid', '=', 'vtiger_crmentity.crmid');
                $join->orOn('vtiger_crmentityrel.crmid', '=', 'vtiger_crmentity.crmid');
            })
            ->leftjoin('vtiger_programmacf', 'vtiger_programmacf.programmaid', '=', 'vtiger_programma.programmaid')
            ->leftjoin('vtiger_users', 'vtiger_users.id', '=', 'vtiger_crmentity.smownerid')
            ->leftjoin('vtiger_groups', 'vtiger_groups.groupid', '=', 'vtiger_crmentity.smownerid')
            ->select('vtiger_programma.*')
            ->where([
                ['vtiger_crmentity.deleted', '=', 0],
                ['vtiger_crmentityrel.crmid', '=', $courseId],
                ['vtiger_programma.programma_relatore', '=', $contactId],
                ['vtiger_programma.s4_ore', '<>', '0']
            ])
            ->get()
            ->toArray();

        $numRows = count($incarichi);
        if ($numRows) {
            foreach ($incarichi as $incarico) {
                $aIncarico = (array)$incarico;
                $hoursminutes = $aIncarico['s4_ore'];
                $HoursMinutes += $hoursminutes;
            }
        }

        //Giorni da Interventi Incarichi
        $GiorniIncarichi = DB::table('vtiger_programma')
            ->join('vtiger_crmentity', 'vtiger_programma.programmaid', '=', 'vtiger_crmentity.crmid')
            ->join('vtiger_crmentityrel', function ($join) {
                $join->on('vtiger_crmentityrel.relcrmid', '=', 'vtiger_crmentity.crmid');
                $join->orOn('vtiger_crmentityrel.crmid', '=', 'vtiger_crmentity.crmid');
            })
            ->leftjoin('vtiger_programmacf', 'vtiger_programmacf.programmaid', '=', 'vtiger_programma.programmaid')
            ->leftjoin('vtiger_users', 'vtiger_users.id', '=', 'vtiger_crmentity.smownerid')
            ->leftjoin('vtiger_groups', 'vtiger_groups.groupid', '=', 'vtiger_crmentity.smownerid')
            ->select(DB::raw('COUNT(DISTINCT vtiger_programma.programma_data) AS days'))
            ->where([
                ['vtiger_crmentity.deleted', '=', 0],
                ['vtiger_crmentityrel.crmid', '=', $courseId],
                ['vtiger_programma.programma_relatore', '=', $contactId]
            ])
            ->get()
            ->toArray();
        $days = (array)$GiorniIncarichi[0];
        $contactDays = $contactDays + $days['days'];

        //Controll if the Days and the hours calculated until now are 0, THEN EXTRACT THE DATE FROM MODULES PRESENZE!!!
        if ($HoursMinutes == 0) {

            $contactDaysPresenze = 0;
            $contactHours = 0;
            $contactMinutes = 0;

            //numero giorni da Presenze
            $DaysPresenze = DB::table('vtiger_presenze')
                ->join('vtiger_crmentity', 'vtiger_presenze.presenzeid', '=', 'vtiger_crmentity.crmid')
                ->leftjoin('vtiger_users', 'vtiger_crmentity.smownerid', '=', 'vtiger_users.id')
                ->leftjoin('vtiger_groups', 'vtiger_crmentity.smownerid', '=', 'vtiger_groups.groupid')
                ->select(DB::raw('COUNT(DISTINCT vtiger_presenze.arrival_date) AS days'))
                ->where([
                    ['vtiger_crmentity.deleted', '=', '0'],
                    ['vtiger_presenze.presenzeid', '>', '0'],
                    ['vtiger_presenze.event', '=', $courseId],
                    ['vtiger_presenze.contact', '=', $contactId],
                ])
                ->whereNotIn('vtiger_presenze.s4ruolopresenza', ['Partecipante', 'Studente', '| Partecipanti non ECM', '| Osservatore (n\/exp)'])
                ->get()
                ->toArray();
            $days = (array)$DaysPresenze[0];
            $contactDaysPresenze = $contactDaysPresenze + $days['days'];

            //ore e minuti da Presenze
            $query = DB::table('vtiger_presenze')
                ->join('vtiger_crmentity', 'vtiger_presenze.presenzeid', '=', 'vtiger_crmentity.crmid')
                ->leftjoin('vtiger_users', 'vtiger_crmentity.smownerid', '=', 'vtiger_users.id')
                ->leftjoin('vtiger_groups', 'vtiger_crmentity.smownerid', '=', 'vtiger_groups.groupid')
                ->select('vtiger_presenze.*')
                ->where([
                    ['vtiger_crmentity.deleted', '=', '0'],
                    ['vtiger_presenze.presenzeid', '>', '0'],
                    ['vtiger_presenze.event', '=', $courseId],
                    ['vtiger_presenze.contact', '=', $contactId],
                    ['vtiger_presenze.leaving_date', '<>', '',]
                ])
                ->whereNotIn('vtiger_presenze.s4ruolopresenza', ['Partecipante', 'Studente', '| Partecipanti non ECM', '| Osservatore (n\/exp)'])
                ->get()
                ->toArray();

            $numRows = count($query);
            if ($numRows) {
                foreach ($query as $Row) {
                    $aRow = (array)$Row;
                    $start = strtotime($aRow['arrival_date'] . ' ' . $aRow['arrival_time']);
                    $stop = strtotime($aRow['leaving_date'] . ' ' . $aRow['leaving_time']);

                    $diff = $stop - $start;
                    $contactHours += $diff / (60 * 60);

                    $contactMinutes += ($diff / 60);

                }
            }
            $hoursOnly = intval($contactHours);
            $minutesOnly = intval($contactMinutes - ($hoursOnly * 60));

//            $minH = round($minutesOnly);
//            $hoursOnly =  Carbon::createFromFormat('H', $contactHours)->format('H');
//            $minutesOnly = Carbon::createFromFormat('H', $contactMinutes)->format('i') - ($contactHours * 60);
//            $retPresenzeIncarichi = array(
//                'timeCount' => Carbon::createFromFormat('H', $hoursOnly)->format('H') . ':' . Carbon::createFromFormat('H', $minH)->format('H'),
//                'daysCount' => $contactDaysPresenze
//            );

            $retPresenzeIncarichi = array(
                'timeCount' => sprintf("%02d", $hoursOnly) . ':' . sprintf("%02d", $minutesOnly) ,
                'daysCount' => $contactDaysPresenze
            );
            return $retPresenzeIncarichi;
        } else {
            $retInterventiRelatoriIncarichi = array(
                'timeCount' => $HoursMinutes . '',
                'daysCount' => $contactDays,
            );
            return $retInterventiRelatoriIncarichi;
        }
    }

}
if (!function_exists('getTotalCreditForYear')) {
// Seleziono i Crediti totali Ecm per gli Interventi Relatore X ANNO!
    function getTotalCreditForYear($contactId, $courseId, $year)
    {
        $ecm = DB::table('vtiger_programma')
            ->join('vtiger_crmentity', 'vtiger_programma.programmaid', '=', 'vtiger_crmentity.crmid')
            ->join('vtiger_crmentityrel', function ($join) {
                $join->on('vtiger_crmentityrel.relcrmid', '=', 'vtiger_crmentity.crmid');
                $join->orOn('vtiger_crmentityrel.crmid', '=', 'vtiger_crmentity.crmid');
            })
            ->leftjoin('vtiger_programmacf', 'vtiger_programmacf.programmaid', '=', 'vtiger_programma.programmaid')
            ->leftjoin('vtiger_users', 'vtiger_users.id', '=', 'vtiger_crmentity.smownerid')
            ->leftjoin('vtiger_groups', 'vtiger_groups.groupid', '=', 'vtiger_crmentity.smownerid')
            ->select(DB::raw('sum(vtiger_programma.s4_crediti )as s4_crediti_Totali'))
            ->where([
                ['vtiger_crmentity.deleted', '=', 0],
                ['vtiger_crmentityrel.crmid', '=', $courseId],
                ['vtiger_programma.programma_relatore', '=', $contactId]
            ])
            ->whereYear('vtiger_programma.programma_data', '=', $year)
            ->first();

        $necm = DB::table('vtiger_programma')
            ->join('vtiger_crmentity', 'vtiger_programma.programmaid', '=', 'vtiger_crmentity.crmid')
            ->join('vtiger_crmentityrel', function ($join) {
                $join->on('vtiger_crmentityrel.relcrmid', '=', 'vtiger_crmentity.crmid');
                $join->orOn('vtiger_crmentityrel.crmid', '=', 'vtiger_crmentity.crmid');
            })
            ->leftjoin('vtiger_programmacf', 'vtiger_programmacf.programmaid', '=', 'vtiger_programma.programmaid')
            ->leftjoin('vtiger_users', 'vtiger_users.id', '=', 'vtiger_crmentity.smownerid')
            ->leftjoin('vtiger_groups', 'vtiger_groups.groupid', '=', 'vtiger_crmentity.smownerid')
            ->select(DB::raw('sum(vtiger_programma.crediti_non_ecm )as s4_crediti_Totali'))
            ->where([
                ['vtiger_crmentity.deleted', '=', 0],
                ['vtiger_crmentityrel.crmid', '=', $courseId],
                ['vtiger_programma.programma_relatore', '=', $contactId]
            ])
            ->whereYear('vtiger_programma.programma_data', '=', $year)
            ->first();

        if (is_null($ecm->s4_crediti_Totali)) {
            $creditiecm = "0.00";
        } else {
            $creditiecm = $ecm->s4_crediti_Totali;
        }

        if (is_null($necm->s4_crediti_Totali)) {
            $creditinecm = "0.00";
        } else {
            $creditinecm = $necm->s4_crediti_Totali;
        }


        $total['credits'] = $creditiecm;
        $total['isEcm'] = 1;

        if ($creditiecm == "0.00") {
            if ($creditinecm != "0.00") {
                $total['credits'] = $creditinecm;
                $total['isEcm'] = 0;
            }
        }


        return $total;
    }

}
if (!function_exists('getTotalCredit')) {
// Seleziono i Crediti totali Ecm per gli Interventi Relatore(sommo il totale dei crediti del RELATORE)
    function getTotalCredit($contactId, $courseId)
    {
        $ecm = DB::table('vtiger_programma')
            ->join('vtiger_crmentity', 'vtiger_programma.programmaid', '=', 'vtiger_crmentity.crmid')
            ->join('vtiger_crmentityrel', function ($join) {
                $join->on('vtiger_crmentityrel.relcrmid', '=', 'vtiger_crmentity.crmid');
                $join->orOn('vtiger_crmentityrel.crmid', '=', 'vtiger_crmentity.crmid');
            })
            ->leftjoin('vtiger_programmacf', 'vtiger_programmacf.programmaid', '=', 'vtiger_programma.programmaid')
            ->leftjoin('vtiger_users', 'vtiger_users.id', '=', 'vtiger_crmentity.smownerid')
            ->leftjoin('vtiger_groups', 'vtiger_groups.groupid', '=', 'vtiger_crmentity.smownerid')
            ->select(DB::raw('sum(vtiger_programma.s4_crediti )as s4_crediti_Totali'))
            ->where([
                ['vtiger_crmentity.deleted', '=', 0],
                ['vtiger_crmentityrel.crmid', '=', $courseId],
                ['vtiger_programma.programma_relatore', '=', $contactId]
            ])
            ->first();

        $necm = DB::table('vtiger_programma')
            ->join('vtiger_crmentity', 'vtiger_programma.programmaid', '=', 'vtiger_crmentity.crmid')
            ->join('vtiger_crmentityrel', function ($join) {
                $join->on('vtiger_crmentityrel.relcrmid', '=', 'vtiger_crmentity.crmid');
                $join->orOn('vtiger_crmentityrel.crmid', '=', 'vtiger_crmentity.crmid');
            })
            ->leftjoin('vtiger_programmacf', 'vtiger_programmacf.programmaid', '=', 'vtiger_programma.programmaid')
            ->leftjoin('vtiger_users', 'vtiger_users.id', '=', 'vtiger_crmentity.smownerid')
            ->leftjoin('vtiger_groups', 'vtiger_groups.groupid', '=', 'vtiger_crmentity.smownerid')
            ->select(DB::raw('sum(vtiger_programma.crediti_non_ecm )as s4_crediti_Totali'))
            ->where([
                ['vtiger_crmentity.deleted', '=', 0],
                ['vtiger_crmentityrel.crmid', '=', $courseId],
                ['vtiger_programma.programma_relatore', '=', $contactId]
            ])
            ->first();

        if (is_null($ecm->s4_crediti_Totali)) {
            $creditiecm = "0.00";
        } else {
            $creditiecm = $ecm->s4_crediti_Totali;
        }

        if (is_null($necm->s4_crediti_Totali)) {
            $creditinecm = "0.00";
        } else {
            $creditinecm = $necm->s4_crediti_Totali;
        }


        $total['credits'] = $creditiecm;
        $total['isEcm'] = 1;

        if ($creditiecm == "0.00") {
            if ($creditinecm != "0.00") {
                $total['credits'] = $creditinecm;
                $total['isEcm'] = 0;
            }
        }


        return $total;
    }

}
if (!function_exists('getProfessions')) {
    function getProfessions()
    {

        $profesisons = DB::table('vtiger_s4multilevel')
            ->join('vtiger_crmentity', 'vtiger_s4multilevel.s4multilevelid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_s4multilevel.s4multilevelid', 'vtiger_s4multilevel.s4_name', 'vtiger_s4multilevel.s4_parent', 'vtiger_s4multilevel.s4_label')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_s4multilevel.s4_label', '=', 'Professione']
            ])
            ->get();
        return $profesisons->toArray();
    }
}
if (!function_exists('getDisciplines')) {
    function getDisciplines()
    {
        $disciplines = DB::table('vtiger_s4multilevel')
            ->join('vtiger_crmentity', 'vtiger_s4multilevel.s4multilevelid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_s4multilevel.s4multilevelid', 'vtiger_s4multilevel.s4_name', 'vtiger_s4multilevel.s4_parent', 'vtiger_s4multilevel.s4_label')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_s4multilevel.s4_label', '=', 'Disciplina']
            ])
            ->get();
        return $disciplines->toArray();
    }
}

if (!function_exists('getDisciplinesForProfession')) {
    function getDisciplinesForProfession($professionId)
    {
        $disciplines = DB::table('vtiger_s4multilevel')
            ->join('vtiger_crmentity', 'vtiger_s4multilevel.s4multilevelid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_s4multilevel.s4multilevelid', 'vtiger_s4multilevel.s4_name', 'vtiger_s4multilevel.s4_parent', 'vtiger_s4multilevel.s4_label')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_s4multilevel.s4_label', '=', 'Disciplina'],
                ['vtiger_s4multilevel.s4_parent', '=', $professionId]
            ])
            ->get()
            ->toArray();
        return $disciplines;
    }
}
if (!function_exists('professionsDisciplinesData')) {
    function professionsDisciplinesData()

    {
        $professions = getProfessions();
        $disciplines = getDisciplines();
        $data = [];
        $professionsArray = [];
        $disciplinsArray = [];

        // Get Unique Professions
        foreach ($professions as $profession) {
            $professionsArray[$profession->s4multilevelid] = [
                's4multilevelid' => $profession->s4multilevelid,
                's4_name' => $profession->s4_name,
                's4_parent' => $profession->s4_parent,
                's4_label' => $profession->s4_label
            ];
            // Get All Disciplins by Profession ID
            foreach ($disciplines as $discipline) {
                if ($profession->s4multilevelid == $discipline->s4_parent) {
                    $disciplinsArray[$profession->s4multilevelid][] = [
                        's4multilevelid' => $discipline->s4multilevelid,
                        's4_name' => $discipline->s4_name,
                        's4_parent' => $discipline->s4_parent,
                        's4_label' => $discipline->s4_label
                    ];
                }
            }
        }
        foreach ($professionsArray as $s4multilevelid => $profession) {
            if (isset($disciplinsArray[$s4multilevelid])) {
                $data[] = [
                    'profession' => $profession,
                    'disciplins' => $disciplinsArray[$s4multilevelid]
                ];
            }

        }

        return $data;
    }
}
if (!function_exists('getS4multilevelRecord')) {
    function getS4multilevelRecord($s4multilevelid)

    {
        $s4multilevel = DB::table('vtiger_s4multilevel')
            ->join('vtiger_crmentity', 'vtiger_s4multilevel.s4multilevelid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_s4multilevel.*')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_s4multilevel.s4multilevelid', '=', $s4multilevelid]
            ])
            ->first();

        return $s4multilevel;
    }
}
if (!function_exists('getS4multilevel')) {
    function getS4multilevel($s4multilevelid)
    {


        $s4multilevel = getS4multilevelRecord($s4multilevelid);
        $breedInfo = [];
        $level = 1;
        if ($s4multilevel) {
            $recordParent = $s4multilevel->s4_parent;
            $breedInfo[$level] = array(
                's4multilevelid' => $s4multilevel->s4multilevelid,
                's4_parent' => $s4multilevel->s4_parent,
                's4_name' => $s4multilevel->s4_name,
                's4_code' => $s4multilevel->s4_code
            );


            if ($recordParent) {

                while ($level <= 4) {
                    $s4multilevelParent = getS4multilevelRecord($recordParent);
                    if ($s4multilevelParent) {
                        $recordParent = $s4multilevelParent->s4_parent;
                        $level++;
                        $breedInfo[$level] = array(
                            's4multilevelid' => $s4multilevelParent->s4multilevelid,
                            's4_parent' => $s4multilevelParent->s4_parent,
                            's4_name' => $s4multilevelParent->s4_name,
                            's4_code' => $s4multilevelParent->s4_code
                        );
                    } else break;
                }

            }

            $reverse = array_reverse($breedInfo);
            $i = 1;
            $result = array();
            foreach ($reverse as $anInfo) {
                $result[$i] = $anInfo;
                $i++;
            }

            return $result;

        }

    }
}
if (!function_exists('getS4multilevelOne')) {
    function getS4multilevelOne($s4multilevelid)
    {

        $s4multilevel = getS4multilevelRecord($s4multilevelid);
        $breedInfo = [];
        $level = 1;
        if ($s4multilevel) {
            $recordParent = $s4multilevel->s4_parent;
            $breedInfo[$level] = array(
                's4multilevelid' => $s4multilevel->s4multilevelid,
                's4_parent' => $s4multilevel->s4_parent,
                's4_name' => $s4multilevel->s4_name,
                's4_code' => $s4multilevel->s4_code
            );

            $reverse = array_reverse($breedInfo);
            $i = 1;
            $result = array();
            foreach ($reverse as $anInfo) {
                $result[$i] = $anInfo;
                $i++;
            }

            return $result;

        }

    }
}
if (!function_exists('subsCatalogo')) {
    function subsCatalogo($contactId = null, $survey = null)
    {
        $subscriptions = DB::table('vtiger_cataloghirelated')
            ->join('vtiger_crmentity', 'vtiger_cataloghirelated.cataloghirelatedid', '=', 'vtiger_crmentity.crmid')
            ->join('vtiger_crmentity as crmcat', 'vtiger_cataloghirelated.s4_catrel_catalog', '=', 'crmcat.crmid')
            ->join('vtiger_catalogo', 'vtiger_cataloghirelated.s4_catrel_catalog', '=', 'vtiger_catalogo.catalogoid')
            ->join('vtiger_metodologieeaccreditamenti', 'vtiger_catalogo.s4_cat_metodologie', '=', 'vtiger_metodologieeaccreditamenti.metodologieeaccreditamentiid')
            ->select('vtiger_cataloghirelated.*', 'vtiger_catalogo.*', 'vtiger_cataloghirelated.s4_cat_role', 'vtiger_crmentity.*', 'vtiger_metodologieeaccreditamenti.*')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['crmcat.deleted', '=', '0'],
                //['vtiger_cataloghirelated.s4_fd_survey', '=', $survey], // Questionario Gradimento
                ['vtiger_cataloghirelated.visualizza_in_cf', '=', '0'],
                ['vtiger_cataloghirelated.s4_catrel_contact', '=', $contactId]
            ])
            //->whereRaw('DATEDIFF(CURRENT_DATE(),vtiger_crmentity.createdtime) <= vtiger_cataloghirelated.cat_s4ggendsubs')
            ->orderBy('vtiger_catalogo.s4_cat_date_begin', 'desc')
            ->get();

        return $subscriptions;
    }
}

if (!function_exists('subsCatalogoHistorySearch')) {
    function subsCatalogoHistorySearch($contactId, $survey, $year = null, $text = null)
    {
        if (isset($text)) {
            $subscriptions = DB::table('vtiger_cataloghirelated')
                ->join('vtiger_crmentity', 'vtiger_cataloghirelated.cataloghirelatedid', '=', 'vtiger_crmentity.crmid')
                ->join('vtiger_crmentity as crmcat', 'vtiger_cataloghirelated.s4_catrel_catalog', '=', 'crmcat.crmid')
                ->join('vtiger_catalogo', 'vtiger_cataloghirelated.s4_catrel_catalog', '=', 'vtiger_catalogo.catalogoid')
                ->select('vtiger_cataloghirelated.*', 'vtiger_catalogo.*', 'vtiger_cataloghirelated.s4_cat_role')
                ->where([
                    ['vtiger_crmentity.deleted', '=', '0'],
                    ['crmcat.deleted', '=', '0'],
                    //['vtiger_cataloghirelated.s4_fd_survey', '=', $survey],
                    ['vtiger_cataloghirelated.s4_catrel_contact', '=', $contactId],
                    ['vtiger_catalogo.s4_cat_course_name', 'LIKE', '%' . $text . '%'],
                    ['vtiger_cataloghirelated.visualizza_in_cf', '=', '1'],         //  Visualizza in Curriculum Formativo
                ])
                ->orderBy('vtiger_catalogo.s4_cat_date_begin', 'desc')
                ->get();
        }
        if (isset($year)) {
            $subscriptions = DB::table('vtiger_cataloghirelated')
                ->join('vtiger_crmentity', 'vtiger_cataloghirelated.cataloghirelatedid', '=', 'vtiger_crmentity.crmid')
                ->join('vtiger_crmentity as crmcat', 'vtiger_cataloghirelated.s4_catrel_catalog', '=', 'crmcat.crmid')
                ->join('vtiger_catalogo', 'vtiger_cataloghirelated.s4_catrel_catalog', '=', 'vtiger_catalogo.catalogoid')
                ->select('vtiger_cataloghirelated.*', 'vtiger_catalogo.*', 'vtiger_cataloghirelated.s4_cat_role')
                ->where([
                    ['vtiger_crmentity.deleted', '=', '0'],
                    ['crmcat.deleted', '=', '0'],
                    //['vtiger_cataloghirelated.s4_fd_survey', '=', $survey],
                    ['vtiger_cataloghirelated.s4_catrel_contact', '=', $contactId],
                    ['vtiger_catalogo.s4_cat_year', '=', $year],
                    ['vtiger_cataloghirelated.visualizza_in_cf', '=', '1'],         //  Visualizza in Curriculum Formativo
                ])
                ->orderBy('vtiger_catalogo.s4_cat_date_begin', 'desc')
                ->get();
        }

        return $subscriptions;
    }
}
if (!function_exists('subsCatalogoHistory')) {

    function subsCatalogoHistory($contactId, $survey, $year = null, $text = null)
    {
        if (isset($text) && isset($year)) {
            $subscriptions = DB::table('vtiger_cataloghirelated')
                ->join('vtiger_crmentity', 'vtiger_cataloghirelated.cataloghirelatedid', '=', 'vtiger_crmentity.crmid')
                ->join('vtiger_crmentity as crmcat', 'vtiger_cataloghirelated.s4_catrel_catalog', '=', 'crmcat.crmid')
                ->join('vtiger_catalogo', 'vtiger_cataloghirelated.s4_catrel_catalog', '=', 'vtiger_catalogo.catalogoid')
                ->join('vtiger_metodologieeaccreditamenti', 'vtiger_catalogo.s4_cat_metodologie', '=', 'vtiger_metodologieeaccreditamenti.metodologieeaccreditamentiid')
                ->select('vtiger_cataloghirelated.s4_catrel_contact',
                    'vtiger_cataloghirelated.cataloghirelatedid',
                    'vtiger_cataloghirelated.s4_catrel_credits',
                    'vtiger_cataloghirelated.s4_catrel_date_end',
                    'vtiger_catalogo.s4_cat_type',
                    'vtiger_catalogo.catalogoid',
                    'vtiger_catalogo.s4_cat_provider',
                    'vtiger_catalogo.s4_cat_course_name',
                    'vtiger_catalogo.s4_cat_place',
                    'vtiger_catalogo.s4_cat_year',
                    'vtiger_metodologieeaccreditamenti.*',
                    'vtiger_cataloghirelated.s4_cat_role')
                ->where([
                    ['vtiger_crmentity.deleted', '=', '0'],
                    ['crmcat.deleted', '=', '0'],
                    ['vtiger_cataloghirelated.s4_catrel_contact', '=', $contactId],
                    ['vtiger_catalogo.s4_cat_course_name', 'LIKE', '%' . $text . '%'],
                    ['vtiger_catalogo.s4_cat_year', '=', $year],
                    ['vtiger_cataloghirelated.visualizza_in_cf', '=', '1'],         //  Visualizza in Curriculum Formativo
                ])
                ->orderBy('vtiger_catalogo.s4_cat_date_begin', 'desc')
                ->get();
        } elseif (isset($text) && !isset($year)) {
            $subscriptions = DB::table('vtiger_cataloghirelated')
                ->join('vtiger_crmentity', 'vtiger_cataloghirelated.cataloghirelatedid', '=', 'vtiger_crmentity.crmid')
                ->join('vtiger_crmentity as crmcat', 'vtiger_cataloghirelated.s4_catrel_catalog', '=', 'crmcat.crmid')
                ->join('vtiger_catalogo', 'vtiger_cataloghirelated.s4_catrel_catalog', '=', 'vtiger_catalogo.catalogoid')
                ->join('vtiger_metodologieeaccreditamenti', 'vtiger_catalogo.s4_cat_metodologie', '=', 'vtiger_metodologieeaccreditamenti.metodologieeaccreditamentiid')
                ->select('vtiger_cataloghirelated.s4_catrel_contact',
                    'vtiger_cataloghirelated.cataloghirelatedid',
                    'vtiger_cataloghirelated.s4_catrel_credits',
                    'vtiger_cataloghirelated.s4_catrel_date_end',
                    'vtiger_catalogo.s4_cat_type',
                    'vtiger_catalogo.catalogoid',
                    'vtiger_catalogo.s4_cat_provider',
                    'vtiger_catalogo.s4_cat_course_name',
                    'vtiger_catalogo.s4_cat_place',
                    'vtiger_catalogo.s4_cat_year',
                    'vtiger_metodologieeaccreditamenti.*',
                    'vtiger_cataloghirelated.s4_cat_role')
                ->where([
                    ['vtiger_crmentity.deleted', '=', '0'],
                    ['crmcat.deleted', '=', '0'],
                    ['vtiger_cataloghirelated.s4_catrel_contact', '=', $contactId],
                    ['vtiger_catalogo.s4_cat_course_name', 'LIKE', '%' . $text . '%'],
                    ['vtiger_cataloghirelated.visualizza_in_cf', '=', '1'],         //  Visualizza in Curriculum Formativo
                ])
                ->orderBy('vtiger_catalogo.s4_cat_date_begin', 'desc')
                ->get();
        } elseif (!isset($text) && isset($year)) {
            $subscriptions = DB::table('vtiger_cataloghirelated')
                ->join('vtiger_crmentity', 'vtiger_cataloghirelated.cataloghirelatedid', '=', 'vtiger_crmentity.crmid')
                ->join('vtiger_crmentity as crmcat', 'vtiger_cataloghirelated.s4_catrel_catalog', '=', 'crmcat.crmid')
                ->join('vtiger_catalogo', 'vtiger_cataloghirelated.s4_catrel_catalog', '=', 'vtiger_catalogo.catalogoid')
                ->join('vtiger_metodologieeaccreditamenti', 'vtiger_catalogo.s4_cat_metodologie', '=', 'vtiger_metodologieeaccreditamenti.metodologieeaccreditamentiid')
                ->select('vtiger_cataloghirelated.s4_catrel_contact',
                    'vtiger_cataloghirelated.cataloghirelatedid',
                    'vtiger_cataloghirelated.s4_catrel_credits',
                    'vtiger_cataloghirelated.s4_catrel_date_end',
                    'vtiger_catalogo.s4_cat_type',
                    'vtiger_catalogo.catalogoid',
                    'vtiger_catalogo.s4_cat_provider',
                    'vtiger_catalogo.s4_cat_course_name',
                    'vtiger_catalogo.s4_cat_place',
                    'vtiger_catalogo.s4_cat_year',
                    'vtiger_metodologieeaccreditamenti.*',
                    'vtiger_cataloghirelated.s4_cat_role')
                ->where([
                    ['vtiger_crmentity.deleted', '=', '0'],
                    ['crmcat.deleted', '=', '0'],
                    ['vtiger_cataloghirelated.s4_catrel_contact', '=', $contactId],
                    ['vtiger_catalogo.s4_cat_year', '=', $year],
                    ['vtiger_cataloghirelated.visualizza_in_cf', '=', '1'],         //  Visualizza in Curriculum Formativo
                ])
                ->orderBy('vtiger_catalogo.s4_cat_date_begin', 'desc')
                ->get();
        } elseif (!isset($text) && !isset($year)) {
            $subscriptions = DB::table('vtiger_cataloghirelated')
                ->join('vtiger_crmentity', 'vtiger_cataloghirelated.cataloghirelatedid', '=', 'vtiger_crmentity.crmid')
                ->join('vtiger_crmentity as crmcat', 'vtiger_cataloghirelated.s4_catrel_catalog', '=', 'crmcat.crmid')
                ->join('vtiger_catalogo', 'vtiger_cataloghirelated.s4_catrel_catalog', '=', 'vtiger_catalogo.catalogoid')
                ->join('vtiger_metodologieeaccreditamenti', 'vtiger_catalogo.s4_cat_metodologie', '=', 'vtiger_metodologieeaccreditamenti.metodologieeaccreditamentiid')
                ->select('vtiger_cataloghirelated.s4_catrel_contact',
                    'vtiger_cataloghirelated.cataloghirelatedid',
                    'vtiger_cataloghirelated.s4_catrel_credits',
                    'vtiger_cataloghirelated.s4_catrel_date_end',
                    'vtiger_catalogo.s4_cat_type',
                    'vtiger_catalogo.catalogoid',
                    'vtiger_catalogo.s4_cat_provider',
                    'vtiger_catalogo.s4_cat_course_name',
                    'vtiger_catalogo.s4_cat_place',
                    'vtiger_catalogo.s4_cat_year',
                    'vtiger_metodologieeaccreditamenti.*',
                    'vtiger_cataloghirelated.s4_cat_role')
                ->where([
                    ['vtiger_crmentity.deleted', '=', '0'],
                    ['crmcat.deleted', '=', '0'],
                    ['vtiger_cataloghirelated.s4_catrel_contact', '=', $contactId],
                    ['vtiger_cataloghirelated.visualizza_in_cf', '=', '1'],         //  Visualizza in Curriculum Formativo
                ])
                ->orderBy('vtiger_catalogo.s4_cat_date_begin', 'desc')
                ->get();
//            dd($subscriptions);
        }


        return $subscriptions;
    }

}

if (!function_exists('subsCatalogoHistoryForCourseExternal')) {
    //***********visualizza il corso esterno           */
    function subsCatalogoHistoryForCourseExternal($courseIdExternal)
    {
        $subscriptions = DB::table('vtiger_cataloghirelated')
            ->join('vtiger_crmentity', 'vtiger_cataloghirelated.cataloghirelatedid', '=', 'vtiger_crmentity.crmid')
            ->join('vtiger_catalogo', 'vtiger_cataloghirelated.s4_catrel_catalog', '=', 'vtiger_catalogo.catalogoid')
            ->select('vtiger_cataloghirelated.*', 'vtiger_catalogo.*')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_catalogo.catalogoid', '=', $courseIdExternal],  // s4_catrel_catalog=  idCorso del catalogo corsi esterno
                ['vtiger_cataloghirelated.visualizza_in_cf', '=', '1'],         //  Visualizza in Curriculum Formativo
            ])
            ->orderBy('vtiger_catalogo.s4_cat_date_begin', 'desc')
            ->first();

        return $subscriptions;
    }

}
if (!function_exists('insertMilestone')) {
    function insertMilestone($uniqueId, $contactId, $request)

    {
        DB::table('vtiger_projectmilestone')->insert(
            [
                'projectmilestoneid' => $uniqueId,
                'projectmilestonename' => $request->nome_milestone,
                'projectmilestonedate' => $request->data_inizio,
                'projectmilestonetype' => $request->state,
                'projectid' => $request->idPratica,
                's4_linkedmodules' => $contactId,
            ]
        );
    }
}
