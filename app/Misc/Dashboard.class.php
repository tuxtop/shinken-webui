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
        $pct = $count ? 100-round(($errors/$count)*100, 2) : 0;
        $a['stats']['hosts']['number'] = $count;
        $a['stats']['hosts']['errors'] = array(
            'number' => $errors,
            'percent' => $pct,
            'style' => $pct<98 ? 'red' : ($pct<95 ? 'orange' : 'green'),
        );

        # Load services stats
        $count = $ref->app->livestatus('GET services', array( 'Stats: business_impact >= 0' ), true);
        $errors = $ref->app->livestatus('GET services', array( 'Filter: state >= 1', 'Stats: business_impact >= 0' ), true);
        $pct = $count ? 100-round(($errors/$count)*100, 2) : 0;
        $a['stats']['services']['number'] = $count;
        $a['stats']['services']['errors'] = array(
            'number' => $errors,
            'percent' => $pct,
            'style' => $pct<98 ? 'red' : ($pct<95 ? 'orange' : 'green'),
        );

        # Compute business impact
        $a['business'] = array( 'style' => 'green', 'text' => 'OK' );
        $res = $ref->app->livestatus('GET services', array(
            'Filter: host_name >= a',
            'Stats: state >= 1',
            'Columns: business_impact'
        ), true);
        $tmp = array();
        foreach (explode('\n', $res) as $line)
        {
            list($bi, $error) = explode(';', $line);
            $tmp[$bi] = $error;
        }
        krsort($tmp);
        $color = array( 5=>'red', '4'=>'orange', '3'=>'yellow', '2'=>'light-green', '1'=>'green', '0'=>'green' );
        $text = array( 5=>'Critical', '4'=>'High', '3'=>'Medium', '2'=>'Low', '1'=>'Weak', '0'=>'None' );
        foreach ($tmp as $bi=>$error)
        {
            if ($error<=0) continue;
            $a['business'] = array( 'style' => $color[$bi], 'text' => $text[$bi] );
            break;
        }

        # Get view
        $ref->view->output('common/dashboard.tpl', $a);

    }


}

?>
