<?php

namespace WordpressLib\Editor\Block;

use RuntimeException;

use function Functional\map;
use function Stringy\create as s;

abstract class AcfBlock
{
    protected $fieldPrefix;
    protected $templateName = 'posts';

    public function __construct(string $instanceName = '')
    {
        $this->fieldPrefix = $this->getFieldPrefix();
        add_action('acf/init', [$this, 'register']);
        add_filter('acf_block_content', [$this, 'noLinksInBlockEditor']);
    }

    public function noLinksInBlockEditor(string $content): string
    {
        if (wp_doing_ajax() && strpos(filter_input(INPUT_SERVER, 'HTTP_REFERER') ?: '', 'wp-admin/post.php')) {
            $content = preg_replace('/href=".*?"/', 'href="javascript: null;"', $content);
        }
        return $content;
    }

    public function getBlockName(): string
    {
        return str_replace('_', '-', $this->fieldPrefix);
    }

    public function setFieldPrefixFromBlockName(string $blockName): self
    {
        $this->fieldPrefix = str_replace(['acf/', '-'], ['', '_'], $blockName);
        return $this;
    }

    public function getFieldsNameValueMap(): array
    {
        return array_combine(
            map($this->getFieldNames(), function ($s) {
                return s($s)->camelize()->__toString();
            }),
            $this->getFieldValues()
        );
    }

    protected function getFieldNames(): array
    {
        return map($this->getBlockFields(), function ($field) {
            return str_replace("{$this->fieldPrefix}_", '', $field['name']);
        });
    }

    protected function getFieldValues(): array
    {
        return map($this->getBlockFields(), function ($field) {
            return get_field($field['name']);
        });
    }

    protected function registerBlockType(): void
    {
        if (!function_exists('acf_register_block_type')) {
            throw new RuntimeException("Gutenberg blocks are not supported by current version of ACF.");
        }
        $tpl = $this->getTemplateName();
        acf_register_block_type([
            'name' => $this->getBlockName(),
            'title' => $this->getBlockTitle(),
            'description' => $this->getBlockDescription(),
            'render_template' => "template-parts/blocks/$tpl.php",
            'icon' => $this->getBlockIcon(),
            'keywords' => $this->getBlockKeywords(),
        ]);
    }

    protected function registerFields(): void
    {
        acf_add_local_field_group([
            'key' => "{$this->fieldPrefix}_field_group",
            'title' => $this->getBlockOptionsTitle(),
            'fields' => $this->getBlockFields(),
            'location' => [
                [
                    [
                        'param' => 'block',
                        'operator' => '==',
                        'value' => 'acf/' . $this->getBlockName(),
                    ],
                ],
            ],
        ]);
    }

    public function register(): void
    {
        $this->registerBlockType();
        $this->registerFields();
    }

    abstract public function getTemplateName(): string;
    abstract public function getBlockOptionsTitle(): string;
    abstract public function getBlockTitle(): string;
    abstract public function getBlockIcon(): string;
    abstract public function getFieldPrefix(): string;
    abstract public function getBlockDescription(): string;
    abstract public function getBlockKeywords(): array;
    abstract public function getBlockFields(): array;
}
