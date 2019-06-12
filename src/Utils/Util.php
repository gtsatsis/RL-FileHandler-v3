<?php
namespace App\Utils;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Session\Session;

class Util {

    private $dbconn;
    private $routing_types;

    public function __construct()
    {
        $this->dbconn = pg_connect("host=" . getenv('DB_HOST') . " port=5432 dbname=" . getenv('DB_NAME') . " user=" . getenv('DB_USERNAME') . " password=" . getenv('DB_PASSWORD'));
        /* Routing Types are condensed in order to not clutter up the code, if editing, them, de-condense. */
        $this->routing_types = ['image' => ['png','bmp','jpg','jpeg','gif',],'audio' => ['mp3','ogg','wav','flac',],'video' => ['mp4','avi','webm',],];
    }


    /* Function to figure out where the file/shorturl should be routed to */
    public function routing_type(string $routing_name)
    {
        /* Condense the name and the extension into an array for easy access */
        $routing_name = ['name' => $routing_name, 'extension' => pathinfo($routing_name, PATHINFO_EXTENSION)];

        if(in_array($routing_name['extension'], $this->routing_types['image'])){
            return 'image';
        }elseif(in_array($routing_name['extension'], $this->routing_types['audio'])){
            return 'audio';
        }elseif(in_array($routing_name['extension'], $this->routing_types['video'])){
            return 'video';
        }elseif(mb_substr($routing_name['name'], 0, 2) == '~.'){
            return 'short_url';
        }elseif(mb_substr($routing_name['name'], 0, 6) == '~json.'){
            return 'json';
        }else{
            return 'none';
        }
    }

    /* Function to get the renderer options for the routing name/type */
    public function routing_options($routing_name, $routing_type){
        if($routing_type == 'json'){
            pg_prepare($this->dbconn, "get_json", "SELECT json FROM json_uploads WHERE url = $1");
            $database_array = pg_fetch_array(pg_execute($this->dbconn, "get_json", array($routing_name)));

            $response_array = [
                'fh_enabled' => true,
                'json' => $database_array['json'],
            ];

            return $response_array;
        }elseif($routing_type == 'short_url'){

            pg_prepare($this->dbconn, "get_short_url", "SELECT * FROM shortened_urls WHERE short_name = $1");
            $database_array = pg_fetch_array(pg_execute($this->dbconn, "get_short_url", array($routing_name)));

            $response_array = [ /* Create an array of the response variables */
                'fh_enabled' => false,
                'raw_url' => $database_array['url'], 
                'url' => $database_array['url'],
                'safe' => true,
                'original_url' => $database_array['url'],
                'date_date' => date('Y-m-d', $database_array['timestamp']),
                'date_time' => date('H:i:s', $database_array['timestamp']),
            ];

            if($database_array['url_safe'] == "t"){
                $response_array['safe'] = true;
            }else{
                $response_array['safe'] = false;
                $response_array['fh_enabled'] = true; /* If not safe, specify that we want RLFH to be enabled */
            }

            return $response_array;

        }else{

            pg_prepare($this->dbconn, "get_routed_file", "SELECT * FROM files WHERE filename = $1");
            $database_array = pg_fetch_array(pg_execute($this->dbconn, "get_routed_file", array($routing_name)));

            pg_prepare($this->dbconn, "get_user_by_routed_file", "SELECT * FROM users WHERE id = $1");
            $database_array_user = pg_fetch_array(pg_execute($this->dbconn, "get_user_by_routed_file", array($database_array['user_id'])));
            
            $response_array = [
                'fh_enabled' => true,
                'type' => 'file', /* Set type to be able to set a custom bucket */
                'ads' => false, /* Ads shall be disabled until further notice */
                'raw_url' => getenv('RAW_URL').$routing_name,
                'original_filename' => $database_array['originalfilename'],
                'file_description' => 'N/A',
                'date_date' => date('Y-m-d', $database_array['timestamp']),
                'date_time' => date('H:i:s', $database_array['timestamp']),
                'embed_url' => getenv('EMBED_URL').$routing_name,
            ];

            if($database_array_user['fh_enabled'] == 't' || !array_key_exists('fh_enabled', $database_array_user)){
                $response_array['fh_enabled'] = true;
            }else{
                $response_array['fh_enabled'] = false;
            }

            return $response_array;
        }
    }

    public function domain_authorization($domain)
    {
        $domain_regex = preg_replace("/^([a-zA-Z0-9].*\.)?([a-zA-Z0-9][a-zA-Z0-9-]{1,61}[a-zA-Z0-9]\.[a-zA-Z.]{2,})$/", '$2', $domain);

        pg_prepare($this->dbconn, "get_domain_authorization", "SELECT *, COUNT(*) from domains WHERE domain_name = $1 AND verified = true GROUP BY id, domain_name, official, wildcard, public, verified, verification_hash, user_id, api_key, bucket");
        $database_array = pg_fetch_array(pg_execute($this->dbconn, "get_domain_authorization", array($domain_regex)));

        if($database_array['count'] > 0){

            return [
                'authorized' => true,
                'bucket' => $database_array['bucket'],
            ];

        }else{

            pg_prepare($this->dbconn, "get_domain_authorization", "SELECT *, COUNT(*) from domains WHERE domain_name = $1 GROUP BY id, domain_name, official, wildcard, public, verified, verification_hash, user_id, api_key, bucket");
            $database_array = pg_fetch_array(pg_execute($this->dbconn, "get_domain_authorization", array($domain)));

            if($database_array['count'] > 0){

                return [
                    'authorized' => true,
                    'bucket' => $database_array['bucket'],
                ];

            }else{
                return [
                    'authorized' => false,
                ];
            }
        
        }
        
    }

    
}