<?php
function xmldb_message_jabber_install(){
    global $DB;

    $result = true;

    $provider = new object();
    $provider->name  = 'jabber';
    if (!$DB->insert_record('message_processors', $provider)) {
        $return = false;
    }
    return $result;
}
