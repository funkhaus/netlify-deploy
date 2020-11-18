$j = jQuery.noConflict();

/* eslint-disable */
var nd = {
    
    // This nd_vars object is injected from WordPress via wp_add_inline_script
    apiUrl: nd_vars.url,
    nonce: nd_vars.nonce,
    loading: false,
    status: 'unknown',
    
    // Start any needed scripts
	init: function(){
        
        // What happens when you click on Deploy button
        $j('#wpadminbar').on('click', '.nd-deploy-button > a', function(e){
            e.preventDefault();
            
            if( $j('.nd-deploy-button').hasClass('nd-status-building') ) {
                // Trying to scheudle concurrent deploys? Ask for a confirmation.
                var confirmation = confirm("A deploy is currently progress. Are you sure you want to deploy again?");
                if(confirmation) {
                    nd.runDeploy();                                
                }
            } else {
                nd.runDeploy();                
            }
        });
        
        // Update button state periodically  
        setInterval(function(){
            nd.getBuildStatus();
        }, 5000);
	},
	
	// Submit a deploy to the custom WP API
	runDeploy: async function() {
        
        // Stop multple loading requests
        if(nd.loading) {
            return
        }
        nd.loading = true;
        nd.setBuildStatus('building');
        
    	try {
            var response = await fetch(nd.apiUrl + '/deploy', {
                method: 'POST',
                mode: 'same-origin',
                cache: 'no-cache',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': nd.nonce,
                }
            });
            
            // Process response
            var data = await response.json();
            
            // Set new nonce
            nd.nonce = data.nonce;
                        
    	} catch(e) {
            alert("There was an error when trying to deploy.");
            nd.setBuildStatus('failed');
            console.log("error", e);
    	} finally {
        	nd.loading = false;
        	nd.getBuildStatus();        	
    	}
    	

	},
	
	// Handles updating UI when status changes 
	setBuildStatus: function(status = 'unknown') {
        nd.status = status;
       
        // Refresh all Netlify badge SVGs
    	$j('.nd-status-image').each(function(){
            var img = $j(this).get(0);
            img.src = img.src + '?' + Date.now();
    	});     	
    	
    	// Update adminbar class
    	$j('.nd-deploy-button').removeClass('nd-status-unknown nd-status-success nd-status-building nd-status-failed').addClass('nd-status-'+status);
	},
	
	// Hits API to get status of build/deploy
    getBuildStatus: async function(){
    
        var response = await fetch(nd.apiUrl + '/build', {
            method: 'GET',
            mode: 'same-origin',
            cache: 'no-cache',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': nd.nonce,
            }
        });
        
        var json = await response.json();
        nd.nonce = json.nonce;
        
        nd.setBuildStatus(json.data.status || 'unknown');

        return json.data.status;
    }
}
jQuery(document).ready(function() {
    nd.init();
})