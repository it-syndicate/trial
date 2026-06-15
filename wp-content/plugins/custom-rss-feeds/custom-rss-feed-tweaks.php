<?php
/**
 * Modify the main feed to add Caption (and Credits).
 *
 * @param  string $content The current content.
 * @return  string
 */
function add_caption_to_images($content) {
    preg_match_all('/<img[^>]+>/i', $content, $matches);

    foreach ($matches[0] as $img_tag) {
        preg_match('/wp-image-(\d+)/', $img_tag, $id_match);

        if (!empty($id_match[1])) {
            $attachment_id = $id_match[1];

            $caption = wp_get_attachment_caption($attachment_id);
            
            if (!empty($caption)) {
				$figure = $img_tag . '<figcaption>' . esc_html($caption) . '</figcaption>';
				$content = str_replace($img_tag, $figure, $content);
            }
        }
    }

    return $content;
}
add_filter('the_content_feed', 'add_caption_to_images');
