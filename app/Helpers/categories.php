<?php

use App\Attachment;
use App\Category;
use App\Course;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

if (!function_exists('categories')) {
    function categories()
    {
        $categoriesFathers = getAllFathers();
        $categories = [];
        $attachments = new Attachment;

        foreach ($categoriesFathers as $key => $category) {
            $categoryId = $category->mycproductcategoriesid;
            $childs = getAllChilds($categoryId);

            $checkIfFather = checkIfFather($categoryId);

            $imgUrl = new Attachment;
            $imgUrl = getAttachemtImageById($categoryId);

            $categories[$category->mycproductcategoriesid] = [
                'id' => $categoryId,
                'name' => $category->name,
                'description' => $category->shortdescription,
                'slug' => $category->slug,
                'ifFather' => $checkIfFather,
                'imgUrl' => $imgUrl,
                'childs' => compact('childs'),
                'total' => getCoursesTotal($categoryId) > 0 ? '(' . getCoursesTotal($categoryId) . ')' : null,
                'totalRaw' => getCoursesTotal($categoryId)
            ];
        }
        return $categories;

    }

}
if (!function_exists('getCategoryById')) {
    function getCategoryById($categoryId)
    {
        $categories = DB::table('vtiger_mycproductcategories')
            ->join('vtiger_crmentity', 'vtiger_mycproductcategories.mycproductcategoriesid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_mycproductcategories.*')
            ->where([
                ['vtiger_mycproductcategories.showonwebsite', '=', '1'],
                ['vtiger_mycproductcategories.mycproductcategoriesid', '=', $categoryId],
                ['vtiger_crmentity.deleted', '=', '0'],
            ])->get();
        return $categories;
    }

}
if (!function_exists('getAllFathers')) {
    function getAllFathers()
    {
//        $categories = Category::with('entity')->orderBy('mycproductcategoriesid', 'desc')
//            ->withCount('entity')
//            ->whereHas('entity', function ($query)  {
//                $query->where('deleted', '0');
//                $query->where('showonwebsite', '1');
//                $query->where('parentcategory', 0);
//            })->get()->map->only('name', 'slug', 'mycproductcategoriesid', 'shortdescription');
        $categories = DB::table('vtiger_mycproductcategories')
            ->join('vtiger_crmentity', 'vtiger_mycproductcategories.mycproductcategoriesid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_mycproductcategories.*')
            ->where([
                ['vtiger_mycproductcategories.showonwebsite', '=', '1'],
                ['vtiger_mycproductcategories.parentcategory', '=', '0'],
                ['vtiger_crmentity.deleted', '=', '0'],
            ])->get();
        return $categories;
    }

}

if (!function_exists('getAllChilds')) {

     function getAllChilds($fatherId)
    {
        $categoryChilds = DB::table('vtiger_mycproductcategories')
            ->join('vtiger_crmentity', 'vtiger_mycproductcategories.mycproductcategoriesid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_mycproductcategories.*')
            ->where([
                ['vtiger_mycproductcategories.showonwebsite', '=', '1'],
                ['vtiger_mycproductcategories.parentcategory', '=', $fatherId],
                ['vtiger_crmentity.deleted', '=', '0'],
            ])->get();

        return $categoryChilds;
    }
}
if (!function_exists('checkIfFather')) {

    function checkIfFather($fatherId)
    {
        $categoryChilds = DB::table('vtiger_mycproductcategories')
            ->join('vtiger_crmentity', 'vtiger_mycproductcategories.mycproductcategoriesid', '=', 'vtiger_crmentity.crmid')
            ->select('vtiger_mycproductcategories.*')
            ->where([
                ['vtiger_mycproductcategories.showonwebsite', '=', '1'],
                ['vtiger_mycproductcategories.parentcategory', '=', $fatherId],
                ['vtiger_crmentity.deleted', '=', '0'],
            ])->count();

        if ($categoryChilds >= 1) {
            return true;
        }
    }
}
if (!function_exists('getCoursesTotal')) {
    function getCoursesTotal($categoryId)
    {
        $courses = new Course;
        $courses = coursesRaw($categoryId);
        $coursesCount = count($courses);
        if ($coursesCount>0) {
            //$categoriesTotal = '('.count($courses).')';
            $categoriesTotal = count($courses);
            return $categoriesTotal;
        }
    }

}
if (!function_exists('getCoursesTotalbyId')) {
    function getCoursesTotalbyId($accountId, $categoryId)
    {
        $courses = Course::query()->with('entity')
            ->whereHas('entity', function ($query) {
                $query->where('deleted', '0');
            })
            ->select("*")
            ->where([
                ['vtiger_products.mycproductcategory', '=', $categoryId],
            ])
            ->where('s4_sponsor', 'LIKE', '%'.$accountId.'%')->count();

        return $courses;
    }

}
