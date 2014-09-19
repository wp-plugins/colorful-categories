<?php
/*
 Plugin Name: Colorful Categories
 Plugin URI: http://parkhomov.com/colorful-categories
 Description: New Categories widget in the awesome style! Bring colours to your categories widget - make every category in own colour.
 Version: 1.0
 Author: Artem Parkhomov
 Author URI: http://parkhomov.com
 Text Domain: colorful-categories
*/

/**
 * Class ColorfulCategories
 * Colorful categories main plugin
 */
class ColorfulCategories
{
    public $version = '1.0';

    /**
     * Plugin constructor.
     * @since 1.0
     */
    public function __construct()
    {
        add_action('admin_head', array($this, 'admin_head'));
        add_action('admin_print_styles', array($this, 'admin_print_styles'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('wp_ajax_update_color_options_array', array($this, 'updateColorOptionsArray'));
        add_action('create_term', array($this, 'addSingleColorOption'), 10, 3);
        add_action('widgets_init', array($this, 'widgets_init'));

        $taxonomy = isset($_REQUEST['taxonomy']) ? sanitize_text_field($_REQUEST['taxonomy']) : false;
        if($taxonomy) {
            add_filter('manage_edit-' . $taxonomy . '_columns', array($this, 'addColumn'));
            add_filter('manage_' . $taxonomy . '_custom_column', array($this, 'modifyColumn'), 10, 3);
        }
    }

    /**
     * Admin head
     * @since 1.0
     */
    public function admin_head()
    {
        ?>
        <style type="text/css">
            .column-color { width: 250px; }
            .colorful-categories-saving { display: none; }
        </style>
        <?php
    }

    /**
     * Print admin styles
     * @since 1.0
     */
    public function admin_print_styles()
    {
        wp_enqueue_style('wp-color-picker');
    }

    /**
     * Enqueue scripts for the backend
     * @since 1.0
     * @param string $screen Current screen
     */
    public function admin_enqueue_scripts($screen)
    {
        if('edit-tags.php' === $screen) {
            wp_enqueue_script('wp-color-picker');
            wp_enqueue_script('underscore');
            wp_enqueue_script('color-category', plugin_dir_url(__FILE__) . 'admin-colorful-categories.js', null, null, true);
            wp_localize_script('color-category', 'ajaxObject', array(
                'ajaxUrl'    => admin_url('admin-ajax.php'),
                'colornonce' => wp_create_nonce('colorCategoriesNonce')
            ));
        }
    }

    /**
     * After plugin activation
     * @since 1.0
     */
    public function afterActivate()
    {
        // fill all taxonomies with colors
        $taxonomies = self::getTaxonomies();
        foreach($taxonomies as $taxonomy) {
            $options = get_option($taxonomy . '_colors');
            if(empty($options)) {
                $this->fillTaxonomyWithColors($taxonomy);
            }
        }
    }

    /**
     * Fill category terms with colors
     *
     * @since 1.0
     *
     * @param string $taxonomy Taxonomy name
     * @return array A list of terms and colors
     */
    public static function fillTaxonomyWithColors($taxonomy)
    {
        $options = array();
        $terms = get_terms($taxonomy, array('hide_empty' => false));
        $usedColors = array();
        foreach($terms as $term) {

            for($i=0; $i<10; $i++) {
                $color = self::generateCategoryColor($term->name);
                if(!in_array($color, $usedColors)) {
                    $usedColors[] =
                    $options[$term->term_id] = $color;
                    break;
                }
            }

            if(!isset($options[$term->term_id])) {
                $options[$term->term_id] = self::generateCategoryColor($term->name);
            }
        }
        add_option($taxonomy . '_colors', $options);

        return $options;
    }

    /**
     * Retrieve categories in use
     * @since 1.0
     * @return array A list of categories (names)
     */
    public static function getTaxonomies()
    {
        return get_taxonomies(array(
            'public'       => true,
            'show_ui'      => true,
            'hierarchical' => true
        ));
    }

    /**
     * Retrieve available color palette
     * @since 1.0
     * @return array
     */
    public static function getAvailableColors()
    {
        $availablePackOfColors = array(
            '#CB0900', '#00CB07', '#C7CC04', '#3587FF', '#CE00C9', '#00C9CA', '#494949', '#EDE500', '#FF0F00', '#487FB0',
            '#FF00FE', '#060059', '#0D0084', '#1700AA', '#2400D3', '#3400FE', '#005A01', '#065784', '#1055AA', '#1D53D3',
            '#2E50FE', '#008503', '#008559', '#008485', '#0883AA', '#1582D3', '#2680FF', '#00AC05', '#00AB5A', '#00AA85',
            '#00A9AB', '#0AA8D3', '#1BA6FF', '#00D508', '#00AA85', '#00A9AB', '#1BA6FF', '#00D45B', '#00D3AB', '#0CD1FF',
            '#00F408', '#00FF5C', '#00FFAC', '#15EAEA', '#590200', '#5B0059', '#5D0084', '#5F00AA', '#6300D3', '#6800FE',
            '#595959', '#5B5884', '#6153D3', '#6650FE', '#6480FF', '#54AB5A', '#5CA8D3', '#61A7FF', '#5CD1FF', '#850400',
            '#860058', '#870084', '#8800AA', '#8A00D3', '#8D00FE', '#845B00', '#855959', '#8756AA', '#8C51FE', '#8B80FE',
            '#81AC04', '#89A7FF', '#AB0600', '#AC0258', '#AE00AA', '#B300FE', '#B251FE', '#D40A00', '#D50658', '#D50184',
            '#D600AA', '#D35C00', '#D45B58', '#D658AA', '#D38700', '#D1AD02', '#FF0F00', '#FF0B57', '#FF00FE', '#FF5E00',
            '#FF5D58', '#FF5B84', '#FE8800', '#FF8684', '#FDAE01', '#1A1A1A', '#4B4B4B', '#A7C711', '#26B6FB', '#EF9227',
            '#FCD65A', '#FFAAD3', '#D7A8FF', '#ADD2FF', '#84FEFF', '#A7D585', '#7CDD9E', '#7CFF86', '#FCD603', '#00FF86',
            '#E81B10', '#FF671C', '#FFC444', '#E1FF00', '#8EF704', '#0ED800', '#00EAA8', '#B144FF', '#4E44FF', '#E8194D'
        );

        return apply_filters('colorful_categories_colors', $availablePackOfColors);
    }

    /**
     * Generate random category color
     *
     * @since 1.0
     *
     * @param string $categoryName Category Name
     * @return string
     */
    public static function generateCategoryColor($categoryName = '')
    {
        if(!empty($categoryName)) {

            $relations = array(
                'Facebook'  => '#3C5A98',
                'Twitter'   => '#1BBBFF',
                'Google'    => '#D7432D',
                'xing'      => '#105A63',
                'pinterest' => '#CD2129',
                'yahoo'     => '#4101AF',
                'youtube'   => '#D02226',
                'wikipedia' => '#6B6B6B',
                'amazon'    => '#FF9900',
                'linkedin'  => '#0077B5',
                'wordpress' => '#00749A',
                'ebay'      => '#86B817',
                'orange'    => '#ff9000',
                'red '      => '#ef0606',
                ' red'      => '#ef0606',
                'blue '     => '#007eff',
                ' blue'     => '#007eff',
                'black '    => '#222222',
                ' black'    => '#222222',
                'gray'      => '#656565',
                'silver'    => '#c2c2c2',
                'violet'    => '#8a1ee6',
                'yellow'    => '#f6e800',
                'green'     => '#18a114'
            );

            $relations = apply_filters('colorful_categories_colors_relations', $relations);

            foreach($relations as $relationName => $relationColor) {
                if(strpos($categoryName, $relationName) !== false) {
                    return $relationColor;
                }
            }
        }

        $availablePackOfColors = self::getAvailableColors();

        return $availablePackOfColors[mt_rand(0, count($availablePackOfColors) - 1)];
    }

    /**
     * Ajax action to update category color
     * @since 1.0
     */
    public function updateColorOptionsArray()
    {
        if(current_user_can('manage_categories') && is_admin() && isset($_POST['termId']) && isset($_POST['color']) && isset($_POST['taxonomy'])) {
            $taxonomy = esc_attr($_POST['taxonomy']);
            $options = get_option($taxonomy . '_colors', array());
            $options[(int) $_POST['termId']] = substr($_POST['color'], 0, 7);
            update_option($taxonomy . '_colors', $options);
        }

        exit;
    }

    /**
     * Add color when new category has been added
     *
     * @since 1.0
     *
     * @param int $term_id Term ID.
     * @param int $tt_id Term taxonomy ID.
     * @param string $taxonomy Taxonomy slug.
     * @return string Generated color for specified term
     */
    public static function addSingleColorOption($term_id, $tt_id, $taxonomy)
    {
        $term = get_term($term_id, $taxonomy);
        $options = get_option($taxonomy . '_colors', array());
        $options[$term_id] = self::generateCategoryColor();
        update_option('colorful-categories', $term->name);

        return $options[$term_id];
    }

    /**
     * Add column on the Manage Categories page
     *
     * @since 1.0
     *
     * @param array $column A list of columns
     * @return array A list of columns with new column
     */
    public function addColumn($column)
    {
        return array_slice($column, 0, 2, true) + array('color' => __('Color', 'colorful-categories')) + array_slice($column, 2, null, true);
    }

    /**
     * Set cell content on the Manage Categories page
     *
     * @since 1.0
     *
     * @param null $null Not used here
     * @param string $columnName Column name
     * @param int $termId Term ID
     */
    public function modifyColumn($null, $columnName, $termId)
    {
        $taxonomy = sanitize_text_field($_GET['taxonomy']);

        $options = get_option($taxonomy . '_colors', array());
        if(empty($options)) {
            $options = $this->fillTaxonomyWithColors($taxonomy);
        }

        if(!isset($options[$termId])) {
            $options[$termId] = $this->addSingleColorOption($termId, null, $taxonomy);
        }

        echo '<input type="text" class="colorful-categories-picker" name="category_color" data-term-id="' . $termId . '" data-taxonomy="' . $taxonomy . '" value="' . $options[$termId] . '">';
        echo '<span class="colorful-categories-saving">' . __('Saving...', 'colorful-categories') . '</span>';
    }

    /**
     * Init widget
     * @since 1.0
     */
    public function widgets_init()
    {
        require_once plugin_dir_path(__FILE__) . 'widget.php';
        register_widget('ColorfulCategoriesWidget');
    }
}

// create plugin
new ColorfulCategories();