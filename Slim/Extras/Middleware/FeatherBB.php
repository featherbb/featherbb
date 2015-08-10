<?php
/**
 * FeatherBB
 *
 *
 * USAGE
 *
 * $app = new \Slim\Slim();
 * $app->add(new \Slim\Extras\Middleware\FeatherBB());
 *
 */
namespace Slim\Extras\Middleware;

class FeatherBB extends \Slim\Middleware
{
	protected $settings = array();

	public function __construct()
	{

	}

    public function call() 
    {
        $feather = $this->app;

       	// Block prefetch requests
        $this->app->hook('slim.before', function () use ($feather) {
        	if ((isset($feather->environment['HTTP_X_MOZ'])) && ($feather->environment['HTTP_X_MOZ'] == 'prefetch')) {
                $feather->halt(403, 'Prefetch forbidden');
        	}
        });

        // No cache headers
        $this->app->response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $this->app->response->headers->set('Pragma', 'no-cache');
        $this->app->expires(0);

        // For servers which might be configured to send something else
        $this->app->response->headers->set('Content-type', 'text/html');
        // Prevent the site to be embedded in iFrame
        $this->app->response->headers->set('X-Frame-Options', 'deny');
        // Yeah !
        $this->app->response->headers->set('X-Powered-By', 'FeatherBB');

        // Call next middleware.
        $this->next->call();
    }


}
