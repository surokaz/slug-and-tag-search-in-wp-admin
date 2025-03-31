<?php
/* 
Plugin Name: Slug and Tag Search
Description: A plugin to search by slug and tag in WordPress admin.
*/
if (is_admin()) {
    function slug_and_tag_search_admin($search, $wp_query) {
        global $wpdb;
        if (empty($search)) {
            return $search;
        }

        $qwe = $wp_query->query_vars;
        if (!isset($qwe['s'])) {
            return $search;
        }

        $qwe_s = $qwe['s'];
        $n = !empty($qwe['exact']) ? '' : '%';
        $search = "";
        $searchhand = "";
        $tag_search = false;

        foreach ((array) $qwe['search_terms'] as $item) {
            $item = esc_sql($wpdb->esc_like($item));

            if (strpos($qwe_s, "slug:") !== false) {
                $item = str_replace(["post_title", "slug:"], ["post_name", ""], $item);
                $search .= "{$searchhand}(LOWER($wpdb->posts.post_name) LIKE LOWER('{$n}{$item}{$n}'))";
            } elseif (strpos($qwe_s, "tag:") !== false) {
                $item = str_replace("tag:", "", $item);
                $tag_search = true;
                $search .= "{$searchhand}(LOWER(t.name) LIKE LOWER('{$n}{$item}{$n}'))";
            }

            $searchhand = " AND ";
        }

        if (!empty($search)) {
            if ($tag_search) {
           
                $search = " AND ($search) ";
                add_filter('posts_join', function ($join) use ($wpdb) {
                    return $join . " LEFT JOIN {$wpdb->term_relationships} AS tr ON ($wpdb->posts.ID = tr.object_id) 
                                     LEFT JOIN {$wpdb->term_taxonomy} AS tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id) 
                                     LEFT JOIN {$wpdb->terms} AS t ON (tt.term_id = t.term_id)";
                });
            } else {
                $search = " AND ($search) ";
            }

            if (!is_user_logged_in()) {
                $search .= " AND ($wpdb->posts.post_password = '') ";
            }
        }

        return $search;
    }

    add_filter('posts_search', 'slug_and_tag_search_admin', 500, 2);
}
