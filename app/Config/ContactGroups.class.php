<?php

/**
 * Shinken WebUI - Contact groups configuration
 *
 * @author Julien "TuxTop" Dumont
 */

namespace Config;

class ContactGroups
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
        $groups = $ref->app->livestatus('GET contactgroups');
        foreach ($groups[1] as $group)
        {
            $a['groups'][] = array(
                'name' => $group['name'],
                'alias' => $group['alias'] ? $group['alias'] : '--',
                'members' => str_replace(',', ', ', $group['members'])
            );
        }

        # Get view
        $ref->view->output('config/contactgroups.tpl', $a);

    }


}

?>
