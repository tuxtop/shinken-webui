<?php

/**
 * Shinken WebUI Dashboard
 *
 * @author Julien "TuxTop" Dumont
 */

namespace Misc;

class Dashboard
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
        $a = array();

        # Load hosts stats
        $count = $ref->app->livestatus('GET hosts', array( 'Stats: business_impact >= 0' ), true);
        $errors = $ref->app->livestatus('GET hosts', array( 'Filter: state >= 1', 'Stats: business_impact >= 0' ), true);
        $pct = $count ? 100-round(($errors/$count)*100, 0) : 0;
        $a['stats']['hosts']['number'] = $count;
        $a['stats']['hosts']['errors'] = array(
            'number' => $errors,
            'percent' => $pct,
            'style' => $pct<98 ? 'red' : ($pct<95 ? 'orange' : 'green'),
        );

        # Load services stats
        $count = $ref->app->livestatus('GET services', array( 'Stats: business_impact >= 0' ), true);
        $errors = $ref->app->livestatus('GET services', array( 'Filter: state >= 1', 'Stats: business_impact >= 0' ), true);
        $pct = $count ? 100-round(($errors/$count)*100, 0) : 0;
        $a['stats']['services']['number'] = $count;
        $a['stats']['services']['errors'] = array(
            'number' => $errors,
            'percent' => $pct,
            'style' => $pct<98 ? 'red' : ($pct<95 ? 'orange' : 'green'),
        );

        # Compute business impact
        $color = array( 5=>'red', '4'=>'orange', '3'=>'yellow', '2'=>'light-green', '1'=>'green', '0'=>'green' );
        $text = array( 5=>'Critical', '4'=>'High', '3'=>'Medium', '2'=>'Low', '1'=>'Weak', '0'=>'None' );
        $a['business'] = array( 'style' => 'green', 'text' => 'OK' );
        for ($i=5; $i>=0; $i--)
        {
            $count = $ref->app->livestatus('GET services', array( "Filter: business_impact = ${i}", 'Stats: state >= 1' ), true);
            $total = $ref->app->livestatus('GET services', array( "Filter: business_impact = ${i}" ));
            if ($count > 0)
            {
                $a['business'] = array( 'style' => $color[$i], 'text' => $text[$i] );
                break;
            }
        }
        
        # 
        /*$daemons = array();
        $dtypes = array( 'arbitrers', 'pollers', 'schedulers', 'reactionners' );
        foreach ($dtypes as $type)
        {
            $daem = $ref->app->livestatus("GET ${type}");
            foreach ($daem[1] as $d)
            {
                $name = $d['name'];
                $alive = $d['alive'];
                $daemons[]= array(
                    'name' => $name,
                    'type' => $type,
                    'status' => array(
                        'color' => $alive ? 'green-text' : 'red-text',
                        'ico' => $alive ? 'mood' : 'mood_bad',
                    )
                );
            }
        }
        $a['daemons'] = $daemons;*/

        # Get view
        $ref->view->output('common/dashboard.tpl', $a);

    }


}

?>
