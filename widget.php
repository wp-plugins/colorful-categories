<?php
/**
 * Class ColorfulCategoriesWidget
 * Colorful categories widget
 */
class ColorfulCategoriesWidget extends WP_Widget
{
    public function __construct()
    {
        parent::__construct(
            'colorful_categories_widget',
            __('Colorful categories', 'colorful-categories'),
            array('description' => __('A list of categories in awesome colors', 'colorful-categories'))
        );
    }

    /**
     * @return array
     */
    protected function getThemes()
    {
        return apply_filters('colorful_categories_themes', array(
            ''       => __('No theme', 'colorful-categories'),
            'bubble' => __('Bubble', 'colorful-categories'),
            'box'    => __('Box', 'colorful-categories')
        ));
    }

    public function form($instance)
    {
        $instance = wp_parse_args((array) $instance, array('title' => '', 'taxonomy' => ''));
        $title = esc_attr($instance['title']);
        $count = isset($instance['count']) ? (bool) $instance['count'] : false;
        ?>
        <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e( 'Title:', 'colorful-categories' ); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>

        <p>
            <label for="<?php echo $this->get_field_id('taxonomy'); ?>"><?php _e( 'Taxonomy:', 'colorful-categories' ); ?></label><br />
            <select id="<?php echo $this->get_field_id('taxonomy'); ?>" name="<?php echo $this->get_field_name('taxonomy'); ?>">
                <?php
                $taxonomies = ColorfulCategories::getTaxonomies();
                foreach($taxonomies as $taxonomy) {
                    $tax = get_taxonomy($taxonomy);
                    echo '<option value="' . $taxonomy . '" ' . selected($taxonomy, $instance['taxonomy']) . '>' . stripslashes($tax->label) . ' [' . $taxonomy . ']</option>';
                }
                ?>
            </select>
        </p>

        <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('empty'); ?>" name="<?php echo $this->get_field_name('empty'); ?>"<?php checked( $count ); ?> />
        <label for="<?php echo $this->get_field_id('empty'); ?>"><?php _e( 'Show empty categories', 'colorful-categories' ); ?></label><br />

        <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('count'); ?>" name="<?php echo $this->get_field_name('count'); ?>"<?php checked( $count ); ?> />
        <label for="<?php echo $this->get_field_id('count'); ?>"><?php _e( 'Show post counts', 'colorful-categories' ); ?></label><br />

        <p>
            <label for="<?php echo $this->get_field_id('theme'); ?>"><?php _e( 'Theme', 'colorful-categories' ); ?></label><br />
            <select id="<?php echo $this->get_field_id('theme'); ?>" name="<?php echo $this->get_field_name('theme'); ?>">
                <?php
                foreach($this->getThemes() as $slug => $name) {
                    $value = empty($instance['theme']) ? 'bubble' : $instance['theme'];
                    echo '<option value="' . $slug . '" ' . selected($slug, $value) . '>' . stripslashes($name) . '</option>';
                }
                ?>
            </select>
        </p>

        <?php
    }

    public function update($new_instance, $old_instance)
    {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['taxonomy'] = strip_tags($new_instance['taxonomy']);
        $instance['empty'] = !empty($new_instance['empty']) ? 1 : 0;
        $instance['count'] = !empty($new_instance['count']) ? 1 : 0;
        $instance['theme'] = sanitize_text_field($new_instance['theme']);
        return $instance;
    }

    public function widget($args, $instance)
    {
        $title = apply_filters('widget_title', empty($instance['title']) ? __('Categories', 'colorful-categories') : $instance['title'], $instance, $this->id_base);

        $t = isset($instance['taxonomy']) ? $instance['taxonomy'] : 'category';
        $c = !empty($instance['count']);
        $e = !empty($instance['empty']);

        echo $args['before_widget'];
        if($title) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        $colors = get_option($t . '_colors', array());
        if(empty($colors)) {
            $colors = ColorfulCategories::fillTaxonomyWithColors($t);
        }
        
        if($instance['theme'] == 'bubble') {
            ?>
            <style type="text/css" scoped="scoped">
                ul.colorful-categories { margin-left: 0; padding-left: 0; margin-right: 0; padding-right: 0; }
                ul.colorful-categories:after { display: block; content: ''; clear: both; }
                ul.colorful-categories li {       margin: 2px 4px 0   0;   padding: 7px 5px 7px 0; list-style: none; float: left; background-image: none; border-width: 0; }
                ul.colorful-categories li:hover { margin: 2px 4px 1px 1px; padding: 6px 4px 7px 0; }
                ul.colorful-categories li a { border-radius: 8px; -webkit-border-radius: 8px; -moz-border-radius: 8px; padding: 4px 8px; color: #fff; }
                .colorful-categories li a sup { font-weight: bold; }
            </style>
            <?php
        } elseif($instance['theme'] == 'box') {
            ?>
            <style type="text/css" scoped="scoped">
                ul.colorful-categories { margin-left: 0; padding-left: 0; margin-right: 0; padding-right: 0; }
                ul.colorful-categories:after { display: block; content: ''; clear: both; }
                ul.colorful-categories li {       margin: 2px 4px 0   0; padding: 10px 8px 10px 0; list-style: none; float: left; background-image: none; border-width: 0; }
                ul.colorful-categories li:hover { margin: 2px 4px 2px 0; padding: 8px 8px 10px 0; }
                ul.colorful-categories li a { padding: 4px 8px; color: #fff; }
                .colorful-categories li a sup { font-weight: bold; }
            </style>
            <?php
        }

        $terms = get_terms($t, apply_filters('colorful_categories_get_terms', array( 'hide_empty' => !$e )));
        if(empty($terms)) {

            echo '<p class="colorful-categories-not-found">' . apply_filters('colorful-categories-not-found', __('List is empty'), $t) . '</p>';

        } else {

            ?>
            <ul class="colorful-categories<?=empty($instance['theme']) ? '' : ' ' . esc_attr($instance['theme'])?>">
            <?php

            foreach($terms as $term) {

                $posts_page = ('page' == get_option('show_on_front') && get_option('page_for_posts')) ? get_permalink(get_option('page_for_posts')) : home_url('/');
                $posts_page = esc_url($posts_page);
                $text = stripslashes($term->name);
                if($c) {
                    $text .= ' <sup>' . $term->count . '</sup>';
                }

                if(!isset($colors[$term->term_id])) {
                    $color = ColorfulCategories::addSingleColorOption($term->term_id, null, $t);
                } else {
                    $color = $colors[$term->term_id];
                }

                echo '<li class="' . esc_attr($term->slug) . '"><a href="' . $posts_page . '" style="background-color: ' . $color . ';">' . $text . '</a></li>';
            }

            echo '</ul>';
        }

        echo $args['after_widget'];
    }
}