<?php

/**
 * App manager
 *
 * @author Julien "TuxTop" Dumont
 * @license GPLv3
 */

class App
{


    /**
     * Class attributes
     */
    public $title;      # Title for the current page
    public $view;       # HTML templating system
    public $class;      # Saved class
    public $ref;        # Propagation of the initial website references


    /**
     * __construct
     * @param $ref
     */
    public function __construct($ref)
    {

        # Default vars
        $this->title = 'Shinken';
        $this->config = &$ref->config;
        $this->ref = &$ref;

        # Start user session
        session_name('shinken');
        session_cache_expire(24*60);
        session_start();

        # Initialize templating
        $view = new \Dwoo\Core();
        if (!is_dir($_SERVER['DOCUMENT_ROOT'].'/cache')) mkdir($_SERVER['DOCUMENT_ROOT'].'/cache');
        $view->setCompileDir($_SERVER['DOCUMENT_ROOT'].'/cache');
        $view->setTemplateDir($_SERVER['DOCUMENT_ROOT'].'/views');
        $this->view = &$view;

        # Detect authentication
        $auth = isset($_SESSION['auth']) ? $_SESSION['auth'] : null;

        # Start catching output
        ob_start();

        # Requires authentication
        if (!$auth)
        {
            ob_clean();
            $this->auth();
        }

    }


    /**
     * Attach URL template to class
     */
    public function url($pattern, $class)
    {

        # Catch current URL
        $url = isset($_SERVER['REDIRECT_URL']) ? $_SERVER['REDIRECT_URL'] : null;

        # 
        $match = preg_replace('/:(\w+)/', '(?<$1>[^/]+)', $pattern);
        if (preg_match("#^${match}$#", $url, $m))
        {
            $data = array();
            foreach ($m as $tag=>$value)
            {
                if (!preg_match('/^\d+$/', $tag)) $data[$tag] = $value;
            }
            $this->class = array(
                'name' => $class,
                'data' => $data
            );
        }

    }


    /**
     * auth
     * Manage auth pages
     * 
     * @return void
     */
    private function auth()
    {

        # Init vars
        $a = array();

        # Catch sign in
        $username = isset($_POST['username']) ? $_POST['username'] : null;
        $password = isset($_POST['password']) ? $_POST['password'] : null;

        # Digest password
        if ($this->config['auth_digest'] != 'none') $password = openssl_digest($password, $this->config['auth_digest']);

        # Perform sign in
        if ($username and $password)
        {
            switch ($this->config['auth_method'])
            {
                case 'basic':
                    if (isset($this->config['auth_list'][$username]) and $this->config['auth_list'][$username] == $password)
                    {
                        $_SESSION['auth'] = array( 'username' => $username );
                        header('Location: /');
                    }
                    else
                    {
                        $a['message'] = array(
                            'text' => 'Invalid credentials.',
                            'style' => 'red darken-4 white-text'
                        );
                    }
                    break;
                default:
                    $a['message'] = array(
                        'text' => 'Authentication method is not supported.',
                        'style' => 'orange darken-4 white-text'
                    );
            }
        }

        # Get view
        $this->view->output('common/login.tpl', $a);

    }


    /**
     * run
     * Display application content
     * 
     * @return void
     */
    public function run()
    {

        # No class
        if (!$this->class)
        {
            http_response_code(404);
            ob_end_clean();
            print <<<HTML
<div class="container">
 <div class="card-panel red darken-4 white-text error">
  <h1>Page not found</h1>
  The page you've requested for does not exists!
 </div>
</div>
HTML;
        }
        else
        {
            try
            {
                $clname = $this->class['name'];
                new $clname($this->ref);
            }
            catch (Exception $e)
            {
                http_response_code(500);
                ob_end_clean();
                print <<<HTML
<div class="container">
 <div class="card-panel black darken-4 white-text error">
  <h1>Failed to load resources</h1>
  The page you've requested fails to load.<br />
  Please open an issue.
 </div>
</div>
HTML;
            }
        }
        $content = ob_get_contents();
        ob_end_clean();

        # Print HTML skeleton
        print <<<HTML
<!DOCTYPE html>

<html>
 <head>
  <title>$this->title</title>
  <link href="/vendors/materialize/css/materialize.min.css" rel="STYLESHEET" type="text/css" media="screen" />
  <link href="/vendors/material-icons/material-icons.css" rel="STYLESHEET" type="text/css" media="screen" />
  <link href="/static/css/theme.css" rel="STYLESHEET" type="text/css" media="screen" />
  <script type="text/javascript" src="/vendors/jquery/jquery-2.2.4.min.js"></script>
  <script type="text/javascript" src="/vendors/materialize/js/materialize.min.js"></script>
 </head>
 <body>
  ${content}
 </body>
</html>
HTML;

        # Prevent any other execution
        exit(0);

    }


}

?>
