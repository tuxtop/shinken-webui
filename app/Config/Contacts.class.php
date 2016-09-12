<?php

/**
 * Shinken WebUI - Contacts configuration
 *
 * @author Julien "TuxTop" Dumont
 */

namespace Config;

class Contacts
{


    /**
     * Class attributes
     */


    /**
     * __construct
     * Classs constructor
     *
     * @param $ref object References to other classes
     * @return class
     */
    public function __construct($ref)
    {

        # Init vars
        $a = array( 'contacts' => array() );

        # Load contacts list
        $contacts = $ref->app->livestatus('GET contacts');
        foreach ($contacts[1] as $contact)
        {
            $a['contacts'][] = array(
                'name' => $contact['name'],
                'email' => $contact['email'],
                'notifications' => array(
                    'hosts' => $contact['host_notifications_enabled'] ? '<i class="material-icons green-text">check</i>' : '<i class="material-icons red-text">close</i>',
                    'services' => $contact['service_notifications_enabled'] ? '<i class="material-icons green-text">check</i>' : '<i class="material-icons red-text">close</i>',
                ),
                'commands' => $contact['can_submit_commands'] ? '<i class="material-icons green-text">check</i>' : '<i class="material-icons red-text">close</i>',
            );
        }

        # Get view
        $ref->view->output('config/contacts.tpl', $a);

    }


}

?>
