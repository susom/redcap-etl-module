<?php

namespace IU\RedCapEtlModule;

/**
 * Class for methods that access the REDCap database directly.
 */
class RedCapDb
{
    public function getUserInfo($username)
    {
        $userInfo = array();
        $sql = "select ui_id, username, user_firstname, user_lastname, user_email "
            ." from redcap_user_information "
            ." where username = '".$username."' and user_suspended_time is null "
            ;
        $result = db_query($sql);
        if ($row = db_fetch_assoc($result)) {
            $userInfo = $row;
        }

        return $userInfo;
    }

    /**
     * Returns information on all REDCap users that have not been suspended who have the
     * specified term in their username, first name, last name or (first) e-mail.
     */
    public function getUserSearchInfo($term)
    {
        $users = array();
        $sql = "select ui_id as id, "
            ." concat(username, ' (', user_firstname, ' ', user_lastname, ') - ', user_email) as value, username "
            ." from redcap_user_information "
            ." where user_suspended_time is null and "
            ."     (username like '%".$term."%' "
            ."     or user_firstname like '%".$term."%'"
            ."     or user_lastname like '%".$term."%'"
            ."     or user_email like '%".$term."%'"
            ."     ) "
            ;
        $result = db_query($sql);
        while ($row = db_fetch_assoc($result)) {
            array_push($users, $row);
        }

        return $users;
    }

    public function getUserProjects($username)
    {
        $projects = array();
        $sql = 'select u.username, p.project_id, p.app_title, '
            .' if(u.api_token is null, 0, 1) as has_api_token, u.api_export '
            .' from redcap_projects p, redcap_user_rights u '
            ." where u.username = '".$username."' "
            ." and p.project_id = u.project_id and p.date_deleted is null"
            ;
        $result = db_query($sql);
        while ($row = db_fetch_assoc($result)) {
            array_push($projects, $row);
        }
        return $projects;
    }
    
    /**
     * Get the API token for the specified user and project.
     *
     * @param string $username the username for the API token.
     * @param string $projectId the project ID for the API token.
     *
     * @return string the API token for the specified user and project.
     */
    public function getApiToken($username, $projectId)
    {
        $apiToken = null;
        
        $sql = "select api_token from redcap_user_rights "
            . " where project_id = ".PROJECT_ID." "
            . " and username = '".USERID."'"
            . " and api_export = 1 "
            ;
        $result = db_query($sql);
        if ($row = db_fetch_assoc($result)) {
            $apiToken = $row['api_token'];
        }
        return $apiToken;
    }

    //
    
    // TRANSACTIONS
    //
    //    db_query("SET AUTOCOMMIT=0");
    //    db_query("BEGIN");
    //    queries... (if false returned, consider it an error)
    //    // If errors, do not commit
    //    $commit = ($errors > 0) ? "ROLLBACK" : "COMMIT";
    //    db_query($commit);
    //    // Set back to initial value
    //    db_query("SET AUTOCOMMIT=1");
}