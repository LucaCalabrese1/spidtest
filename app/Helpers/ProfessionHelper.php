<?php

namespace App\Helpers;

class ProfessionHelper
{
    /** 
     * Get Contact Professions In Control Format
     * 
     * @param Array $profesisons Raw Format
     * @return Array $profesisons Control Format
     */
    public static function view($professions, string $contact)
    {
        $professionNames = [];

        foreach ($professions as $key => $profession) {
            if (is_null($profession)) {
                $professionNames[] = 'Tutte le Professioni';
            }
            if(is_array($profession)) {
                if(count($profession) == 1) {
                    foreach ($profession as $key => $professionName) {
                        $professionNames[] = $professionName['s4_name'];
                    }
                }
                if(count($profession) == 2) {
                    foreach ($profession as $key => $professionName) {
                        if ($contact == 'course') {
                            $professionNames[] = $profession[1]['s4_name'] . ' - ' . $profession[2]['s4_name'];
                        } else {
                            $professionNames[] = $profession[1]['s4_name'];
                            $professionNames[] = $profession[1]['s4_name'] . ' - ' . $profession[2]['s4_name'];
                        }

                    }
                }
            }
        }
 
        return array_unique($professionNames);
    }

    /** 
     * Get Contact Professions In Control Format
     * 
     * @param Array $profesisons Raw Format
     * @return Array $profesisons Control Format
     */
    public static function viewControl($professions, string $contact)
    {
        $professionNames = [];

        foreach ($professions as $key => $profession) {
            if (is_null($profession)) {
                $professionNames[] = 'Tutte le Professioni';
            }
            if(is_array($profession)) {
                if(count($profession) == 1) {
                    foreach ($profession as $key => $professionName) {
                        $professionNames[] = $professionName['s4_name'];
                    }
                }
                if(count($profession) == 2) {
                    foreach ($profession as $key => $professionName) {
                        if ($contact == 'course') {
                            $professionNames[] = $profession[1]['s4_name'] . ' - ' . $profession[2]['s4_name'];
                        } else {
                            $professionNames[] = $profession[1]['s4_name'];
                            $professionNames[] = $profession[1]['s4_name'] . ' - ' . $profession[2]['s4_name'];
                        }

                    }
                }
            }
        }
 
        return array_unique($professionNames);
    }

    /** 
     * Get Contact Professions In Control Format
     * 
     * @param Array $profesisons Raw Format
     * @return Array $profesisons Control Format
     */
    public static function viewProfession($professions)
    {
        $professionNames = [];

        foreach ($professions as $key => $profession) {
            if (is_null($profession)) {
                $professionNames[] = 'Tutte le Professioni';
            }
            if(is_array($profession)) {
                if(count($profession) == 1) {
                    foreach ($profession as $key => $professionName) {
                        $professionNames[] = $professionName['s4_name'];
                    }
                }
                if(count($profession) == 2) {
                    foreach ($profession as $key => $professionName) {
                        $professionNames[] = $profession[1]['s4_name'] . ' - ' . $profession[2]['s4_name'];
                    }
                }
            }

        }
 
        return array_unique($professionNames);
    }
    
    /** 
     * Convert Profesisons Array to Json 
     * For Saving into professions
     * 
     * @param Array $professions
     * @return Json $professionsToJson
     */
    public static function to_json($professions)
    {
        $professionsToJson = [];
        foreach ($professions as $key => $profession) {
            foreach ($profession as $key => $professionRec) {
                $professionsToJson[] = [ 
                    'id' => (string)$professionRec['s4multilevelid'],
                    'parent' => $professionRec['s4_parent'],
                    'name' => $professionRec['s4_name'],
                ];
            }
        }

        return json_encode($professionsToJson);
    }
}
