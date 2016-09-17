<?php

/**
 * Shinken WebUI - Search Engine page
 *
 * @author Julien "TuxTop" Dumont
 */

namespace Status;

class Search
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
        $a = $rules = array();
        $rules['_list'] = array();
        $rules['type'] = 'services';

        # Get filter
        $raw = isset($_GET['filter']) ? $_GET['filter'] : '';
        $filter = trim(urldecode($raw));
        if (!$filter) $filter = 'type:services';

        # Explode filter
        foreach (explode(' ', $filter) as $f)
        {
            if (preg_match('/^(\w+)([:<>=]+)(.+)$/', $f, $m))
            {
                list(, $key, $match, $value) = $m;
                switch ($key)
                {
                    case 'type':
                        $rules['type'] = $value;
                        break;
                    case 'bi':
                        $rules['_list'][] = "Filter: business_impact ${match} ${value}";
                        break;
                    case 'state':
                        $rules['_list'][] = "Filter: state ${match} ${value}";
                        break;
                    default:
                        throw new \Exception("Invalid filter ${f}!");
                }
            }
            elseif (preg_match('/^\w+$/', $f))
            {
                $rules['_keywords'] = $f;
            }
            else
            {
                throw new \Exception("Invalid filter ${f}!");
            }
        }

        # States
        $states = array(
            0 => array( 'color' => 'green', 'text' => 'OK', 'background' => '' ),
            1 => array( 'color' => 'orange', 'text' => 'WARNING', 'background' => 'orange lighten-4' ),
            2 => array( 'color' => 'red', 'text' => 'CRITICAL', 'background' => 'red lighten-4' ),
            3 => array( 'color' => 'purple', 'text' => 'UNKNOWN' ),
            255 => array( 'color' => 'light-blue', 'text' => 'PLUGIN ERROR' ),
        );

        # Compute Search
        $arr = array();
        if ($rules['type'] == 'hosts') $arr[]= 'Filter: description = PING';
        if (isset($rules['_keywords']))
        {
            $arr[]= 'Filter: description ~~ '.$rules['_keywords'];
        }
        if (count($rules['_list'])) $arr = array_merge($arr, $rules['_list']);
        $arr[]= 'Limit: 50';
        $svcs = $ref->app->livestatus('GET services', $arr);

        # Prepare results
        $prevname = null;
        $bg = 'odd';
        $a['results'] = array();
        foreach ($svcs[1] as $svc)
        {
            $hostname = $svc['host_name'];
            $displayname = $hostname == $prevname ? '&nbsp;' : $hostname;
            $a['results'][] = array(
                'hostname'   => $displayname,
                'background' => $bg,
                'service'    => $svc['display_name'],
                'output'     => $svc['plugin_output'],
                'longoutput' => $svc['long_plugin_output'],
                'lgout_mode' => !empty($svc['long_plugin_output']),
                'status'     => $states[$svc['state']],
                'url'        => urlencode($svc['host_name']).'/'.urlencode($svc['display_name']),
            );
            $prevname = $hostname;
            $bg = $bg == 'odd' ? 'even' : 'odd';
        }

        # No result
        if (!count($a['results'])) $a['no_results'] = true;
        
        # Get view
        $ref->view->output('status/search.tpl', $a);

    }


}

?>
