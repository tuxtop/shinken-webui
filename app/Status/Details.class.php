<?php

/**
 * Shinken WebUI - Details page
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

        # Update vars: last **
        foreach (['last_check', 'last_state_change'] as $idx)
        {
            $row[$idx.'_date'] = date('d/m/Y @ H:i:s', $row[$idx]);
            $fmt = [];
            $diff = (new \DateTime())->diff(new \DateTime(date('Y-m-d H:i:s', $row[$idx])));
            $fmt_labels = [ 'days'=>'day(s)', 'h'=>'hour(s)', 'i'=>'minute(s)', 's'=>'second(s)' ];
            foreach ($fmt_labels as $flag=>$label)
            {
                if ($diff->$flag <= 0) continue;
                $fmt[]= $diff->$flag.' '.$label;
                if (count($fmt) >= 2) break;
            }
            $row[$idx.'_intv'] = implode(', ', $fmt);
        }

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
        $ref->app->title.= ' - Service details: '.$data->service.' @ '.$data->host;
        $ref->view->output('status/details.tpl', $row);

    }


}

?>
