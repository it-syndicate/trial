<?php
/**
 * Plugin Name: Custom RSS Feeds
 * Description: Creates a custom RSS feed based on selected category names and options for feed output.
 * Version: 1.3.0
 * Author: SiteCare
 * Author URI: https://sitecare.com
 */

// Include feed options configuration and tweaks
require_once plugin_dir_path( __FILE__ ) . 'custom-rss-feed-options.php';
require_once plugin_dir_path( __FILE__ ) . 'custom-rss-feed-tweaks.php';

/**
  * Custom RSS2 Feed Template for the 'alt' feed.
  */
function custom_rss() {
    $feed_url = $_SERVER['REQUEST_URI'];
    $registered = FALSE;

    // Exit if this is not a feed URL
    if( ! str_contains($feed_url, 'feed')) {
        return;
    } 

    // If the feed URL contains 'alt', add a custom alt feed. This applies to all category alt feeds as well.
    if( str_contains($feed_url, 'alt')) {
        add_feed( 'alt', 'custom_rss_function' );  
    }


    // Check if the feed is already registered
    $rules = get_option( 'rewrite_rules' );
    $feeds = array_keys( $rules, 'index.php?&feed=$matches[1]' );

    foreach ( $feeds as $feed ){
        if ( FALSE !== strpos( $feed, 'alt' ) )
            $registered = TRUE;
    }

    // Feed not yet registered, so lets flush the rules once.
    if ( ! $registered ){
        flush_rewrite_rules( FALSE );
    }

}
add_action( 'init', 'custom_rss', 999 );



/**
 * Create a custom RSS feed based on selected category names.
 */
function custom_rss_function() {

     // Get category name if this is a category
     $category_name = get_query_var( 'category_name' );

    // Construct proper arguments for custom query
    $args = array(
        'posts_per_page' => 10,
        'post_status'    => 'publish',  // Include only published posts
    );


    // Check if a category name is provided in the URL. If so, add the category to the query arguments.
    if( $category_name ){
        $args['category_name'] = $category_name;
    } else {
        $args['category_name'] = '';
    }

    $custom_query = new WP_Query( $args );


    header( 'Content-Type: application/rss+xml' );
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    ?>

    <rss version="2.0"
        xmlns:content="http://purl.org/rss/1.0/modules/content/"
        xmlns:wfw="http://wellformedweb.org/CommentAPI/"
        xmlns:dc="http://purl.org/dc/elements/1.1/"
        xmlns:atom="http://www.w3.org/2005/Atom"
        xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
        xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
        <?php do_action( 'rss2_ns' ); ?>>
    <channel>
        <title><?php bloginfo_rss('name'); ?> - Feed</title>
        <atom:link href="<?php self_link(); ?>" rel="self" type="application/rss+xml" />
        <link><?php bloginfo_rss( 'url' ); ?></link>
        <description><?php bloginfo_rss( 'description' ); ?></description>
        <lastBuildDate><?php echo mysql2date( 'D, d M Y H:i:s +0000', get_lastpostmodified( 'GMT' ), false ); ?></lastBuildDate>
        <language>en-US</language>
        <sy:updatePeriod><?php echo apply_filters( 'rss_update_period', 'hourly' ); ?></sy:updatePeriod>
        <sy:updateFrequency><?php echo apply_filters( 'rss_update_frequency', '1' ); ?></sy:updateFrequency>
        <?php do_action( 'rss2_head' ); ?>
        <?php while( $custom_query->have_posts() ) : $custom_query->the_post(); ?>
            <?php
            // Get the custom field 'custom_title' for the current post.
            $custom_title = get_field( 'custom_title', get_the_ID() );
            ?>
            <item>
                <title><?php echo $custom_title ? esc_html( $custom_title ) : get_the_title_rss(); ?></title>
                <link><?php the_permalink_rss(); ?></link>
                <pubDate><?php echo mysql2date('D, d M Y H:i:s +0000', get_post_time('Y-m-d H:i:s', true), false); ?></pubDate>
                <dc:creator><?php the_author(); ?></dc:creator>
                <guid isPermaLink="false"><?php the_guid(); ?></guid>
                <description><![CDATA[<?php the_excerpt_rss() ?>]]></description>
                <content:encoded><![CDATA[<?php the_content_feed() ?>]]></content:encoded>
                <?php rss_enclosure(); ?>
                <?php do_action( 'rss2_item' ); ?>
            </item>
        <?php endwhile; ?>
    </channel>
    </rss>
    <?php
}



// Set the feed cache transient lifetime to 1 hour. this replaces the turn_off_feed_caching() method above
add_filter( 'wp_feed_cache_transient_lifetime' , 'return_3600' );