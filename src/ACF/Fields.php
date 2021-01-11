<?php

namespace WordpressLib\ACF;

use Functional as F;

abstract class Fields
{

    protected $postType = 'post';

    public function __construct()
    {
    }

    abstract protected function getFieldsDefs();

    protected function getFieldNames()
    {
        return F\pluck($this->getFieldsDefs(), 'name');
    }

    protected function getFieldsSelectClauses()
    {
        global $wpdb;

        return [
            "GROUP_CONCAT(`$wpdb->postmeta`.`meta_key`) AS field_names",
            "GROUP_CONCAT(`$wpdb->postmeta`.`meta_value`, '__||__') AS field_values",
        ];
    }

    protected function getSelectClauses()
    {
        global $wpdb;

        return array_merge(
            ["`$wpdb->posts`.`ID` AS id"],
            F\map(['title','type','status','date'], function ($s) use ($wpdb) {
                return "`$wpdb->posts`.`post_$s` AS $s";
            }),
            F\map(['slug' => 'name'], function ($src, $tgt) use ($wpdb) {
                return "`$wpdb->posts`.`post_$src` AS $tgt";
            }),
            $this->getFieldsSelectClauses()
        );
    }

    protected function getFromClauses()
    {
        global $wpdb;

        return [
            "`$wpdb->posts`",
            "`$wpdb->postmeta` ON `$wpdb->posts`.`ID`=`$wpdb->postmeta`.`post_id`",
        ];
    }

    protected function getFieldsWhereClause()
    {
        global $wpdb;

        $fieldNames = F\map($this->getFieldNames(), function ($s) {
            return "'$s'";
        });
        return sprintf("`$wpdb->postmeta`.`meta_key` IN (%s)", implode(',', $fieldNames));
    }

    protected function getWhereClauses()
    {
        global $wpdb;

        return [
            "`$wpdb->posts`.`post_status` = 'publish'",
            $this->getFieldsWhereClause(),
            sprintf("`$wpdb->posts`.`post_type` = '%s'", $this->postType),
        ];
    }

    protected function getGroupByClauses()
    {
        global $wpdb;

        return [
            "`$wpdb->postmeta`.`post_id`",
        ];
    }

    protected function getOrderByClauses()
    {
        global $wpdb;

        return [
            "`$wpdb->posts`.`post_date` DESC",
        ];
    }

    protected function getLimitOffset()
    {
        return 0;
    }

    protected function getLimitCount()
    {
        return 10;
    }

    protected function processPostObject(&$p)
    {
        $values = explode('__||__', $p->field_values);
        foreach (explode(',', $p->field_names) as $i => $name) {
            $p->$name = trim($values[$i], ',');
        }
        unset($p->field_names);
        unset($p->field_values);
    }

    /**
     * Gets posts from the database with custom fields added, all in one single query.
     */
    public function getPosts()
    {
        global $wpdb;

        $sql = sprintf(
            'SELECT %s FROM %s WHERE %s GROUP BY %s ORDER BY %s LIMIT %d,%d',
            implode(', ', $this->getSelectClauses()),
            implode(' JOIN ', $this->getFromClauses()),
            implode(' AND ', $this->getWhereClauses()),
            implode(' AND ', $this->getGroupByClauses()),
            implode(' AND ', $this->getOrderByClauses()),
            $this->getLimitOffset(),
            $this->getLimitCount()
        );

        $posts = $wpdb->get_results($sql);
        foreach ($posts as &$p) {
            $this->processPostObject($p);
        }
        return $posts;
    }

    protected function processRepeaterValues(&$groupedValues)
    {
    }

    public function getRepeaterValues($name)
    {
        global $post;

        $groupedValues = [];
        $metaValues = get_post_meta($post->ID);

        foreach ($metaValues as $key => $values) {
            if (preg_match("/^{$name}_([0-9]+)_(.*)/", $key, $matches)) {
                $groupedValues[intval($matches[1])][$matches[2]] = $values[0];
            }
        }

        $this->processRepeaterValues($groupedValues);
        return $groupedValues;
    }
}
