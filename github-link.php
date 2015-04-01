<?php
/*
Plugin Name: GitHub Link
Version: 0.3.0
Plugin URI: https://github.com/szepeviktor/github-link
Description: Displays GitHub link on the Plugins page given there is a <code>GitHub Plugin URI</code> plugin header.
License: The MIT License (MIT)
Author: Viktor Szépe
Author URI: http://www.online1.hu/webdesign/
Domain Path:       /languages
Text Domain:       github-link
GitHub Plugin URI: https://github.com/szepeviktor/github-link
*/

// Load textdomain
load_plugin_textdomain( 'github-link', false, __DIR__ . '/languages' );

if ( ! function_exists( 'add_filter' ) ) {
    error_log( 'Malicious sign detected: wpf2b_direct_access '
        . addslashes( $_SERVER['REQUEST_URI'] )
    );
    ob_get_level() && ob_end_clean();
    header( 'Status: 403 Forbidden' );
    header( 'HTTP/1.0 403 Forbidden' );
    exit();
}

add_action( 'init', 'GHL_initialize_external_classes' );

add_filter( "extra_plugin_headers", "GHL_extra_headers" );
add_filter( "plugin_action_links", "GHL_plugin_link", 10, 4 );
add_filter( "network_admin_plugin_action_links", "GHL_plugin_link", 10, 4 );

function GHL_extra_headers( $extra_headers ) {

    // keys will get lost
    return array_merge( $extra_headers, array(
        "GitHubURI" => "GitHub Plugin URI",
        "GitHubBranch" => "GitHub Branch",
        "GitHubToken" => "GitHub Access Token",
        "BitbucketURI" => "Bitbucket Plugin URI",
        "BitbucketBranch" => "Bitbucket Branch"
    ) );
}

function GHL_plugin_link( $actions, $plugin_file, $plugin_data, $context ) {
fb( array(    $actions, $plugin_file, $plugin_data, $context));
    // no GitHub data on search
    if ( 'search' === $context )
        return $actions;

    $submodules = GHL_gitmodules_get_all();
    $link_template = '<a href="%s" title="%s" target="_blank"><img src="%s" style="vertical-align:-3px" height="16" width="16" alt="%s" />%s</a>';

    $on_wporg = false;
    _maybe_update_plugins();
    $plugin_state = get_site_transient( 'update_plugins' );
    if ( isset( $plugin_state->response[$plugin_file] )
        || isset( $plugin_state->no_update[$plugin_file] )
    )
        $on_wporg = true;
    fb( array( PLUGINDIR . '/' . $plugin_data['slug'], $plugin_data ) );

    if ( 
        ! empty( $plugin_data["GitHub Plugin URI"] ) 
            OR
        array_key_exists( PLUGINDIR . '/' . $plugin_data['slug'], $submodules)
        ) {
        $icon = "icon/GitHub-Mark-32px.png";
        $branch = '';

        if ( ! empty( $plugin_data["GitHub Access Token"] ) )
            $icon = 'icon/GitHub-Mark-Light-32px.png" style="vertical-align:-3px;background-color:black;border-radius:50%';
        if ( ! empty( $plugin_data["GitHub Branch"] ) )
            $branch = '/' . $plugin_data["GitHub Branch"];

        $new_action = array ('github' => sprintf(
            $link_template,
            $plugin_data["GitHub Plugin URI"],
            __( "Visit GitHub repository" , "github-link" ),
            plugins_url( $icon, __FILE__ ),
            "GitHub",
            $branch
        ) );
        // if on WP.org + master -> put the icon after other actions
        if ( $on_wporg && ( empty( $branch ) || '/master' === $branch ) ) {
            $actions = array_merge( $actions, $new_action );
        } else {
            $actions = array_merge( $new_action, $actions );
        }
    }

    if ( ! empty( $plugin_data["Bitbucket Plugin URI"] ) ) {
        $icon = "icon/bitbucket_32_darkblue_atlassian.png";
        $branch = '';

        if ( ! empty( $plugin_data["Bitbucket Branch"] ) )
            $branch = '/' . $plugin_data["Bitbucket Branch"];

        $new_action = array('bitbucket' => sprintf(
            $link_template,
            $plugin_data["Bitbucket URI"],
            __( "Visit Bitbucket repository" , "github-link" ),
            plugins_url( $icon, __FILE__ ),
            "Bitbucket",
            $branch
        ) );
        // if on WP.org + master -> put the icon after other actions
        if ( $on_wporg && ( empty( $branch ) || '/master' === $branch ) ) {
            $actions = array_merge( $actions, $new_action );
        } else {
            $actions = array_merge( $new_action, $actions );
        }
    }

    return $actions;
}

/**
* Include and setup custom libraries
*
*/
function GHL_initialize_external_classes () {

    /**
    * PHP .gitmodules parser
    *
    * A (very small) API for parsing .gitmodules files.
    *
    * @since    2015.03.26
    * @link     https://github.com/bornemix/gitmodules-parser
    */
    require_once WP_PLUGIN_DIR . '/' . dirname( plugin_basename( __FILE__ ) ) . '/libs/gitmodules-parser/gitmodules-parser.php';

}

function GHL_gitmodules_get_all( ) {
/**/      
    if ( false === ( $submodules = get_transient( 'GHL_all_gitmodules' ) ) ) :


#        set_transient( 'GHL_all_gitmodules', $submodules, 1 * HOUR_IN_SECONDS );
#        set_transient( 'GHL_all_gitmodules', $submodules, 365 * 24 * HOUR_IN_SECONDS );
    endif;

        $protocol = ( is_ssl() ? 'https://' : 'http://' );
        $dir = trim( $_SERVER['HTTP_HOST'] );
        $submodules = gitmodules_get_all( $protocol.$dir );

if ( ! empty( $submodules ) ) {
    $names   = wp_list_pluck( $submodules, 'name' );
    $gitmodules = array_combine( $names, $submodules );
}

    fb( $gitmodules );
    return $gitmodules;
}