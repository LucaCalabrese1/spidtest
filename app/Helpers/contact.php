<?php

use App\Attachment;
use App\Category;
use App\Course;
use App\Helpers\DataHelper;
use App\Helpers\ProfessionHelper;
use App\Profession;
use App\S4Multilevel;
use App\Vtiger\CrmEntity;
use App\Vtiger\ModTracker\ModTracker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


if (!function_exists('getContactAllInformationsById')) {
    function getContactAllInformationsById($contactId)
    {
        $contactAllInformations = DB::table('vtiger_contactdetails')
            ->join('vtiger_crmentity', 'vtiger_contactdetails.contactid', '=', 'vtiger_crmentity.crmid')
            ->leftJoin('vtiger_contactaddress', 'vtiger_contactdetails.contactid', '=', 'vtiger_contactaddress.contactaddressid')
            ->leftJoin('vtiger_contactsubdetails', 'vtiger_contactdetails.contactid', '=', 'vtiger_contactsubdetails.contactsubscriptionid')
            ->leftJoin('vtiger_contacts_profession', 'vtiger_contactdetails.contactid', '=', 'vtiger_contacts_profession.contactid')
            ->select('vtiger_contactdetails.*', 'vtiger_contactaddress.*', 'vtiger_contactsubdetails.*', 'vtiger_contacts_profession.*')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_contactdetails.contactid', '=', $contactId]
            ])
            ->get();
        return $contactAllInformations;
    }
}
if (!function_exists('getContactPluckInfo')) {
    function getContactPluckInfo($contactId)
    {
        $contactAllInformations = DB::table('vtiger_contactdetails')
            ->join('vtiger_crmentity', 'vtiger_contactdetails.contactid', '=', 'vtiger_crmentity.crmid')
            ->leftJoin('vtiger_contactaddress', 'vtiger_contactdetails.contactid', '=', 'vtiger_contactaddress.contactaddressid')
            ->leftJoin('vtiger_contactsubdetails', 'vtiger_contactdetails.contactid', '=', 'vtiger_contactsubdetails.contactsubscriptionid')
            ->leftJoin('vtiger_contacts_profession', 'vtiger_contactdetails.contactid', '=', 'vtiger_contacts_profession.contactid')
            ->select(
                'vtiger_contactdetails.contactid',
                'vtiger_contactdetails.firstname',
                'vtiger_contactdetails.lastname',
                'vtiger_contactdetails.reportsto',
                'vtiger_contactdetails.s4_external',
                'vtiger_contactaddress.*',
                'vtiger_contactsubdetails.*',
                'vtiger_contacts_profession.*')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_contactdetails.contactid', '=', $contactId]
            ])->get();
        return $contactAllInformations;
    }
}
if (!function_exists('contact')) {
    function contact($contactId)
    {
        $contactAllData = getContactAllInformationsById($contactId);
        $contact = [];

        if ($contactAllData[0]->birthday === '0000-00-00') {
            $contactAllData[0]->birthday = '';
        }

        if ($contactAllData[0]->birthday === '') {
            $birthday = '';
        } else
            $birthday = DataHelper::dateFormat($contactAllData[0]->birthday);

        foreach ($contactAllData as $contactData) {
            $contact = [
                'id' => $contactId,
                'firstName' => $contactData->firstname,
                'lastName' => $contactData->lastname,
                'dateOfBirth' => $birthday,
                'placeOfBirth' => $contactData->s4_birthplace,
                'stateOfBirth' => $contactData->s4_birthdistrict,
                'gender' => $contactData->s4_gender,
                'nationOfBirth' => $contactData->s4_nationality,
                'fiscalCode' => $contactData->s4_taxcode,
                'qualification' => $contactData->s4_qualification,
                'email' => $contactData->email,
                'pec' => $contactData->s4_pec,
                'cell' => $contactData->mobile,
                'moodleUserId' => $contactData->s4_moodle_user,
                'professionsText' => $contactData->s4_professions,
                'external' => $contactData->s4_external,
                'agenastype' => $contactData->s4_agenastype,
                'supervisor' => $contactData->reportsto,
                'supervisorName' => getSupervisor($contactData->reportsto),
                'invoicing' => $contactData->s4invoicing,
                'address' => $contactData->mailingstreet,
                'reportsto' => $contactData->reportsto,
                'city' => $contactData->mailingcity,
                'zip' => $contactData->mailingzip,
                'state' => $contactData->mailingstate,
                'country' => $contactData->mailingcountry,
                'desc_incarico' => $contactData->department
            ];
        }
        return $contact;
    }
}
if (!function_exists('contactInfo')) {
    function contactInfo($contactId)
    {
        $contactAllData = getContactPluckInfo($contactId);
        $contact = [];


        foreach ($contactAllData as $contactData) {
            $contact = [
                'id' => $contactId,
                'firstName' => $contactData->firstname,
                'lastName' => $contactData->lastname,
                'external' => $contactData->s4_external,
                'supervisor' => $contactData->reportsto,
                'supervisorName' => getSupervisor($contactData->reportsto),
                'reportsto' => $contactData->reportsto,
            ];
        }
//        dd($contactAllData);
        return $contact;
    }
}

if (!function_exists('getSupervisor')) {
    function getSupervisor($contactId)
    {
        $contactAllData = getContactAllInformationsById($contactId);
        $contact = '';
        foreach ($contactAllData as $contactData) {
            $contact .= $contactData->lastname . ' ' . $contactData->firstname;
        }

        return $contact;
    }
}

if (!function_exists('getContactProfessions')) {
    function getContactProfessions($contactId)
    {
        $contactAllData = getContactAllInformationsById($contactId);
        $professions = [];
        foreach ($contactAllData as $contactData) {
            $professions[] = [
                'Id' => $contactData->s4_profession_id,
                's4_name' => getS4multilevel($contactData->s4_profession_id)
            ];
        }
        return $professions;
    }
}

if (!function_exists('getQualificationType')) {
    function getQualificationType()
    {
        return DB::table('vtiger_s4_qualification')->get();
    }
}
if (!function_exists('getAgenasType')) {
    function getAgenasType()
    {
        return DB::table('vtiger_s4_agenastype')->get();
    }
}
if (!function_exists('checkSupervisor')) {
    function checkSupervisor()
    {
        if (auth()->check()) {
            $userId = Auth()->user()->id;
            $result = DB::table('vtiger_contactdetails')
                ->join('vtiger_crmentity', 'vtiger_contactdetails.contactid', '=', 'vtiger_crmentity.crmid')
                ->select('reportsto')
                ->where([
                    ['vtiger_crmentity.deleted', '=', '0'],
                    ['vtiger_contactdetails.s4_external', '=', '0'],
                    ['vtiger_contactdetails.contactid', '=', $userId],
                    ['vtiger_contactdetails.reportsto', '=', '0'],
                ])
                ->first();
            return (isset($result->reportsto) && $result->reportsto == 0) ? true : false;
        }
    }

}
if (!function_exists('contacts')) {
    function contacts()
    {
        $userId = auth()->user()->id;
        $contacts = DB::table('vtiger_contactdetails')
            ->join('vtiger_crmentity', 'vtiger_contactdetails.contactid', '=', 'vtiger_crmentity.crmid')
            ->join('vtiger_contactaddress', 'vtiger_contactdetails.contactid', '=', 'vtiger_contactaddress.contactaddressid')
            ->join('vtiger_contactsubdetails', 'vtiger_contactdetails.contactid', '=', 'vtiger_contactsubdetails.contactsubscriptionid')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_contactdetails.s4_external', '<>', 1],
                ['vtiger_contactdetails.contactid', '<>', $userId]
            ])
            ->orderby('vtiger_contactdetails.lastname')
            ->get();

        return $contacts;
    }
}
if (!function_exists('internalContacts')) {
    function internalContacts()
    {
        $contacts = DB::table('vtiger_contactdetails')
            ->join('vtiger_crmentity', 'vtiger_contactdetails.contactid', '=', 'vtiger_crmentity.crmid')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_contactdetails.s4_external', '<>', 1],
                ['vtiger_contactdetails.reportsto', '!=', auth()->user()->id]
            ])
            ->orderby('vtiger_contactdetails.lastname')
            ->distinct()->get(['contactid', 'firstname', 'lastname']);
        return $contacts;
    }
}
if (!function_exists('checkFiscalCode')) {
    function checkFiscalCode($fiscalCode)
    {
        $cfChecker = DB::table('vtiger_contactdetails')
            ->join('vtiger_crmentity', 'vtiger_contactdetails.contactid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_contactdetails.s4_taxcode')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_contactdetails.s4_taxcode', '=', $fiscalCode]
            ])
            ->get();
        $check = (count($cfChecker) >= 1) ? false : true;
        return $check;
    }
}

if (!function_exists('whereBarcodeNumber')) {
    function whereBarcodeNumber($number)
    {
        $barcode = DB::table('vtiger_contactdetails')
            ->join('vtiger_crmentity', 'vtiger_contactdetails.contactid', '=', 'vtiger_crmentity.crmid')
            ->select('*')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_contactdetails.s4_barcode', '=', $number]
            ])
            ->get();

        $check = (count($barcode) == 0) ? false : true;
        return $check;
    }
}
if (!function_exists('checkProfessionExists')) {
    function checkProfessionExists($contactId, $s4multilevelId)
    {
        $checkProfession = DB::table('vtiger_contacts_profession')
            ->select('vtiger_contacts_profession.*')
            ->where([
                ['vtiger_contacts_profession.contactid', '=', $contactId],
                ['vtiger_contacts_profession.s4_profession_id', '=', $s4multilevelId]
            ])->get();
        $check = (count($checkProfession) >= 1) ? false : true;
        return $check;
    }
}
if (!function_exists('checkContactProfessionExists')) {
    function checkContactProfessionExists($contactId, $s4multilevelId)
    {
        $checkProfession = DB::table('vtiger_contacts_profession')
            ->select('vtiger_contacts_profession.*')
            ->where([
                ['vtiger_contacts_profession.contactid', '=', $contactId],
                ['vtiger_contacts_profession.s4_profession_id', '=', $s4multilevelId]
            ])->get();
        $check = (count($checkProfession) >= 1) ? false : true;
        return $check;
    }
}
if (!function_exists('isInternal')) {
    function isInternal()
    {
        if (auth()->check()) {
            $contactId = auth()->user()->id;

            $contact = DB::table('vtiger_contactdetails')
                ->join('vtiger_crmentity', 'vtiger_contactdetails.contactid', '=', 'vtiger_crmentity.crmid')
                ->join('vtiger_contactaddress', 'vtiger_contactdetails.contactid', '=', 'vtiger_contactaddress.contactaddressid')
                ->join('vtiger_contactsubdetails', 'vtiger_contactdetails.contactid', '=', 'vtiger_contactsubdetails.contactsubscriptionid')
                ->where([
                    ['vtiger_crmentity.deleted', '=', '0'],
                    ['vtiger_contactdetails.s4_external', '<>', 'On'],
                    ['vtiger_contactdetails.s4_external', '<>', 1],
                    ['vtiger_contactdetails.contactid', '=', $contactId]
                ])
                ->count();

            return ($contact == 1) ? true : false;

        }
    }
}
if (!function_exists('privacyCheck')) {
    function privacyCheck()
    {
        $user = auth()->user();

        if ($user) {
            $contactId = $user->id;

            $privacyChecker = DB::table('vtiger_contactdetails')
                ->join('vtiger_crmentity', 'vtiger_contactdetails.contactid', '=', 'vtiger_crmentity.crmid')
                ->select('vtiger_contactdetails.s4_privacy')
                ->where([
                    ['vtiger_crmentity.deleted', '=', '0'],
                    ['vtiger_contactdetails.contactid', '=', $contactId],
                    ['vtiger_contactdetails.s4_privacy', '=', '1']
                ])
                ->get();
            $check = (count($privacyChecker) >= 1) ? true : false;
            return $check;

        }

        return false;
    }
}
if (!function_exists('createContact')) {
    function createContact(array $attributes = [])

    {
        // Create CrmEntity Data.
        $moduleName = 'Contacts';
        $s4_nominativo = $attributes['lastname'] . ' ' . $attributes['firstname'] . '-' . $attributes['s4_taxcode'];
        $crmEntity = CRMEntity::create($moduleName, $s4_nominativo);
        DB::table('vtiger_contactdetails')->insert(
            [
                'contactid' => $crmEntity['crmEntityId'],
                'contact_no' => $crmEntity['sequenceNumber'],
                'lastname' => DataHelper::upperCase($attributes['lastname']),
                'firstname' => DataHelper::upperCase($attributes['firstname']),
                's4_nominative' => DataHelper::upperCase($s4_nominativo),
                's4_taxcode' => DataHelper::upperCase($attributes['s4_taxcode']),
                'email' => $attributes['email'],
                's4_external' => $attributes['s4_external'],
                's4_wstatus' => $attributes['s4_w_status'],
                'accountid' => $attributes['accountid'],
                's4_barcode' => $attributes['barcode'],
                's4_privacy' => $attributes['s4_privacy'] ?? '',
            ]
        );

        return $crmEntity;
    }
}
if (!function_exists('create_foreigner')) {
    function create_foreigner(array $attributes = [])

    {
        // Create CrmEntity Data.
        $moduleName = 'Contacts';
        $s4_nominativo = $attributes['lastname'] . ' ' . $attributes['firstname'] . '-' . $attributes['s4_taxcode'];
        $crmEntity = CRMEntity::create($moduleName, $s4_nominativo);
        DB::table('vtiger_contactdetails')->insert(
            [
                'contactid' => $crmEntity['crmEntityId'],
                'contact_no' => $crmEntity['sequenceNumber'],
                'lastname' => DataHelper::upperCase($attributes['lastname']),
                'firstname' => DataHelper::upperCase($attributes['firstname']),
                's4_nominative' => DataHelper::upperCase($s4_nominativo),
                's4_taxcode' => DataHelper::upperCase($attributes['s4_taxcode']),
                'email' => $attributes['email'],
                's4_external' => $attributes['s4_external'],
                's4_wstatus' => $attributes['s4_w_status'],
                'accountid' => $attributes['accountid'],
                's4_barcode' => $attributes['barcode'],
                's4_privacy' => $attributes['s4_privacy'],
                's4_gender' => $attributes['gender'],
                's4_birthplace' => $attributes['placeOfBirth'],
            ]
        );

        DB::table('vtiger_contactsubdetails')->insert([
            'contactsubscriptionid' => $crmEntity['crmEntityId'],
            'birthday' => DataHelper::dateFormatMysql($attributes['dateOfBirth']),
        ]);

        return $crmEntity;
    }
}
if (!function_exists('updateContact')) {
    function updateContact($cf, array $attributes = [])

    {
        $user = DB::table('vtiger_contactdetails')
            ->join('vtiger_crmentity', 'vtiger_contactdetails.contactid', '=', 'vtiger_crmentity.crmid')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_contactdetails.s4_taxcode', '=', $cf]
            ])
            ->update($attributes);

        return $user;
    }
}
if (!function_exists('updateContactById')) {
    function updateContactById($contactId, array $attributes = [])

    {

        $contactSubDetails = DB::table('vtiger_contactsubdetails')
            ->where('contactsubscriptionid', $contactId)
            ->first();

        if (!$contactSubDetails) {
            DB::table('vtiger_contactsubdetails')->insert([
                'contactsubscriptionid' => $contactId
            ]);
        }

        $contactAddress = DB::table('vtiger_contactaddress')
            ->where('contactaddressid', $contactId)
            ->first();

        if (!$contactAddress) {
            DB::table('vtiger_contactaddress')->insert([
                'contactaddressid' => $contactId
            ]);
        }

        $contact = DB::table('vtiger_contactdetails')
            ->join('vtiger_crmentity', 'vtiger_contactdetails.contactid', '=', 'vtiger_crmentity.crmid')
            ->leftJoin('vtiger_contactsubdetails', 'vtiger_contactdetails.contactid', '=', 'vtiger_contactsubdetails.contactsubscriptionid')
            ->leftJoin('vtiger_contactaddress', 'vtiger_contactdetails.contactid', '=', 'vtiger_contactaddress.contactaddressid')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_contactdetails.contactid', '=', $contactId]
            ])
            ->update($attributes);

        return $contact;
    }
}
if (!function_exists('updatePersonal')) {
    function updatePersonal($contactId, array $attributes)

    {
        // create date if not exist
        $birthday = DB::table('vtiger_contactsubdetails')
            ->join('vtiger_crmentity', 'vtiger_contactsubdetails.contactsubscriptionid', '=', 'vtiger_crmentity.crmid')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_contactsubdetails.contactsubscriptionid', '=', $contactId]
            ])->count();

        if (($birthday == 0) && (isset($attributes['birthday']))) {
            DB::table('vtiger_contactsubdetails')->insert(
                [
                    'contactsubscriptionid' => $contactId,
                    'birthday' => $attributes['birthday']
                ]);
        }
        // update if exist
         DB::table('vtiger_contactdetails')
            ->join('vtiger_crmentity', 'vtiger_contactdetails.contactid', '=', 'vtiger_crmentity.crmid')
            ->leftJoin('vtiger_contactaddress', 'vtiger_contactdetails.contactid', '=', 'vtiger_contactaddress.contactaddressid')
            ->leftJoin('vtiger_contactsubdetails', 'vtiger_contactdetails.contactid', '=', 'vtiger_contactsubdetails.contactsubscriptionid')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_contactdetails.contactid', '=', $contactId]
            ])
            ->update($attributes);

        // check Address
        $check = DB::table('vtiger_contactaddress')
            ->join('vtiger_crmentity', 'vtiger_contactaddress.contactaddressid', '=', 'vtiger_crmentity.crmid')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_contactaddress.contactaddressid', '=', $contactId]
            ])
            ->count();
        if ($check == 0 && !isset($attributes['s4_professions'])) {
            DB::table('vtiger_contactaddress')->insert([
                'contactaddressid' => $contactId,
                'mailingstreet' => isset($attributes['mailingstreet']) ? $attributes['mailingstreet'] : '',
                'mailingcity' => isset($attributes['mailingcity']) ? $attributes['mailingcity'] : '',
                'mailingzip' => isset($attributes['mailingzip']) ? $attributes['mailingzip'] : '',
                'mailingstate' => isset($attributes['mailingstate']) ? $attributes['mailingstate'] : '',
                'mailingcountry' => isset($attributes['mailingcountry']) ? $attributes['mailingcountry'] : '',
            ]);
        } elseif (!isset($attributes['s4_professions'])) {
            $contact = DB::table('vtiger_contactaddress')
                ->join('vtiger_crmentity', 'vtiger_contactaddress.contactaddressid', '=', 'vtiger_crmentity.crmid')
                ->where([
                    ['vtiger_crmentity.deleted', '=', '0'],
                    ['vtiger_contactaddress.contactaddressid', '=', $contactId]
                ])
                ->update([
                    'contactaddressid' => $contactId,
                    'mailingstreet' => isset($attributes['mailingstreet']) ? $attributes['mailingstreet'] : '',
                    'mailingcity' => isset($attributes['mailingcity']) ? $attributes['mailingcity'] : '',
                    'mailingzip' => isset($attributes['mailingzip']) ? $attributes['mailingzip'] : '',
                    'mailingstate' => isset($attributes['mailingstate']) ? $attributes['mailingstate'] : '',
                    'mailingcountry' => isset($attributes['mailingcountry']) ? $attributes['mailingcountry'] : '',
                ]);
        }
        return true;
    }
}
if (!function_exists('updatePersonalCollaborator')) {
    function updatePersonalCollaborator($collaboratorId, array $attributes)

    {
        // create date if not exist
        $birthday = DB::table('vtiger_contactsubdetails')
            ->join('vtiger_crmentity', 'vtiger_contactsubdetails.contactsubscriptionid', '=', 'vtiger_crmentity.crmid')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_contactsubdetails.contactsubscriptionid', '=', $collaboratorId]
            ])->count();

        if (($birthday == 0) && (isset($attributes['birthday']))) {
            DB::table('vtiger_contactsubdetails')->insert(
                [
                    'contactsubscriptionid' => $collaboratorId,
                    'birthday' => $attributes['birthday']
                ]);
        }
        // update if exist
        DB::table('vtiger_contactdetails')
            ->join('vtiger_crmentity', 'vtiger_contactdetails.contactid', '=', 'vtiger_crmentity.crmid')
            ->leftJoin('vtiger_contactaddress', 'vtiger_contactdetails.contactid', '=', 'vtiger_contactaddress.contactaddressid')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_contactdetails.contactid', '=', $collaboratorId]
            ])
            ->update($attributes);

        // check Address
        $check = DB::table('vtiger_contactaddress')
            ->join('vtiger_crmentity', 'vtiger_contactaddress.contactaddressid', '=', 'vtiger_crmentity.crmid')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_contactaddress.contactaddressid', '=', $collaboratorId]
            ])
            ->count();
        if ($check == 0 && !isset($attributes['s4_professions'])) {
            DB::table('vtiger_contactaddress')->insert([
                'contactaddressid' => $collaboratorId,
                'mailingstreet' => isset($attributes['mailingstreet']) ? $attributes['mailingstreet'] : '',
                'mailingcity' => isset($attributes['mailingcity']) ? $attributes['mailingcity'] : '',
                'mailingzip' => isset($attributes['mailingzip']) ? $attributes['mailingzip'] : '',
                'mailingstate' => isset($attributes['mailingstate']) ? $attributes['mailingstate'] : '',
                'mailingcountry' => isset($attributes['mailingcountry']) ? $attributes['mailingcountry'] : '',
            ]);
        } elseif (!isset($attributes['s4_professions'])) {
            $contact = DB::table('vtiger_contactaddress')
                ->join('vtiger_crmentity', 'vtiger_contactaddress.contactaddressid', '=', 'vtiger_crmentity.crmid')
                ->where([
                    ['vtiger_crmentity.deleted', '=', '0'],
                    ['vtiger_contactaddress.contactaddressid', '=', $collaboratorId]
                ])
                ->update([
                    'contactaddressid' => $collaboratorId,
                    'mailingstreet' => isset($attributes['mailingstreet']) ? $attributes['mailingstreet'] : '',
                    'mailingcity' => isset($attributes['mailingcity']) ? $attributes['mailingcity'] : '',
                    'mailingzip' => isset($attributes['mailingzip']) ? $attributes['mailingzip'] : '',
                    'mailingstate' => isset($attributes['mailingstate']) ? $attributes['mailingstate'] : '',
                    'mailingcountry' => isset($attributes['mailingcountry']) ? $attributes['mailingcountry'] : '',
                ]);
        }
        return true;
    }
}
if (!function_exists('updateMoodleId')) {
    function updateMoodleId($contactId, array $attributes)

    {
        $contact = DB::table('vtiger_contactdetails')
            ->join('vtiger_crmentity', 'vtiger_contactdetails.contactid', '=', 'vtiger_crmentity.crmid')
            ->leftJoin('vtiger_contactaddress', 'vtiger_contactdetails.contactid', '=', 'vtiger_contactaddress.contactaddressid')
            ->leftJoin('vtiger_contactsubdetails', 'vtiger_contactdetails.contactid', '=', 'vtiger_contactsubdetails.contactsubscriptionid')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_contactdetails.contactid', '=', $contactId]
            ])
            ->update($attributes);
    }
}
if (!function_exists('addProfession')) {
    function addProfession($contactId, $s4multilevelId)

    {
        $params = [$contactId, $s4multilevelId];
        $contact = DB::insert('insert into vtiger_contacts_profession (contactid, s4_profession_id) values (?, ?)', $params);
        professionsText($contactId);
        return true;
    }
}
if (!function_exists('deleteProfession')) {
    function deleteProfession($contactId, $s4multilevelId)

    {
        $params = [$contactId, $s4multilevelId];
        $contact = DB::delete('delete from vtiger_contacts_profession where contactid = ? AND s4_profession_id = ?', $params);
        professionsText($contactId);
        return true;
    }
}
if (!function_exists('professionsText')) {
    function professionsText($contactId)

    {
        $contactProfesisonsData = (new Profession)->getContactProfessionsForUpdate($contactId);
        $contactProfessions = ProfessionHelper::viewProfession($contactProfesisonsData);
        $contactProfessions = implode(', ', $contactProfessions);

        $attributes = [
            's4_professions' => (string)$contactProfessions
        ];
        (new ModTracker)->trace($contactId, 'Contacts', 'UPDATE', $attributes);

        updatePersonal($contactId, $attributes);
        return true;
    }
}

if (!function_exists('mandatoryFields')) {

    function mandatoryFields()
    {
        $userId = Auth()->user()->id;

        $fields = config('sailfor.mandatory');
//        dd($fields);
        $contactData = contact($userId);
        $checker = [];
        foreach ($fields as $alias => $field) {
            if ($contactData[$alias] == NULL && Str::contains($field, 'required')) {
                $checker[$alias] = [
                    'check' => true,
                    'field' => $alias,
                ];
            }
        }
        return $checker;
    }
}
if (!function_exists('contactRequired')) {
    function contactRequired($fieldName)

    {
        $fields = config('sailfor.mandatory');
        $content = $fields[$fieldName];
        if ($fieldName == 'fiscalCode') {
            $content = 'required';
        }
        return (array_key_exists($fieldName, $fields) && Str::contains($content, 'required'));
    }
}

if (!function_exists('clean')) {
    function clean(array $attributes)

    {
        $keyMap = [
            'firstname' => 'firstName',
            'lastname' => 'lastName',
            'birthday' => 'dateOfBirth',
            's4_birthplace' => 'placeOfBirth',
            's4_birthdistrict' => 'stateOfBirth',
            's4_gender' => 'gender',
            's4_nationality' => 'nationOfBirth',
            's4_taxcode' => 'fiscalCode',
            'email' => 'mail', // NOT Working Same Name
            's4_pec' => 'pec',
            'mobile' => 'cell', // Not WOrking Same Name
            's4_agenastype' => 'agenastype',
            's4_qualification' => 'qualification',
            'reportsto' => 'supervisor',
            's4invoicing' => 'invoicing',
            'mailingstreet' => 'address',
            'mailingcity' => 'city',
            'mailingzip' => 'zip',
            'mailingstate' => 'state',
            'mailingcountry' => 'country',
        ];

        // Key Maps
        foreach ($attributes as $key => $contact) {
            unset($attributes['_token']);
            if ($contact == null) {
                //unset($attributes[$key]);
                $attributes[$key] = "";
            }
            foreach ($keyMap as $dbKey => $requestKey) {
                if (($requestKey == $key) && isset($attributes[$key])) {
                    $attributes[$dbKey] = $attributes[$key];
                    unset($attributes[$key]);
                }
            }
        }
        // Data Valdiation
        foreach ($attributes as $key => $value) {
            if ($key == 'birthday' && $value != "" && isset($attributes[$key])) {
                $attributes[$key] = DataHelper::dateFormatMysql($value);
            } elseif ($key != 'email' &&
                $key != 's4_pec' &&
                $key != 'mobile' &&
                $key != 's4_gender' &&
                $key != 's4_agenastype' &&
                $key != 's4_qualification') {
                $attributes[$key] = DataHelper::upperCase($value);
            }
        }

        return $attributes;
    }
}
if (!function_exists('privacySubscribe')) {
    function privacySubscribe()

    {
        $user = auth()->user();

        if ($user) {
            $contactId = $user->id;

            $contact = DB::table('vtiger_contactdetails')
                ->join('vtiger_crmentity', 'vtiger_contactdetails.contactid', '=', 'vtiger_crmentity.crmid')
                ->where([
                    ['vtiger_crmentity.deleted', '=', '0'],
                    ['vtiger_contactdetails.contactid', '=', $contactId]
                ])
                ->update([
                    's4_privacy' => '1'
                ]);

        }

        return false;
    }
}
if (!function_exists('getAccountByIdRaw')) {
    function getAccountByIdRaw($accountId)
    {
        $account = DB::table('vtiger_account')
            ->join('vtiger_crmentity', 'vtiger_account.accountid', '=', 'vtiger_crmentity.crmid')
            ->join('vtiger_accountbillads', 'vtiger_account.accountid', '=', 'vtiger_accountbillads.accountaddressid')
            ->join('vtiger_accountshipads', 'vtiger_account.accountid', '=', 'vtiger_accountshipads.accountaddressid')
            ->select('vtiger_account.*', 'vtiger_accountbillads.*', 'vtiger_accountshipads.*')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_account.accountid', '=', $accountId],
            ])->get();
        return $account;
    }
}
if (!function_exists('accountAdress')) {
function accountAdress($accountId)
{
    $accountsData = getAccountByIdRaw($accountId);
    $accountAdress = [];
    foreach ($accountsData as $key => $accountData) {
        $accountAdress = [
            'structure' => $accountData->accountname,
            'adress' => $accountData->bill_street,
            'city' => $accountData->bill_city,
            'state' => $accountData->bill_state,
            'country' => $accountData->bill_country,
            'zipCode' => $accountData->bill_code,
            'pobox' => $accountData->bill_pobox, // CAP Dentro Sailfor
            'email' => $accountData->email1,
            'phone' => $accountData->phone,
            'otherphone' => $accountData->otherphone, // Phone Dentro Sailfor
            'fax' => $accountData->fax,
            'website' => $accountData->website
        ];
    }
    return $accountAdress;
}
}
if (!function_exists('getAccountRaw')) {
    function getAccountRaw()
    {
        $account = DB::table('vtiger_account')
            ->join('vtiger_crmentity', 'vtiger_account.accountid', '=', 'vtiger_crmentity.crmid')
            ->join('vtiger_accountbillads', 'vtiger_account.accountid', '=', 'vtiger_accountbillads.accountaddressid')
            ->join('vtiger_accountshipads', 'vtiger_account.accountid', '=', 'vtiger_accountshipads.accountaddressid')
            ->select('vtiger_account.*', 'vtiger_accountbillads.*', 'vtiger_accountshipads.*')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_account.s4showonportal', '=', '1'],
            ])->get();
        return $account;
    }
}
if (!function_exists('accounts')) {
    function accounts()
    {
        $accountsData = getAccountRaw();
        $accounts = [];
        foreach ($accountsData as $key => $accountData) {
            $accounts[] = [
                'accountid' => $accountData->accountid,
                'accountname' => $accountData->accountname
            ];
        }
        return $accounts;
    }
}
if (!function_exists('reqInputContact')) {
    function reqInputContact()
    {
        $reqInput = \request()->input('reportsto');
        $contactRepo = contact($reqInput)['reportsto'];
        $contactResponsible = contact($contactRepo );
        return $contactResponsible;
    }
}
