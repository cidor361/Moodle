<?php
    $capability  =  array (

    'block / simplehtml: myaddinstance'  =>  array (
        'captype'  =>  'write' ,
        'contextlevel'  => CONTEXT_SYSTEM ,
        'archetypes'  =>  array (
            'user'  => CAP_ALLOW
        ) ,

        'clonepermissionsfrom'  =>  'moodle / my: manageblocks'
    ) ,

    'block / simplehtml: addinstance'  =>  array (
        'riskbitmask'  => RISK_SPAM | RISK_XSS ,

        'captype'  =>  'write' ,
        'contextlevel'  => CONTEXT_BLOCK ,
        'archetypes'  =>  array (
            'editingteacher'  => CAP_ALLOW ,
            'manager'  => CAP_ALLOW
        ) ,

        'clonepermissionsfrom'  =>  'moodle / site: manageblocks'
    ) ,
) ;

    /**
    enrol/xxx:enrol - Must be defined when enrol_plugin::allow_enrol() returns true.
    enrol/xxx:unenrol - Must be implemented when enrol_plugin::allow_unenrol() or enrol_plugin::allow_unenrol_user() returns true.
    enrol/xxx:manage - Must be implemented when enrol_plugin::allow_manage() returns true.
    enrol/xxx:unenrolself - Usually implemented when plugin support self-unenrolment.
    enrol/xxx:config - Implemented when plugin allows user to modify instance properties. Automatic synchronisation plugins do not usually need this capability.
     */