<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\RedirectResponse;

use App\Utils\Util;
use App\Utils\PageRenderer;

class FileHandler_r2 extends AbstractController {

    private $dbconn;
    private $util;
    private $page_renderer;

    public function __construct()
    {
		$this->dbconn = pg_connect("host=" . getenv('DB_HOST') . " port=5432 dbname=" . getenv('DB_NAME') . " user=" . getenv('DB_USERNAME') . " password=" . getenv('DB_PASSWORD'));
        $this->util = new Util();
        $this->page_renderer = new PageRenderer();
    }

    /**
	* Matches anything tbh
	*
	* @Route("/{routing_name}", name="serve_file")
    */
    
    public function serve_file(Request $request, $routing_name)
    {
        $routing_type = $this->util->routing_type($routing_name);
        $routing_options = $this->util->routing_options($routing_name, $routing_type);
        $domain_authorization = $this->util->domain_authorization($_SERVER['HTTP_HOST']);

        if($domain_authorization['authorized']){

            if(!empty($routing_type) && !empty($routing_options)){

                if($domain_authorization['bucket'] != getenv('DEFAULT_BUCKET') && $routing_options['type'] == 'file'){
                    $routing_options['raw_url'] = getenv('CUSTOM_BUCKET_HOST').'/'.$domain_authorization['bucket'].'/'.$routing_name;
                }
                if($routing_options['fh_enabled'] == true){
                    if($routing_type == 'image'){
                        return new Response($this->page_renderer->render_image($routing_options));
                    }elseif($routing_type == 'audio'){
                        return new Response($this->page_renderer->render_audio($routing_options));
                    }elseif($routing_type == 'video'){
                        return new Response($this->page_renderer->render_video($routing_options));
                    }elseif($routing_type == 'short_url'){
                        return new Response($this->page_renderer->render_short_url($routing_options));
                    }elseif($routing_type == 'json'){
                        $response = new Response($routing_options['json']);
                        $response->headers->set('Content-Type', 'application/json');
                        return $response;
                    }elseif($routing_type == 'none'){
                        return new Response($this->page_renderer->render_none($routing_options));
                    }
                }else{
                    return $this->redirect($routing_options['raw_url']);
                }
            }else{
                return $this->page_renderer->handle_no_type();
            }

        }else{
            $response = new Response(json_encode(
                [
                    'success' => false,
                    'error_details' => [
                        'error_code' => 01,
                        'error_message' => 'This domain is not registered for use with the RATELIMITED services. Please add it via the API.',
                        'unauthorized_domain' => $_SERVER['HTTP_HOST'],
                    ],
                ]
            ));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }
    }
}
