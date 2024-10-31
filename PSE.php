<?php
/*
Plugin Name:       PSE PornoScraperExecutor
Plugin URI:        http://pornoscraper.com
Description:       Plugin required to receive content from pornoscraper.com
Version:           1.0.2
Author:            Wilson Moises Diaz
License:           GPL-3.0
License URI:       https://www.gnu.org/licenses/gpl-3.0.html
*/

function PSE_ready_admin_notice() {

    if ( get_option('permalink_structure') ) {
        echo "
<div class=\"notice notice-success\">
    <p>This website is ready to receive posts using <a href='https://app.pornoscraper.com' target='_blank'>https://app.pornoscraper.com</a></p>
</div>";
    } else {
        echo "
<div class=\"notice notice-error\">
    <p>PSE needs permalinks enabled to receive posts using <a href='https://app.pornoscraper.com' target='_blank'>https://app.pornoscraper.com</a></p>
</div>";
    }

}

function PSE_valid_video_embed($content) {

    $query = "https://openload.co/embed/";

    if (substr($content, 0, strlen($query)) === $query) {
        return true;
    }

    return false;

}

function PSE_embed_video_content($atts, $content = null) {

    /*
     *
     *  If content is a valid openload embedded video, show it.
     *
     * */

    if (PSE_valid_video_embed($content)) {

        return "
        <style>
        
        #PSE .PSE-wrapper, #tinymce .PSE-wrapper {
            display: block;
            margin-bottom: 1.5em;
            width: 100%
        }
        
        #PSE .PSE-wrapper::after, #tinymce .PSE-wrapper::after {
            content: \"\";
            display: table;
            clear: both
        }
        
        #PSE .PSE-wrapper.alignright, #tinymce .PSE-wrapper.alignright {
            margin-top: .4em;
            margin-left: 1.5em
        }
        
        #PSE .PSE-wrapper.alignleft, #tinymce .PSE-wrapper.alignleft {
            margin-top: .4em;
            margin-right: 1.5em
        }
        
        #PSE .PSE-embed-container, #tinymce .PSE-embed-container {
            position: relative;
            display: block;
            padding: 0;
            padding-bottom: 56.25%;
            margin: 0;
            height: 0;
            overflow: hidden
        }
        
        #PSE .PSE-iframe, #PSE .PSE-play-btn, #PSE .PSE-thumbnail, #tinymce .PSE-iframe,
        #tinymce .PSE-play-btn, #tinymce .PSE-thumbnail {
            position: absolute;
            padding: 0;
            margin: 0;
            top: 0;
            left: 0;
            bottom: 0;
            height: 100%;
            width: 100%;
            border: 0
        }
        
        #PSE .PSE-video, #tinymce .PSE-video {
            padding: 0;
            margin: 0;
            width: 100%
        }
        
        #PSE .PSE-promote-link, #tinymce .PSE-promote-link {
            float: right;
            font-family: \"Open Sans\", \"Sagoe UI\", Arvo, Lato, Arial, sans-serif;
            font-size: .8em
        }
        
        .PSE-hidden {
            display: none !important
        }
        
        </style>
        
        <script type='text/javascript'>
        
            !function(e) {
            \"use strict\";
            function r() {
                e(\".PSE-wrapper\").find(\"p, .video-wrap, .fluid-width-video-wrapper, .fluid-vids\").contents().unwrap(), e(\".PSE-wrapper br\").remove(), e(\".PSE-iframe, .PSE-video\").removeAttr(\"width height style\")
            }
            function t() {
                e('html[id=\"PSE\"]').length || (e(\"html[id]\").length <= 0 ? e(\"html\").attr(\"id\", \"PSE\") : e(\"body[id]\").length <= 0 ? e(\"body\").attr(\"id\", \"PSE\") : e(\"body\").wrapInner('<div id=\"PSE\">'))
            }
            r(), t(), e(document).ready(function() {
                r(), t()
            })
        }(jQuery);
        
        </script>
        
        <div class=\"PSE-wrapper\">
            <div class=\"PSE-embed-container\" style=\"padding-bottom:56.250000%\">
                <iframe allowfullscreen=\"\" class=\"PSE-iframe fitvidsignore\" frameborder=\"0\" scrolling=\"no\" src=\"" . $content . "\"></iframe>
            </div>
        </div>";

    } else {

        return "";

    }

}

function PSE_eXecutor($data) {

    require_once(ABSPATH . 'wp-admin/includes/taxonomy.php');
    include_once( ABSPATH . 'wp-admin/includes/image.php' );

    $callback = $data['callback'];

    $json = null;

    $user = $data['user'];
    $pass = $data['pass'];

    $titulo = $data['titulo'];
    $imagen = $data['imagen'];
    $contenido = $data['contenido'];
    $conjuntoTags = $data['tags'];
    $conjuntoCategorias = $data['cats'];

    if (user_pass_ok($user,$pass)) {

        if (empty($titulo) || empty($contenido)) {

            $arrayMensaje = array('login' => 'ok');
            $json = json_encode($arrayMensaje);

        } else {

            $my_post = array(
                'post_title'    => $titulo,
                'post_content'  => $contenido,
                'post_status'   => 'publish',
                'post_author'   => 1
            );

            $post_id = wp_insert_post( $my_post );

            if ($imagen != null) {

                $datosimagen = @file_get_contents($imagen);

                if ($datosimagen !== false) {

                    $filetype = wp_check_filetype(basename($imagen), null);

                    $titulo = str_replace("/", "-", $titulo);
                    $titulo = str_replace(" ", "_", $titulo);

                    $upldir = wp_upload_dir();

                    $filename = realpath($upldir['path'])."/".$titulo.".".$filetype['ext'];

                    file_put_contents($filename, $datosimagen);

                    $attachment = array(
                        'guid'           => $_SERVER['DOCUMENT_ROOT'] . '/' . basename( $filename ),
                        'post_mime_type' => $filetype['type'],
                        'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
                        'post_content'   => '',
                        'post_status'    => 'inherit'
                    );

                    $attach_id = wp_insert_attachment( $attachment, $filename, 0);

                    $attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
                    wp_update_attachment_metadata( $attach_id, $attach_data );

                    set_post_thumbnail( $post_id, $attach_id );

                }

            }

            if ($conjuntoTags != null) {
                $tags = explode(',',$conjuntoTags);
                wp_set_post_tags( $post_id, $tags, false);
            }

            if ($conjuntoCategorias != null) {

                $categorias = explode(',',$conjuntoCategorias);
                $setCategorias = get_categories('');
                $arrayCategorias = array();

                foreach ($categorias as $cat) {

                    $existe = false;
                    foreach ($setCategorias as $categoriaUsada) {
                        if ($categoriaUsada->cat_name == $cat) {
                            array_push($arrayCategorias,$categoriaUsada->cat_ID);
                            $existe = true;
                        }
                    }
                    if ($existe == false) {
                        $idCatTemp = wp_create_category($cat);
                        array_push($arrayCategorias, $idCatTemp);
                    }
                }

                wp_set_post_categories($post_id, $arrayCategorias);

            }

            $arrayMensaje = array(
                'id_post' => $post_id,
                'login' => 'ok' );
            $json = json_encode($arrayMensaje);

            print "$json";

            exit;

        }

    } else {

        $data = array('login' => 'error');
        $json = json_encode($data);

    }

    $jsonp_callback = isset($callback) ? $callback : null;

    header('Content-type: application/javascript');
    print "$jsonp_callback($json)";

    exit;

}

function PSE_register_routes() {
    register_rest_route( 'PSE/v1', '/eXecutor', array(
        'methods' => 'GET',
        'callback' => 'PSE_eXecutor',
    ) );
}

add_action('admin_notices','PSE_ready_admin_notice');

add_action('rest_api_init', 'PSE_register_routes');

add_shortcode('PSE', 'PSE_embed_video_content');

?>