<?php
    
/*
 * Return the last time WordPress deployed all published content to Netlify
 */
    function nd_get_last_deploy_datetimestamp() {
        $options = get_option('nd_settings');
        
        return $options['last_deploy'] ?? '';
    }


/*
 * Return the last piece of content created/updated in WordPress
 */    
    function nd_get_last_published_content() {        
        
        // Get all types to watch for updates
        $types = ['any', 'nav_menu_item', 'attachment'];
        $cpt_types = get_post_types([
            'show_in_graphql' => true
        ]);

        // Get most recent published content        
        $args = [
            'post_type' => array_merge($types, $cpt_types),
            'post_status' => 'publish',
            'order' => 'DESC',
            'orderby' => 'modified',
            'posts_per_page' => 1
        ];
        $query = new WP_Query( $args );
  
        return $query->posts[0] ?? false;
    }
    

/*
 * Return boolean if new content time is newer than the last deployed time
 */  
    function nd_deploy_needed() {
        $options = get_option('nd_settings');
        $last_deply = $options['last_deploy'] ?? false;
        
        // Exit early if never deployed
        if(!$last_deply) {
            return true;
        }
        
        // Get last content time, default to beginning of time.
        $last_content = nd_get_last_published_content();
        $last_timestamp = gmdate('Y-m-d H:i:s', 0);
        if($last_content) {
            $last_timestamp = $last_content->post_modified_gmt;            
        }

        return $last_timestamp >= $last_deply;
    }
    
/*
 * Return latest build status
 */  
    function nd_get_build_status($content_header = '') {
        $options = get_option('nd_settings');
                
        // Get staus badge headers
        $response = wp_remote_head($options['status_url']);
    
        // Make sure we have headers                
        if($response['headers']) {
            $content_header = $response['headers']->offsetGet('content-disposition');
        }

        //var_dump($content_header); die;

        // Determine status from content-disposition header (it inlcudes the SVG filename so we look for that).
        $status = 'unknown';
        switch(true) {
            case strpos($content_header, 'success') !== false:
                $status = 'success';
                break;
                
            case strpos($content_header, 'building') !== false:
                $status = 'building';
                break;
                
            case strpos($content_header, 'failed') !== false:
                $status = 'failed';
                break;                                
        } 
        
        return $status;       
    }    