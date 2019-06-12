<?php
namespace App\Utils;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Session\Session;

class PageRenderer {

    private $loader;
    private $twig;

    public function __construct()
    {
			/* Initialize Twig */
			$this->loader = new \Twig_Loader_Filesystem(__DIR__.'/../../templates');
			$this->twig = new \Twig_Environment($this->loader, [
				'cache' => __DIR__.'/../../templates_c',
			]);
    }

    public function render_image($routing_options)
    {
        $template = $this->twig->load('image/no_ads.twig.html');
        return $template->render($routing_options);
    }

    public function render_audio($routing_options)
    {
        $template = $this->twig->load('audio/no_ads.twig.html');
        return $template->render($routing_options);
    }
    
    public function render_video($routing_options)
    {
        $template = $this->twig->load('video/no_ads.twig.html');
        return $template->render($routing_options);
    }

    public function render_short_url($routing_options)
    {
        $template = $this->twig->load('short_url_warn/no_ads.twig.html');
        return $template->render($routing_options);
    }

    public function render_none($routing_options)
    {
        $template = $this->twig->load('no_embed/no_ads.twig.html');
        return $template->render($routing_options);
    }

}