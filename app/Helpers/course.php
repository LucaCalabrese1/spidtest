<?php

use App\Account;
use App\Accreditation;
use App\Assignment;
use App\Attachment;
use App\Cms;
use App\Course;
use App\Contact;
use App\Helpers\DataHelper;
use App\Location;
use App\Moodle\Faddy;
use App\Vtiger\ModTracker\ModTracker;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

if (!function_exists('courseRawById')) {
    function courseRawById($courseId)
    {
        $today = Carbon::today()->toDateString();

        $course = DB::table('vtiger_products')
            ->join('vtiger_crmentity', 'vtiger_products.productid', '=', 'vtiger_crmentity.crmid')
            ->leftJoin('vtiger_account', 'vtiger_account.accountid', '=', 'vtiger_products.commitente_azienda')
            ->select('vtiger_products.*', 'vtiger_account.accountname as committente', 'vtiger_crmentity.description as subtitle',
                DB::raw('"0" as isStorico'))
            ->where([
                ['vtiger_products.showonwebsite', '=', '1'],
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_products.productid', '=', $courseId],
                ['vtiger_products.start_date', '<=', $today],
                ['vtiger_products.expiry_date', '>=', $today]
            ])
            ->orderBy('vtiger_products.s4_start_date', 'desc')
            ->get();
        return $course;
    }


}
if (!function_exists('getCourseAttachments')) {
    function getCourseAttachments($courseId)
    {
        $courseRawById = courseRawById($courseId);
        $courseAttachments = [];
        $attachments_directory = config('sailfor.attachments_directory');
        $sailforUrl = config('sailfor.url');
        foreach ($courseRawById as $key => $courseRaw) {

            if (!empty($courseRaw->s4_cv_relators)) {
                if (env('APP_NAME') == 'Coformed') {
                    $courseAttachments['course.LBL_CURRICULUM_COFORMED'] = $sailforUrl . $attachments_directory . $courseRaw->s4_cv_relators;
                } else {
                    $courseAttachments['course.LBL_CURRICULUM'] = $sailforUrl . $attachments_directory . $courseRaw->s4_cv_relators;
                }
            }
            if (!empty($courseRaw->s4_program_attachment)) {
                if (env('APP_NAME') == 'Coformed') {
                    $courseAttachments['course.LBL_DEPLIANT_COFORMED'] = $sailforUrl . $attachments_directory . $courseRaw->s4_program_attachment;
                } else {
                    $courseAttachments['course.LBL_DEPLIANT'] = $sailforUrl . $attachments_directory . $courseRaw->s4_program_attachment;

                }
            }

        }

        return $courseAttachments;
    }

}
if (!function_exists('getCourseAttachmentsBySub')) {
    function getCourseAttachmentsBySub($courseId)
    {
        $courseRawById = courseRawByIdBySub($courseId);
        $courseAttachments = '';
        $attachments_directory = config('sailfor.attachments_directory');
        $sailforUrl = config('sailfor.url');
        foreach ($courseRawById as $key => $courseRaw) {
            if (!empty($courseRaw->s4_program_attachment)) {
                $courseAttachments = $sailforUrl . $attachments_directory . $courseRaw->s4_program_attachment;
            }
        }

        return $courseAttachments;
    }

}
if (!function_exists('courseRawByIdBySub')) {
    function courseRawByIdBySub($courseId)
    {
        $today = Carbon::today()->toDateString();

        $course = DB::table('vtiger_products')
            ->join('vtiger_crmentity', 'vtiger_products.productid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_products.*',
                DB::raw('"0" as isStorico'))
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_products.productid', '=', $courseId],
            ])
            ->orderBy('vtiger_products.s4_start_date', 'desc')
            ->get();
        return $course;
    }


}
if (!function_exists('courseByIdAll')) {
    function courseByIdAll($courseId)
    {
        $courseRawById = courseRawByIdAll($courseId);
        $attachments = new Attachment;
        $course = [];
        foreach ($courseRawById as $key => $courseRaw) {
            $imgUrl = new Attachment;
            $imgUrl = getAttachemtImageById($courseRaw->productid);

            $course = [
                'id' => $courseRaw->productid,
                'title' => $courseRaw->fulldescription, // Limit text to 30
                'description' => $courseRaw->s4_rich_description, // Limit text to 60
                'start' => DataHelper::dateFormat($courseRaw->s4_start_date), // display date dd/mm/Y
                'startHour' => DataHelper::hourFormat($courseRaw->s4_start_hour),
                'end' => DataHelper::dateFormat($courseRaw->s4_end_date), // display date dd/mm/Y
                'endHour' => DataHelper::hourFormat($courseRaw->s4_end_hour),
                'subsStart' => DataHelper::dateFormat($courseRaw->sales_start_date),
                'subsEnd' => DataHelper::dateFormat($courseRaw->sales_end_date),
                'code' => $courseRaw->s4_code,
                'seats' => $courseRaw->qtyinstock,
                'credits' => $courseRaw->s4_crediti,
                'days' => $courseRaw->s4_expected_days_number,
                'location' => (new Location)->location($courseRaw->s4_location), // to get the location
                'expectedDays' => $courseRaw->s4_expected_days_number,
                'hoursAmount' => $courseRaw->s4_hours_amount,
                'seatsAvailable' => $courseRaw->qtyinstock,
                'price' => DataHelper::priceFormat($courseRaw->unit_price),
                'priceRaw' => $courseRaw->unit_price,
                'requirements' => $courseRaw->s4_part_requirements,
                'defaultescrizione' => $courseRaw->s4_defaultescrizione,
                'imgUrl' => $imgUrl,
                'attachments' => getCourseAttachments($courseRaw->productid),
                'isElearning' => $courseRaw->abilita_elearning,
                'moodleCourseId' => $courseRaw->s4_moodle_course,
                'edition' => $courseRaw->s4_edizione,
                'isStorico' => $courseRaw->isStorico,
                'active_survey' => $courseRaw->s4_active_survey,
                'shortdescription' => $courseRaw->shortdescription,
                'external_link' => $courseRaw->website,
                'subtitle' => $courseRaw->subtitle,
            ];
        }
        return $course;
    }

}
if (!function_exists('courseRawByIdAll')) {
    function courseRawByIdAll($courseId)
    {
        $course = DB::table('vtiger_products')
            ->join('vtiger_crmentity', 'vtiger_products.productid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_products.*', 'vtiger_crmentity.description as subtitle',
                DB::raw('"0" as isStorico'))
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_products.productid', '=', $courseId]
            ])
            ->orderBy('vtiger_products.s4_start_date', 'asc')
            ->get();
        return $course;
    }


}
if (!function_exists('courseByIdHistory')) {
    function courseByIdHistory($courseId)
    {
        $courseRawById = courseRawByIdHistory($courseId);
        $attachments = new Attachment;
        $course = [];
        foreach ($courseRawById as $key => $courseRaw) {
            $location = [];
            if (!$courseRaw->isStorico) {
                $location = (new Location)->location($courseRaw->s4_location);
            } else {
                $location = $courseRaw->s4_location;
            }
            $imgUrl = new Attachment;
            $imgUrl = getAttachemtImageById($courseRaw->productid);

            $course = [
                'id' => $courseRaw->productid,
                'title' => $courseRaw->fulldescription, // Limit text to 30
                'description' => $courseRaw->s4_rich_description, // Limit text to 60
                'start' => DataHelper::dateFormat($courseRaw->s4_start_date), // display date dd/mm/Y
                'startHour' => DataHelper::hourFormat($courseRaw->s4_start_hour),
                'end' => DataHelper::dateFormat($courseRaw->s4_end_date), // display date dd/mm/Y
                'endHour' => DataHelper::hourFormat($courseRaw->s4_end_hour),
                'subsStart' => DataHelper::dateFormat($courseRaw->sales_start_date),
                'subsEnd' => DataHelper::dateFormat($courseRaw->sales_end_date),
                'code' => $courseRaw->s4_code,
                'credits' => $courseRaw->s4_crediti,
                'days' => $courseRaw->s4_expected_days_number,
                'location' => $location, // to get the location
                'expectedDays' => $courseRaw->s4_expected_days_number,
                'hoursAmount' => $courseRaw->s4_hours_amount,
                'seatsAvailable' => $courseRaw->qtyinstock,
                'price' => DataHelper::priceFormat($courseRaw->unit_price),
                'priceRaw' => $courseRaw->unit_price,
                'requirements' => $courseRaw->s4_part_requirements,
                'defaultescrizione' => $courseRaw->s4_defaultescrizione,
                'imgUrl' => $imgUrl,
                'isFree' => (round($courseRaw->unit_price, 2) == 0) ? true : false,
                'attachments' => getCourseAttachments($courseRaw->productid),
                'isElearning' => $courseRaw->abilita_elearning,
                'moodleCourseId' => $courseRaw->s4_moodle_course,
                'reserved' => $courseRaw->s4_reserved,
                'overbooking' => overbooking($courseRaw->productid),
                'edition' => $courseRaw->s4_edizione,
                'isStorico' => $courseRaw->isStorico,
                'obiettiviformativi' => $courseRaw->obiettiviformativi,
                'normativa' => $courseRaw->normativa,
                'professioni' => $courseRaw->professioni,
            ];
        }
        return $course;
    }

}
if (!function_exists('courseRawByIdHistory')) {
    function courseRawByIdHistory($courseId)
    {
        // $today = Carbon::today()->toDateString();
        // $course = DB::table('vtiger_products')
        // ->join('vtiger_crmentity', 'vtiger_products.productid', '=', 'vtiger_crmentity.crmid')
        // ->select('vtiger_products.*',
        // DB::raw('"0" as isStorico'))
        // ->where([
        //     ['vtiger_products.showonwebsite', '=', '1'],
        //     ['vtiger_crmentity.deleted', '=', '0'],
        //     ['vtiger_products.discontinued', '=', '0'],
        //     ['vtiger_products.productid', '=', $courseId]
        // ])
        // ->orderBy('vtiger_products.s4_start_date', 'fulldescription')
        // ->get();
        //DB::enableQueryLog();
        $newHistoryCourse = DB::table('vtiger_storicocorsi')
            ->select('vtiger_storicocorsi.storicocorsiid as productid',
                'vtiger_storicocorsi.titolocorso as shortdescription',
                'vtiger_storicocorsi.titolocorso as fulldescription',
                'vtiger_storicocorsi.datainizio as s4_start_date',
                'vtiger_storicocorsi.datafine as s4_end_date',
                DB::raw('"" as s4_start_hour'),
                DB::raw('"" as s4_end_hour'),
                DB::raw('"" as sales_start_date'),
                DB::raw('"" as sales_end_date'),
                'vtiger_storicocorsi.codice as s4_code',
                DB::raw('"" as qtyinstock'),
                'vtiger_storicocorsi.crediti as s4_crediti',
                'vtiger_storicocorsi.giorniprevisti as s4_expected_days_number',
                'vtiger_storicocorsi.sedecorsuale as s4_location',
                DB::raw('"" as s4_hours_amount'),
                'vtiger_storicocorsi.descrizionecorso as s4_rich_description',
                DB::raw('"0.00" as unit_price'),
                DB::raw('"" as s4_part_requirements'),
                DB::raw('"0" as abilita_elearning'),
                DB::raw('"" as s4_defaultescrizione'),
                DB::raw('"" as s4_moodle_course'),
                DB::raw('"0" as s4_reserved'),
                DB::raw('"1" as isStorico'),
                DB::raw('"0" as s4_edizione'),
                DB::raw('"" as s4_cv_relators'),
                DB::raw('"" as s4_program_attachment'),
                'vtiger_storicocorsi.obiettiviformativi as obiettiviformativi',
                'vtiger_storicocorsi.normativa as normativa',
                'vtiger_storicocorsi.professioni as professioni')
            ->join('vtiger_crmentity', 'vtiger_storicocorsi.storicocorsiid', '=', 'vtiger_crmentity.crmid')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_storicocorsi.storicocorsiid', '=', $courseId]
            ]);

        $course = DB::table('vtiger_products')
            ->join('vtiger_crmentity', 'vtiger_products.productid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_products.productid',
                'vtiger_products.shortdescription',
                'vtiger_products.fulldescription',
                'vtiger_products.s4_start_date',
                'vtiger_products.s4_end_date',
                'vtiger_products.s4_start_hour',
                'vtiger_products.s4_end_hour',
                'vtiger_products.sales_start_date',
                'vtiger_products.sales_end_date',
                'vtiger_products.s4_code',
                'vtiger_products.qtyinstock',
                'vtiger_products.s4_crediti',
                'vtiger_products.s4_expected_days_number',
                'vtiger_products.s4_location',
                'vtiger_products.s4_hours_amount',
                'vtiger_products.s4_rich_description',
                'vtiger_products.unit_price',
                'vtiger_products.s4_part_requirements',
                'vtiger_products.s4_defaultescrizione',
                'vtiger_products.abilita_elearning',
                'vtiger_products.s4_moodle_course',
                'vtiger_products.s4_reserved',
                DB::raw('"0" as isStorico'),
                'vtiger_products.s4_edizione',
                'vtiger_products.s4_cv_relators',
                'vtiger_products.s4_program_attachment',
                DB::raw('"" as obiettiviformativi'),
                DB::raw('"" as normativa'),
                DB::raw('"" as professioni'))
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_products.discontinued', '=', '0'],
                ['vtiger_products.productid', '=', $courseId]
            ])
            ->union($newHistoryCourse)
            ->orderBy('s4_start_date', 'desc')
            ->get();

        return $course;
    }


}
if (!function_exists('overbooking')) {
    function overbooking($courseId)
    {
        $course = courseById($courseId);
        $today = Carbon::today();
        if (!empty($course['subsStart']) && !empty($course['subsEnd'])) {
            $subsStart = Carbon::createFromFormat('d/m/Y', $course['subsStart']);
            $subsEnd = Carbon::createFromFormat('d/m/Y', $course['subsEnd']);
            $overbooking = $course['overbooking'];
            if (($today->gte($subsStart)) && ($today->lte($subsEnd))
                &&
                ($overbooking == '1') || ($overbooking == 'On')) {
                return true;
            }
        }

        return false;
    }

}
if (!function_exists('coursesRawHistory')) {
    function coursesRawHistory($year = null)
    {
        if ($year != null) {
            $today = Carbon::today()->toDateString();
            $newHistoryCourses = DB::table('vtiger_storicocorsi')
                ->select('vtiger_storicocorsi.storicocorsiid as productid',
                    'vtiger_storicocorsi.titolocorso as shortdescription',
                    'vtiger_storicocorsi.titolocorso as fulldescription',
                    'vtiger_storicocorsi.titolocorso as productname',
                    'vtiger_storicocorsi.datainizio as s4_start_date',
                    'vtiger_storicocorsi.datafine as s4_end_date',
                    DB::raw('"" as sales_start_date'),
                    DB::raw('"" as sales_end_date'),
                    'vtiger_storicocorsi.codice as s4_code',
                    DB::raw('"" as qtyinstock'),
                    'vtiger_storicocorsi.crediti as s4_crediti',
                    'vtiger_storicocorsi.annocompetenza as s4_year',
                    'vtiger_storicocorsi.giorniprevisti as s4_expected_days_number',
                    'vtiger_storicocorsi.sedecorsuale as s4_location',
                    'vtiger_storicocorsi.descrizionecorso as s4_rich_description',
                    DB::raw('"0.00" as unit_price'),
                    DB::raw('"0" as abilita_elearning'),
                    DB::raw('"0" as s4_reserved'),
                    DB::raw('"1" as isStorico'),
                    DB::raw('"0" as s4_edizione'),
                    'vtiger_storicocorsi.obiettiviformativi as obiettiviformativi',
                    'vtiger_storicocorsi.normativa as normativa',
                    'vtiger_storicocorsi.professioni as professioni')
                ->join('vtiger_crmentity', 'vtiger_storicocorsi.storicocorsiid', '=', 'vtiger_crmentity.crmid')
                ->where([
                    ['vtiger_crmentity.deleted', '=', '0'],
                    ['vtiger_storicocorsi.s4_attestato_inviato_status', '=', '1'],
                    ['vtiger_storicocorsi.annocompetenza', '=', $year],
                ]);

            $courses = DB::table('vtiger_products')
                ->join('vtiger_crmentity', 'vtiger_products.productid', '=', 'vtiger_crmentity.crmid')
                ->select('vtiger_products.productid',
                    'vtiger_products.shortdescription',
                    'vtiger_products.fulldescription',
                    'vtiger_products.productname',
                    'vtiger_products.s4_start_date',
                    'vtiger_products.s4_end_date',
                    'vtiger_products.sales_start_date',
                    'vtiger_products.sales_end_date',
                    'vtiger_products.s4_code',
                    'vtiger_products.qtyinstock',
                    'vtiger_products.s4_crediti',
                    'vtiger_products.s4_year',
                    'vtiger_products.s4_expected_days_number',
                    'vtiger_products.s4_location',
                    'vtiger_products.s4_rich_description',
                    'vtiger_products.unit_price',
                    'vtiger_products.abilita_elearning',
                    'vtiger_products.s4_reserved',
                    DB::raw('"0" as isStorico'),
                    'vtiger_products.s4_edizione',
                    DB::raw('"" as obiettiviformativi'),
                    DB::raw('"" as normativa'),
                    DB::raw('"" as professioni'))
                ->where([
                    ['vtiger_products.showonwebsite', '=', '1'],
                    ['vtiger_crmentity.deleted', '=', '0'],
                    ['vtiger_products.discontinued', '=', '0'],
                    ['vtiger_products.s4_end_date', '<', $today],
                    ['vtiger_products.s4_year', '=', $year],
                ])->union($newHistoryCourses);
        } else {
            $today = Carbon::today()->toDateString();
            $newHistoryCourses = DB::table('vtiger_storicocorsi')
                ->select('vtiger_storicocorsi.storicocorsiid as productid',
                    'vtiger_storicocorsi.titolocorso as shortdescription',
                    'vtiger_storicocorsi.titolocorso as fulldescription',
                    'vtiger_storicocorsi.titolocorso as productname',
                    'vtiger_storicocorsi.datainizio as s4_start_date',
                    'vtiger_storicocorsi.datafine as s4_end_date',
                    DB::raw('"" as sales_start_date'),
                    DB::raw('"" as sales_end_date'),
                    'vtiger_storicocorsi.codice as s4_code',
                    DB::raw('"" as qtyinstock'),
                    'vtiger_storicocorsi.crediti as s4_crediti',
                    'vtiger_storicocorsi.annocompetenza as s4_year',
                    'vtiger_storicocorsi.giorniprevisti as s4_expected_days_number',
                    'vtiger_storicocorsi.sedecorsuale as s4_location',
                    'vtiger_storicocorsi.descrizionecorso as s4_rich_description',
                    DB::raw('"0.00" as unit_price'),
                    DB::raw('"0" as abilita_elearning'),
                    DB::raw('"0" as s4_reserved'),
                    DB::raw('"1" as isStorico'),
                    DB::raw('"0" as s4_edizione'),
                    'vtiger_storicocorsi.obiettiviformativi as obiettiviformativi',
                    'vtiger_storicocorsi.normativa as normativa',
                    'vtiger_storicocorsi.professioni as professioni')
                ->join('vtiger_crmentity', 'vtiger_storicocorsi.storicocorsiid', '=', 'vtiger_crmentity.crmid')
                ->where([
                    ['vtiger_crmentity.deleted', '=', '0'],
                    ['vtiger_storicocorsi.s4_attestato_inviato_status', '=', '1'],
                ]);

            $courses = DB::table('vtiger_products')
                ->join('vtiger_crmentity', 'vtiger_products.productid', '=', 'vtiger_crmentity.crmid')
                ->select('vtiger_products.productid',
                    'vtiger_products.shortdescription',
                    'vtiger_products.fulldescription',
                    'vtiger_products.productname',
                    'vtiger_products.s4_start_date',
                    'vtiger_products.s4_end_date',
                    'vtiger_products.sales_start_date',
                    'vtiger_products.sales_end_date',
                    'vtiger_products.s4_code',
                    'vtiger_products.qtyinstock',
                    'vtiger_products.s4_crediti',
                    'vtiger_products.s4_year',
                    'vtiger_products.s4_expected_days_number',
                    'vtiger_products.s4_location',
                    'vtiger_products.s4_rich_description',
                    'vtiger_products.unit_price',
                    'vtiger_products.abilita_elearning',
                    'vtiger_products.s4_reserved',
                    DB::raw('"0" as isStorico'),
                    'vtiger_products.s4_edizione',
                    DB::raw('"" as obiettiviformativi'),
                    DB::raw('"" as normativa'),
                    DB::raw('"" as professioni'))
                ->where([
                    ['vtiger_products.showonwebsite', '=', '1'],
                    ['vtiger_crmentity.deleted', '=', '0'],
                    ['vtiger_products.discontinued', '=', '0'],
                    ['vtiger_products.s4_end_date', '<', $today],
                ])->union($newHistoryCourses);
        }
        $querySql = $courses->toSql();
        $groupBy = DB::table(DB::raw("($querySql) as a"))->mergeBindings($courses)->groupBy('productname', 's4_start_date', 's4_year')->orderBy('s4_start_date', 'desc')->get();
        return $groupBy;
    }


}
if (!function_exists('coursesActive')) {
    function coursesRawId($productId)
    {
        $today = Carbon::today()->toDateString();
        $courses = DB::table('vtiger_products')
            ->join('vtiger_crmentity', 'vtiger_products.productid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_products.*',
                DB::raw('"0" as isStorico'))
            ->where([
                ['vtiger_products.showonwebsite', '=', '1'],
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_products.discontinued', '=', '1'],
                ['vtiger_products.productid', '=', $productId],
                ['vtiger_products.start_date', '<=', $today],
                ['vtiger_products.expiry_date', '>=', $today]
            ])
            ->orderBy('vtiger_products.s4_start_date', 'desc')
            ->get();
        return $courses;

    }

}
if (!function_exists('coursesActive')) {
    function coursesActive()
    {
        $today = Carbon::today()->toDateString();
        $courses = DB::table('vtiger_products')
            ->join('vtiger_crmentity', 'vtiger_products.productid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_products.*',
                DB::raw('"0" as isStorico'))
            ->where([
                ['vtiger_products.showonwebsite', '=', '1'],
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_products.discontinued', '=', '1'],
                ['vtiger_products.start_date', '<=', $today],
                ['vtiger_products.expiry_date', '>=', $today]
            ])
            ->orderBy('vtiger_products.s4_start_date', 'desc')
            ->get();
        return $courses;

    }

}
if (!function_exists('coursesRaw')) {
    function coursesRaw($categoryId)
    {
        $today = Carbon::today()->toDateString();
        $courses = DB::table('vtiger_products')
            ->join('vtiger_crmentity', 'vtiger_products.productid', '=', 'vtiger_crmentity.crmid')
            ->leftJoin('vtiger_account', 'vtiger_account.accountid', '=', 'vtiger_products.commitente_azienda')
            ->select('vtiger_products.*', 'vtiger_account.accountname as committente',
                DB::raw('"0" as isStorico'))
            ->where([
                ['vtiger_products.showonwebsite', '=', '1'],
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_products.discontinued', '=', '1'],
                ['vtiger_products.mycproductcategory', '=', $categoryId],
                ['vtiger_products.start_date', '<=', $today],
                ['vtiger_products.expiry_date', '>=', $today]
            ])
            ->orderBy('vtiger_products.s4_start_date', 'asc')
            ->get();

        return $courses;

    }

}

if (!function_exists('coursesRawCalendar')) {
    function coursesRawCalendar()
    {
        $today = Carbon::today()->toDateString();
        $courses = DB::table('vtiger_products')
            ->join('vtiger_crmentity', 'vtiger_products.productid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_products.*',
                DB::raw('"0" as isStorico'))
            ->where([
                ['vtiger_products.showonwebsite', '=', '1'],
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_products.discontinued', '=', '1'],
                ['vtiger_products.start_date', '<=', $today],
                ['vtiger_products.expiry_date', '>=', $today]
            ])
            ->orderBy('vtiger_products.s4_start_date', 'desc')
            ->get();
        return $courses;

    }

}
if (!function_exists('coursesRawCalendarName')) {
    function coursesRawCalendarName()
    {
        $today = Carbon::today()->toDateString();
        $courses = DB::table('vtiger_products')
            ->join('vtiger_crmentity', 'vtiger_products.productid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_products.*',
                DB::raw('"0" as isStorico'))
            ->where([
                ['vtiger_products.showonwebsite', '=', '1'],
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_products.discontinued', '=', '1'],
                ['vtiger_products.start_date', '<=', $today],
                ['vtiger_products.expiry_date', '>=', $today]
            ])
            ->orderBy('vtiger_products.s4_start_date', 'desc')
            ->get();
        return $courses;

    }

}
if (!function_exists('getCourseDates')) {
    function getCourseDates($courseId)
    {
        $courseDates = DB::table('vtiger_products')
            ->join('vtiger_crmentity', 'vtiger_products.productid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_products.start_date', 'vtiger_products.expiry_date')
            ->where([
                ['vtiger_products.showonwebsite', '=', '1'],
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_products.productid', '=', $courseId]
            ])
            ->orderBy('vtiger_products.s4_start_date', 'desc')
            ->first();
        return $courseDates;
    }

}
if (!function_exists('courseRawByIdSubs')) {
    function courseRawByIdSubs($courseId)
    {
        $today = Carbon::today()->toDateString();
        $course = DB::table('vtiger_products')
            ->join('vtiger_crmentity', 'vtiger_products.productid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_products.*', 'vtiger_crmentity.description as subtitle',
                DB::raw('"0" as isStorico'))
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_products.productid', '=', $courseId]
            ])
            ->orderBy('vtiger_products.s4_start_date', 'desc')
            ->get();
//        $course = Course::query()->with('accounts')
//            ->whereHas('entity', function ($query) {
//                $query->where('deleted', '0');
//            })->where([
//                ['vtiger_products.discontinued', '=', '1'],
//                ['vtiger_products.productid', '=', $courseId],
//
//            ])
//            ->get();
        return $course;
    }

}
if (!function_exists('courseRawByName')) {
    function courseRawByName($courseName)

    {
        $today = Carbon::today()->toDateString();
        $courses = DB::table('vtiger_products')
            ->join('vtiger_crmentity', 'vtiger_products.productid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_products.*',
                DB::raw('"0" as isStorico'))
            ->where([
                ['vtiger_products.showonwebsite', '=', '1'],
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_products.fulldescription', 'like', "%{$courseName}%"],
                ['vtiger_products.start_date', '<=', $today],
                ['vtiger_products.expiry_date', '>=', $today]
            ])
            ->orderBy('vtiger_products.s4_start_date', 'asc')
            ->get();
        return $courses;
    }
}

if (!function_exists('coursesSearch')) {
    function coursesSearch($searchParam)

    {
        $coursesRaw = courseRawByName($searchParam);
        $courses = [];
        $attachments = new Attachment;

        foreach ($coursesRaw as $key => $courseRaw) {
            $imgUrl = new Attachment;
            $imgUrl = getAttachemtImageById($courseRaw->productid);
            $courses[$courseRaw->productid] = [
                'id' => $courseRaw->productid,
                'title' => DataHelper::limitText($courseRaw->shortdescription, 60),
                'description' => DataHelper::limitText($courseRaw->fulldescription, 90),
                'start' => DataHelper::dateFormat($courseRaw->s4_start_date),
                'end' => DataHelper::dateFormat($courseRaw->s4_end_date),
                'subsStart' => DataHelper::dateFormat($courseRaw->sales_start_date),
                'subsEnd' => DataHelper::dateFormat($courseRaw->sales_end_date),
                'code' => $courseRaw->s4_code,
                'seats' => $courseRaw->qtyinstock,
                'credits' => $courseRaw->s4_crediti,
                'days' => $courseRaw->s4_expected_days_number,
                'location' => $courseRaw->s4_location, // to get the location
                'price' => DataHelper::priceFormat($courseRaw->unit_price),
                'isFree' => (round($courseRaw->unit_price, 2) == 0) ? true : false,
                'priceRaw' => number_format($courseRaw->unit_price, 2),
                'imgUrl' => $imgUrl,
                'isFaddy' => $courseRaw->abilita_elearning,
                'reserved' => $courseRaw->s4_reserved,
                'subscribable' => subscribable($courseRaw->productid),
                'overbooking' => overbooking($courseRaw->productid),
                'edition' => $courseRaw->s4_edizione,
                'isStorico' => $courseRaw->isStorico,
            ];
        }
        return $courses;
    }
}

if (!function_exists('coursesSearchProfession')) {
    function coursesSearchProfession($searchParam)

    {
        $coursesRaw = coursesSearchByProfession($searchParam);
        $courses = [];
        $attachments = new Attachment;

        foreach ($coursesRaw as $key => $courseRaw) {
            $imgUrl = new Attachment;
            $imgUrl = getAttachemtImageById($courseRaw->productid);
            $courses[$courseRaw->productid] = [
                'id' => $courseRaw->productid,
                'title' => DataHelper::limitText($courseRaw->shortdescription, 60),
                'description' => DataHelper::limitText($courseRaw->fulldescription, 90),
                'start' => DataHelper::dateFormat($courseRaw->s4_start_date),
                'end' => DataHelper::dateFormat($courseRaw->s4_end_date),
                'subsStart' => DataHelper::dateFormat($courseRaw->sales_start_date),
                'subsEnd' => DataHelper::dateFormat($courseRaw->sales_end_date),
                'code' => $courseRaw->s4_code,
                'seats' => $courseRaw->qtyinstock,
                'credits' => $courseRaw->s4_crediti,
                'days' => $courseRaw->s4_expected_days_number,
                'location' => $courseRaw->s4_location, // to get the location
                'price' => DataHelper::priceFormat($courseRaw->unit_price),
                'isFree' => (round($courseRaw->unit_price, 2) == 0) ? true : false,
                'priceRaw' => number_format($courseRaw->unit_price, 2),
                'imgUrl' => $imgUrl,
                'isFaddy' => $courseRaw->abilita_elearning,
                'reserved' => $courseRaw->s4_reserved,
                'subscribable' => subscribable($courseRaw->productid),
                'overbooking' => overbooking($courseRaw->productid),
                'edition' => $courseRaw->s4_edizione,
                'isStorico' => $courseRaw->isStorico,
            ];
        }
        return $courses;
    }
}

if (!function_exists('courses')) {
    function courses($categoryId)
    {
        $coursesRaw = coursesRaw($categoryId);
        $courses = [];
        $attachments = new Attachment;

        foreach ($coursesRaw as $key => $courseRaw) {
            $imgUrl = new Attachment;
            $imgUrl = getAttachemtImageById($courseRaw->productid);
            $sponsorList = str_replace(' |##| ', ',', $courseRaw->s4_sponsor);
            $arrayConverted = preg_split("/[,]/", $sponsorList);
//            dd($sponsorList);
            $courses[$courseRaw->productid] = [
                'id' => $courseRaw->productid,
                'title' => DataHelper::limitText($courseRaw->shortdescription, 90), //MY was 60
                'description' => DataHelper::limitText($courseRaw->fulldescription, 90),
                'start' => DataHelper::dateFormat($courseRaw->s4_start_date),
                'end' => DataHelper::dateFormat($courseRaw->s4_end_date),
                'subsStart' => DataHelper::dateFormat($courseRaw->sales_start_date),
                'subsEnd' => DataHelper::dateFormat($courseRaw->sales_end_date),
                'code' => $courseRaw->s4_code,
                'seats' => $courseRaw->qtyinstock,
                'credits' => $courseRaw->s4_crediti,
                'days' => $courseRaw->s4_expected_days_number,
                'location' => $courseRaw->s4_location, // to get the location
                'price' => DataHelper::priceFormat($courseRaw->unit_price),
                'isFree' => (round($courseRaw->unit_price, 2) == 0) ? true : false,
                'priceRaw' => number_format($courseRaw->unit_price, 2),
                'imgUrl' => $imgUrl,
                'isFaddy' => $courseRaw->abilita_elearning,
                'reserved' => $courseRaw->s4_reserved,
                'subscribable' => subscribable($courseRaw->productid),
                'overbooking' => $courseRaw->s4_overbooking,
                'edition' => $courseRaw->s4_edizione,
                'isStorico' => $courseRaw->isStorico,
                'committente' => $courseRaw->committente,
                'sponsors' => $sponsorList
            ];
        }

        return $courses;
    }
}
if (!function_exists('coursesCalendar')) {
    function coursesCalendar($id = null, $type = null)
    {
        if ($type == 'Search') {
            $coursesRaw = coursesRaw($id);
        } elseif ($type == 'SearchCal') {
            $coursesRaw = coursesRawId($id);
        } else {
            $coursesRaw = coursesRawCalendar();
        }

        $courses = [];

        foreach ($coursesRaw as $key => $courseRaw) {

            $courses[$courseRaw->productid] = [
                'id' => $courseRaw->productid,
                'title' => DataHelper::limitText($courseRaw->shortdescription, 90), //MY was 60
                'start' => DataHelper::dateFormatCalendar($courseRaw->s4_start_date),
                'end' => DataHelper::dateFormatCalendar($courseRaw->s4_end_date),
            ];
        }
        return $courses;
    }

}

if (!function_exists('coursesHistory')) {
    function coursesHistory($year = null)
    {
        if (isset($year)) {
            $coursesRaw = coursesRawHistory($year);
        } else {
            $coursesRaw = coursesRawHistory();
        }
        $courses = [];
        $attachments = new Attachment;
        foreach ($coursesRaw as $key => $courseRaw) {
            $imgUrl = $attachments;
            $imgUrl = getAttachemtImageById($courseRaw->productid);
            $accreditations = new Accreditation;
            $accreditations = accreditations($courseRaw->productid);
            $accreditation = "";
            foreach ($accreditations as $key => $accreditationDetail) {
                $accreditation = $accreditationDetail;
            }
            if (!$courseRaw->isStorico) {
                $location = [];
                $locationCoursesDetail = (new Location)->getLocationByIdRaw($courseRaw->s4_location);
                foreach ($locationCoursesDetail as $key => $locationCourseDetail) {
                    $location = $locationCourseDetail;
                }
            } else {
                $location = $courseRaw->s4_location;
            }
            $courses[$courseRaw->productid] = [
                'id' => $courseRaw->productid,
                'title' => DataHelper::limitText($courseRaw->shortdescription, 60),
                'description' => DataHelper::limitText($courseRaw->fulldescription, 90),
                'start' => DataHelper::dateFormat($courseRaw->s4_start_date),
                'end' => DataHelper::dateFormat($courseRaw->s4_end_date),
                'subsStart' => DataHelper::dateFormat($courseRaw->sales_start_date),
                'subsEnd' => DataHelper::dateFormat($courseRaw->sales_end_date),
                'code' => $courseRaw->s4_code,
                'seats' => $courseRaw->qtyinstock,
                'credits' => $courseRaw->s4_crediti,
                'days' => $courseRaw->s4_expected_days_number,
                'location' => $courseRaw->s4_location, // to get the location
                'locationComplete' => $location,
                'accreditation' => $accreditation,
                'richDescription' => $courseRaw->s4_rich_description,
                'price' => DataHelper::priceFormat($courseRaw->unit_price),
                'isFree' => (round($courseRaw->unit_price, 2) == 0) ? true : false,
                'priceRaw' => number_format($courseRaw->unit_price, 2),
                'imgUrl' => $imgUrl,
                'isFaddy' => $courseRaw->abilita_elearning,
                'reserved' => $courseRaw->s4_reserved,
                'overbooking' => overbooking($courseRaw->productid),
                'edition' => $courseRaw->s4_edizione,
                'isStorico' => $courseRaw->isStorico,
                'obiettiviformativi' => $courseRaw->obiettiviformativi,
                'normativa' => $courseRaw->normativa,
                'professioni' => $courseRaw->professioni,
                'edizione' => $courseRaw->s4_year,
            ];
        }
        return $courses;
    }
}

if (!function_exists('courseByIdSubsShow')) {
    function courseByIdSubsShow($courseId)

    {
        $courseRawById = courseRawByIdSubs($courseId);

        $attachments = new Attachment;
        $course = [];
        foreach ($courseRawById as $key => $courseRaw) {

            $imgUrl = new Attachment;
            $imgUrl = getAttachemtImageById($courseRaw->productid);
            $sponsorList = str_replace(' |##| ', ',', $courseRaw->s4_sponsor);
            $arrayConverted = preg_split("/[,]/", $sponsorList);
            $course = [
                'id' => $courseRaw->productid,
                'title' => $courseRaw->fulldescription, // Limit text to 30
                'description' => $courseRaw->s4_rich_description, // Limit text to 60
                'start' => DataHelper::dateFormat($courseRaw->s4_start_date), // display date dd/mm/Y
                'startHour' => DataHelper::hourFormat($courseRaw->s4_start_hour),
                'end' => DataHelper::dateFormat($courseRaw->s4_end_date), // display date dd/mm/Y
                'endHour' => DataHelper::hourFormat($courseRaw->s4_end_hour),
                'subsStart' => DataHelper::dateFormat($courseRaw->sales_start_date),
                'subsEnd' => DataHelper::dateFormat($courseRaw->sales_end_date),
                'code' => $courseRaw->s4_code,
                'credits' => $courseRaw->s4_crediti,
                'days' => $courseRaw->s4_expected_days_number,
                'location' => (new Location)->location($courseRaw->s4_location), // to get the location
                'expectedDays' => $courseRaw->s4_expected_days_number,
                'hoursAmount' => $courseRaw->s4_hours_amount,
                'seatsAvailable' => $courseRaw->qtyinstock,
                'price' => DataHelper::priceFormat($courseRaw->unit_price),
                'priceRaw' => $courseRaw->unit_price,
                'requirements' => $courseRaw->s4_part_requirements,
                'defaultescrizione' => $courseRaw->s4_defaultescrizione,
                'imgUrl' => $imgUrl,
                'isFree' => (round($courseRaw->unit_price, 2) == 0) ? true : false,
                'attachments' => getCourseAttachments($courseRaw->productid),
                'isElearning' => $courseRaw->abilita_elearning,
                'moodleCourseId' => $courseRaw->s4_moodle_course,
                'reserved' => $courseRaw->s4_reserved,
                'overbooking' => overbooking($courseRaw->productid),
                'edition' => $courseRaw->s4_edizione,
                'isStorico' => $courseRaw->isStorico,
                'subtitle' => $courseRaw->subtitle,
                'sponsors' => $sponsorList
            ];
        }

        return $course;
    }
}

if (!function_exists('seatCourseDeduct')) {
    function seatCourseDeduct($courseId)

    {
        $course = courseById($courseId);

        $seatNow = $course['seats'];
        $seatUpdate = (int)$seatNow - 1;
        DB::update('update vtiger_products set vtiger_products.qtyinstock = ? where vtiger_products.productid = ?', [$seatUpdate, $courseId]);

        (new ModTracker)->trace($courseId, 'Products', 'UPDATE', ['qtyinstock' => $seatUpdate], ['qtyinstock' => $seatNow]);

        return true;
    }
}
/**
 * Update Seats Number After Cancel subscription
 *
 * @param String $courseId
 * @return true
 */
if (!function_exists('seatCourseAdd')) {
    function seatCourseAdd($courseId)

    {
        $course = (array)course($courseId);
        if ($course) {
            $seatNow = $course['qtyinstock'];
            $seatUpdate = (int)$seatNow + 1;
            DB::update('update vtiger_products set vtiger_products.qtyinstock = ? where vtiger_products.productid = ?', [$seatUpdate, $courseId]);

            (new ModTracker)->trace($courseId, 'Products', 'UPDATE', ['qtyinstock' => $seatUpdate], ['qtyinstock' => $seatNow]);

            return true;
        } else {
            return false;
        }
    }
}

if (!function_exists('subscribable')) {
    function subscribable($courseId)

    {
        $course = courseById($courseId);
        $today = Carbon::today();

        //dd($course);

        if (!empty($course['subsStart']) && !empty($course['subsEnd'])) {
            $start = (Carbon::createFromFormat('d/m/Y', $course['start']))->format('Y-m-d');
            $end = (Carbon::createFromFormat('d/m/Y', $course['end']))->format('Y-m-d');
            $subsStart = (Carbon::createFromFormat('d/m/Y', $course['subsStart']))->format('Y-m-d');
            $subsEnd = (Carbon::createFromFormat('d/m/Y', $course['subsEnd']))->format('Y-m-d');
            $seatsNumber = $course['seats'];
            $overbooking = $course['overbooking'];

            if (
                ($today->greaterThanOrEqualTo(Carbon::parse($start))) &&
                ($today->lessThanOrEqualTo(Carbon::parse($end)))
            ) {


                if (
                    ($today->greaterThanOrEqualTo(Carbon::parse($subsStart))) &&
                    ($today->lessThanOrEqualTo(Carbon::parse($subsEnd))) &&
                    ((int)$seatsNumber > 0)

                ) {
                    return true;
                }

                if (
                    ($today->greaterThanOrEqualTo(Carbon::parse($subsStart))) &&
                    ($today->lessThanOrEqualTo(Carbon::parse($subsEnd))) &&
                    ((int)$seatsNumber <= 0) &&
                    ($overbooking == 1)

                ) {
                    return true;
                }

            } elseif ($today->lessThanOrEqualTo(Carbon::parse($start))) {
                if (
                    ($today->greaterThanOrEqualTo(Carbon::parse($subsStart))) &&
                    ($today->lessThanOrEqualTo(Carbon::parse($subsEnd))) &&
                    ((int)$seatsNumber > 0)

                ) {
                    return true;
                }

                if (
                    ($today->greaterThanOrEqualTo(Carbon::parse($subsStart))) &&
                    ($today->lessThanOrEqualTo(Carbon::parse($subsEnd))) &&
                    ((int)$seatsNumber <= 0) &&
                    ($overbooking == 1)

                ) {
                    return true;
                }
            }


            return false;
        }
    }
}
if (!function_exists('courseById')) {
    function courseById($courseId)
    {
        $courseRawById = courseRawById($courseId);
        $attachments = new Attachment;
        $course = [];

        if (auth()->check()) {
            $contact = Contact::find(auth()->user()->id);

            if (config('sailfor.faddy')) {
                if ($contact['s4_moodle_user'] != '' || $contact['s4_moodle_user'] != 0) {
                    $login = (new Faddy)->login($contact['s4_moodle_user']);
                } else {
                    $login = '';
                }
            }
        }

        foreach ($courseRawById as $key => $courseRaw) {
            $imgUrl = $attachments;
            $imgUrl = getAttachemtImageById($courseRaw->productid);
            if (auth()->check()) {
                $params = [
                    'userName' => 'sailuser' . auth()->user()->id,
                    'courseId' => $courseRaw->s4_moodle_course
                ];
                if (config('sailfor.faddy')) {
                    $resource = (new Faddy)->resource($params['courseId']);
                    $faddyUrl = $login . $resource;
                } else {
                    $faddyUrl = false;
                }
            }

            $course = [
                'id' => $courseRaw->productid,
                'title' => $courseRaw->fulldescription ?? '', // Limit text to 30
                'description' => $courseRaw->s4_rich_description, // Limit text to 60
                'start' => DataHelper::dateFormat($courseRaw->s4_start_date), // display date dd/mm/Y
                'startHour' => DataHelper::hourFormat($courseRaw->s4_start_hour),
                'end' => DataHelper::dateFormat($courseRaw->s4_end_date), // display date dd/mm/Y
                'endHour' => DataHelper::hourFormat($courseRaw->s4_end_hour),
                'subsStart' => DataHelper::dateFormat($courseRaw->sales_start_date),
                'subsEnd' => DataHelper::dateFormat($courseRaw->sales_end_date),
                'code' => $courseRaw->s4_code,
                'seats' => $courseRaw->qtyinstock,
                'credits' => $courseRaw->s4_crediti,
                'days' => $courseRaw->s4_expected_days_number,
                'location' => (new Location)->location($courseRaw->s4_location), // to get the location
                'expectedDays' => $courseRaw->s4_expected_days_number,
                'hoursAmount' => $courseRaw->s4_hours_amount,
                'seatsAvailable' => $courseRaw->qtyinstock,
                'price' => DataHelper::priceFormat($courseRaw->unit_price),
                'priceRaw' => $courseRaw->unit_price,
                'requirements' => $courseRaw->s4_part_requirements,
                'defaultescrizione' => $courseRaw->s4_defaultescrizione,
                'imgUrl' => $imgUrl,
                'isFree' => (round($courseRaw->unit_price, 2) == 0) ? true : false,
                'attachments' => getCourseAttachments($courseRaw->productid),
                'isElearning' => $courseRaw->abilita_elearning,
                'moodleCourseId' => $courseRaw->s4_moodle_course,
                'reserved' => $courseRaw->s4_reserved,
                'overbooking' => $courseRaw->s4_overbooking,
                'edition' => $courseRaw->s4_edizione,
                'controllo_iscritto' => $courseRaw->s4_potential_access_control,
                's4ggendsubs' => $courseRaw->s4ggendsubs,
                's4_scadenza_iscrizioni' => $courseRaw->s4_scadenza_iscrizioni,
                'isStorico' => $courseRaw->isStorico,
                'faddyUrl' => $faddyUrl ?? '',
                'subtitle' => $courseRaw->subtitle,
                'questionari_faddy' => $courseRaw->s4_active_survey,
                'corso_faddy' => $courseRaw->abilita_elearning,
                'committente' => $courseRaw->committente,
                'category' => $courseRaw->mycproductcategory,
            ];
        }
        return $course;
    }


}
if (!function_exists('checkoutData')) {
    function checkoutData($courseId)

    {
        $data = [];
        $course = new Course;
        $course = courseById($courseId);

        $data['items'] = [
            [
                'name' => $course['title'],
                'price' => round(floatval($course['priceRaw']), 2),
                'qty' => 1
            ],
        ];

        $data['invoice_id'] = $courseId . rand(1, 999999999);
        $data['invoice_description'] = "Fattura #{$data['invoice_id']} Per corso {$course['title']}";
        $data['return_url'] = url('/subscription/success');
        $data['cancel_url'] = url('/subscription/fail');

        $total = 0;
        foreach ($data['items'] as $item) {
            $total += $item['price'] * $item['qty'];
        }

        $data['total'] = $total;
        return $data;
    }
}
if (!function_exists('newFeaturedCourses')) {
    function newFeaturedCourses()

    {
        $coursesRaw = newFeaturedCoursesRaw();
        $featuredCourses = [];
        $newCourses = [];
        $result = [];
        $attachments = new Attachment;
        //$lastDays = Carbon::now()->subDays(Config('sailfor.nuovoCorsoMaxGiorni'))->toDateString();
        $today = Carbon::today()->toDateString();
        foreach ($coursesRaw as $key => $courseRaw) {
            $imgUrl = new Attachment;
            $imgUrl = getAttachemtImageById($courseRaw->productid);

            if ($courseRaw->isfeatured == 1) {
                $featuredCourses[$courseRaw->productid] = [
                    'id' => $courseRaw->productid,
                    'title' => DataHelper::limitText($courseRaw->shortdescription, 60),
                    'description' => DataHelper::limitText($courseRaw->fulldescription, 90),
                    'start' => DataHelper::dateFormat($courseRaw->s4_start_date),
                    'end' => DataHelper::dateFormat($courseRaw->s4_end_date),
                    'subsStart' => DataHelper::dateFormat($courseRaw->sales_start_date),
                    'subsEnd' => DataHelper::dateFormat($courseRaw->sales_end_date),
                    'code' => $courseRaw->s4_code,
                    'seats' => $courseRaw->qtyinstock,
                    'credits' => $courseRaw->s4_crediti,
                    'days' => $courseRaw->s4_expected_days_number,
                    'location' => $courseRaw->s4_location, // to get the location
                    'price' => DataHelper::priceFormat($courseRaw->unit_price),
                    'isFree' => (round($courseRaw->unit_price, 2) == 0) ? true : false,
                    'priceRaw' => number_format($courseRaw->unit_price, 2),
                    'imgUrl' => $imgUrl,
                    'isFaddy' => $courseRaw->abilita_elearning,
                    'edition' => $courseRaw->s4_edizione,
                    'isStorico' => $courseRaw->isStorico,
                    'isOverbooking' => $courseRaw->s4_overbooking,
                ];
            }

            if ($courseRaw->s4_start_date >= $today) {

                $newCourses[$courseRaw->productid] = [
                    'id' => $courseRaw->productid,
                    'title' => DataHelper::limitText($courseRaw->shortdescription, 90),
                    'description' => DataHelper::limitText($courseRaw->fulldescription, 90),
                    'start' => DataHelper::dateFormat($courseRaw->s4_start_date),
                    'end' => DataHelper::dateFormat($courseRaw->s4_end_date),
                    'subsStart' => DataHelper::dateFormat($courseRaw->sales_start_date),
                    'subsEnd' => DataHelper::dateFormat($courseRaw->sales_end_date),
                    'code' => $courseRaw->s4_code,
                    'seats' => $courseRaw->qtyinstock,
                    'credits' => $courseRaw->s4_crediti,
                    'days' => $courseRaw->s4_expected_days_number,
                    'location' => $courseRaw->s4_location, // to get the location
                    'price' => DataHelper::priceFormat($courseRaw->unit_price),
                    'priceRaw' => number_format($courseRaw->unit_price, 2),
                    'isFree' => (round($courseRaw->unit_price, 2) == 0) ? true : false,
                    'imgUrl' => $imgUrl,
                    'isFaddy' => $courseRaw->abilita_elearning,
                    'edition' => $courseRaw->s4_edizione,
                    'isStorico' => $courseRaw->isStorico,
                    'isOverbooking' => $courseRaw->s4_overbooking,
                ];
            }

        }

        $result = ['featured' => array_slice($featuredCourses, 0, config('sailfor.numeroCorsiHome')), 'new' => array_slice($newCourses, 0, config('sailfor.numeroCorsiHome'))];
        return $result;
    }
}
if (!function_exists('newFeaturedPortalCourses')) {
    function newFeaturedPortalCourses($accountId)

    {
        $coursesRaw = newFeaturedCoursesRawPortal($accountId);
//        dd($coursesRaw);
        $featuredCourses = [];
        $newCourses = [];
        $result = [];
        $attachments = new Attachment;
        //$lastDays = Carbon::now()->subDays(Config('sailfor.nuovoCorsoMaxGiorni'))->toDateString();
        $today = Carbon::today()->toDateString();
        foreach ($coursesRaw as $key => $courseRaw) {
            $imgUrl = new Attachment;
            $imgUrl = getAttachemtImageById($courseRaw->productid);
            $sponsorList = str_replace(' |##| ', ',', $courseRaw->s4_sponsor);
            $arrayConverted = preg_split("/[,]/", $sponsorList);
            if ($courseRaw->isfeatured == 1) {
                $featuredCourses[$courseRaw->productid] = [
                    'id' => $courseRaw->productid,
                    'title' => DataHelper::limitText($courseRaw->shortdescription, 60),
                    'description' => DataHelper::limitText($courseRaw->fulldescription, 90),
                    'start' => DataHelper::dateFormat($courseRaw->s4_start_date),
                    'end' => DataHelper::dateFormat($courseRaw->s4_end_date),
                    'subsStart' => DataHelper::dateFormat($courseRaw->sales_start_date),
                    'subsEnd' => DataHelper::dateFormat($courseRaw->sales_end_date),
                    'code' => $courseRaw->s4_code,
                    'seats' => $courseRaw->qtyinstock,
                    'credits' => $courseRaw->s4_crediti,
                    'days' => $courseRaw->s4_expected_days_number,
                    'location' => $courseRaw->s4_location, // to get the location
                    'price' => DataHelper::priceFormat($courseRaw->unit_price),
                    'isFree' => (round($courseRaw->unit_price, 2) == 0) ? true : false,
                    'priceRaw' => number_format($courseRaw->unit_price, 2),
                    'imgUrl' => $imgUrl,
                    'isFaddy' => $courseRaw->abilita_elearning,
                    'edition' => $courseRaw->s4_edizione,
                    'isStorico' => $courseRaw->isStorico,
                    'isOverbooking' => $courseRaw->s4_overbooking,
                    'categoryId' => $courseRaw->mycproductcategory,
                    'sponsors' => $sponsorList
                ];
            }

            if ($courseRaw->s4_start_date >= $today) {

                $newCourses[$courseRaw->productid] = [
                    'id' => $courseRaw->productid,
                    'title' => DataHelper::limitText($courseRaw->shortdescription, 90),
                    'description' => DataHelper::limitText($courseRaw->fulldescription, 90),
                    'start' => DataHelper::dateFormat($courseRaw->s4_start_date),
                    'end' => DataHelper::dateFormat($courseRaw->s4_end_date),
                    'subsStart' => DataHelper::dateFormat($courseRaw->sales_start_date),
                    'subsEnd' => DataHelper::dateFormat($courseRaw->sales_end_date),
                    'code' => $courseRaw->s4_code,
                    'seats' => $courseRaw->qtyinstock,
                    'credits' => $courseRaw->s4_crediti,
                    'days' => $courseRaw->s4_expected_days_number,
                    'location' => $courseRaw->s4_location, // to get the location
                    'price' => DataHelper::priceFormat($courseRaw->unit_price),
                    'priceRaw' => number_format($courseRaw->unit_price, 2),
                    'isFree' => (round($courseRaw->unit_price, 2) == 0) ? true : false,
                    'imgUrl' => $imgUrl,
                    'isFaddy' => $courseRaw->abilita_elearning,
                    'edition' => $courseRaw->s4_edizione,
                    'isStorico' => $courseRaw->isStorico,
                    'isOverbooking' => $courseRaw->s4_overbooking,
                    'categoryId' => $courseRaw->mycproductcategory,
                    'sponsors' => $sponsorList
                ];
            }

        }

        $result = ['featured' => array_slice($featuredCourses, 0, config('sailfor.numeroCorsiHome')), 'new' => array_slice($newCourses, 0, config('sailfor.numeroCorsiHome'))];
        return $result;
    }
}
if (!function_exists('newFeaturedCoursesRaw')) {
    function newFeaturedCoursesRaw()

    {
        $today = Carbon::today()->toDateString();
        #$result = [];
        $newFeaturedCourses = DB::table('vtiger_products')
            ->join('vtiger_crmentity', 'vtiger_products.productid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_products.*',
                DB::raw('"0" as isStorico'))
            ->where([
                ['vtiger_products.showonwebsite', '=', '1'],
                ['vtiger_crmentity.deleted', '=', '0'],
                //  ['vtiger_products.s4_origin', '<>', 'Esterno'],
                ['vtiger_products.discontinued', '=', '1'],
                ['vtiger_products.start_date', '<=', $today],
                ['vtiger_products.expiry_date', '>=', $today]
            ])
            ->orderBy('vtiger_products.s4_start_date', 'desc')
            ->get();

        return $newFeaturedCourses;
    }
}
if (!function_exists('newFeaturedCoursesRawPortal')) {
    function newFeaturedCoursesRawPortal($accountId)

    {
        $today = Carbon::today()->toDateString();
        #$result = [];
        $newFeaturedCourses = DB::table('vtiger_products')
            ->join('vtiger_crmentity', 'vtiger_products.productid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_products.*',
                DB::raw('"0" as isStorico'))
            ->where([
                ['vtiger_products.showonwebsite', '=', '1'],
                ['vtiger_crmentity.deleted', '=', '0'],
                //  ['vtiger_products.s4_origin', '<>', 'Esterno'],
                ['vtiger_products.discontinued', '=', '1'],
                ['vtiger_products.start_date', '<=', $today],
                ['vtiger_products.expiry_date', '>=', $today]
            ])
            ->where('s4_sponsor', 'LIKE', '%'.$accountId.'%')
            ->orderBy('vtiger_products.s4_start_date', 'desc')
            ->get();

        return $newFeaturedCourses;
    }
}
if (!function_exists('coursesFilteredBYProfession')) {
    function coursesFilteredBYProfession($profession)

    {
        $coursesRaw = coursesSearchByProfession($profession);
        $courses = [];
        $attachments = new Attachment;

        foreach ($coursesRaw as $key => $courseRaw) {
            $imgUrl = new Attachment;
            $imgUrl = getAttachemtImageById($courseRaw->productid);

            $courses[$courseRaw->productid] = [
                'id' => $courseRaw->productid,
                'title' => DataHelper::limitText($courseRaw->shortdescription, 60),
                'description' => DataHelper::limitText($courseRaw->fulldescription, 90),
                'start' => DataHelper::dateFormat($courseRaw->s4_start_date),
                'end' => DataHelper::dateFormat($courseRaw->s4_end_date),
                'subsStart' => DataHelper::dateFormat($courseRaw->sales_start_date),
                'subsEnd' => DataHelper::dateFormat($courseRaw->sales_end_date),
                'code' => $courseRaw->s4_code,
                'seats' => $courseRaw->qtyinstock,
                'credits' => $courseRaw->s4_crediti,
                'days' => $courseRaw->s4_expected_days_number,
                'location' => $courseRaw->s4_location, // to get the location
                'price' => DataHelper::priceFormat($courseRaw->unit_price),
                'isFree' => (round($courseRaw->unit_price, 2) == 0) ? true : false,
                'priceRaw' => number_format($courseRaw->unit_price, 2),
                'imgUrl' => $imgUrl,
                'isFaddy' => $courseRaw->abilita_elearning,
                'reserved' => $courseRaw->s4_reserved,
                'subscribable' => subscribable($courseRaw->productid),
                'overbooking' => overbooking($courseRaw->productid),
                'edition' => $courseRaw->s4_edizione,
                'isStorico' => $courseRaw->isStorico,
            ];
        }
        return $courses;
    }
}
if (!function_exists('coursesSearchByProfession')) {
    function coursesSearchByProfession($profession)

    {
        $searchables = [(int)$profession];
        $disciplines = getDisciplinesForProfession($profession);
        $coursesRaw = [];
        foreach ($disciplines as $key => $discipline) {
            $searchables[] = $discipline->s4multilevelid;
        }
        foreach ($searchables as $key => $searchable) {
            $result = getCourseByProfessionId($searchable);

            if (count($result) >= 1) {
                $coursesRaw = $result;
            }
        }
        return $coursesRaw;
    }
}
if (!function_exists('getCourseByProfessionId')) {
    function getCourseByProfessionId($searchable)

    {
        $today = Carbon::today()->toDateString();
        $course = DB::table('vtiger_products')
            ->join('vtiger_crmentity', 'vtiger_products.productid', '=', 'vtiger_crmentity.crmid')
            ->join('vtiger_products_accreditation', 'vtiger_products.productid', '=', 'vtiger_products_accreditation.productid')
            ->select('vtiger_products.*',
                DB::raw('"0" as isStorico'))
            ->where([
                ['vtiger_products.showonwebsite', '=', '1'],
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_products.discontinued', '=', '1'],
                ['vtiger_products.start_date', '<=', $today],
                ['vtiger_products.expiry_date', '>=', $today]
            ])
            ->where(function ($query) use ($searchable) {
                $query->where('vtiger_products_accreditation.s4_profession_id', 'LIKE', '%' . $searchable . '%');
            })
            ->orderBy('vtiger_products.s4_start_date', 'desc')
            ->get();
        return $course;
    }
}
if (!function_exists('checkCourseHasProfession')) {
    function checkCourseHasProfession($courseId)

    {
        $today = Carbon::today()->toDateString();
        $course = DB::table('vtiger_products')
            ->join('vtiger_crmentity', 'vtiger_products.productid', '=', 'vtiger_crmentity.crmid')
            ->join('vtiger_products_accreditation', 'vtiger_products.productid', '=', 'vtiger_products_accreditation.productid')
            ->select('vtiger_products_accreditation.s4_profession_id')
            ->where([
                ['vtiger_products.productid', '=', $courseId]
            ])
            ->orderBy('vtiger_products.s4_start_date', 'desc')
            ->first();
        $check = (empty($course->s4_profession_id)) ? false : true;
        return $check;
    }
}
if (!function_exists('course')) {
    function course($courseId)

    {
        $course = DB::table('vtiger_products')
            ->join('vtiger_crmentity', 'vtiger_products.productid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_products.*', 'vtiger_crmentity.description as subtitle',
                DB::raw('"0" as isStorico'))
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_products.productid', '=', $courseId],
                // vincolo se data fine corso maggiore o uguale ad oggi
                //['vtiger_products.s4_end_date', '>=', $today]
            ])
            ->orderBy('vtiger_products.s4_start_date', 'desc')
            ->first();
        return $course;
    }
}
if (!function_exists('getRawHistoryYear')) {
    function getRawHistoryYear()

    {
        $yearsNewHistoryCourses = DB::table('vtiger_storicocorsi')
            ->join('vtiger_crmentity', 'vtiger_storicocorsi.storicocorsiid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_storicocorsi.annocompetenza as s4_year')
            ->where([['vtiger_crmentity.deleted', '=', '0']]);

        $years = DB::table('vtiger_products')
            ->join('vtiger_crmentity', 'vtiger_products.productid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_products.s4_year')
            ->where([
                ['vtiger_products.showonwebsite', '=', '1'],
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_products.discontinued', '=', '0'],
            ])
            ->union($yearsNewHistoryCourses)
            ->orderBy('s4_year', 'desc')
            ->distinct('s4_year')
            ->get();

        //dd($years);
        return $years->toArray();
    }
}
if (!function_exists('getCoursesYear')) {
    function getCoursesYear($yearHistory)

    {
        /*$years = DB::table('vtiger_products')
                 ->join('vtiger_crmentity', 'vtiger_products.productid', '=', 'vtiger_crmentity.crmid')
                 ->select('vtiger_products.*',
                 DB::raw('"0" as isStorico'))
                 ->where([
                         ['vtiger_products.showonwebsite', '=', '1'],
                         ['vtiger_crmentity.deleted', '=', '0'],
                         ['vtiger_products.discontinued', '=', '0'],
                         ['vtiger_products.s4_year', '=', $yearHistory]
                         ])
                 ->orderBy('vtiger_products.s4_start_date', 'desc')
                 ->get();

         */
        $newHistoryCourses = DB::table('vtiger_storicocorsi')
            ->select('vtiger_storicocorsi.storicocorsiid as productid',
                'vtiger_storicocorsi.titolocorso as shortdescription',
                'vtiger_storicocorsi.titolocorso as fulldescription',
                'vtiger_storicocorsi.datainizio as s4_start_date',
                'vtiger_storicocorsi.datafine as s4_end_date',
                DB::raw('"" as sales_start_date'),
                DB::raw('"" as sales_end_date'),
                'vtiger_storicocorsi.codice as s4_code',
                DB::raw('"" as qtyinstock'),
                'vtiger_storicocorsi.crediti as s4_crediti',
                'vtiger_storicocorsi.giorniprevisti as s4_expected_days_number',
                'vtiger_storicocorsi.sedecorsuale as s4_location',
                'vtiger_storicocorsi.descrizionecorso as s4_rich_description',
                DB::raw('"0.00" as unit_price'),
                DB::raw('"0" as abilita_elearning'),
                DB::raw('"0" as s4_reserved'),
                DB::raw('"1" as isStorico'),
                DB::raw('"0" as s4_edizione'),
                'vtiger_storicocorsi.obiettiviformativi as obiettiviformativi',
                'vtiger_storicocorsi.normativa as normativa',
                'vtiger_storicocorsi.professioni as professioni')
            ->join('vtiger_crmentity', 'vtiger_storicocorsi.storicocorsiid', '=', 'vtiger_crmentity.crmid')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_storicocorsi.annocompetenza', '=', $yearHistory]
            ]);

        $coursesByYears = DB::table('vtiger_products')
            ->join('vtiger_crmentity', 'vtiger_products.productid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_products.productid',
                'vtiger_products.shortdescription',
                'vtiger_products.fulldescription',
                'vtiger_products.s4_start_date',
                'vtiger_products.s4_end_date',
                'vtiger_products.sales_start_date',
                'vtiger_products.sales_end_date',
                'vtiger_products.s4_code',
                'vtiger_products.qtyinstock',
                'vtiger_products.s4_crediti',
                'vtiger_products.s4_expected_days_number',
                'vtiger_products.s4_location',
                'vtiger_products.s4_rich_description',
                'vtiger_products.unit_price',
                'vtiger_products.abilita_elearning',
                'vtiger_products.s4_reserved',
                DB::raw('"0" as isStorico'),
                'vtiger_products.s4_edizione',
                DB::raw('"" as obiettiviformativi'),
                DB::raw('"" as normativa'),
                DB::raw('"" as professioni'))
            ->where([
                ['vtiger_products.showonwebsite', '=', '1'],
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_products.discontinued', '=', '0'],
                ['vtiger_products.s4_year', '=', $yearHistory]
            ])
            ->union($newHistoryCourses)
            ->orderBy('s4_start_date', 'desc')
            ->get();
        return $coursesByYears->toArray();
    }
}

if (!function_exists('coursesHistoryYear')) {
    function coursesHistoryYear($year)

    {

        if ($year == "Tutti i corsi") {
            $coursesRaw = coursesRawHistory();
        } else {
            $coursesRaw = getCoursesYear($year);
        }
        $courses = [];
        $attachments = new Attachment;

        foreach ($coursesRaw as $key => $courseRaw) {
            $imgUrl = new Attachment;
            $imgUrl = getAttachemtImageById($courseRaw->productid);
            $accreditation = "";
            if (!$courseRaw->isStorico) {
                $accreditations = new Accreditation;
                $accreditations = accreditations($courseRaw->productid);
                foreach ($accreditations as $key => $accreditationDetail) {
                    $accreditation = $accreditationDetail;
                }
            }

            if (!$courseRaw->isStorico) {
                $location = [];
                $locationCoursesDetail = (new Location)->getLocationByIdRaw($courseRaw->s4_location);
                foreach ($locationCoursesDetail as $key => $locationCourseDetail) {
                    $location = $locationCourseDetail;
                }
            } else {
                $location = $courseRaw->s4_location;
            }
            $courses[$courseRaw->productid] = [
                'id' => $courseRaw->productid,
                'title' => DataHelper::limitText($courseRaw->shortdescription, 60),
                'description' => DataHelper::limitText($courseRaw->fulldescription, 90),
                'start' => DataHelper::dateFormat($courseRaw->s4_start_date),
                'end' => DataHelper::dateFormat($courseRaw->s4_end_date),
                'subsStart' => DataHelper::dateFormat($courseRaw->sales_start_date),
                'subsEnd' => DataHelper::dateFormat($courseRaw->sales_end_date),
                'code' => $courseRaw->s4_code,
                'seats' => $courseRaw->qtyinstock,
                'credits' => $courseRaw->s4_crediti,
                'days' => $courseRaw->s4_expected_days_number,
                'location' => $courseRaw->s4_location, // to get the location
                'locationComplete' => $location,
                'accreditation' => $accreditation,
                'richDescription' => $courseRaw->s4_rich_description,
                'price' => DataHelper::priceFormat($courseRaw->unit_price),
                'isFree' => (round($courseRaw->unit_price, 2) == 0) ? true : false,
                'priceRaw' => number_format($courseRaw->unit_price, 2),
                'imgUrl' => $imgUrl,
                'isFaddy' => $courseRaw->abilita_elearning,
                'reserved' => $courseRaw->s4_reserved,
                'overbooking' => overbooking($courseRaw->productid),
                'edition' => $courseRaw->s4_edizione,
                'isStorico' => $courseRaw->isStorico,
                'obiettiviformativi' => $courseRaw->obiettiviformativi,
                'normativa' => $courseRaw->normativa,
                'professioni' => $courseRaw->professioni,
            ];
        }
        return $courses;
    }
}

if (!function_exists('getHistoryYear')) {
    function getHistoryYear()

    {
        $yearsRaw = getRawHistoryYear();
        $years = [];

        foreach ($yearsRaw as $key => $yearRaw) {
            $years[$yearRaw->s4_year] = [
                's4_year' => $yearRaw->s4_year
            ];
        }
        return $years;
    }
}

if (!function_exists('getSubtitle')) {
    function getSubtitle($id = null)

    {
        $course = DB::table('vtiger_products')
            ->join('vtiger_crmentity', 'vtiger_products.productid', '=', 'vtiger_crmentity.crmid')
            ->leftJoin('vtiger_account', 'vtiger_account.accountid', '=', 'vtiger_products.commitente_azienda')
            ->select('vtiger_crmentity.description as subtitle',
                DB::raw('"0" as isStorico'))
            ->where([
                ['vtiger_products.productid', '=', $id],
            ])
            ->orderBy('vtiger_products.s4_start_date', 'desc')
            ->first();

        return $course->subtitle ?? '';
    }
}
if (!function_exists('historyCreditsSub')) {
    function historyCreditsSub($userId)

    {
        $subscriptions = subsByContactIdHistory($userId);
        $resultsRaw = [];
        $result = [];
        $i = 0;
        foreach ($subscriptions as $sub) {
            foreach ($sub['historyCredits'] as $year => $credits) {
                $resultsRaw[$year] = array_sum($credits);
            }
        }
        foreach ($resultsRaw as $year => $credits) {
            $result[$year] = $credits;
            if ($i++ == 2) {
                break;
            }
        }
        return $result;
    }
}
if (!function_exists('historyExternalCreditSub')) {
    function historyExternalCreditSub($userId)

    {
        // Cataloghi History
        // $cataloghi = Catalogo::subsCatalogo($userId, 1);
        $cataloghi = subsCatalogoHistory($userId, 1);
        $credits = [];

        foreach ($cataloghi as $key => $catalogo) {
            $year = \Carbon\Carbon::createFromFormat('Y-m-d', $catalogo->s4_catrel_date_end)->year;
            $credits[$year][] = $catalogo->s4_catrel_credits;

        }

        $results = [];
        foreach ($credits as $year => $credit) {
            $results[$year] = array_sum($credit);
        }

        return $results;
    }
}

if (!function_exists('historyCreditsAssignmentSub')) {
    function historyCreditsAssignmentSub($userId)

    {
        $assignments = new Assignment;
        $assignments = assignmentsByContactIdHistory($userId);
        $resultsRaw = [];
        $result = [];
        $i = 0;
        foreach ($assignments as $ass) {
            foreach ($ass['historyCredits'] as $year => $credits) {
                $resultsRaw[$year] = array_sum($credits);
            }
        }
        foreach ($resultsRaw as $year => $credits) {
            $result[$year] = $credits;
            if ($i++ == 2) {
                break;
            }
        }
        return $result;
    }
}
if (!function_exists('historyCredits')) {
    function historyCredits($userId)

    {
        $subscriptions = subsByContactIdHistory($userId);
        $storico = storicoCorsiById($userId) ?? [];
        $resultsRaw = [];
        $result = [];
        $i = 0;

        foreach ($subscriptions as $sub) {
            foreach ($sub['historyCredits'] as $year => $credits) {
                $resultsRaw[$year] = array_sum($credits);
            }
        }

        foreach ($storico as $sub) {
            if ($sub['tipo_corso'] == 'INTERNO' && $sub['role'] != 'Docente' && $sub['role'] != 'Relatore') {
                foreach ($sub['historyCredits'] as $year => $credits) {
                    $resultsRaw[$year] = array_sum($credits);
                }
            }
        }

        foreach ($resultsRaw as $year => $credits) {
            $result[$year] = $credits;
            if ($i++ == 2) {
                break;
            }
        }

        return $result;
    }
}

if (!function_exists('historyExternalCredits')) {
    function historyExternalCredits($userId)

    {
        $cataloghi = subsCatalogoHistory($userId, 1);
        $storico = storicoCorsiById($userId) ?? [];

        $credits = [];

        foreach ($cataloghi as $key => $catalogo) {
            $year = Carbon::createFromFormat('Y-m-d', $catalogo->s4_catrel_date_end)->year;
            $credits[$year][] = $catalogo->s4_catrel_credits;

        }

        $results = [];
        foreach ($credits as $year => $credit) {
            $results[$year] = array_sum($credit);
        }

        foreach ($storico as $sub) {
            if ($sub['tipo_corso'] == 'CATALOGO') {
                foreach ($sub['historyCredits'] as $year => $credits) {
                    $results[$year] = array_sum($credits);
                }
            }
        }

        return $results;
    }
}
if (!function_exists('historyExternalCredits_2')) {
    function historyExternalCredits_2($contactId)

    {
        $cataloghi = subsCatalogoHistory($contactId, 1);
        $storico = storicoCorsiById($contactId) ?? [];

        $credits = [];

        foreach ($cataloghi as $key => $catalogo) {
            $year = Carbon::createFromFormat('Y-m-d', $catalogo->s4_catrel_date_end)->year;
            $credits[$year][] = $catalogo->s4_catrel_credits;

        }

        $results = [];
        foreach ($credits as $year => $credit) {
            $results[$year] = array_sum($credit);
        }

        foreach ($storico as $sub) {
            if ($sub['tipo_corso'] == 'CATALOGO') {
                foreach ($sub['historyCredits'] as $year => $credits) {
                    $results[$year] = array_sum($credits);
                }
            }
        }

        return $results;
    }
}
if (!function_exists('historyCreditsAssignments')) {
    function historyCreditsAssignments($userId)
    {
        $assignments = new Assignment;
        $assignments = assignmentsByContactIdHistory($userId);
        $storico = storicoCorsiById($userId) ?? [];
        $resultsRaw = [];
        $result = [];
        $i = 0;

        foreach ($assignments as $ass) {
            foreach ($ass['historyCredits'] as $year => $credits) {
                $resultsRaw[$year] = array_sum($credits);
            }
        }

        foreach ($storico as $sub) {
            if ($sub['tipo_corso'] == 'INTERNO' && $sub['role'] == 'Docente' && $sub['role'] == 'Relatore') {
                foreach ($sub['historyCredits'] as $year => $credits) {
                    $resultsRaw[$year] = array_sum($credits);
                }
            }
        }

        foreach ($resultsRaw as $year => $credits) {
            $result[$year] = $credits;
            if ($i++ == 2) {
                break;
            }
        }
        return $result;
    }
}

if (!function_exists('newCoursesResult')) {
    function newCoursesResult()
    {
        $result = new Course;
        $result = newFeaturedCourses();
        $newCourses = $result['new'];
        return $newCourses;
    }
}
if (!function_exists('newCoursesPortalResult')) {
    function newCoursesPortalResult($accountId)
    {
        $result = new Course;
        $result = newFeaturedPortalCourses($accountId);
        $newCourses = $result['new'];
        return $newCourses;
    }
}
if (!function_exists('newFeaturedResult')) {
    function newFeaturedResult()
    {
        $result = new Course;
//        $res = newFeaturedCourses();
        $userId = auth()->user()->id ?? 27;
//        $res = Cache::remember($userId * 67, now()->addMinutes(10), function () {
//            return newFeaturedCourses();
//        });
//        $result = Cache::get($userId * 67);
        $result = newFeaturedCourses();

//        $subscribable = new Course;
//        $subscribable = subscribable($courseId);
        $featuredCourses = $result['featured'];
//        dd($featuredCourses);
        return $featuredCourses;
    }
}
if (!function_exists('newPortalFeaturedResult')) {
    function newPortalFeaturedResult($accountId)
    {
        $result = new Course;
        $userId = auth()->user()->id ?? 27;
        $result = newFeaturedPortalCourses($accountId);
        $featuredCourses = $result['featured'];
        return $featuredCourses;
    }
}
if (!function_exists('newsList')) {
    function newsList()
    {
        $blockslst = new CMS;
        $blockslst = blocks();

        $blocks = [];
        foreach ($blockslst as $key => $block) {
            $blocks[$block->portalcmsid] = [
                'portalcmsid' => $block->portalcmsid,
                's4_name' => $block->s4_name,
                's4_blocco' => $block->s4_blocco,
                's4_tipo' => $block->s4_tipo,
                's4_html' => $block->s4_html,
                's4_ordine' => $block->s4_ordine,
                's4_nome_sistema' => $block->s4_nome_sistema,
                's4_config_value' => $block->s4_config_value
            ];
        }
        return $blocks;
    }
}

if (!function_exists('todayDate')) {
    function todayDate()
    {
        $todayDate = Carbon::parse(Carbon::now())->format('d/m/Y');
        return $todayDate;
    }
}
