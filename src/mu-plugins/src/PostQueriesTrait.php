<?php

trait PostQueriesTrait {

	protected static function attachmentIdFrom($key, $meta_key='', $parentTable='posts') {
		global $wpdb;
		if (empty($meta_key)) $meta_key = $key;
		if ($meta_key === 'thumb') $meta_key = '_thumbnail_id';
		$key = "{$key}_id";
		return "LEFT JOIN `$wpdb->postmeta` $key ON $parentTable.`ID`=$key.`post_id` AND $key.`meta_key`='$meta_key'";
	}

	protected static function attachmentDataFrom($key) {
		global $wpdb;
		$id_key = "{$key}_id";
		$val_key = "{$key}_data";
		return "LEFT JOIN `$wpdb->postmeta` $val_key ON $id_key.`meta_value`=$val_key.`post_id` AND $val_key.`meta_key`='_wp_attachment_metadata'";
	}

	protected static function attachmentFileFrom($key) {
		global $wpdb;
		$id_key = "{$key}_id";
		$val_key = "{$key}_file";
		return "LEFT JOIN `$wpdb->postmeta` $val_key ON $id_key.`meta_value`=$val_key.`post_id` AND $val_key.`meta_key`='_wp_attached_file'";
	}

	protected static function attachmentFileSelect($key) {
		return "{$key}_file.`meta_value` AS {$key}_file";
	}

	protected static function attachmentDataSelect($key) {
		return "{$key}_data.`meta_value` AS {$key}_serialized";
	}

	protected static function repeaterPostIdFrom($repeater_item_key, $post_key) {
		global $wpdb;
		$table = "{$repeater_item_key}_post_id";
		return "LEFT JOIN $wpdb->postmeta $table ON posts.`ID`=$table.`post_id` AND $table.`meta_key`='{$repeater_item_key}_{$post_key}'";
	}

	protected static function repeaterPostFrom($repeater_item_key, $post_key) {
		global $wpdb;
		$table = "{$repeater_item_key}_post";
		return "LEFT JOIN $wpdb->posts $table ON $table.`ID`={$table}_id.`meta_value`";
	}

	protected static function repeaterPostColumnSelect($repeater_item_key, $col) {
		return "{$repeater_item_key}_post.`$col` AS {$repeater_item_key}_$col";
	}

	protected static function textFieldSelect($key) {
		return "$key.`meta_value` AS $key";
	}

	protected static function textFieldFrom($key) {
		global $wpdb;
		return "LEFT JOIN `$wpdb->postmeta` $key ON posts.`ID`=$key.`post_id` AND $key.`meta_key`='$key'";
	}

	public static function customPostsQuery($ids, $fields) {
		global $wpdb;

		$selects = ["posts.*"];
		$froms = ["`$wpdb->posts` posts"];

		foreach ($fields['text'] as $key) {
			$selects[] = self::textFieldSelect($key);
			$froms[] = self::textFieldFrom($key);
		}
		foreach ($fields['attachments'] as $key) {
			$selects[] = self::attachmentFileSelect($key);
			$selects[] = self::attachmentDataSelect($key);
			$froms[] = self::attachmentIdFrom($key);
			$froms[] = self::attachmentDataFrom($key);
			$froms[] = self::attachmentFileFrom($key);
		}
		foreach ($fields['repeaters'] as $repeater_key => $repeater_fields) {
			$selects[] = "3 AS {$repeater_key}_count";
			for ($i = 0; $i < 3; $i++) {
				$index_key = "{$repeater_key}_{$i}";
				$froms[] = self::repeaterPostIdFrom($index_key, $repeater_fields['post']);
				$froms[] = self::repeaterPostFrom($index_key, $repeater_fields['post']);
				foreach ($repeater_fields['post_columns'] as $col) {
					$selects[] = self::repeaterPostColumnSelect($index_key, $col);
				}
				foreach ($repeater_fields['text'] as $key) {
					$_key = "{$repeater_key}_{$i}_{$key}";
					$selects[] = self::textFieldSelect($_key);
					$froms[] = self::textFieldFrom($_key);
				}
				foreach ($repeater_fields['attachments'] as $key) {
					$item_key = "{$repeater_key}_{$i}_{$key}";
					$froms[] = self::attachmentIdFrom($item_key, $key, "{$repeater_key}_{$i}_post");
					$selects[] = self::attachmentDataSelect($item_key);
					$selects[] = self::attachmentFileSelect($item_key);
					$froms[] = self::attachmentDataFrom($item_key);
					$froms[] = self::attachmentFileFrom($item_key);
				}
			}
		}

		$sql = sprintf('SELECT %s FROM %s WHERE posts.`ID` IN (%s)', implode(',', $selects), implode(' ', $froms), implode(',', $ids));
		return $wpdb->get_results($sql);
	}

}
