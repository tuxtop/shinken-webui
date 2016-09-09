<?php

/**
 * HTML templating
 *
 * @author Julien "TuxTop" Dumont
 */

class Template
{


    /**
     * __construct
     * Class constructor
     *
     * @param $template string Path to template
     * @param $data object Data to use to replace tags in template
     * @return class
     */
    public function __construct()
    {
        # Nothing to do
    }


    /**
     * get
     * Return interpreted content
     *
     * @return string
     */
    public function get($template, $data=null)
    {

        # Replace flag
        $template = str_replace('~', $_SERVER['DOCUMENT_ROOT'].'/views', $template);

        # Test file
        if (!file_exists($template)) throw new \Exception("Could not load template file ${template}!");

        # Load raw content
        $raw = file_get_contents($template);

        # Mark "if/for" block
        $tagIDs = array( 'id'=>0, 'for'=>0 );
        foreach (explode("\n", $raw) as $line)
        {
            if (preg_replace('/\{\{(if|for) (.*)\}\}/', $line, $m))
            {
                list(, $tag, $tagCond) = $m;
                $line = preg_replace('/\{\{(if|for) (.*)\}\}/', '{{$1:'.$tagIDs[$tag].' $2}}', $line);
                $tagIDs[$tag]++;
            }
            $nraw.= "$line\n";
        }

        # Return content
        return $content;

    }


}

?>
