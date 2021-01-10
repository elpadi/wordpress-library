<?php

namespace MustUsePlugin\Widgets;

abstract class BaseWidget extends \WP_Widget
{

    protected static $_name;
    protected static $_title;
    protected static $_description;

    abstract protected function getFormFields($instance);

    /**
     * Register widget with WordPress.
     */
    public function __construct()
    {
        parent::__construct(
            static::$_name . '_widget',
            static::$_title,
            ['description' => static::$_description]
        );
    }

    /**
     * Front-end display of widget.
     *
     * @see WP_Widget::widget()
     *
     * @param array $args     Widget arguments.
     * @param array $instance Saved values from database.
     */
    public function widget($args, $instance)
    {
        extract(apply_filters('preprocess_widget_values', array_merge($args, $instance, ['widget' => $this])));
        $widget = $this;
        require(sprintf('%s/templates/widget-%s.php', MU_PLUGINS_DIR, static::$_name));
    }

    /**
     * Back-end widget form.
     *
     * @see WP_Widget::form()
     *
     * @param array $instance Previously saved values from database.
     */
    public function form($instance)
    {
        $fields = $this->getFormFields($instance);
        require(sprintf('%s/templates/widget-form.php', MU_PLUGINS_DIR));
    }

    /**
     * Sanitize widget form values as they are saved.
     *
     * @see WP_Widget::update()
     *
     * @param array $new_instance Values just sent to be saved.
     * @param array $old_instance Previously saved values from database.
     *
     * @return array Updated safe values to be saved.
     */
    public function update($new_instance, $old_instance)
    {
        return $new_instance;
    }

    protected static function sanitizer($type)
    {
        switch ($type) {
            case 'textarea':
                return 'esc_textarea';
        }
        return 'esc_attr';
    }

    protected function formField($name, $instance, $type = 'text', $default = '')
    {
        $value = empty($instance[$name]) ? $default : $instance[$name];
        return [
            'id' => $this->get_field_id($name),
            'name' => $this->get_field_name($name),
            'label' => ucwords(str_replace('_', ' ', $name)),
            'value' => call_user_func(self::sanitizer($type), $value),
            'type' => $type,
        ];
    }
}
