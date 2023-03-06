<?php

use App\Attachment;
use App\Contact;
use App\Course;
use App\Helpers\DataHelper;
use App\Helpers\ProfessionHelper;
use App\Location;
use App\Moodle\Faddy;
use App\Profession;
use App\Subscription;
use App\Vtiger\CrmEntity;
use App\Vtiger\ModTracker\ModTracker;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

if (!function_exists('createSubId')) {
    function createSubId($attributes)
    {
        $today = Carbon::today()->toDateString();

        $moduleName = 'Potentials';
        $courseInformations = new Course;
        $courseInformations = courseById($attributes['courseId']);

        $label = '';
        $now = Carbon::now();
        $crmEntity = CRMEntity::create($moduleName, $label);

        if (isset($attributes['bonifico']) == true && isset($attributes['checkPay']) == true) 
        {
            $potentialtype = 'Confermato attesa pagamento';
        } elseif (($courseInformations['overbooking'] == 1 || $courseInformations['overbooking'] == 'On') && $courseInformations['seats'] <= 0) {
            $potentialtype = 'Overbooking';
        } else {
            $potentialtype = (empty($courseInformations['defaultescrizione'])) ? 'Confermato' : $courseInformations['defaultescrizione'];
        }

        $dbAttributes = [
            'potentialid' => $crmEntity['crmEntityId'],
            'potentialname' => isset($attributes['reclutato']) ? $attributes['reclutato'] . '- in data: ' . DataHelper::dateFormat($today) : ' ',
            'potential_no' => $crmEntity['sequenceNumber'],
            'amount' => (isset($attributes['checkPay']) && $attributes['checkPay'] == false) ? 0 : $courseInformations['priceRaw'],
            'contact_id' => $attributes['contactId'],
            'productid' => $attributes['courseId'],
            's4_professions' => $attributes['professions'],
            's4_role' => 'Partecipante',
            's4_data_iscrizione' => $now->toDateString(),
            'potentialtype' => $potentialtype,
            's4_from_portal' => 1,
            'faddy_idoneo' => 0,
            's4_elegible' => 0,
            's4_attestato_generato' => 0,
            's4_survey' => 0,
            's4_scadenza_iscrizioni' => $courseInformations['s4_scadenza_iscrizioni'],
            's4_requisiti_parte_pot' => $attributes['s4_requisiti_parte_pot']
        ];

        DB::table('vtiger_potential')->insert($dbAttributes);

        (new ModTracker)->trace($crmEntity['crmEntityId'], $moduleName, 'CREATE', $dbAttributes);

        $statusPreIscritto = new Subscription;
        $statusPreIscritto = checkStatusPreIscritto()->toArray();
        $statusPreIscritto1 = (array)$statusPreIscritto[0];
        switch ($potentialtype) {
            case 'Confermato':
                $updateCourse = new Course;
                $updateCourse = seatCourseDeduct($attributes['courseId']);
                break;
            case 'Confermato attesa pagamento':
                $updateCourse = new Course;
                $updateCourse = seatCourseDeduct($attributes['courseId']);

                break;
            case 'Pre Iscritto':
                if ($statusPreIscritto1['s4_scale_preregistered'] == 1)
                    $updateCourse = new Course;
                $updateCourse = seatCourseDeduct($attributes['courseId']);
                break;
        }

        return $crmEntity['crmEntityId'];
    }
}
if (!function_exists('createSubIdCf')) {
    function createSubIdCf($attributes)
    {
        $today = Carbon::today()->toDateString();

        $moduleName = 'Potentials';
        $courseInformations = new Course;
        $courseInformations = courseById($attributes['courseId']);

        $label = '';
        $now = Carbon::now();
        $crmEntity = CRMEntity::create($moduleName, $label);
        if (isset($attributes['bonifico'])) {
            $potentialtype = 'Confermato attesa pagamento';
        } elseif (($courseInformations['overbooking'] == 1 || $courseInformations['overbooking'] == 'On') && $courseInformations['seats'] <= 0) {
            $potentialtype = 'Overbooking';
        } else {
            $potentialtype = (empty($courseInformations['defaultescrizione'])) ? 'Confermato' : $courseInformations['defaultescrizione'];
        }

        //FLag: Idoneo alla visualizzazione nel Cf
        $visualizza_cf = 0;

        if (config('sailfor.switch.iscrivi_con_idoneo_cf')) {
            $visualizza_cf = 1;
        }

        $dbAttributes = [
            'potentialid' => $crmEntity['crmEntityId'],
            'potentialname' => isset($attributes['reclutato']) ? $attributes['reclutato'] . '- in data: ' . DataHelper::dateFormat($today) : ' ',
            'potential_no' => $crmEntity['sequenceNumber'],
            'amount' => (isset($attributes['isInternal']) && $attributes['isInternal'] && config('sailfor.switch.freeInternal')) ? 0 : $courseInformations['priceRaw'],
            'contact_id' => $attributes['contactId'],
            'productid' => $attributes['courseId'],
            's4_professions' => $attributes['professions'],
            's4_role' => 'Partecipante',
            's4_data_iscrizione' => $now->toDateString(),
            'potentialtype' => $potentialtype,
            's4_from_portal' => 1,
            'faddy_idoneo' => 0,
            's4_elegible' => 0,
            's4_attestato_generato' => 0,
            's4_survey' => 0,
            'visualizza_in_cf' => $visualizza_cf,
            's4_scadenza_iscrizioni' => $courseInformations['s4_scadenza_iscrizioni'],
            's4_requisiti_parte_pot' => $attributes['s4_requisiti_parte_pot']
        ];

        DB::table('vtiger_potential')->insert($dbAttributes);

        (new ModTracker)->trace($crmEntity['crmEntityId'], $moduleName, 'CREATE', $dbAttributes);

        $statusPreIscritto = new Subscription;
        $statusPreIscritto = checkStatusPreIscritto()->toArray();
        $statusPreIscritto1 = (array)$statusPreIscritto[0];
        switch ($potentialtype) {
            case 'Confermato':
                $updateCourse = new Course;
                $updateCourse = seatCourseDeduct($attributes['courseId']);
                break;
            case 'Confermato attesa pagamento':
                $updateCourse = new Course;
                $updateCourse = seatCourseDeduct($attributes['courseId']);

                break;
            case 'Pre Iscritto':
                if ($statusPreIscritto1['s4_scale_preregistered'] == 1)
                    $updateCourse = new Course;
                $updateCourse = seatCourseDeduct($attributes['courseId']);
                break;
        }

        return $crmEntity['crmEntityId'];
    }
}
if (!function_exists('checkSubExists')) {
    function checkSubExists($contactId, $courseId)
    {
        $courseInformations = new Course;
        $courseInformations = courseById($courseId);

        $subCheck = DB::table('vtiger_potential')
            ->join('vtiger_crmentity', 'vtiger_potential.potentialid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_potential.potentialid')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_potential.productid', '=', $courseId],
                ['vtiger_potential.contact_id', '=', $contactId],
            ])
            ->where(function ($query) {
                $query->where('vtiger_potential.potentialtype', '=', 'Confermato attesa pagamento')
                    ->orWhere('vtiger_potential.potentialtype', '=', 'Confermato')
                    ->orWhere('vtiger_potential.potentialtype', '=', 'Overbooking')
                    ->orWhere('vtiger_potential.potentialtype', '=', 'Pre Iscritto')
                    ->orWhere('vtiger_potential.potentialtype', '=', 'Invitato')
                    ->orWhere('vtiger_potential.potentialtype', '=', 'Sospeso')
                    ->orWhere('vtiger_potential.potentialtype', '=', 'Annullato')
                    ->orWhere('vtiger_potential.potentialtype', '=', 'Assente (ex confermato)')
                    ->orWhere('vtiger_potential.potentialtype', '=', 'Cambio Edizione')
                    ->orWhere('vtiger_potential.potentialtype', '=', 'Invitato');
            })
            ->get();
        $check = (count($subCheck) >= 1) ? false : true;

        return $check;
    }
}

/**
 * Check If Sub Exists
 *
 * @param String $fiscalCode
 * @return Bool
 */
if (!function_exists('CheckCourseReservedForEmployes')) {
    function CheckCourseReservedForEmployes($contactId, $courseId)
    {
        $courseInformations = new Course;
        $courseInformations = courseById($courseId);
        $contactInformations = new Contact;
        $contactInformations = contact($contactId);

        $courseRiserved = $courseInformations['reserved'];
        $external = $contactInformations['external'];
        $check = (($external == 'on' || $external == '1') && ($courseRiserved == 'on' || $courseRiserved == '1')) ? false : true;
        return $check;
    }
}
if (!function_exists('CheckProfessionsCompliance')) {
    function CheckProfessionsCompliance($contactId, $courseId)
    {
        $courseProfesisonsData = (new Profession)->getCourseProfessions($courseId);
        $contactProfesisonsData = (new Profession)->getContactProfessionsControl($contactId);
        $courseProfesisons = ProfessionHelper::viewControl($courseProfesisonsData, 'course');
        $contactProfessions = ProfessionHelper::viewControl($contactProfesisonsData, 'contact');

        if ((count(array($courseProfesisons))) == 0) {
            $courseProfesisons[0] = 'null';
        }

        $check = false;
        if ($courseProfesisons) {
            if ($courseProfesisons[0] == config('sailfor.all_professions') || ($courseProfesisons[0] == 'Tutte le Professioni') || ($courseProfesisons[0] == 'Tutte le Professioni NON ECM') || ($courseProfesisons[0] == 'Tutte le Professioni ECM')) {
                $check = true;
            }
        }


        if (count(array_intersect($courseProfesisons, $contactProfessions)) > 0) {
            $check = true;
        }
        return $check;
    }
}

if (!function_exists('checkCourseFree')) {
    function checkCourseFree($courseId)
    {
        $course = new Course;
        $course = courseById($courseId);
        $price = $course['priceRaw'];
        $check = (floatval($price) >= 1) ? false : true;
        return $check;
    }
}
if (!function_exists('subsRaw')) {
    function subsRaw($contactId)
    {
        if (config('sailfor.switch.abilita_cf_incorso')) {

            $switch = config('sailfor.switch.stato_iscrizioni');

            if (!$switch) {

                $mySubs = DB::table('vtiger_potential')
                    ->join('vtiger_crmentity', 'vtiger_potential.potentialid', '=', 'vtiger_crmentity.crmid')
                    ->join('vtiger_products', 'vtiger_products.productid', '=', 'vtiger_potential.productid')
                    ->select('vtiger_potential.*', 'vtiger_products.*', 'vtiger_potential.s4ggendsubs as potential_s4ggendsubs', 'vtiger_potential.s4_scadenza_iscrizioni as s4_scadenza_iscrizioni', 'vtiger_potential.s4_data_iscrizione as s4_data_iscrizione')
                    ->where([
                        ['vtiger_crmentity.deleted', '=', '0'],
                        ['vtiger_potential.contact_id', '=', $contactId],
                        ['vtiger_products.discontinued', '=', '1'],
                        ['vtiger_potential.s4_elegible', '=', '0'],
                    ])
                    ->where(function ($query) {
                        $query->where('vtiger_potential.potentialtype', '=', 'Confermato attesa pagamento')
                            ->orWhere('vtiger_potential.potentialtype', '=', 'Confermato')
                            ->orWhere('vtiger_potential.potentialtype', '=', 'Overbooking')
                            ->orWhere('vtiger_potential.potentialtype', '=', 'Pre Iscritto')
                            ->orWhere('vtiger_potential.potentialtype', '=', 'Invitato')
                            ->orWhere('vtiger_potential.potentialtype', '=', 'Sospeso')
                            ->orWhere('vtiger_potential.potentialtype', '=', 'Annullato')
                            ->orWhere('vtiger_potential.potentialtype', '=', 'Assente (ex confermato)')
                            ->orWhere('vtiger_potential.potentialtype', '=', 'Cambio Edizione')
                            ->orWhere('vtiger_potential.potentialtype', '=', 'Invitato');
                    })
                    ->orderBy('vtiger_products.s4_start_date', 'desc')
                    ->get();
            } else {
                $mySubs = DB::table('vtiger_potential')
                    ->join('vtiger_crmentity', 'vtiger_potential.potentialid', '=', 'vtiger_crmentity.crmid')
                    ->join('vtiger_products', 'vtiger_products.productid', '=', 'vtiger_potential.productid')
                    ->select('vtiger_potential.*', 'vtiger_products.*', 'vtiger_potential.s4ggendsubs as potential_s4ggendsubs', 'vtiger_potential.s4_scadenza_iscrizioni as s4_scadenza_iscrizioni', 'vtiger_potential.s4_data_iscrizione as s4_data_iscrizione')
                    ->where([
                        ['vtiger_crmentity.deleted', '=', '0'],
                        ['vtiger_potential.contact_id', '=', $contactId],
                        ['vtiger_products.discontinued', '=', '1'],
                        ['vtiger_potential.s4_elegible', '=', '0'],
                    ])
                    ->orderBy('vtiger_products.s4_start_date', 'desc')
                    ->get();
            }
        } else {
            $switch = config('sailfor.switch.stato_iscrizioni');

            if (!$switch) {

                $mySubs = DB::table('vtiger_potential')
                    ->join('vtiger_crmentity', 'vtiger_potential.potentialid', '=', 'vtiger_crmentity.crmid')
                    ->join('vtiger_products', 'vtiger_products.productid', '=', 'vtiger_potential.productid')
                    ->select('vtiger_potential.*', 'vtiger_products.*', 'vtiger_potential.s4ggendsubs as potential_s4ggendsubs', 'vtiger_potential.s4_scadenza_iscrizioni as s4_scadenza_iscrizioni', 'vtiger_potential.s4_data_iscrizione as s4_data_iscrizione')
                    ->where([
                        ['vtiger_crmentity.deleted', '=', '0'],
                        ['vtiger_potential.contact_id', '=', $contactId],
                        ['vtiger_products.discontinued', '=', '1'],
                    ])
                    ->where(function ($query) {
                        $query->where('vtiger_potential.potentialtype', '=', 'Confermato attesa pagamento')
                            ->orWhere('vtiger_potential.potentialtype', '=', 'Confermato')
                            ->orWhere('vtiger_potential.potentialtype', '=', 'Overbooking')
                            ->orWhere('vtiger_potential.potentialtype', '=', 'Pre Iscritto')
                            ->orWhere('vtiger_potential.potentialtype', '=', 'Invitato')
                            ->orWhere('vtiger_potential.potentialtype', '=', 'Sospeso')
                            ->orWhere('vtiger_potential.potentialtype', '=', 'Annullato')
                            ->orWhere('vtiger_potential.potentialtype', '=', 'Assente (ex confermato)')
                            ->orWhere('vtiger_potential.potentialtype', '=', 'Cambio Edizione')
                            ->orWhere('vtiger_potential.potentialtype', '=', 'Invitato');
                    })
                    ->orderBy('vtiger_products.s4_start_date', 'desc')
                    ->get();
            } else {
                $mySubs = DB::table('vtiger_potential')
                    ->join('vtiger_crmentity', 'vtiger_potential.potentialid', '=', 'vtiger_crmentity.crmid')
                    ->join('vtiger_products', 'vtiger_products.productid', '=', 'vtiger_potential.productid')
                    ->select('vtiger_potential.*', 'vtiger_products.*', 'vtiger_potential.s4ggendsubs as potential_s4ggendsubs', 'vtiger_potential.s4_scadenza_iscrizioni as s4_scadenza_iscrizioni', 'vtiger_potential.s4_data_iscrizione as s4_data_iscrizione')
                    ->where([
                        ['vtiger_crmentity.deleted', '=', '0'],
                        ['vtiger_potential.contact_id', '=', $contactId],
                        ['vtiger_products.discontinued', '=', '1'],
                    ])
                    ->orderBy('vtiger_products.s4_start_date', 'desc')
                    ->get();
            }
        }

        return $mySubs;
    }
}


if (!function_exists('getStoricoCorsiById')) {
    function getStoricoCorsiById($userId, $year = null, $text = null)
    {
        if (isset($text) && isset($year)) {
            $storico = DB::table('vtiger_storicocorsi')
                ->join('vtiger_crmentity', 'vtiger_storicocorsi.storicocorsiid', '=', 'vtiger_crmentity.crmid')
                ->select('vtiger_storicocorsi.storicocorsiid as corsoId',
                    'vtiger_storicocorsi.titolocorso as titolo',
                    'vtiger_storicocorsi.metodologia as metodologia',
                    'vtiger_storicocorsi.datainizio as start_date',
                    'vtiger_storicocorsi.datafine as end_date',
                    'vtiger_storicocorsi.oretotali as total_hours',
                    'vtiger_storicocorsi.giorniprevisti as giorni',
                    'vtiger_storicocorsi.sedecorsuale as location',
                    'vtiger_storicocorsi.s4_crediti_ecm_iscritto as ecm_credits',
                    'vtiger_storicocorsi.crediti as credits',
                    'vtiger_storicocorsi.s4_ruolo_iscrizione as role',
                    'vtiger_storicocorsi.annocompetenza as annocompetenza',
                    'vtiger_storicocorsi.s4_storico_edizione as edizione',
                    'vtiger_storicocorsi.riservatointerni as materia',
                    'vtiger_storicocorsi.s4_categoria_corso as categoria_corso',
                    'vtiger_storicocorsi.s4_tipo_corso as tipo_corso')
                ->where([
                    ['vtiger_crmentity.deleted', '=', '0'],
                    ['vtiger_storicocorsi.s4_nominative_storico', '=', $userId],
                    ['vtiger_storicocorsi.titolocorso', 'LIKE', '%' . $text . '%'],
                    ['vtiger_storicocorsi.annocompetenza', '=', $year],
                    ['vtiger_storicocorsi.s4_visualizza_nel_curr_for', '=', 1],
                ])
                ->orderBy('start_date', 'desc')
                ->get();
        } elseif (isset($text) && !isset($year)) {
            $storico = DB::table('vtiger_storicocorsi')
                ->join('vtiger_crmentity', 'vtiger_storicocorsi.storicocorsiid', '=', 'vtiger_crmentity.crmid')
                ->select('vtiger_storicocorsi.storicocorsiid as corsoId',
                    'vtiger_storicocorsi.titolocorso as titolo',
                    'vtiger_storicocorsi.metodologia as metodologia',
                    'vtiger_storicocorsi.datainizio as start_date',
                    'vtiger_storicocorsi.datafine as end_date',
                    'vtiger_storicocorsi.oretotali as total_hours',
                    'vtiger_storicocorsi.giorniprevisti as giorni',
                    'vtiger_storicocorsi.sedecorsuale as location',
                    'vtiger_storicocorsi.s4_crediti_ecm_iscritto as ecm_credits',
                    'vtiger_storicocorsi.crediti as credits',
                    'vtiger_storicocorsi.s4_ruolo_iscrizione as role',
                    'vtiger_storicocorsi.annocompetenza as annocompetenza',
                    'vtiger_storicocorsi.s4_storico_edizione as edizione',
                    'vtiger_storicocorsi.riservatointerni as materia',
                    'vtiger_storicocorsi.s4_categoria_corso as categoria_corso',
                    'vtiger_storicocorsi.s4_tipo_corso as tipo_corso')
                ->where([
                    ['vtiger_crmentity.deleted', '=', '0'],
                    ['vtiger_storicocorsi.s4_nominative_storico', '=', $userId],
                    ['vtiger_storicocorsi.titolocorso', 'LIKE', '%' . $text . '%'],
                    ['vtiger_storicocorsi.s4_visualizza_nel_curr_for', '=', 1],
                ])
                ->orderBy('start_date', 'desc')
                ->get();
        } elseif (!isset($text) && isset($year)) {
            $storico = DB::table('vtiger_storicocorsi')
                ->join('vtiger_crmentity', 'vtiger_storicocorsi.storicocorsiid', '=', 'vtiger_crmentity.crmid')
                ->select('vtiger_storicocorsi.storicocorsiid as corsoId',
                    'vtiger_storicocorsi.titolocorso as titolo',
                    'vtiger_storicocorsi.metodologia as metodologia',
                    'vtiger_storicocorsi.datainizio as start_date',
                    'vtiger_storicocorsi.datafine as end_date',
                    'vtiger_storicocorsi.oretotali as total_hours',
                    'vtiger_storicocorsi.giorniprevisti as giorni',
                    'vtiger_storicocorsi.sedecorsuale as location',
                    'vtiger_storicocorsi.s4_crediti_ecm_iscritto as ecm_credits',
                    'vtiger_storicocorsi.crediti as credits',
                    'vtiger_storicocorsi.s4_ruolo_iscrizione as role',
                    'vtiger_storicocorsi.annocompetenza as annocompetenza',
                    'vtiger_storicocorsi.s4_storico_edizione as edizione',
                    'vtiger_storicocorsi.riservatointerni as materia',
                    'vtiger_storicocorsi.s4_categoria_corso as categoria_corso',
                    'vtiger_storicocorsi.s4_tipo_corso as tipo_corso')
                ->where([
                    ['vtiger_crmentity.deleted', '=', '0'],
                    ['vtiger_storicocorsi.s4_nominative_storico', '=', $userId],
                    ['vtiger_storicocorsi.annocompetenza', '=', $year],
                    ['vtiger_storicocorsi.s4_visualizza_nel_curr_for', '=', 1],
                ])
                ->orderBy('start_date', 'desc')
                ->get();
        } elseif (!isset($text) && !isset($year)) {
            $storico = DB::table('vtiger_storicocorsi')
                ->join('vtiger_crmentity', 'vtiger_storicocorsi.storicocorsiid', '=', 'vtiger_crmentity.crmid')
                ->select('vtiger_storicocorsi.storicocorsiid as corsoId',
                    'vtiger_storicocorsi.titolocorso as titolo',
                    'vtiger_storicocorsi.metodologia as metodologia',
                    'vtiger_storicocorsi.datainizio as start_date',
                    'vtiger_storicocorsi.datafine as end_date',
                    'vtiger_storicocorsi.oretotali as total_hours',
                    'vtiger_storicocorsi.giorniprevisti as giorni',
                    'vtiger_storicocorsi.sedecorsuale as location',
                    'vtiger_storicocorsi.s4_crediti_ecm_iscritto as ecm_credits',
                    'vtiger_storicocorsi.crediti as credits',
                    'vtiger_storicocorsi.s4_ruolo_iscrizione as role',
                    'vtiger_storicocorsi.annocompetenza as annocompetenza',
                    'vtiger_storicocorsi.s4_storico_edizione as edizione',
                    'vtiger_storicocorsi.riservatointerni as materia',
                    'vtiger_storicocorsi.s4_categoria_corso as categoria_corso',
                    'vtiger_storicocorsi.s4_tipo_corso as tipo_corso')
                ->where([
                    ['vtiger_crmentity.deleted', '=', '0'],
                    ['vtiger_storicocorsi.s4_nominative_storico', '=', $userId],
                    ['vtiger_storicocorsi.s4_visualizza_nel_curr_for', '=', 1],
                ])
                ->orderBy('start_date', 'desc')
                ->get();
        }

        //dd($storico);

        return $storico;
    }
}

if (!function_exists('getPicklist')) {
    function getPicklist($courseId)
    {
        $pickList = DB::table('vtiger_products')
            ->select('vtiger_products.s4_requisiti_parte_prod')
            ->where([
                ['vtiger_products.productid', '=', $courseId],
            ])
            ->orderBy('vtiger_products.s4_requisiti_parte_prod', 'asc')
            ->get();
        return $pickList;
    }
}

if (!function_exists('getDichiarazione')) {
    function getDichiarazione()
    {
        $dichiarazione = DB::table('vtiger_s4_requisiti_parte_prod')
            ->select('vtiger_s4_requisiti_parte_prod.s4_requisiti_parte_prod')
            ->get();
        return $dichiarazione;
    }
}
if (!function_exists('getSelectedDichiarazione')) {
    function getSelectedDichiarazione($contactId, $courseId)
    {
        $selectedDichiarazione = DB::table('vtiger_potential')
            ->select('vtiger_potential.s4_requisiti_parte_pot')
            ->where([
                ['vtiger_potential.productid', '=', $courseId],
                ['vtiger_potential.contact_id', '=', $contactId]
            ])
            ->get();
        return $selectedDichiarazione;
    }
}

if (!function_exists('updateDichiarazioneByCourseId')) {
    function updateDichiarazioneByCourseId($courseId, $contactId, $getSelectedPickList)
    {
        DB::table('vtiger_potential')->where([
            ['vtiger_potential.productid', '=', $courseId],
            ['vtiger_potential.contact_id', '=', $contactId]
        ])
            ->update(array('vtiger_potential.s4_requisiti_parte_pot' => $getSelectedPickList));
    }
}
if (!function_exists('subsByContactId')) {
    function subsByContactId($contactId)
    {
        $contact = Contact::find($contactId);

        $subsRaw = subsRaw($contactId);

        if (config('sailfor.faddy')) {
            if ($contact['s4_moodle_user'] != '' || $contact['s4_moodle_user'] != 0) {
                $login = (new Faddy)->login($contact['s4_moodle_user']);
            } else {
                $login = '';
            }
        }

        $subs = [];
        foreach ($subsRaw as $key => $subRaw) {

            $course = new Course;
            $course = courseByIdAll($subRaw->productid);

            if ($course) {

                $location = "";
                if (count(array_keys($course['location'])) > 0)
                    $location = array_keys($course['location'])[0];
                $attachments = new Attachment;
                $attachments = attachmentsAndNotes($subRaw->productid);

                $params = [
                    'userName' => 'sailuser' . $contactId,
                    'courseId' => $course['moodleCourseId']
                ];
                if (config('sailfor.faddy')) {
                    $resource = (new Faddy)->resource($params['courseId']);
                    $faddyUrl = $login . $resource;
                } else {
                    $faddyUrl = false;
                }

                if ($subRaw->abilita_elearning == 0 && $subRaw->s4_active_survey == 0) {
                    $faddy = 0;
                }

                $subs[$subRaw->potentialid] = [
                    'subId' => $subRaw->potentialid,
                    'subNo' => $subRaw->potential_no,
                    'contactId' => $subRaw->contact_id,
                    'courseId' => $subRaw->productid,
                    'course' => $course['title'],
                    'status' => $subRaw->potentialtype,
                    'role' => $subRaw->s4_role,
                    'subDate' => DataHelper::dateFormat($subRaw->s4_data_iscrizione),
                    'courseStar' => $course['start'],
                    'courseEnd' => $course['end'],
                    'courseHoureStart' => $course['startHour'],
                    'coursehoursAmount' => $course['hoursAmount'],
                    'location' => $location,
                    'professions' => $subRaw->s4_professions,
                    'isElearning' => DataHelper::isElearning($course['isElearning']),
                    'attachments' => $attachments,
                    'faddyUrl' => $faddyUrl,
                    'credits' => DataHelper::numberFormat($subRaw->s4_final_credits),
                    'edition' => $course['edition'],
                    'active_survey' => $course['active_survey'],
                    's4_moodle_course' => $course['moodleCourseId'] ?? '',
                    's4_requisiti_parte_pot' => $subRaw->s4_requisiti_parte_pot,
                    'external_link' => $course['external_link'] ?? '',
                    's4_data_iscrizione' => $subRaw->s4_data_iscrizione,
                    's4_scadenza_iscrizioni' => $subRaw->s4_scadenza_iscrizioni,
                    's4ggendsubs' => $subRaw->s4ggendsubs,
                    'controllo_iscritto' => $subRaw->s4_potential_access_control ?? '',
                    's4_code' => $subRaw->s4_code,
                    'potential_s4ggendsubs' => $subRaw->potential_s4ggendsubs,
                    'isActiveFaddy' => $subRaw->faddy_idoneo,
                    'subtitle' => $course['subtitle'],
                ];
            }
        }

        return $subs;
    }
}

if (!function_exists('subsByContactIdDigitalArchive')) {
    function subsByContactIdDigitalArchive($contactId)
    {
        $subsRaw = subsRaw($contactId);

        $subs = [];
        foreach ($subsRaw as $key => $subRaw) {

            $course = new Course;
            $course = courseByIdAll($subRaw->productid);
            if ($course) {
                $attachment = new Attachment;
                $didacticMaterial = new Attachment;
                $didacticMaterial = attachmentsAndNotes($subRaw->productid);
                $subsDocs = new Attachment;
                $subsDocs = attachmentsAndNotesSubsDocs($subRaw->potentialid);

                $subs[$subRaw->potentialid] = [
                    'subNo' => $subRaw->potential_no,
                    'courseId' => $subRaw->productid,
                    'course' => $course['title'],
                    'didacticMaterial' => $didacticMaterial,
                    'subsDocs' => $subsDocs
                ];
            }

        }

        return $subs;
    }
}
if (!function_exists('storicoCorsiById')) {
    function storicoCorsiById($userId, $year = null, $text = null)
    {
        if (isset($year) || isset($text)) {
            $corsi = getStoricoCorsiById($userId, $year, $text);
        } else {
            $corsi = getStoricoCorsiById($userId);
        }

        $storicoCorsi = [];

        foreach ($corsi as $key => $storico) {
//            dd($storico);
            //Seleziono i crediti ECM
            $finalcredits = DataHelper::numberFormat($storico->ecm_credits);
            $isEcm = 1;

            //Se sono vuoti, seleziono quelli non ecm
            if (DataHelper::numberFormat($storico->ecm_credits) == "0.00") {
                if (DataHelper::numberFormat($storico->credits) != "0.00") {
                    $finalcredits = DataHelper::numberFormat($storico->credits);
                    $isEcm = 0;
                } else {
                    $isEcm = 1;
                }
            }


            $historyCredits[$storico->annocompetenza][] = $finalcredits;

            $storicoCorsi[$storico->corsoId] = [
                'courseId' => $storico->corsoId,
                'courseName' => $storico->titolo,
                'countAttachments' => '',
                'metodologia' => $storico->metodologia,
                'courseStar' => DataHelper::dateFormat($storico->start_date),
                'courseEnd' => DataHelper::dateFormat($storico->end_date),
                'orePresenza' => $storico->total_hours,
                'giorniPresenza' => $storico->giorni,
                'location' => $storico->location,
                'materia' => $storico->materia,
                'credits' => $finalcredits,
                'role' => $storico->role,
                'edizione' => $storico->edizione,
                'tipo_corso' => $storico->categoria_corso,
                'annocompetenza' => $storico->annocompetenza,
                'att' => '',
                'subtitle' => '',
                'historyCredits' => $historyCredits,
                'type' => 'storico',
            ];

        }
        return $storicoCorsi;
    }
}

if (!function_exists('subsByContactIdHistory')) {
    function subsByContactIdHistory($contactId, $year = null, $text = null)
    {
        $isEcm = ' ';

        if (isset($year) || isset($text)) {
            $subsRaw = Subscription::subsRawHistory($contactId, $year, $text);
        } else {
            $subsRaw = Subscription::subsRawHistory($contactId);
        }


        $contact = Contact::find($contactId);

        if (config('sailfor.faddy')) {

            if ($contact['s4_moodle_user'] != '' || $contact['s4_moodle_user'] != 0) {
                $login = (new Faddy)->login($contact['s4_moodle_user']);
            } else {
                $login = '';
            }
        }
        $subs = [];

        $historyCredits = [];
        $ore = '';
        $attachmentsFile = [];
        $accreditationMethodName = [];
        foreach ($subsRaw as $key => $subRaw) {
            $course = new Course;
            $course = courseByIdAll($subRaw->productid);

            if ($course) {

                // Se la data di fine iscritto che proviene da Moodle, non è Presente, mi prendo la data di fine corso del Corso!
                if ($subRaw->s4_exam_date <> '') {
                    $year = substr($subRaw->s4_exam_date, 0, 4);
                } else {
                    $year = substr($course['end'], 6, 4);
                }

                //ore e giorni
                $statsInfo = getStatsInfo($subRaw->productid, $contactId);

                $ore = $statsInfo['timeCount'];

                //Seleziono i crediti ECM
                $finalcredits = DataHelper::numberFormat($subRaw->s4_final_credits);
                $isEcm = 1;

                if (DataHelper::numberFormat($subRaw->s4_final_credits) == "0.00") {
                    if (DataHelper::numberFormat($subRaw->nonecmsubcredits) != "0.00") {
                        $finalcredits = DataHelper::numberFormat($subRaw->nonecmsubcredits);
                        $isEcm = 0;
                    } else {
                        $isEcm = 1;
                    }
                }

                // SE le ore PARTECIPANTE sono diverse da 0 allora aggiungo i crediti alla finestra dei crediti
                //Prima prendeva solo ecm
                if ($ore <> '0:0') {
                    $historyCredits[$year][] = $finalcredits;
                } elseif ($course['hoursAmount'] != 0) {
                    // OPPURE SE le ore del CORSO sono diverse da 0 allora faccio vedere lo stesso il pannello crediti anche se il partecipante non ne ha
                    $historyCredits[$year][] = $finalcredits;
                }

                //materia
                $materia = '';
                $getMateria = getMateria($subRaw->productid);
                $materia = empty($getMateria) ? '' : (array)($getMateria[0]);
                $courseName = $course['title'] . ($course['edition'] == 0 ? "" : " - " . $course['edition']);   //nome corso breve + n edizione
//                $accreditationMethodName = accreditations($subRaw->productid);
//                dd($accreditationMethodName);
                //prima prendevo la chiave che corrispondeva al nome dell array
                //if(count(array_keys($course['location'])) > 0)
                //  $location = array_keys($course['location'])[0];
                //ora estraggo la location dalla query getLocationByIdRaw
                $location = '';
                $courseForLocation = new Course;
                $courseForLocation = courseRawByIdAll($subRaw->productid);

                $locationComplete = (new Location)->getLocationByIdRaw($courseForLocation[0]->s4_location); // to get the location
                if (count($locationComplete) > 0) {
                    $location = $locationComplete[0]->locationsala_tks_nomesala;
                    if ($locationComplete[0]->s4address) {
                        $location = $locationComplete[0]->s4address;
                    } else {
                        $location = $locationComplete[0]->locationsala_tks_nomesala;
                    }
                }
                $attachments = new Attachment;
                $attachments = attachmentsAndNotes($subRaw->productid);

                // conto tutti gli allegati(di qualsiasi tipo materiale didattico, file digitale, ecc) del corso specifico, del utente in questione
                $countAttachments = new Attachment;
                $countAttachments = attachmentsAndNotesForCourse($contactId, $subRaw->productid);
                foreach ($countAttachments as $as) {
                    $attachmentsFile[] = $as;
                }
                $attachS = new Attachment;
                $attachS = attachmentsAndNotesForCourse($contactId, $subRaw->productid);
                $params = [
                    'userName' => 'sailuser' . $contactId,
                    'courseId' => $course['moodleCourseId']
                ];
                if (config('sailfor.faddy')) {
                    $resource = (new Faddy)->resource($params['courseId']);
                    $faddyUrl = $login . $resource;
                } else {
                    $faddyUrl = false;
                }

                $subs[$subRaw->potentialid] = [
                    'subId' => $subRaw->potentialid,
                    'subNo' => $subRaw->potential_no,
                    'contactId' => $subRaw->contact_id,
                    'courseId' => $subRaw->productid,
//                    'courseMethodName' => $accreditationMethodName['methodologyCourseName']->metodologia,
                    'course' => $course['title'],
                    'status' => $subRaw->potentialtype,
                    'role' => $subRaw->s4_role,
                    's4_year' => $subRaw->s4_year,
                    'subDate' => DataHelper::dateFormat($subRaw->s4_data_iscrizione),
                    //se non c è la data di fine iscrizione(chiamata anche data completamento) allora metto la data fine corso del corso
                    //'courseDateEndSubscription' => ($subRaw->s4_exam_date == '') ? $course['end'] : DataHelper::dateFormat($subRaw->s4_exam_date),
                    'courseStar' => $course['start'],
                    'courseHoureStart' => $course['startHour'],
                    'coursehoursAmount' => $course['hoursAmount'],
                    'eligibility' => DataHelper::isEligible($subRaw->s4_elegible),
                    'professions' => $subRaw->s4_professions,
                    'isElearning' => DataHelper::isElearning($course['isElearning']),
                    'attachments' => $attachments,
                    'faddyUrl' => $faddyUrl,
                    'credits' => $finalcredits,
                    'isEcm' => $isEcm,
                    'historyCredits' => $historyCredits,
                    'countAttachments' => count($countAttachments),
                    'attachS' => $attachS->toArray(),
                    // Inserisco il percorso dei fascicoli digitali appartenenti al corso del curriculum formativo
                    'pathAttachment' => collect($attachmentsFile),
                    'location' => ($location == '') ? '' : $location,
                    'courseName' => $courseName,
                    'orePresenza' => ($statsInfo['timeCount'] == '0:0') ? '' : $statsInfo['timeCount'],
                    'giorniPresenza' => ($statsInfo['daysCount'] == '0:0') ? '' : $statsInfo['daysCount'],
                    'materia' => ($materia == '') ? '' : $materia['s4_descrizione_materia'],
                    'idoneoSecondoFaddy' => $subRaw->faddy_idoneo,
                    'isStorico' => $course['isStorico'],
                    'expectedDays' => $course['expectedDays'],
                    'courseDateEndSubscription' => $course['end'], //Per inail è cambiata la logica, ho lasciato la stessa variabile, prendo la data fine corso
                    'subtitle' => $course['subtitle'],
                ];
                //se Ore presenza = 0 non faccio vedere l iscrizione, a meno che il corso non sia di tipo faddy.
                if ($subs[$subRaw->potentialid]['orePresenza'] == '0:00' && $course['isElearning'] == 1 || $subs[$subRaw->potentialid]['orePresenza'] == '0:00' && $course['active_survey'] == 1) {
                    // in questo caso, prendo ora e giorni del corso
                    if ($subs[$subRaw->potentialid]['coursehoursAmount'] != 0) {
                        $subs[$subRaw->potentialid]['orePresenza'] = $subs[$subRaw->potentialid]['coursehoursAmount'] . ":00";
                        $subs[$subRaw->potentialid]['giorniPresenza'] = $subs[$subRaw->potentialid]['expectedDays'];
                    } else {
                        //rimuovo il corso dalla lista
                        unset($subs[$subRaw->potentialid]);
                    }
                }

            }
        }
//        dd($subs);
        return $subs;
    }
}
if (!function_exists('subsByContactIdHistoryCF')) {
    function subsByContactIdHistoryCF($contactId, $year = null, $text = null)
    {
        $isEcm = ' ';

        if (isset($year) || isset($text)) {
            $subsRaw = Subscription::subsRawHistoryCF($contactId, $year, $text);
        } else {
            $subsRaw = Subscription::subsRawHistoryCF($contactId);
        }

        $contact = Contact::find($contactId);

        if (config('sailfor.faddy')) {

            if ($contact['s4_moodle_user'] != '' || $contact['s4_moodle_user'] != 0) {
                $login = (new Faddy)->login($contact['s4_moodle_user']);
            } else {
                $login = '';
            }
        }
        $subs = [];

        $historyCredits = [];
        $ore = '';
        $attachmentsFile = [];
        $accreditationMethodName = [];
        foreach ($subsRaw as $key => $subRaw) {
            $course = new Course;
            $course = courseByIdAll($subRaw->productid);
//dd($course);
            if ($course) {

                // Se la data di fine iscritto che proviene da Moodle, non è Presente, mi prendo la data di fine corso del Corso!
                if ($subRaw->s4_exam_date <> '') {
                    $year = substr($subRaw->s4_exam_date, 0, 4);
                } else {
                    $year = substr($course['end'], 6, 4);
                }

                //ore e giorni
                $statsInfo = getStatsInfo($subRaw->productid, $contactId);

                $ore = $statsInfo['timeCount'];

                //Seleziono i crediti ECM
                $finalcredits = DataHelper::numberFormat($subRaw->s4_final_credits);
                $isEcm = 1;

                if (DataHelper::numberFormat($subRaw->s4_final_credits) == "0.00") {
                    if (DataHelper::numberFormat($subRaw->nonecmsubcredits) != "0.00") {
                        $finalcredits = DataHelper::numberFormat($subRaw->nonecmsubcredits);
                        $isEcm = 0;
                    } else {
                        $isEcm = 1;
                    }
                }

                // SE le ore PARTECIPANTE sono diverse da 0 allora aggiungo i crediti alla finestra dei crediti
                //Prima prendeva solo ecm
                if ($ore <> '0:0') {
                    $historyCredits[$year][] = $finalcredits;
                } elseif ($course['hoursAmount'] != 0) {
                    // OPPURE SE le ore del CORSO sono diverse da 0 allora faccio vedere lo stesso il pannello crediti anche se il partecipante non ne ha
                    $historyCredits[$year][] = $finalcredits;
                }

                //materia
                $materia = '';
                $getMateria = getMateria($subRaw->productid);
                $materia = empty($getMateria) ? '' : (array)($getMateria[0]);
                $courseName = $course['title'] . ($course['edition'] == 0 ? "" : " - " . $course['edition']);   //nome corso breve + n edizione
//                $accreditationMethodName = accreditations($subRaw->productid);
//                dd($accreditationMethodName);
                //prima prendevo la chiave che corrispondeva al nome dell array
                //if(count(array_keys($course['location'])) > 0)
                //  $location = array_keys($course['location'])[0];
                //ora estraggo la location dalla query getLocationByIdRaw
                $location = '';
                $courseForLocation = new Course;
                $courseForLocation = courseRawByIdAll($subRaw->productid);

                $locationComplete = (new Location)->getLocationByIdRaw($courseForLocation[0]->s4_location); // to get the location
                if (count($locationComplete) > 0) {
                    $location = $locationComplete[0]->locationsala_tks_nomesala;
                    if ($locationComplete[0]->s4address) {
                        $location = $locationComplete[0]->s4address;
                    } else {
                        $location = $locationComplete[0]->locationsala_tks_nomesala;
                    }
                }
                $attachments = new Attachment;
                $attachments = attachmentsAndNotes($subRaw->productid);

                // conto tutti gli allegati(di qualsiasi tipo materiale didattico, file digitale, ecc) del corso specifico, del utente in questione
                $countAttachments = new Attachment;
                $countAttachments = attachmentsAndNotesForCourse($contactId, $subRaw->productid);
                foreach ($countAttachments as $as) {
                    $attachmentsFile[] = $as;
                }
                $attachS = new Attachment;
                $attachS = attachmentsAndNotesForCourse($contactId, $subRaw->productid);
                $params = [
                    'userName' => 'sailuser' . $contactId,
                    'courseId' => $course['moodleCourseId']
                ];
                if (config('sailfor.faddy')) {
                    $resource = (new Faddy)->resource($params['courseId']);
                    $faddyUrl = $login . $resource;
                } else {
                    $faddyUrl = false;
                }

                $subs[$subRaw->potentialid] = [
                    'subId' => $subRaw->potentialid,
                    'subNo' => $subRaw->potential_no,
                    'contactId' => $subRaw->contact_id,
                    'courseId' => $subRaw->productid,
//                    'courseMethodName' => $accreditationMethodName['methodologyCourseName']->metodologia,
                    'course' => $course['title'],
                    'status' => $subRaw->potentialtype,
                    'role' => $subRaw->s4_role,
                    's4_year' => $subRaw->s4_year,
                    'subDate' => DataHelper::dateFormat($subRaw->s4_data_iscrizione),
                    //se non c è la data di fine iscrizione(chiamata anche data completamento) allora metto la data fine corso del corso
                    //'courseDateEndSubscription' => ($subRaw->s4_exam_date == '') ? $course['end'] : DataHelper::dateFormat($subRaw->s4_exam_date),
                    'courseStar' => $course['start'],
                    'courseHoureStart' => $course['startHour'],
                    'coursehoursAmount' => $course['hoursAmount'],
                    'eligibility' => DataHelper::isEligible($subRaw->s4_elegible),
                    'professions' => $subRaw->s4_professions,
                    'isElearning' => DataHelper::isElearning($course['isElearning']),
                    'attachments' => $attachments,
                    'faddyUrl' => $faddyUrl,
                    'credits' => $finalcredits,
                    'isEcm' => $isEcm,
                    'historyCredits' => $historyCredits,
                    'countAttachments' => count($countAttachments),
                    'attachS' => $attachS->toArray(),
                    // Inserisco il percorso dei fascicoli digitali appartenenti al corso del curriculum formativo
                    'pathAttachment' => collect($attachmentsFile),
                    'location' => ($location == '') ? '' : $location,
                    'courseName' => $courseName,
                    'orePresenza' => ($statsInfo['timeCount'] == '0:0') ? '' : $statsInfo['timeCount'],
                    'giorniPresenza' => ($statsInfo['daysCount'] == '0:0') ? '' : $statsInfo['daysCount'],
                    'materia' => ($materia == '') ? '' : $materia['s4_descrizione_materia'],
                    'idoneoSecondoFaddy' => $subRaw->faddy_idoneo,
                    'isStorico' => $course['isStorico'],
                    'expectedDays' => $course['expectedDays'],
                    'courseDateEndSubscription' => $course['end'], //Per inail è cambiata la logica, ho lasciato la stessa variabile, prendo la data fine corso
                    'subtitle' => $course['subtitle'],
                ];
                //se Ore presenza = 0 non faccio vedere l iscrizione, a meno che il corso non sia di tipo faddy.
                if ($subs[$subRaw->potentialid]['orePresenza'] == '0:00' && $course['isElearning'] == 1 || $subs[$subRaw->potentialid]['orePresenza'] == '0:00' && $course['active_survey'] == 1) {
                    // in questo caso, prendo ora e giorni del corso
                    if ($subs[$subRaw->potentialid]['coursehoursAmount'] != 0) {
                        $subs[$subRaw->potentialid]['orePresenza'] = $subs[$subRaw->potentialid]['coursehoursAmount'] . ":00";
                        $subs[$subRaw->potentialid]['giorniPresenza'] = $subs[$subRaw->potentialid]['expectedDays'];
                    } else {
                        //rimuovo il corso dalla lista
                        unset($subs[$subRaw->potentialid]);
                    }
                }

            }
        }
//        dd($subs);
        return $subs;
    }
}
if (!function_exists('cancel')) {
    function cancel($subId, $contactId, $courseId)
    {
        $potentialType = new Subscription;
        $potentialType = getPotentialType($subId)->toArray();
        $potentialType1 = (array)$potentialType[0];
        $statusPreIscritto = new Subscription;
        $statusPreIscritto = checkStatusPreIscritto()->toArray();

        $statusPreIscritto1 = (array)$statusPreIscritto[0];
        switch ($potentialType1['potentialtype']) {
            case 'Confermato':
                $courseAdd = new Course;
                $courseAdd = seatCourseAdd($courseId);
                break;
            case 'Confermato attesa pagamento':
                $courseAdd = new Course;
                $courseAdd = seatCourseAdd($courseId);
                break;
            case 'Pre Iscritto':
                if ($statusPreIscritto1['s4_scale_preregistered'] == 1)
                    $courseAdd = new Course;
                $courseAdd = seatCourseAdd($courseId);
                break;
        }


        DB::table('vtiger_potential')
            ->where('potentialid', $subId)
            ->update(['potentialtype' => '| Annullato']);

        (new ModTracker)->trace($subId, 'Potentials', 'UPDATE', ['opportunity_type' => '| Annullato'], ['opportunity_type' => 'Confermato']);
    }
}
if (!function_exists('subDetail')) {
    function subDetail($subId)
    {
        $mySubs = DB::table('vtiger_potential')
            ->join('vtiger_crmentity', 'vtiger_potential.potentialid', '=', 'vtiger_crmentity.crmid')
            ->leftJoin('vtiger_products', 'vtiger_products.productid', '=', 'vtiger_potential.productid')
            ->leftJoin('vtiger_contactdetails', 'vtiger_contactdetails.contactid', '=', 'vtiger_potential.contact_id')
            ->select('vtiger_potential.*', 'vtiger_contactdetails.*', 'vtiger_products.*')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_potential.potentialid', '=', $subId],
                ['vtiger_products.discontinued', '=', '1'],
            ])
            ->first();
        return $mySubs;
    }
}

if (!function_exists('checkStatusPreIscritto')) {
    function checkStatusPreIscritto()
    {
        $mySubs = DB::table('vtiger_customdefaults')
            ->select('vtiger_customdefaults.s4_scale_preregistered')
            ->get();
        return $mySubs;
    }
}
if (!function_exists('getPotentialType')) {
    function getPotentialType($subId)
    {
        $mySubs = DB::table('vtiger_potential')
            ->select('vtiger_potential.potentialtype')
            ->where('potentialid', $subId)
            ->get();
        return $mySubs;
    }
}
if (!function_exists('getStatsInfo')) {
    function getStatsInfo($courseId, $contactId)
    {

        $contactDays = 0;
        $contactHours = 0;
        $contactMinutes = 0;

        //numero giorni
        $resultDays = DB::table('vtiger_presenze')
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
            ->whereIn('vtiger_presenze.s4ruolopresenza', ['Partecipante', 'Studente', '| Partecipanti non ECM', '| Osservatore (n\/exp)'])
            ->get()
            ->toArray();
        $days = (array)$resultDays[0];
        $contactDays = $contactDays + $days['days'];

        //ore e minuti
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
                ['vtiger_presenze.leaving_date', '<>', ''],
            ])
            ->whereIn('vtiger_presenze.s4ruolopresenza', ['Partecipante', 'Studente', '| Partecipanti non ECM', '| Osservatore (n\/exp)'])
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

//        $to = Carbon::createFromFormat('Y-m-d H:i:s', '2015-5-5 3:00:00');
//        $from = Carbon::createFromFormat('Y-m-d H:i:s', '2015-5-6 12:19:00');
//        $decimalMin = $to->diffInMinutes($from) / 10;
//        $decimalConverted = intval($decimalMin / 10);
//        $decimalHour = $to->diffInHours($from);
//        $diff_in_hours = $decimalHour . ':' . $decimalConverted;

        if (($minutesOnly / 10) > 0) {
            $ret = array(
                'timeCount' => $hoursOnly . ':' . $minutesOnly . '',
                'daysCount' => $contactDays,
            );
        } else {
            $ret = array(
                'timeCount' => $hoursOnly . ':0' . $minutesOnly . '',
                'daysCount' => $contactDays,
            );

        }

        return $ret;
    }
}
// get Materie
if (!function_exists('getMateria')) {
    function getMateria($courseId)
    {
        $materia = DB::table('vtiger_products')
            ->join('vtiger_crmentity', 'vtiger_products.productid', '=', 'vtiger_crmentity.crmid')
            ->join('vtiger_materie', 'vtiger_products.s4_materia_id', '=', 'vtiger_materie.materieid')
            ->select('vtiger_materie.s4_descrizione_materia')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_products.productid', '=', $courseId]
            ])
            ->get()
            ->toArray();
        return $materia;
    }
}
if (!function_exists('updateSuspended')) {
    function updateSuspended($subId, $contactId, $courseId)
    {
        $potentialType = new Subscription;
        $potentialType = getPotentialType($subId);
        $potentialType1 = (array)$potentialType[0];
        $statusPreIscritto = new Subscription;
        $statusPreIscritto = checkStatusPreIscritto();
        $statusPreIscritto1 = (array)$statusPreIscritto[0];
        switch ($potentialType1['potentialtype']) {
            case 'Confermato':
                $courseAdd = new Course;
                $courseAdd = seatCourseAdd($courseId);
                break;
            case 'Confermato attesa pagamento':
                $courseAdd = new Course;
                $courseAdd = seatCourseAdd($courseId);
                break;
            case 'Pre Iscritto':
                if ($statusPreIscritto1['s4_scale_preregistered'] == 1)
                    $courseAdd = new Course;
                $courseAdd = seatCourseAdd($courseId);
                break;
        }


        DB::table('vtiger_potential')
            ->where('potentialid', $subId)
            ->update(['potentialtype' => 'Sospeso']);

        (new ModTracker)->trace($subId, 'Potentials', 'UPDATE', ['opportunity_type' => 'Sospeso'], ['opportunity_type' => 'Confermato']);
    }
}
if (!function_exists('preSubscription')) {
    function preSubscription($subId, $contactId, $courseId)
    {
        $potentialType = new Subscription;
        $potentialType = getPotentialType($subId);
        $potentialType1 = (array)$potentialType[0];
        $statusPreIscritto = new Subscription;
        $statusPreIscritto = checkStatusPreIscritto();
        $statusPreIscritto1 = (array)$statusPreIscritto[0];
        switch ($potentialType1['potentialtype']) {
            case 'Confermato':
                $courseAdd = new Course;
                $courseAdd = seatCourseAdd($courseId);
                break;
            case 'Confermato attesa pagamento':
                $courseAdd = new Course;
                $courseAdd = seatCourseAdd($courseId);
                break;
            case 'Pre Iscritto':
                if ($statusPreIscritto1['s4_scale_preregistered'] == 1)
                    $courseAdd = new Course;
                $courseAdd = seatCourseAdd($courseId);
                break;
        }


        DB::table('vtiger_potential')
            ->where('potentialid', $subId)
            ->update(['potentialtype' => 'Pre Iscritto']);

        (new ModTracker)->trace($subId, 'Potentials', 'UPDATE', ['opportunity_type' => 'Pre Iscritto'], ['opportunity_type' => 'Confermato']);
    }
}
if (!function_exists('waitConfirm')) {
    function waitConfirm($subId, $contactId, $courseId)
    {
        $potentialType = new Subscription;
        $potentialType = getPotentialType($subId);
        $potentialType1 = (array)$potentialType[0];
        $statusPreIscritto = new Subscription;
        $statusPreIscritto = checkStatusPreIscritto();
        $statusPreIscritto1 = (array)$statusPreIscritto[0];
        switch ($potentialType1['potentialtype']) {
            case 'Confermato':
                $courseAdd = new Course;
                $courseAdd = seatCourseAdd($courseId);
                break;
            case 'Confermato attesa pagamento':
                $courseAdd = new Course;
                $courseAdd = seatCourseAdd($courseId);
                break;
            case 'Pre Iscritto':
                if ($statusPreIscritto1['s4_scale_preregistered'] == 1)
                    $courseAdd = new Course;
                $courseAdd = seatCourseAdd($courseId);
                break;
        }


        DB::table('vtiger_potential')
            ->where('potentialid', $subId)
            ->update(['potentialtype' => 'Confermato attesa pagamento']);

        (new ModTracker)->trace($subId, 'Potentials', 'UPDATE', ['opportunity_type' => 'Confermato attesa pagamento'], ['opportunity_type' => 'Confermato']);
    }
}
if (!function_exists('overBooked')) {
    function overBooked($subId, $contactId, $courseId)
    {
        $potentialType = new Subscription;
        $potentialType = getPotentialType($subId);
        $potentialType1 = (array)$potentialType[0];
        $statusPreIscritto = new Subscription;
        $statusPreIscritto = checkStatusPreIscritto();
        $statusPreIscritto1 = (array)$statusPreIscritto[0];
        switch ($potentialType1['potentialtype']) {
            case 'Confermato':
                $courseAdd = new Course;
                $courseAdd = seatCourseAdd($courseId);
                break;
            case 'Confermato attesa pagamento':
                $courseAdd = new Course;
                $courseAdd = seatCourseAdd($courseId);
                break;
            case 'Pre Iscritto':
                if ($statusPreIscritto1['s4_scale_preregistered'] == 1)
                    $courseAdd = new Course;
                $courseAdd = seatCourseAdd($courseId);
                break;
        }


        DB::table('vtiger_potential')
            ->where('potentialid', $subId)
            ->update(['potentialtype' => 'Overbooking']);

        (new ModTracker)->trace($subId, 'Potentials', 'UPDATE', ['opportunity_type' => 'Overbooking'], ['opportunity_type' => 'Confermato']);
    }
}
if (!function_exists('absentConfirmed')) {
    function absentConfirmed($subId, $contactId, $courseId)
    {
        $potentialType = new Subscription;
        $potentialType = getPotentialType($subId);
        $potentialType1 = (array)$potentialType[0];
        $statusPreIscritto = new Subscription;
        $statusPreIscritto = checkStatusPreIscritto();
        $statusPreIscritto1 = (array)$statusPreIscritto[0];
        switch ($potentialType1['potentialtype']) {
            case 'Confermato':
                $courseAdd = new Course;
                $courseAdd = seatCourseAdd($courseId);
                break;
            case 'Confermato attesa pagamento':
                $courseAdd = new Course;
                $courseAdd = seatCourseAdd($courseId);
                break;
            case 'Pre Iscritto':
                if ($statusPreIscritto1['s4_scale_preregistered'] == 1)
                    $courseAdd = new Course;
                $courseAdd = seatCourseAdd($courseId);
                break;
        }


        DB::table('vtiger_potential')
            ->where('potentialid', $subId)
            ->update(['potentialtype' => 'Assente (ex confermato)']);

        (new ModTracker)->trace($subId, 'Potentials', 'UPDATE', ['opportunity_type' => 'Assente (ex confermato)'], ['opportunity_type' => 'Confermato']);
    }
}
if (!function_exists('editionChanged')) {
    function editionChanged($subId, $contactId, $courseId)
    {
        $potentialType = new Subscription;
        $potentialType = getPotentialType($subId);
        $potentialType1 = (array)$potentialType[0];
        $statusPreIscritto = new Subscription;
        $statusPreIscritto = checkStatusPreIscritto();
        $statusPreIscritto1 = (array)$statusPreIscritto[0];
        switch ($potentialType1['potentialtype']) {
            case 'Confermato':
                $courseAdd = new Course;
                $courseAdd = seatCourseAdd($courseId);
                break;
            case 'Confermato attesa pagamento':
                $courseAdd = new Course;
                $courseAdd = seatCourseAdd($courseId);
                break;
            case 'Pre Iscritto':
                if ($statusPreIscritto1['s4_scale_preregistered'] == 1)
                    $courseAdd = new Course;
                $courseAdd = seatCourseAdd($courseId);
                break;
        }


        DB::table('vtiger_potential')
            ->where('potentialid', $subId)
            ->update(['potentialtype' => 'Cambio Edizione']);

        (new ModTracker)->trace($subId, 'Potentials', 'UPDATE', ['opportunity_type' => 'Cambio Edizione'], ['opportunity_type' => 'Confermato']);
    }
}
if (!function_exists('confirmedStatus')) {
    function confirmedStatus($subId, $contactId, $courseId)
    {
        $potentialType = new Subscription;
        $potentialType = getPotentialType($subId);
        $potentialType1 = (array)$potentialType[0];
        $statusPreIscritto = new Subscription;
        $statusPreIscritto = checkStatusPreIscritto();
        $statusPreIscritto1 = (array)$statusPreIscritto[0];
        switch ($potentialType1['potentialtype']) {
            case 'Confermato':
                $courseAdd = new Course;
                $courseAdd = seatCourseAdd($courseId);
                break;
            case 'Confermato attesa pagamento':
                $courseAdd = new Course;
                $courseAdd = seatCourseAdd($courseId);
                break;
            case 'Pre Iscritto':
                if ($statusPreIscritto1['s4_scale_preregistered'] == 1)
                    $courseAdd = new Course;
                $courseAdd = seatCourseAdd($courseId);
                break;
        }


        DB::table('vtiger_potential')
            ->where('potentialid', $subId)
            ->update(['potentialtype' => 'Confermato']);

        (new ModTracker)->trace($subId, 'Potentials', 'UPDATE', ['opportunity_type' => 'Confermato'], ['opportunity_type' => 'Confermato']);
    }
}
if (!function_exists('updateCrm')) {
    function updateCrm($moduleName, $crmId)
    {
        $sql = "update vtiger_crmentity set smownerid=?,modifiedby=?, modifiedtime=? where crmid=?";
        $portalUser = config('sailfor.portal_user');
        $params = [
            'smownerid' => $portalUser, // PortalUser
            'modifiedby' => $portalUser,
            'modifiedtime' => Carbon::now()->toDateTimeString(),
        ];
        DB::table('vtiger_crmentity')
            ->where('crmid', $crmId)
            ->update($params);
    }
}
if (!function_exists('waitingConfirm')) {
    function waitingConfirm($subId, $contactId, $courseId)
    {
        $potentialType = new Subscription;
        $potentialType = getPotentialType($subId);
        $potentialType1 = (array)$potentialType[0];
        $statusPreIscritto = new Subscription;
        $statusPreIscritto = checkStatusPreIscritto();
        $statusPreIscritto1 = (array)$statusPreIscritto[0];
        switch ($potentialType1['potentialtype']) {
            case 'Confermato':
                $courseAdd = new Course;
                $courseAdd = seatCourseAdd($courseId);
                break;
            case 'Confermato attesa pagamento':
                $courseAdd = new Course;
                $courseAdd = seatCourseAdd($courseId);
                break;
            case 'Pre Iscritto':
                if ($statusPreIscritto1['s4_scale_preregistered'] == 1)
                    $courseAdd = new Course;
                $courseAdd = seatCourseAdd($courseId);
                break;
        }


        DB::table('vtiger_potential')
            ->where('potentialid', $subId)
            ->update(['potentialtype' => 'Confermato attesa pagamento']);

        (new ModTracker)->trace($subId, 'Potentials', 'UPDATE', ['opportunity_type' => 'Confermato attesa pagamento'], ['opportunity_type' => 'Confermato']);
    }
}
