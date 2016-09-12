<?php

/**
 * Shinken WebUI Shinken status
 *
 * @author Julien "TuxTop" Dumont
 */

namespace Misc;

class Status
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
        $a = array( 'daemons' => array() );

        # Get daemons
        $daemons = array( 'arbitrer', 'poller', 'scheduler', 'broker', 'reactionner' );
        foreach ($daemons as $daemon)
        {
            list($headers, $rows) = $ref->app->livestatus("GET ${daemon}s");
            foreach ($rows as $row)
            {
                $a['daemons'][]= array(
                    'name' => $row['name'],
                    'type' => $daemon,
                    'status' => array(
                        'ico' => $row['alive'] ? 'mood' : 'mood_bad',
                        'style' => $row['alive'] ? 'green' : 'red',
                    )
                );
            }
        }

        # Get view
        $ref->view->output('common/status.tpl', $a);

    }


}

?>
