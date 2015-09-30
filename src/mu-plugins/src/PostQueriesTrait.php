<?php

trait PostQueriesTrait {

	protected static function attachmentIdFrom($key, $meta_key) {
		global $wpdb;
		$key = "{$key}_id";
		return "LEFT JOIN `$wpdb->postmeta` $key ON posts.`ID`=$key.`post_id` AND $key.`meta_key`='$meta_key'";
	}

	protected static function repeaterImageIdFrom($key) {
		global $wpdb;
		$id_key = "{$key}_id";
		$val_key = "{$key}_thumb_id";
		return "LEFT JOIN `$wpdb->postmeta` $val_key ON $id_key.`meta_value`=$val_key.`post_id` AND $val_key.`meta_key`='_thumbnail_id'";
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
			$meta_key = $key === 'thumb' ? '_thumbnail_id' : $key;
			$selects[] = self::attachmentFileSelect($key);
			$selects[] = self::attachmentDataSelect($key);
			$froms[] = self::attachmentIdFrom($key, $meta_key);
			$froms[] = self::attachmentDataFrom($key);
			$froms[] = self::attachmentFileFrom($key);
		}
		foreach ($fields['repeaters'] as $repeater_key => $repeater_fields) {
			$selects[] = "3 AS {$repeater_key}_count";
			for ($i = 0; $i < 3; $i++) {
				foreach ($repeater_fields['text'] as $key) {
					$_key = "{$repeater_key}_{$i}_{$key}";
					$selects[] = self::textFieldSelect($_key);
					$froms[] = self::textFieldFrom($_key);
				}
				foreach ($repeater_fields['attachments'] as $key) {
					$item_key = "{$repeater_key}_{$i}_{$key}_item";
					$thumb_key = "{$repeater_key}_{$i}_{$key}_item_thumb";
					$_key = "{$repeater_key}_{$i}_{$key}";
					$froms[] = self::attachmentIdFrom($item_key, $_key);
					$froms[] = self::repeaterImageIdFrom($item_key);
					$selects[] = self::attachmentDataSelect($thumb_key);
					$selects[] = self::attachmentFileSelect($thumb_key);
					$froms[] = self::attachmentDataFrom($thumb_key);
					$froms[] = self::attachmentFileFrom($thumb_key);
				}
			}
		}

		$sql = sprintf('SELECT %s FROM %s WHERE posts.`ID` IN (%s)', implode(',', $selects), implode(' ', $froms), implode(',', $ids));
		return $wpdb->get_results($sql);
	}

}
