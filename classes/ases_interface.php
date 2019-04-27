<?php
namespace local_customgrader;
class ases_interface
{
    const TRACKING_STATUS_ACTIVE = 1;

    /**
     * @param $mdl_id string|number related to mdl_user.id
     */
    static function is_ases_by_mdl_id($mdl_id)
    {
        global $DB;
        return $DB->record_exists(
            'talentospilos_user_extended',
            array(
                'id_moodle_user' => $mdl_id,
                'tracking_status' => self::TRACKING_STATUS_ACTIVE
            ));
    }
}