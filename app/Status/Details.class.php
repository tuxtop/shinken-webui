<?php

/**
 * Shinken WebUI - Search Engine page
 *
 * @author Julien "TuxTop" Dumont
 */

namespace Status;

class Details
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
        $data = (object)$ref->app->class['data'];
        foreach ($data as $key=>$val) $data->$key = urldecode($val);
        $intv_steps = [ 'hour(s)'=>60*60, 'minute(s)'=>60, 'second(s)'=>1 ];

        # Get config
        $res = $ref->app->livestatus('GET status');
        $intv = $res[1][0]['interval_length'];

        # Load all informations
        $res = $ref->app->livestatus('GET services', [ "Filter: description = $data->service", "Filter: host_name = $data->host" ]);
        if (!$res or count($res[1])!=1)
        {
            http_response_code(404);
            $ref->view->output('status/notfound.tpl', [ 'host'=>$data->host, 'service'=>$data->service ]);
            exit(0);
        }
        list($headers, $rows) = $res;
        $row = $rows[0];

        # Update vars: last check
        $row['last_check_date'] = date('d/m/Y @ H:i:s', $row['last_check']);
        $fmt = [];
        $diff = (new \DateTime())->diff(new \DateTime(date('Y-m-d H:i:s', $row['last_check'])));
        $fmt_labels = [ 'days'=>'day(s)', 'h'=>'hour(s)', 'm'=>'minute(s)', 'i'=>'second(s)' ];
        foreach ($fmt_labels as $flag=>$label)
        {
            if ($diff->$flag) $fmt[]= $diff->$flag.' '.$label;
        }
        $row['last_check_intv'] = implode(', ', $fmt);

        # Update vars: ** interval
        foreach (['check','retry','notification'] as $type)
        {
            $row[$type.'_interval']*= $intv;
            $fmt = [];
            $r = $row[$type.'_interval'];
            foreach ($intv_steps as $sname=>$sp)
            {
                $v = intval($r/$sp);
                if ($v>0)
                {
                    $fmt[]= $v.' '.$sname;
                    $r-= $v*$sp;
                }
            }
            $row[$type.'_interval'] = implode(', ', $fmt);
        }

        # Get view
        $ref->view->output('status/details.tpl', $row);

    }


}

?>
