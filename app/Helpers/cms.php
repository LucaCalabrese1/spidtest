<?php

use App\Attachment;
use App\Category;
use App\Course;
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
if (!function_exists('blocks')) {
    function blocks()
    {
        $blocks = DB::table('vtiger_portalcms')
            ->join('vtiger_crmentity', 'vtiger_portalcms.portalcmsid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_portalcms.*')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_portalcms.s4_tipo', '=', 'Blocco'],
            ])->orderBy('vtiger_portalcms.s4_ordine')->get();

        return $blocks;
    }
}
if (!function_exists('privacy')) {

    function privacy()
    {
        $blocks = DB::table('vtiger_portalcms')
            ->join('vtiger_crmentity', 'vtiger_portalcms.portalcmsid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_portalcms.*')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_portalcms.s4_tipo', '=', 'Privacy'],
            ])->first();

        return $blocks;
    }
}
if (!function_exists('gdprDirittoPortabilitaDati')) {
    function gdprDirittoPortabilitaDati()
    {
        $blocks = DB::table('vtiger_portalcms')
            ->join('vtiger_crmentity', 'vtiger_portalcms.portalcmsid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_portalcms.*')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_portalcms.s4_tipo', '=', 'Diritto alla portabilita dei dati'],
            ])->first();

        return $blocks;
    }
}
if (!function_exists('gdprDirittoCancellazione')) {
    function gdprDirittoCancellazione()
    {
        $blocks = DB::table('vtiger_portalcms')
            ->join('vtiger_crmentity', 'vtiger_portalcms.portalcmsid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_portalcms.*')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_portalcms.s4_tipo', '=', 'Diritto alla cancellazione'],
            ])->first();

        return $blocks;
    }
}
if (!function_exists('gdprDirittoInformazione')) {

    function gdprDirittoInformazione()
    {
        $blocks = DB::table('vtiger_portalcms')
            ->join('vtiger_crmentity', 'vtiger_portalcms.portalcmsid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_portalcms.*')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_portalcms.s4_tipo', '=', 'Diritto di essere informato'],
            ])->first();

        return $blocks;
    }
}
if (!function_exists('gdprDirittoAccesso')) {

    function gdprDirittoAccesso()
    {
        $blocks = DB::table('vtiger_portalcms')
            ->join('vtiger_crmentity', 'vtiger_portalcms.portalcmsid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_portalcms.*')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_portalcms.s4_tipo', '=', 'Diritto di accesso/Diritto di rettifica'],
            ])->first();

        return $blocks;
    }
}
if (!function_exists('termsOfUse')) {

    function termsOfUse()
    {
        $blocks = DB::table('vtiger_portalcms')
            ->join('vtiger_crmentity', 'vtiger_portalcms.portalcmsid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_portalcms.*')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_portalcms.s4_tipo', 'like', 'Condizioni di Vendita'],
            ])->first();

        return $blocks;
    }
}
if (!function_exists('cookiePolicy')) {
    function cookiePolicy()
    {
        $blocks = DB::table('vtiger_portalcms')
            ->join('vtiger_crmentity', 'vtiger_portalcms.portalcmsid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_portalcms.*')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_portalcms.s4_tipo', 'like', 'Cookie Policy'],
            ])->first();

        return $blocks;
    }
}
if (!function_exists('bySlug')) {

    function bySlug(string $slug)
    {
        $blocks = DB::table('vtiger_portalcms')
            ->join('vtiger_crmentity', 'vtiger_portalcms.portalcmsid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_portalcms.*')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_portalcms.s4_tipo', '=', 'Info'],
                ['vtiger_portalcms.s4_nome_sistema', 'like', $slug],
            ])->first();

        if (isset($blocks)) {
            if ($slug == 'bonifico') {
                $value = $blocks->s4_html;
            } else {
                $value = $blocks->s4_config_value;
            }
            return $value;
        }
    }
}
if (!function_exists('getGlobalEmail')) {

    function getGlobalEmail()
    {
        $smtp = DB::table('vtiger_systems')->first();
        $customerEmail = $smtp->from_email_field;

        return $customerEmail;
    }
}
if (!function_exists('check')) {

    function check(string $slug)
    {
        $blocks = DB::table('vtiger_portalcms')
            ->join('vtiger_crmentity', 'vtiger_portalcms.portalcmsid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_portalcms.*')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_portalcms.s4_tipo', '=', 'Info'],
                ['vtiger_portalcms.s4_nome_sistema', 'like', $slug],
            ])->first();

        return ($blocks->s4_config_value == '') ? false : true;
    }
}
if (!function_exists('bonifico')) {

    function bonifico()
    {
        $blocks = DB::table('vtiger_portalcms')
            ->join('vtiger_crmentity', 'vtiger_portalcms.portalcmsid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_portalcms.*')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_portalcms.s4_tipo', '=', 'Info'],
                ['vtiger_portalcms.s4_nome_sistema', 'like', 'bonifico'],
            ])->first();

        $value = $blocks->s4_html;

        return $value;

    }
}
if (!function_exists('notifica')) {

    function notifica()
    {
        $blocks = DB::table('vtiger_portalcms')
            ->join('vtiger_crmentity', 'vtiger_portalcms.portalcmsid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_portalcms.*')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_portalcms.s4_tipo', '=', 'Info'],
                ['vtiger_portalcms.s4_nome_sistema', 'like', 'notifica'],
            ])->first();

            if (isset($blocks->s4_html)) {
                $blocks->s4_html = strip_tags($blocks->s4_html);
            }

        if (!empty($blocks)) {
            return $blocks;
        }
        else
        {
            return false;
        }

    }
}

if (!function_exists('getCompanyInfobyCms')) {

    function getCompanyInfobyCms()
    {
        $blocks = DB::table('vtiger_portalcms')
            ->join('vtiger_crmentity', 'vtiger_portalcms.portalcmsid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_portalcms.*')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_portalcms.s4_tipo', '=', 'Info'],
                ['vtiger_portalcms.s4_nome_sistema', 'like', 'company_info'],
            ])->first();

            if (isset($blocks->s4_html)) {
                $blocks->s4_html = strip_tags($blocks->s4_html);
            }

        if (!empty($blocks)) {
            return $blocks;
        }
        else
        {
            return false;
        }

    }
}
if (!function_exists('getTelbyCms')) {

    function getTelbyCms()
    {
        $blocks = DB::table('vtiger_portalcms')
            ->join('vtiger_crmentity', 'vtiger_portalcms.portalcmsid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_portalcms.*')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_portalcms.s4_tipo', '=', 'Info'],
                ['vtiger_portalcms.s4_nome_sistema', 'like', 'company_tel'],
            ])->first();

            if (isset($blocks->s4_html)) {
                $blocks->s4_html = strip_tags($blocks->s4_html);
            }

        if (!empty($blocks)) {
            return $blocks;
        }
        else
        {
            return false;
        }

    }
}
if (!function_exists('getAvvisoRegistrazione')) {

    function getAvvisoRegistrazione()
    {
        $blocks = DB::table('vtiger_portalcms')
            ->join('vtiger_crmentity', 'vtiger_portalcms.portalcmsid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_portalcms.*')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_portalcms.s4_tipo', '=', 'Info'],
                ['vtiger_portalcms.s4_nome_sistema', 'like', 'avviso_fine_registrazione'],
            ])->first();

            if (isset($blocks->s4_html)) {
                $blocks->s4_html = strip_tags($blocks->s4_html);
            }

        if (!empty($blocks)) {
            return $blocks;
        }
        else
        {
            return false;
        }

    }
}
if (!function_exists('getEmailbyCms')) {

    function getEmailbyCms()
    {
        $blocks = DB::table('vtiger_portalcms')
            ->join('vtiger_crmentity', 'vtiger_portalcms.portalcmsid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_portalcms.*')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_portalcms.s4_tipo', '=', 'Info'],
                ['vtiger_portalcms.s4_nome_sistema', 'like', 'company_mail'],
            ])->first();

            if (isset($blocks->s4_html)) {
                $blocks->s4_html = strip_tags($blocks->s4_html);
            }

        if (!empty($blocks)) {
            return $blocks;
        }
        else
        {
            return false;
        }

    }
}
if (!function_exists('getOfficeNameCms')) {

    function getOfficeNameCms()
    {
        $blocks = DB::table('vtiger_portalcms')
            ->join('vtiger_crmentity', 'vtiger_portalcms.portalcmsid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_portalcms.*')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_portalcms.s4_tipo', '=', 'Info'],
                ['vtiger_portalcms.s4_nome_sistema', 'like', 'office_name'],
            ])->first();

//        $value = $blocks->s4_config_value;
        if (isset($blocks->s4_html)) {
            $blocks->s4_html = strip_tags($blocks->s4_html);
        }

        if (!empty($blocks)) {
            return $blocks;
        }
        else
        {
            return false;
        }

    }
}
if (!function_exists('getWelcomeMessage')) {

    function getWelcomeMessage()
    {
        $blocks = DB::table('vtiger_portalcms')
            ->join('vtiger_crmentity', 'vtiger_portalcms.portalcmsid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_portalcms.*')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_portalcms.s4_tipo', '=', 'Info'],
                ['vtiger_portalcms.s4_nome_sistema', 'like', 'welcome_message'],
            ])->first();

//        $value = $blocks->s4_config_value;
        if (isset($blocks->s4_html)) {
            $blocks->s4_html = strip_tags($blocks->s4_html);
        }

        if (!empty($blocks)) {
            return $blocks;
        }
        else
        {
            return false;
        }

    }
}
if (!function_exists('getHelpDeskTitle')) {

    function getHelpDeskTitle()
    {
        $blocks = DB::table('vtiger_portalcms')
            ->join('vtiger_crmentity', 'vtiger_portalcms.portalcmsid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_portalcms.*')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_portalcms.s4_tipo', '=', 'Info'],
                ['vtiger_portalcms.s4_nome_sistema', 'like', 'help_desk_title'],
            ])->first();
//        $value = $blocks->s4_config_value;
        if (isset($blocks->s4_html)) {
            $blocks->s4_html = strip_tags($blocks->s4_html);
        }


        if (!empty($blocks)) {
            return $blocks;
        }
        else
        {
            return false;
        }

    }
}
if (!function_exists('getHelpDeskMail')) {

    function getHelpDeskMail()
    {
        $blocks = DB::table('vtiger_portalcms')
            ->join('vtiger_crmentity', 'vtiger_portalcms.portalcmsid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_portalcms.*')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_portalcms.s4_tipo', '=', 'Info'],
                ['vtiger_portalcms.s4_nome_sistema', 'like', 'help_desk_mail'],
            ])->first();

//        $value = $blocks->s4_config_value;
        if (isset($blocks->s4_html)) {
            $blocks->s4_html = strip_tags($blocks->s4_html);

        }

        if (!empty($blocks)) {
            return $blocks;
        }
        else
        {
            return false;
        }

    }
}
if (!function_exists('getHelpDeskWhatsApp')) {

    function getHelpDeskWhatsApp()
    {
        $blocks = DB::table('vtiger_portalcms')
            ->join('vtiger_crmentity', 'vtiger_portalcms.portalcmsid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_portalcms.*')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_portalcms.s4_tipo', '=', 'Info'],
                ['vtiger_portalcms.s4_nome_sistema', 'like', 'help_desk_whatsapp'],
            ])->first();

//        $value = $blocks->s4_config_value;
        if (isset($blocks->s4_html)) {
            $blocks->s4_html = strip_tags($blocks->s4_html);

        }

        if (!empty($blocks)) {
            return $blocks;
        }
        else
        {
            return false;
        }

    }
}
if (!function_exists('getHelpDeskTel')) {

    function getHelpDeskTel()
    {
        $blocks = DB::table('vtiger_portalcms')
            ->join('vtiger_crmentity', 'vtiger_portalcms.portalcmsid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_portalcms.*')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_portalcms.s4_tipo', '=', 'Info'],
                ['vtiger_portalcms.s4_nome_sistema', 'like', 'help_desk_tel'],
            ])->first();

//        $value = $blocks->s4_config_value;
        if (isset($blocks->s4_html)) {
            $blocks->s4_html = strip_tags($blocks->s4_html);

        }

        if (!empty($blocks)) {
            return $blocks;
        }
        else
        {
            return false;
        }

    }
}
if (!function_exists('bunner')) {

    function bunner()
    {
        $blocks = DB::table('vtiger_portalcms')
            ->join('vtiger_crmentity', 'vtiger_portalcms.portalcmsid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_portalcms.*')
            ->where([
                ['vtiger_crmentity.deleted', '=', '0'],
                ['vtiger_portalcms.s4_tipo', '=', 'Info'],
                ['vtiger_portalcms.s4_nome_sistema', 'like', 'bunner'],
            ])->first();


        if (!empty($blocks)) {
            return $blocks;
        }
        else
        {
            return false;
        }

    }
}
