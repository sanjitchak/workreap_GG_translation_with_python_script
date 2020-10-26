<?php
if (!class_exists('AndroidAppGetServicesRoutes')) {

    class AndroidAppGetServicesRoutes extends WP_REST_Controller{

        /**
         * Register the routes for the objects of the controller.
         */
        public function register_routes() {
            $version 	= '1';
            $namespace 	= 'api/v' . $version;
            $base 		= 'services';

            register_rest_route($namespace, '/' . $base . '/get_services',
                array(
                  array(
                        'methods' 	=> WP_REST_Server::READABLE,
                        'callback' 	=> array(&$this, 'get_services'),
                        'args' 		=> array(),
						'permission_callback' => '__return_true',
                    ),
                )
            );
			
			register_rest_route($namespace, '/' . $base . '/get_addons_services',
                array(
                  array(
                        'methods' 	=> WP_REST_Server::READABLE,
                        'callback' 	=> array(&$this, 'get_addons_services'),
                        'args' 		=> array(),
						'permission_callback' => '__return_true',
                    ),
                )
            );
			
			register_rest_route($namespace, '/' . $base . '/add_service',
                array(
                  array(
                        'methods' 	=> WP_REST_Server::CREATABLE,
                        'callback' 	=> array(&$this, 'add_service'),
                        'args' 		=> array(),
						'permission_callback' => '__return_true',
                    ),
                )
            );
			
			register_rest_route($namespace, '/' . $base . '/add_addon_service',
                array(
                  array(
                        'methods' 	=> WP_REST_Server::CREATABLE,
                        'callback' 	=> array(&$this, 'add_addon_service'),
                        'args' 		=> array(),
						'permission_callback' => '__return_true',
                    ),
                )
            );
			
			register_rest_route($namespace, '/' . $base . '/delete_addon_service',
                array(
                  array(
                        'methods' 	=> WP_REST_Server::CREATABLE,
                        'callback' 	=> array(&$this, 'delete_addon_service'),
                        'args' 		=> array(),
						'permission_callback' => '__return_true',
                    ),
                )
            );
			
			register_rest_route($namespace, '/' . $base . '/delete_service',
                array(
                  array(
                        'methods' 	=> WP_REST_Server::CREATABLE,
                        'callback' 	=> array(&$this, 'delete_service'),
                        'args' 		=> array(),
						'permission_callback' => '__return_true',
                    ),
                )
            );
			
			
        }
		
		/**
         * Add Service
         *
         * @param WP_REST_Request $request Full data about the request.
         * @return WP_Error|WP_REST_Response
         */
		public function add_service($request) {
			$user_id			= !empty( $request['user_id'] ) ? intval( $request['user_id'] ) : '';
			
			$json				= array();
			$items				= array();
			$service_files		= array();
			$submitted_files	= array();
			
			//disabled
			if( empty( $user_id ) ) {
				if( apply_filters('workreap_is_feature_allowed', 'packages', $user_id) === false ){	
					if( apply_filters('workreap_is_feature_job','wt_services', $user_id) === false){
						$json['type'] 		= 'error';
						$json['message'] 	= esc_html__('You’ve consumed all you points to add new service.','workreap_api');
						$items[] 			= $json;
						return new WP_REST_Response($items, 203);
					}
				}
			}
			
			$is_featured        = !empty( $request['is_featured'] ) ? $request['is_featured'] : '';
			
			if( empty( $user_id ) ) {
				if( apply_filters('workreap_is_feature_allowed', 'packages', $user_id) === false ){	
					if( !empty( $is_featured ) && $is_featured === 'on' ) {
						if( apply_filters('workreap_featured_service', $user_id) === false ){
							$json['type'] 		= 'error';
							$json['message'] 	= esc_html__('You’ve consumed all you points to add featured service.','workreap_api');
							$items[] 			= $json;
							return new WP_REST_Response($items, 203);
						} 
					}
				}
			}
			
			$required = array(
							'title'   			=> esc_html__('Service title is required', 'workreap_api'),
							'delivery_time'  	=> esc_html__('Delivery time is required', 'workreap_api'),
							'price'  			=> esc_html__('Service price is required', 'workreap_api'),
							'english_level'  	=> esc_html__('English level is required', 'workreap_api'),
							'categories'   		=> esc_html__('Category is required', 'workreap_api')
						);

			foreach ($required as $key => $value) {
				if( empty( $request[$key] ) ){
					$json['type'] 		= 'error';
					$json['message'] 	= $value;        
					$items[] 			= $json;
					return new WP_REST_Response($items, 203);
				} 
			}
			
			//Addon check
			if( !empty( $request['addons_service'] ) ){
				$required = array(
					'title'   			=> esc_html__('Addons Service title is required', 'workreap_api'),
					'price'  			=> esc_html__('Addons Service price is required', 'workreap_api'),
				);

				foreach( $request['addons_service'] as $key => $item ) {
					foreach( $required as $inner_key => $item_check ) {
						if( empty( $request['addons_service'][$key][$inner_key] ) ){
							$json['type'] = 'error';
							$json['message'] =  $item_check;      
							return new WP_REST_Response($json, 203);
						}
					}
				}	
			}
			
			
			$is_featured    = !empty( $request['is_featured'] ) ? $request['is_featured'] : '';
			$hide_map 		= 'show';
			
			if (function_exists('fw_get_db_post_option') ) {
				$hide_map		= fw_get_db_settings_option('hide_map');
				$job_status		= fw_get_db_settings_option('job_status');
			}

			$job_status	=  !empty( $job_status ) ? $job_status : 'publish';
			
			$title				= !empty( $request['title'] ) ? esc_attr( $request['title'] ) : '';
			$description		= !empty( $request['description'] ) ? $request['description'] : '';
			$user_post = array(
				'post_title'    => wp_strip_all_tags( $title ),
				'post_status'   => $job_status,
				'post_content'  => $description,
				'post_author'   => $user_id,
				'post_type'     => 'micro-services',
			);

			$post_id    		= wp_insert_post( $user_post );

			
			if( !empty( $post_id ) ){
				
				$addons	        = !empty( $request['addons'] ) ? $request['addons'] : array();
			
				if( !empty( $request['addons_service'] ) ){
					foreach( $request['addons_service'] as $key => $item ) {

						$user_post = array(
							'post_title'    => wp_strip_all_tags( $item['title'] ),
							'post_excerpt'  => $item['description'],
							'post_author'   => $user_id,
							'post_type'     => 'addons-services',
							'post_status'	=> 'publish'
						);

						$addon_post_id    		= wp_insert_post( $user_post );
						$addons[]				= $addon_post_id;

						$price	        		= !empty( $item['price'] ) ? $item['price'] : '';

						//update
						update_post_meta($addon_post_id, '_price', $price);

						//update unyson meta
						$fw_options 					= array();
						$fw_options['price']         	= $price;

						//Update User Profile
						fw_set_db_post_option($addon_post_id, null, $fw_options);
					}	
				}

				update_post_meta( $post_id, '_addons', $addons );

				
				update_post_meta( $post_id, '_featured_service_string', 0 );
				
				$remaning_services		= workreap_get_subscription_metadata( 'wt_services',intval($user_id) );
				$remaning_services  	= !empty( $remaning_services ) ? intval($remaning_services) : 0;

				if( !empty( $remaning_services) && $remaning_services >= 1 ) {
					$update_services	= intval( $remaning_services ) - 1 ;
					$update_services	= intval($update_services);

					$wt_subscription 	= get_user_meta(intval($user_id), 'wt_subscription', true);
					$wt_subscription	= !empty( $wt_subscription ) ?  $wt_subscription : array();

					$wt_subscription['wt_services'] = $update_services;

					update_user_meta( intval($user_id), 'wt_subscription', $wt_subscription);
				}

				$expiry_string		= workreap_get_subscription_metadata( 'subscription_featured_string',$user_id );
				if( !empty($expiry_string) ) {
					update_post_meta($post_id, '_expiry_string', $expiry_string);
				}

				$categories		= !empty( $request['categories'] ) ? $request['categories'] : array();
				$languages		= !empty( $request['languages'] ) ? $request['languages'] : array();

				$price				= !empty( $request['price'] ) ? $request['price'] : '';
				$delivery_time		= !empty( $request['delivery_time'] ) ? $request['delivery_time'] : array();
				$response_time		= !empty( $request['response_time'] ) ? $request['response_time'] : array();
				$english_level		= !empty( $request['english_level'] ) ? $request['english_level'] : '';
				
				
				$total_attachments 	= !empty($request['size']) ? $request['size'] : 0;
				if( !empty($is_featured) ){
					if( $is_featured === 'on'){
						$featured_services	= workreap_featured_service( $current_user->ID );

						if( $featured_services ) {
							$featured_string	= workreap_is_feature_value( 'subscription_featured_string', $user_id );
							update_post_meta($post_id, '_featured_service_string', 1);
						}

						$remaning_featured_services		= workreap_get_subscription_metadata( 'wt_featured_services',intval($user_id) );
						$remaning_featured_services  	= !empty( $remaning_featured_services ) ? intval($remaning_featured_services) : 0;

						if( !empty( $remaning_featured_services) && $remaning_featured_services >= 1 ) {
							$update_featured_services	= intval( $remaning_featured_services ) - 1 ;
							$update_featured_services	= intval( $update_featured_services );

							$wt_subscription 	= get_user_meta(intval($user_id), 'wt_subscription', true);
							$wt_subscription	= !empty( $wt_subscription ) ?  $wt_subscription : array();

							$wt_subscription['wt_featured_services'] = $update_featured_services;
							update_user_meta( intval($user_id), 'wt_subscription', $wt_subscription);
						}
					} else {
						update_post_meta( $post_id, '_featured_service_string', 0 );
					}
				} else {
					update_post_meta( $post_id, '_featured_service_string', 0 );
				}

				if( !empty( $categories ) ){
					wp_set_post_terms( $post_id, $categories, 'project_cat' );
				}

				if( !empty( $languages ) ){
					wp_set_post_terms( $post_id, $languages, 'languages' );
				}

				if( !empty( $delivery_time ) ){
					wp_set_post_terms( $post_id, $delivery_time, 'delivery' );
				}

				if( !empty( $response_time ) ){
					wp_set_post_terms( $post_id, $response_time, 'response_time' );
				}

				//update location
				$address    = !empty( $request['address'] ) ? esc_attr( $request['address'] ) : '';
				$country    = !empty( $request['country'] ) ? $request['country'] : '';
				$latitude   = !empty( $request['latitude'] ) ? esc_attr( $request['latitude'] ): '';
				$longitude  = !empty( $request['longitude'] ) ? esc_attr( $request['longitude'] ): '';
				$videos 	= !empty( $request['videos'] ) ? $request['videos'] : array();

				update_post_meta($post_id, '_country', $country);

				//Set country for unyson
				$locations = get_term_by( 'slug', $country, 'locations' );

				$location = array();
				if( !empty( $locations ) ){
					$location[0] = $locations->term_id;

					if( !empty( $location ) ){
						wp_set_post_terms( $post_id, $location, 'locations' );
					}

				}

				if( !empty( $_FILES ) && $total_attachments != 0 ){
					if ( ! function_exists( 'wp_handle_upload' ) ) {
						require_once( ABSPATH . 'wp-admin/includes/file.php' );
						require_once(ABSPATH . 'wp-admin/includes/image.php');
						require_once( ABSPATH . 'wp-includes/pluggable.php' );
					}
					
					$counter	= 0;
					for ($x = 0; $x < $total_attachments; $x++) {
						$submitted_files = $_FILES['service_documents'.$x];
						$uploaded_image  = wp_handle_upload($submitted_files, array('test_form' => false));
						$file_name		 = basename($submitted_files['name']);
						$file_type 		 = wp_check_filetype($uploaded_image['file']);

						// Prepare an array of post data for the attachment.
						$attachment_details = array(
							'guid' => $uploaded_image['url'],
							'post_mime_type' => $file_type['type'],
							'post_title' => preg_replace('/\.[^.]+$/', '', basename($file_name)),
							'post_content' => '',
							'post_status' => 'inherit'
						);

						$attach_id = wp_insert_attachment($attachment_details, $uploaded_image['file']);
						$attach_data = wp_generate_attachment_metadata($attach_id, $uploaded_image['file']);
						wp_update_attachment_metadata($attach_id, $attach_data);
						$attachments['attachment_id']	= $attach_id;
						$attachments['url']				= wp_get_attachment_url($attach_id);

						$service_files[]					= $attachments;
					}
				}
				
				if( !empty( $service_files [0]['attachment_id'] ) ){
					set_post_thumbnail( $post_id, $service_files [0]['attachment_id']);
				}
				
				$total_downloads 	= !empty($request['donwload_size']) ? $request['donwload_size'] : 0;
				$downloads_files	= array();
				if( !empty( $_FILES ) && $total_downloads != 0 ){
					if ( ! function_exists( 'wp_handle_upload' ) ) {
						require_once( ABSPATH . 'wp-admin/includes/file.php' );
						require_once(ABSPATH . 'wp-admin/includes/image.php');
						require_once( ABSPATH . 'wp-includes/pluggable.php' );
					}
					
					$counter	= 0;
					for ($x = 0; $x < $total_downloads; $x++) {
						$download_files = $_FILES['downloads_documents'.$x];
						$uploaded_image  = wp_handle_upload($download_files, array('test_form' => false));
						$file_name		 = basename($download_files['name']);
						$file_type 		 = wp_check_filetype($uploaded_image['file']);

						// Prepare an array of post data for the attachment.
						$attachment_details = array(
							'guid' => $uploaded_image['url'],
							'post_mime_type' => $file_type['type'],
							'post_title' => preg_replace('/\.[^.]+$/', '', basename($file_name)),
							'post_content' => '',
							'post_status' => 'inherit'
						);

						$attach_id 		= wp_insert_attachment($attachment_details, $uploaded_image['file']);
						$attach_data 	= wp_generate_attachment_metadata($attach_id, $uploaded_image['file']);
						
						wp_update_attachment_metadata($attach_id, $attach_data);
						
						$downloads['attachment_id']		= $attach_id;
						$downloads['url']				= wp_get_attachment_url($attach_id);
						$downloads_files[]				= $downloads;
					}
				}
				
				$is_downloable	= !empty( $request['downloadable'] ) ? $request['downloadable'] : '';

				if( !empty( $is_downloable ) && $is_downloable === 'yes' && !empty( $downloads_files ) ){
					update_post_meta( $post_id, '_downloadable_files', $downloads_files );
				}

				update_post_meta( $post_id, '_downloadable', $is_downloable );
				
				//update
				update_post_meta($post_id, '_price', $price);
				update_post_meta($post_id, '_english_level', $english_level);

				//update unyson meta
				$fw_options = array();
				$fw_options['price']         	= $price;
				$fw_options['english_level']    = $english_level;
				$fw_options['downloadable']     = $is_downloable;
				$fw_options['docs']    			= $service_files;
				$fw_options['address']          = $address;
				$fw_options['longitude']        = $longitude;
				$fw_options['latitude']         = $latitude;
				$fw_options['country']          = $location;
				$fw_options['videos']           = $videos;
				
				//Update User Profile
				fw_set_db_post_option($post_id, null, $fw_options);


				if (class_exists('Workreap_Email_helper')) {
					if (class_exists('WorkreapServicePost')) {
						$email_helper = new WorkreapServicePost();
						$emailData 	  = array();

						$freelancer_name 		= workreap_get_username($user_id);
						$freelancer_email 		= get_userdata( $user_id )->user_email;

						$freelancer_profile 	= get_permalink($user_id);
						$service_title 			= get_the_title($post_id);
						$service_link 			= get_permalink($post_id);


						$emailData['freelancer_name'] 	= esc_attr( $freelancer_name );
						$emailData['freelancer_email'] 	= esc_attr( $freelancer_email );
						$emailData['freelancer_link'] 	= esc_url( $freelancer_profile );
						$emailData['status'] 			= esc_url( $job_status );
						$emailData['service_title'] 	= esc_attr( $service_title );
						$emailData['service_link'] 		= esc_url( $service_link );

						$email_helper->send_admin_service_post($emailData);
						$email_helper->send_freelancer_service_post($emailData);
					}
				}
				
				$json['type'] 		= 'success';
				$json['message'] 	= esc_html__('Your service have been posted successfully.','workreap_api');
				$items[] 			= $json;
				return new WP_REST_Response($items, 200);
			}
			
		}
		
		/**
         * Add addon-Service
         *
         * @param WP_REST_Request $request Full data about the request.
         * @return WP_Error|WP_REST_Response
         */
		public function add_addon_service($request) {
			$user_id			= !empty( $request['user_id'] ) ? intval( $request['user_id'] ) : '';
			$submit_type		= !empty( $request['submit_type'] ) ? intval( $request['submit_type'] ) : '';
			
			$json				= array();
			$items				= array();
			$required = array(
							'title'   			=> esc_html__('Addon Service title is required', 'workreap_api'),
							'user_id'  			=> esc_html__('User ID is required', 'workreap_api'),
							'price'  			=> esc_html__('Addon Service Service price is required', 'workreap_api')
						);

			foreach ($required as $key => $value) {
				if( empty( $request[$key] ) ){
					$json['type'] 		= 'error';
					$json['message'] 	= $value;        
					$items[] 			= $json;
					return new WP_REST_Response($items, 203);
				} 
			}
			
			$title				= !empty( $request['title'] ) ? $request['title'] : rand(1,999999);
			$description		= !empty( $request['description'] ) ?  $request['description'] : '';
			$price				= !empty( $request['price'] ) ?  $request['price'] : '';
			
			if( isset( $submit_type ) && $submit_type === 'update' ){
				
				$current = !empty($request['id']) ? intval($request['id']) : '';

				$post_author = get_post_field('post_author', $current);
				$post_id 	 = $current;

				if( intval( $post_author ) === intval( $user_id ) ){
					$article_post = array(
						'ID' 			=> $current,
						'post_title' 	=> $title,
						'post_excerpt' 	=> $description,
					);

					wp_update_post($article_post);
				} else{
					$json['type'] = 'error';
					$json['message'] = esc_html__('Some error occur, please try again later', 'workreap_api');
					wp_send_json( $json );
				}

			} else{
				//Create Post
				$user_post = array(
					'post_title'    => wp_strip_all_tags( $title ),
					'post_excerpt'  => $description,
					'post_author'   => $user_id,
					'post_type'     => 'addons-services',
					'post_status'	=> 'publish'
				);

				$post_id    		= wp_insert_post( $user_post );

			}

			if( !empty( $post_id ) ){

				//update
				update_post_meta($post_id, '_price', $price);

				//update unyson meta
				$fw_options = array();
				$fw_options['price']         	= $price;
				//Update User Profile
				fw_set_db_post_option($post_id, null, $fw_options);

				if( isset( $request['submit_type'] ) && $request['submit_type'] === 'update' ){
					$json['type'] 		= 'success';
					$json['message'] 	= esc_html__('Your addons service has been updated', 'workreap_api');
				} else{
					$json['type'] 		= 'success';
					$json['message'] 	= esc_html__('Your addons service has been added', 'workreap_api');
				}
				$items[] 				= $json;
				return new WP_REST_Response($items, 200);
			} else{
				$json['type'] 		= 'error';
				$json['message'] 	= esc_html__('Some error occur, please try again later', 'workreap_api');
				$items[] 			= $json;
				return new WP_REST_Response($items, 203);
			}
			
		}
		
		/**
         * Get Listings aadons
         *
         * @param WP_REST_Request $request Full data about the request.
         * @return WP_Error|WP_REST_Response
         */
        public function get_addons_services($request){
			$user_id		= !empty( $request['user_id'] ) ? intval( $request['user_id'] ) : '';
			$post_ids		= !empty( $request['post_ids'] ) ?  $request['post_ids'] : '';
			$items			= array();
			$itm			= array();
			
			if( !empty($user_id ) ){
				$args = array(
						'posts_per_page' 	=> -1,
						'post_type' 		=> 'addons-services',
						'post_status' 		=> array('publish'),
						'author' 			=> $user_id,
						'suppress_filters'  => false
					);
				
				if( !empty( $post_ids ) ){
					$args['post__in'] = array($post_ids);
				}
				
				$query 			= new WP_Query($args);
				if ($query->have_posts()) {
					while ($query->have_posts()) : $query->the_post();
						global $post;
					
						$service_title		= get_the_title( $post->ID );
						$itm['title']		= !empty( $service_title ) ? $service_title : '';
						$db_price			= 0;
						if (function_exists('fw_get_db_post_option')) {
							$db_price   = fw_get_db_post_option($post->ID,'price');
						}
						
						$itm['price']		= !empty( $db_price ) ?  workreap_price_format( $db_price,'return' ) : '';
						$perma_link			= get_the_permalink($post->ID);
						$post_status		= get_post_status($post->ID);
						$itm['status']		= !empty( $post_status ) ? $post_status : '';
						$itm['ID']			= !empty( $post->ID ) ? $post->ID : '';
						$addon_excerpt		= get_the_excerpt( $post->ID);
						$itm['description']	= !empty( $addon_excerpt ) ? $addon_excerpt : '';
						$items[]				= maybe_unserialize($itm);					
					endwhile;
					return new WP_REST_Response($items, 200);
					
					
				} else {
					$json['type'] 		= 'error';
					$json['message'] 	= esc_html__('Empty Service Addon.', 'workreap_api');
					$items[] 			= $json;
					return new WP_REST_Response($items, 203);
				}
			} else {
				$json['type'] 		= 'error';
				$json['message'] 	= esc_html__('User Id is required', 'workreap_api');
				$items[] 			= $json;
				return new WP_REST_Response($items, 203);
			}
			
		}
		
		/**
         * Delete addon service
         *
         * @param WP_REST_Request $request Full data about the request.
         * @return WP_Error|WP_REST_Response
         */
        public function delete_addon_service($request){
			$user_id		= !empty( $request['user_id'] ) ? intval( $request['user_id'] ) : '';
			$service_id		= !empty( $request['id'] ) ?  $request['id'] : '';
			$items			= array();
			$itm			= array();
			
			if(empty($service_id)){
				
				$json['type'] 		= 'error';
				$json['message'] 	= esc_html__('Addons service ID is required', 'workreap_api');;     
				$items[] 			= $json;
				return new WP_REST_Response($items, 203);
			}

			if( !empty( $service_id ) ){
				wp_delete_post($service_id);
				$json['type'] 		= 'success';
				$json['message'] 	= esc_html__('Successfully!  removed this addon service.', 'workreap_api');	
				$items[] 			= $json;
				return new WP_REST_Response($items, 203);
			} 
			
		}
		
		/**
         * Delete service
         *
         * @param WP_REST_Request $request Full data about the request.
         * @return WP_Error|WP_REST_Response
         */
        public function delete_service($request){
			$user_id		= !empty( $request['user_id'] ) ? intval( $request['user_id'] ) : '';
			$service_id		= !empty( $request['id'] ) ?  $request['id'] : '';
			$items			= array();
			$itm			= array();
			
			if(empty($service_id)){
				
				$json['type'] 		= 'error';
				$json['message'] 	= esc_html__('Addons service ID is required', 'workreap_api');;     
				$items[] 			= $json;
				return new WP_REST_Response($items, 203);
			}

			if( !empty( $service_id ) ){

				$queu_services		= workreap_get_services_count('services-orders',array('hired'), $service_id);
				if( $queu_services === 0 ){
					$update				= workreap_save_service_status($service_id, 'deleted');
					$json['type'] 		= 'success';
					$json['message'] 	= esc_html__('Successfully!  removed this service.', 'workreap_api');	
				} else {
					$json['type'] 		= 'error';
					$json['message'] 	= esc_html__('You can\'t your service because you have orders in queue.', 'workreap_api');
				}
				
				$json['type'] 		= 'success';
				$json['message'] 	= esc_html__('Successfully!  removed this addon service.', 'workreap_api');	
				$items[] 			= $json;
				return new WP_REST_Response($items, 203);
			} 
			
		}
		
        /**
         * Get Listings
         *
         * @param WP_REST_Request $request Full data about the request.
         * @return WP_Error|WP_REST_Response
         */
        public function get_services($request){
			
			$limit			= !empty( $request['show_users'] ) ? intval( $request['show_users'] ) : 6;
			$service_id		= !empty( $request['service_id'] ) ? intval( $request['service_id'] ) : '';
			$profile_id		= !empty( $request['profile_id'] ) ? intval( $request['profile_id'] ) : '';
			$page_number	= !empty( $request['page_number'] ) ? intval( $request['page_number'] ) : 1;
			$listing_type	= !empty( $request['listing_type'] ) ? esc_attr( $request['listing_type'] ) : '';
			
			
			$offset 		= ($page_number - 1) * $limit;
			
			$json			= array();
			$items			= array();
			$today 			= time();
			
			if( !empty($profile_id) ) {
				$saved_services	= get_post_meta($profile_id,'_saved_services',true);
			}else {
				$saved_services	= array();
			}
			
			$defult			= get_template_directory_uri().'/images/featured.png';
			
			$json['type']		= 'error';
			$json['message']	= esc_html__('Some error occur, please try again later','workreap_api');
			if( $request['listing_type'] === 'single' ){
				
				$query_args = array(
					'posts_per_page' 	  	=> 1,
					'post_type' 	 	  	=> 'micro-services',
					'post__in' 		 	  	=> array($service_id),
					'post_status' 	 	  	=> 'publish',
					'ignore_sticky_posts' 	=> 1
				);
				$query 			= new WP_Query($query_args);
				$count_post 	= $query->found_posts;
			}else if( !empty($listing_type) && $listing_type === 'featured' ){
				$order		 = 'DESC';
				$query_args = array(
					'posts_per_page' 	  => $limit,
					'post_type' 	 	  => 'micro-services',
					'paged' 		 	  => $page_number,
					'post_status' 	 	  => 'publish',
					'ignore_sticky_posts' => 1
				);
				//order by pro member
				$query_args['meta_key'] = '_featured_service_string';
				$query_args['orderby']	 = array( 
					'meta_value' 	=> 'DESC', 
					'ID'      		=> 'DESC'
				); 

				//Meta Query
				if (!empty($meta_query_args)) {
					$query_relation = array('relation' => 'AND',);
					$meta_query_args = array_merge($query_relation, $meta_query_args);
					$query_args['meta_query'] = $meta_query_args;
				}
				$query 			= new WP_Query($query_args);
				$count_post 	= $query->found_posts;
				

			} elseif( !empty($listing_type) && $listing_type === 'single' ){
				$post_id		= !empty( $service_id ) ? $service_id : '';
				$query_args = array(
					'post_type' 	 	  	=> 'any',
					'p'						=> $post_id
				);
				$query 			= new WP_Query($query_args);
				$count_post 	= $query->found_posts;
				
			} elseif( !empty($listing_type) && $listing_type === 'latest' ){
				$order		 	= 'DESC';

				//code added by Sanjit 
				$myarray = array(2723,2630,1989,1884,1986,1984,1952,1965,1970,1968,1963,1954);
				$query_args 	= array(
									'posts_per_page' 	  	=> $limit,
									'post_type' 	 	  	=> 'micro-services',
									'paged' 		 	  	=> $page_number,
									'post_status' 	 	  	=> 'publish',
									'order'					=> 'ID',
									//code added by Sanjit 
									'post__in'      => $myarray,
									'orderby'				=> $order,
								);

				$query 			= new WP_Query($query_args);
				$count_post 	= $query->found_posts;
				
			} elseif( !empty($listing_type) && $listing_type === 'favorite' ){
				$user_id			= !empty( $request['user_id'] ) ? intval( $request['user_id'] ) : '';
				$linked_profile   	= workreap_get_linked_profile_id($user_id);
				$wishlist 			= get_post_meta($linked_profile, '_saved_services',true);
				$wishlist			= !empty($wishlist) ? $wishlist : array();
				if( !empty($wishlist) ) {
					$order		 = 'DESC';
					$query_args = array(
						'posts_per_page' 	  	=> $limit,
						'post_type' 	 	  	=> 'micro-services',
						'post__in'				=> $wishlist,
						'paged' 		 	  	=> $page_number,
						'post_status' 	 	  	=> 'publish',
						'order'					=> 'ID',
						'orderby'				=> $order,
						'ignore_sticky_posts' 	=> 1
					);
					$query 			= new WP_Query($query_args);
					$count_post 	= $query->found_posts;
				} else {
					$json['type']		= 'error';
					$json['message']	= esc_html__('You have no services in your favorite list.','workreap_api');
					$items[] 			= $json;
					return new WP_REST_Response($items, 203);
				}
				
			}elseif( !empty($listing_type) && $listing_type === 'search' ){
				//Search parameters
				$keyword 		= !empty( $request['keyword']) ? $request['keyword'] : '';
				$categories 	= !empty( $request['category']) ? $request['category'] : array();
				$locations 	 	= !empty( $request['location']) ? $request['location'] : array();
				$delivery 		= !empty( $request['service_duration'] ) ? $request['service_duration'] : array();
				$response_time	= !empty( $request['response_time'] ) ? $request['response_time'] : array();
				$languages 		= !empty( $request['language']) ? $request['language'] : array();

				$tax_query_args  = array();
				$meta_query_args = array();

				//Languages
				if ( !empty($languages[0]) && is_array($languages) ) {   
					$query_relation = array('relation' => 'OR',);
					$lang_args  	= array();

					foreach( $languages as $key => $lang ){
						$lang_args[] = array(
								'taxonomy' => 'languages',
								'field'    => 'slug',
								'terms'    => $lang,
							);
					}

					$tax_query_args[] = array_merge($query_relation, $lang_args);   
				}

				//Delivery
				if ( !empty($delivery[0]) && is_array($delivery) ) {   
					$query_relation = array('relation' => 'OR',);
					$delv_args  	= array();

					foreach( $delivery as $key => $del ){
						$delv_args[] = array(
								'taxonomy' => 'delivery',
								'field'    => 'slug',
								'terms'    => $del,
							);
					}

					$tax_query_args[] = array_merge($query_relation, $delv_args);   
				}

				//Delivery
				if ( !empty($response_time[0]) && is_array($response_time) ) {   
					$query_relation = array('relation' => 'OR',);
					$reponse_args  	= array();

					foreach( $response_time as $key => $res ){
						$reponse_args[] = array(
								'taxonomy' => 'response_time',
								'field'    => 'slug',
								'terms'    => $res,
							);
					}

					$tax_query_args[] = array_merge($query_relation, $reponse_args);   
				}

				//Categories
				if ( !empty($categories[0]) && is_array($categories) ) {   
					$query_relation = array('relation' => 'OR',);
					$category_args  = array();

					foreach( $categories as $key => $cat ){
						$category_args[] = array(
								'taxonomy' => 'project_cat',
								'field'    => 'slug',
								'terms'    => $cat,
							);
					}

					$tax_query_args[] = array_merge($query_relation, $category_args);
				}

				//Locations
				if ( !empty($locations[0]) && is_array($locations) ) {    
					$query_relation = array('relation' => 'OR',);
					$location_args  = array();

					foreach( $locations as $key => $loc ){
						$location_args[] = array(
								'taxonomy' => 'locations',
								'field'    => 'slug',
								'terms'    => $loc,
							);
					}

					$tax_query_args[] = array_merge($query_relation, $location_args);
				}

				//Main Query
				$query_args = array(
					'posts_per_page' 	  => $limit,
					'post_type' 	 	  => 'micro-services',
					'paged' 		 	  => $page_number,
					'post_status' 	 	  => array('publish'),
					'ignore_sticky_posts' => 1
				);

				//keyword search
				if( !empty($keyword) ){
					$query_args['s']	=  $keyword;
				}

				//order by pro member
				$query_args['meta_key'] = '_featured_service_string';
				$query_args['orderby']	 = array( 
					'meta_value' 	=> 'DESC', 
					'ID'      		=> 'DESC'
				); 

				//Taxonomy Query
				if ( !empty( $tax_query_args ) ) {
					$query_relation = array('relation' => 'AND',);
					$query_args['tax_query'] = array_merge($query_relation, $tax_query_args);    
				}

				//Meta Query
				if (!empty($meta_query_args)) {
					$query_relation 			= array('relation' => 'AND',);
					$meta_query_args 			= array_merge($query_relation, $meta_query_args);
					$query_args['meta_query'] 	= $meta_query_args;
				}
				//print_r($query_args);die();
				$query 			= new WP_Query($query_args); 
				$count_post 	= $query->found_posts;		
				
			}else {
				if(!empty($count_post) && $count_post ) {
					$json['type']		= 'error';
					$json['message']	= esc_html__('Please provide api type','workreap_api');
					return new WP_REST_Response($json, 203);
				} else {
					$json['type']		= 'error';
					$json['message']	= esc_html__('Please provide api type','workreap_api');
					return new WP_REST_Response($json, 203);
				}
			}
			
			//Start Query working.
			
			if ($query->have_posts()) {
				$width			= 355;
				$height			= 352;
				$formate_date	= get_option('date_format');
				while ($query->have_posts()) { 
					$query->the_post();
					global $post;
					do_action('workreap_post_views', $post->ID,'services_views');
					
					if( !empty($saved_services)  &&  in_array($post->ID,$saved_services)) {
						$item['favorit']			= 'yes';
					} else {
						$item['favorit']			= '';
					}
					
					$item['service_id']		= $post->ID;
					$service_url			= get_the_permalink($post->ID);
					$item['service_url']	= !empty( $service_url ) ? esc_url( $service_url ) : '';
					
					
					$db_addons				= get_post_meta($post->ID,'_addons',true);
					$db_addons				= !empty( $db_addons ) ? $db_addons : array();
					$itm					= array();
					$addons_items			= array();
					
					if( !empty( $db_addons ) ){
						foreach( $db_addons as $addon ) { 
							$service_title		= get_the_title($addon );
							$itm['title']		= !empty( $service_title ) ? $service_title : '';
							$db_price			= 0;
							if (function_exists('fw_get_db_post_option')) {
								$db_price   = fw_get_db_post_option($addon,'price');
							}

							$itm['price']		= !empty( $db_price ) ?  workreap_price_format( $db_price,'return' ) : '';
							$post_status		= get_post_status($addon);
							$itm['status']		= !empty( $post_status ) ? $post_status : '';
							$addon_excerpt		= get_the_excerpt( $addon);
							$itm['description']	= !empty( $addon_excerpt ) ? $addon_excerpt : '';
							$itm['ID']			= !empty( $addon ) ? $addon : '';
							$addons_items[]		= maybe_unserialize($itm);	
						}
					}
					
					$item['addons']	= $addons_items;
						
					$auther_id				= get_post_field('post_author',$post->ID);
					$auther_profile_id		= !empty( $auther_id ) ? workreap_get_linked_profile_id( $auther_id ) : '';
					$auther_title			= get_the_title($auther_profile_id);
					$item['auther_title']	= !empty( $auther_title ) ? $auther_title : '';
					
					$freelancer_avatar = apply_filters(
							'workreap_freelancer_avatar_fallback', workreap_get_freelancer_avatar(array('width' => 100, 'height' => 100), $auther_profile_id), array('width' => 100, 'height' => 100) 
						);
					$item['auther_image']	= !empty( $freelancer_avatar ) ? esc_url( $freelancer_avatar ) : '';
					
					$auther_verivifed			= get_post_meta($auther_profile_id,"_is_verified",true);
					$item['auther_verified']	= !empty( $auther_verivifed ) ? esc_attr( $auther_verivifed ) : '';
					
					$created_date			= get_the_date($formate_date,$auther_profile_id);
					$item['auther_date']	= !empty( $created_date ) ? $created_date : '';
					
					$post_name				= workreap_get_slug( $auther_profile_id );
					$item['auther_slug']	= !empty( $post_name ) ? esc_attr( $post_name ) : '';
					
					$services_views_count   = get_post_meta($post->ID, 'services_views', true);
					
					$item['service_views']	= !empty( $services_views_count ) ? intval( $services_views_count ) : 0 ;
					
					//Featured Service
					$featured_service		= get_post_meta($post->ID,'_featured_service_string',true);
					$item['featured_text']	= !empty( $featured_service ) ? esc_html__('Featured','workreap_api') : '';
					
					$db_project_cat 		= wp_get_post_terms($post->ID, 'project_cat',array( 'fields' => 'all' ));
					$categories				= !empty( $db_project_cat ) ? $db_project_cat : array();
					$item['categories']		= array();
					if( !empty( $categories ) ){
						$serv_count	= 0;
						foreach( $categories as $cat ) {
							$serv_count ++;
							$item['categories'][]['category_name']	= $cat->name;
						}
					}
					
					$service_title			= get_the_title($post->ID);
					$item['title']			= !empty( $service_title ) ? esc_html( $service_title ) : '';
					
					$service_content		= get_the_content($post->ID);
					$item['content']		= !empty( $service_content ) ?  $service_content : '';
					
					$serviceTotalRating		= get_post_meta( $post->ID , '_service_total_rating',true );
					$serviceFeedbacks		= get_post_meta( $post->ID , '_service_feedbacks',true );
					$queu_services			= workreap_get_services_count('services-orders',array('hired'),$post->ID);
					$item['rating']			= !empty( $serviceTotalRating ) ? $serviceTotalRating : 0;
					$item['feedback']		= !empty( $serviceFeedbacks ) ? intval( $serviceFeedbacks ) : 0;
					
					
					if( !empty( $serviceTotalRating ) || !empty( $serviceFeedbacks ) ) {
						$serviceTotalRating	= $serviceTotalRating / $serviceFeedbacks;
					} else {
						$serviceTotalRating	= 0;
					}

					$item['total_rating'] 		= number_format((float) $serviceTotalRating, 1);
					
					if (function_exists('fw_get_db_post_option')) {
						$db_docs   			= fw_get_db_post_option($post->ID,'docs');
						$order_details   	= fw_get_db_post_option($post->ID,'order_details');
						$db_price   		= fw_get_db_post_option($post->ID,'price');
						$db_downloadable   	= fw_get_db_post_option($post->ID,'downloadable');
					}

					$item['downloadable']	= !empty( $db_downloadable ) ? $db_downloadable : 'no';
					$db_docs				= !empty( $db_docs ) ? $db_docs : array();
					$item['price']			= !empty( $db_price ) ? workreap_price_format( $db_price,'return' ) : '';
					
					$db_delivery_time 		= wp_get_post_terms($post->ID, 'delivery');
					$db_response_time 		= wp_get_post_terms($post->ID, 'response_time');
					$item['delivery_time']	= !empty( $db_delivery_time[0] ) ? $db_delivery_time[0]->name : '';
					$item['response_time']	= !empty( $db_response_time[0] ) ? $db_response_time[0]->name : '';
					
					$db_response_time 		= wp_get_post_terms($post->ID, 'response_time');
					
					$queu_services			= workreap_get_services_count('services-orders',array('hired'),$post->ID);
					$item['queu']			= !empty( $queu_services ) ? $queu_services : 0;
					
					$completed_services		= workreap_get_services_count('services-orders',array('completed'),$post->ID);					$item['soled']			= !empty( $completed_services ) ? $completed_services : 0;
					
					$item['images']	= array();
					if( !empty( $db_docs ) ){
						$docs_count	= 0;
						foreach( $db_docs as $key => $doc ){
							$docs_count ++;
							$attachment_id				= !empty( $doc['attachment_id'] ) ? $doc['attachment_id'] : '';
							$image_url					= workreap_prepare_image_source($attachment_id, $width, $height);
							$item['images'][]['url'] 	= !empty( $image_url ) ? esc_url( $image_url ) : '';
						}
					}
					
					
					//Services Reviews
					$service_id		= $post->ID;
					$reviews		= array();
					$args_reviews	= array(
										'posts_per_page' 	=> -1,
										'post_type' 		=> 'services-orders',
										'post_status' 		=> array('completed'),
										'suppress_filters' 	=> false
									);
					$meta_query_args_reviews[] = array(
						'key' 		=> '_service_id',
						'value' 	=> $service_id,
						'compare' 	=> '='
					);
					$query_relation 			= array('relation' => 'AND',);
					$args_reviews['meta_query'] = array_merge($query_relation, $meta_query_args_reviews);
					$query_reviews 	= new WP_Query($args_reviews);
					
					//$count_post 	= $query_reviews->found_posts;
					
					$count	= 0;
					
					if( $query_reviews->have_posts() ){
						while ($query_reviews->have_posts()) : $query_reviews->the_post();
							global $post;
							$count ++;
							
							$author_id 		= get_the_author_meta( 'ID' );  
							$linked_profile = workreap_get_linked_profile_id($author_id);
							$tagline		= workreap_get_tagline($linked_profile);
							$employer_title = get_the_title( $linked_profile );
							$employer_avatar = apply_filters(
												'workreap_employer_avatar_fallback', workreap_get_employer_avatar(array('width' => 100, 'height' => 100), $linked_profile), array('width' => 100, 'height' => 100) 
											);
							$service_ratings	= get_post_meta($post->ID,'_hired_service_rating',true);
							if( function_exists('fw_get_db_post_option') ) {
								$feedback	 		= fw_get_db_post_option($post->ID, 'feedback');
							}
							$reviews[$count]['feedback']		= !empty( $feedback ) ? $feedback : '';
							$reviews[$count]['employer_title']	= !empty( $employer_title ) ? $employer_title : '';
							$reviews[$count]['employer_avatar']	= !empty( $employer_avatar ) ? esc_url($employer_avatar) : '';
						
							$verivifed							= get_post_meta($linked_profile,"_is_verified",true);
							$reviews[$count]['_is_verified']	= !empty( $verivifed ) ? $verivifed : '';
						
							$service_loaction					= workreap_get_location($linked_profile);
							$reviews[$count]['location']		= !empty( $service_loaction ) ? $service_loaction : array();
							
							$reviews[$count]['service_rating']	= !empty( $service_ratings ) ? $service_ratings : '';
						endwhile;
						wp_reset_postdata();
					} 
					
					$item['reviews']			= array_values($reviews);
					$item['count_totals']       = !empty($count_post) ? intval($count_post) : 0;
					$items[]					= maybe_unserialize($item);					
				}
				return new WP_REST_Response($items, 200);
				//end query
				
			}else{
				$json['type']		= 'error';
				$json['message']	= esc_html__('Some error occur, please try again later','workreap_api');
				$items[] = $json;
				return new WP_REST_Response($items, 203);
			} 
        }

    }
}

add_action('rest_api_init',
function () {
	$controller = new AndroidAppGetServicesRoutes;
	$controller->register_routes();
});
