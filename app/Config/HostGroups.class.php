<?php

/**
 * Shinken WebUI - Host groups configuration
 *
 * @author Julien "TuxTop" Dumont
 */

namespace Config;

class HostGroups
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
        $a = array( 'groups' => array() );

        # Load contacts list
        $groups = $ref->app->livestatus('GET hostgroups');
        foreach ($groups[1] as $group)
        {
            $members = array();
            foreach (explode(',', $group['members']) as $member)
            {
                $members[]= "<a href=\"/search?filter=".urlencode("host:${member}")."\">${member}</a>";
            }
            $a['groups'][] = array(
                'name' => $group['name'],
                'members' => implode(', ', $members),
                'notes' => htmlentities(trim($group['notes'], '"')),
            );
        }

        # Get view
        $ref->view->output('config/hostgroups.tpl', $a);

    }


}

?>
