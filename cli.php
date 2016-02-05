<?php
/**
 * User generation command.
 */
class Usergen_CLI extends WP_CLI_Command {

	/**
	 * Generate random users.
	 *
	 * ## OPTIONS
	 *
	 * <count>
	 * : Number of users to generate
	 *
	 * ## EXAMPLES
	 *
	 *     wp usergen generate 10
	 *
	 * @access		public
	 * @param		  array $args
	 * @param		  array $assoc_args
	 * @return		void
	 */
	public function generate( $args, $assoc_args ) {

		list( $count ) = $args;

		// Verify the number is integer and greater than 0
		if( filter_var( $count, FILTER_VALIDATE_INT ) == false || $count < 0 ) {
			WP_CLI::error( 'You must specify the amount of users you wish to generate.' );
			return;
		}

		$mock_data = $this->retrieve_mock_data();
		$total     = count( $mock_data );

		// Verify the requested amount is available within the mock data.
		if( $count > $total ) {
			WP_CLI::error( sprintf( 'You must specify an amount less than %s or generate your custom json data.', $total ) );
			return;
		}

		$mock_data = array_slice( $mock_data, 0, $count ); // Get the selected amount from the array.

		$notify = \WP_CLI\Utils\make_progress_bar( "Generating $count users(s)", $count );

		for( $i = 0; $i < count( $mock_data ); $i++ ) {
			$notify->tick();
			$this->register_user( $mock_data[$i] );
		}

		$notify->finish();

		WP_CLI::success( 'Done.' );

	}

	/**
	 * Deletes all users excepts administrators.
	 *
	 * ## EXAMPLES
	 *
	 *     wp usergen purge
	 *
	 * @access		public
	 * @param		  array $args
	 * @param		  array $assoc_args
	 * @return		void
	 */
	public function purge( $args, $assoc_args ) {

		WP_CLI::line( '' );
		WP_CLI::confirm( 'Are you sure you want to remove all users? This will NOT delete administrators.' );

		$roles_to_delete = $this->get_roles();

		foreach ( $roles_to_delete as $role => $name ) {

			$query_args = array( 'role' => $role, 'number' => 99999999 );
			$user_query = new WP_User_Query( $query_args );
			$results    = $user_query->get_results();
			$total      = $user_query->get_total();

			if( ! empty( $results ) ) {

				WP_CLI::line( '' );
				$notify = \WP_CLI\Utils\make_progress_bar( "Deleting $total $name(s)", $total );

				for( $i = 0; $i < count( $results ); $i++ ) {
					$notify->tick();
					wp_delete_user( $results[$i]->data->ID, null );
				}

				$notify->finish();

			}

		}

		WP_CLI::line( '' );
		WP_CLI::success( 'Done.' );
		WP_CLI::line( '' );

	}

	/**
	 * Retrieve the mock data from the json file within the plugin's folder.
	 *
	 * @access private
	 * @return mixed
	 */
	private function retrieve_mock_data() {

		$plugin_url = plugin_dir_url( __FILE__ ) . '/MOCK_DATA.json' ;
		$data       = wp_remote_get( $plugin_url );
		$data       = wp_remote_retrieve_body( $data );

		return json_decode( $data );

	}

	/**
	 * Register a user.
	 *
	 * @access private
	 * @param  object $data information of the user.
	 * @return void
	 */
	private function register_user( $data ) {

		$username   = $data->username;
		$first_name = $data->first_name;
		$last_name  = $data->last_name;
		$email      = $data->email;

		if( ! username_exists( $username ) && ! email_exists( $email ) ) {

			$password    = wp_generate_password( 12, false );
			$create_user = wp_create_user( $username, $password, $email );

			if( ! is_wp_error( $create_user ) ) {
				wp_update_user( array( 'ID' => $create_user, 'first_name' => $first_name, 'last_name' => $last_name ) );
			}

		}

	}

	/**
	 * Retrieve list of user roles to delete.
	 *
	 * @access private
	 * @return array list of roles to delete.
	 */
	private function get_roles() {

		global $wp_roles;

		$roles = $wp_roles->get_names();

		if( array_key_exists( 'administrator' , $roles ) )
			unset( $roles['administrator'] );

		return $roles;

	}

}
WP_CLI::add_command( 'usergen', 'Usergen_CLI' );
