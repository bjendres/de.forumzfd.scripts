<?php

/**
* Synchronize the user-pictures to civicrm
**/
function civicrm_api3_forumzfd_scripts_syncpictures($params) {

    $public_url = "https://www.forumzfd-akademie.de/sites/default/files/";
    $domain_id = 9;
    $drupal_db_user='afk';
    $drupal_db_name='afk';
    $drupal_db_pass='passwd';
    $query = "
        SELECT u.uid, m.uri
        FROM users u
        LEFT JOIN file_managed m
        ON u.picture = m.fid
        ";

    if (!$drupal_db_link = mysql_connect('localhost', $drupal_db_user, $drupal_db_pass)) {
        error_log("syncpictures: Could not connect to mysql");
        return civicrm_api3_create_error("syncpictures: Could not connect to mysql");
    }

    if (!mysql_select_db($drupal_db_name, $drupal_db_link)) {
        error_log("syncpictures: Could not connect to database");
        return civicrm_api3_create_error("syncpictures: Could not connect to database");
    }

    $result = mysql_query($query, $drupal_db_link);
    if (!$result) {
        error_log("syncpictures: Could not query the database");
        return civicrm_api3_create_error("syncpictures: Could not query the database");
    }

    $count=0;
    $error=0;
    while($row = mysql_fetch_array($result)) {
        $uid = $row['uid'];
        $uri = $row['uri'];
        if ($uri) {
            $url = str_replace('public://', $public_url, $uri);
        } else {
            $url='';
        }

        $query="
            UPDATE civicrm_contact c
            LEFT JOIN civicrm_uf_match u
            ON c.id = u.contact_id
            SET c.image_URL = '$url'
            WHERE u.uf_id = $uid
            AND u.domain_id = $domain_id
            ";
        $return = CRM_Core_DAO::executeQuery($query);

        if (is_array($return) && $return['is_error']) {
            error_log('syncpictures: ' . $return['error_code']);
            $error++;
        } else {
            $count++;
        }
    }

    mysql_close($drupal_db_link);
    return civicrm_api3_create_success($count);
}
