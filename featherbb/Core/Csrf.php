<?php
/**
* CSRF Guard
*
* Use this middleware with your Slim Framework application
* to protect you from CSRF attacks.
*
*/

namespace FeatherBB;

class Csrf extends \Slim\Middleware
{
    protected $key,
    $token;

    /**
    * Call middleware.
    *
    * @return void
    */
    public function call()
    {
        // Attach as hook.
        $this->app->hook('slim.before', array($this, 'check'));

        // Call next middleware.
        $this->next->call();
    }

    /**
    * Check CSRF token is valid.
    * Note: Also checks POST data to see if a Moneris RVAR CSRF token exists.
    *
    * @return void
    */
    public function check() {
        // Check sessions are enabled.
        if (session_id() === '') {
            throw new \Exception('Sessions are required to use the CSRF Guard middleware.');
        }

        $this->key = 'featherbb_csrf';

        if (!isset($_SESSION[$this->key])) {
            $_SESSION[$this->key] = sha1(serialize($_SERVER) . rand(0, 0xffffffff));
        }

        $this->token = $_SESSION[$this->key];

        // Validate the CSRF token.
        if (in_array($this->app->request()->getMethod(), array('POST', 'PUT', 'DELETE'))) {
            $userToken = $this->app->request()->post($this->key);
            if ($this->token !== $userToken) {
                $this->app->halt(400, 'Invalid or missing CSRF token.');
            }
        }

        // Assign CSRF token key and value to view.
        $this->app->view()->appendData(array(
            'csrf_key'      => $this->key,
            'csrf_token'    => $this->token,
        ));
        $this->app->view2->replace(array(
            'csrf_key'      => $this->key,
            'csrf_token'    => $this->token,
        ));
    }
}
